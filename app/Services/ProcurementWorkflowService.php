<?php

namespace App\Services;

use App\Models\Procurement\ProcurementAssetModel;
use App\Models\Procurement\ProcurementAuditTrailModel;
use App\Models\Procurement\ProcurementDefectReturnModel;
use App\Models\Procurement\ProcurementDocumentModel;
use App\Models\Procurement\ProcurementInventoryStockModel;
use App\Models\Procurement\ProcurementPurchaseOrderModel;
use App\Models\Procurement\ProcurementPurchaseOrderItemModel;
use App\Models\Procurement\ProcurementRequestItemModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Coordinate procurement workflow rules and persistence.
 */
class ProcurementWorkflowService
{
    use Notifiable;

    public const HIGH_VALUE_THRESHOLD = 50000;
    public const DUPLICATE_LOOKBACK_DAYS = 7;
    public const OVERDUE_HOURS = 48;
    public const DEFECT_REASON_OPTIONS = [
        'damaged_on_arrival' => 'Damaged on Arrival',
        'wrong_item_received' => 'Wrong Item Received',
        'missing_parts_or_accessories' => 'Missing Parts or Accessories',
        'not_working' => 'Not Working',
        'quality_issue' => 'Quality Issue',
        'expired_or_contaminated' => 'Expired or Contaminated',
        'other' => 'Other',
    ];

    /**
     * Get assigned studio IDs for the supplied user and portal.
     */
    public function getAssignedStudioIds(UserModel $user, ?string $portal = null): Collection
    {
        $portalName = $portal ?? $user->role;
        $studioIds = $user->getAssignedStudioIds($portalName);

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::where('user_id', $user->id)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty() && $user->role === 'owner') {
            $studioIds = StudiosModel::where('user_id', $user->id)->pluck('id');
        }

        return $studioIds->unique()->values();
    }

    /**
     * Get the primary assigned studio for the user.
     */
    public function getPrimaryAssignedStudio(UserModel $user, ?string $portal = null): ?StudiosModel
    {
        $studioIds = $this->getAssignedStudioIds($user, $portal);

        if ($studioIds->isEmpty()) {
            return null;
        }

        return StudiosModel::whereIn('id', $studioIds)->orderBy('id')->first();
    }

    /**
     * Get requester procurement records for the selected portal.
     */
    public function getRequesterRequests(UserModel $user, string $portal): EloquentCollection
    {
        $studioIds = $this->getAssignedStudioIds($user, $portal);

        return ProcurementRequestModel::with(['studio', 'purchaseOrder'])
            ->where('requester_id', $user->id)
            ->whereIn('studio_id', $studioIds)
            ->orderByRaw("CASE WHEN status = 'delivered' THEN 0 WHEN status = 'defect_reported' THEN 1 WHEN status = 'return_in_progress' THEN 2 WHEN status = 'returned_for_revision' THEN 3 WHEN status = 'draft' THEN 4 WHEN status = 'pending_finance_review' THEN 5 ELSE 6 END")
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get finance-managed procurement records.
     */
    public function getFinanceRequests(UserModel $financeUser): EloquentCollection
    {
        $studioIds = $this->getAssignedStudioIds($financeUser, 'studio-finance');

        return ProcurementRequestModel::with(['studio', 'requester', 'purchaseOrder'])
            ->whereIn('studio_id', $studioIds)
            ->orderByRaw("CASE WHEN status = 'pending_finance_review' THEN 0 WHEN status = 'defect_reported' THEN 1 WHEN status = 'return_in_progress' THEN 2 WHEN status = 'approved' THEN 3 WHEN status = 'ordered' THEN 4 WHEN status = 'delivered' THEN 5 WHEN status = 'received' THEN 6 WHEN status = 'payment_processing' THEN 7 ELSE 8 END")
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Get owner-managed procurement records.
     */
    public function getOwnerRequests(UserModel $ownerUser): EloquentCollection
    {
        $studioIds = $this->getAssignedStudioIds($ownerUser, 'owner');

        return ProcurementRequestModel::with(['studio', 'requester', 'purchaseOrder'])
            ->whereIn('studio_id', $studioIds)
            ->orderByRaw("CASE WHEN status = 'pending_owner_approval' THEN 0 WHEN status = 'approved' THEN 1 WHEN status = 'ordered' THEN 2 ELSE 3 END")
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Create a new procurement request.
     */
    public function createRequest(UserModel $user, array $validated, string $portal): ProcurementRequestModel
    {
        $assignedStudio = $this->getPrimaryAssignedStudio($user, $portal);

        if (!$assignedStudio) {
            throw ValidationException::withMessages([
                'studio_id' => ['No studio assigned to your account.'],
            ]);
        }

        $action = $validated['action'] ?? 'save_draft';
        $items = $validated['items'] ?? [];

        if ($action === 'submit') {
            $this->guardAgainstDuplicatesAndInventory($assignedStudio->id, $items, null, $validated['inventory_bypass_reason'] ?? null);
        }

        return DB::transaction(function () use ($user, $assignedStudio, $validated, $items, $action) {
            $estimatedTotal = $this->calculateEstimatedTotal($items);
            $status = $action === 'submit'
                ? ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW
                : ProcurementRequestModel::STATUS_DRAFT;

            $procurementRequest = ProcurementRequestModel::create([
                'request_reference' => $this->generateRequestReference(),
                'studio_id' => $assignedStudio->id,
                'requester_id' => $user->id,
                'requester_role' => $user->role,
                'status' => $status,
                'is_urgent' => (bool) ($validated['is_urgent'] ?? false),
                'is_high_value' => $estimatedTotal >= self::HIGH_VALUE_THRESHOLD,
                'required_date' => $validated['required_date'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'inventory_bypass_reason' => $validated['inventory_bypass_reason'] ?? null,
                'estimated_total' => $estimatedTotal,
            ]);

            $this->syncRequestItems($procurementRequest, $items);
            $this->storeDocuments(
                $procurementRequest,
                null,
                $validated['request_attachments'] ?? [],
                'request_attachment',
                $user
            );

            $this->recordAudit(
                $procurementRequest,
                $user,
                $action === 'submit' ? 'request_submitted' : 'draft_saved',
                null,
                $status,
                $validated['purpose'] ?? null
            );

            if ($status === ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW) {
                $this->notifyFinanceOfSubmission($procurementRequest->fresh(['studio', 'requester']));
            }

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Update an editable procurement request.
     */
    public function updateRequest(ProcurementRequestModel $procurementRequest, UserModel $user, array $validated): ProcurementRequestModel
    {
        if (!$procurementRequest->canBeEditedByRequester()) {
            throw ValidationException::withMessages([
                'status' => ['Only draft or returned requests can be edited.'],
            ]);
        }

        $action = $validated['action'] ?? 'save_draft';
        $items = $validated['items'] ?? [];

        if ($action === 'submit') {
            $this->guardAgainstDuplicatesAndInventory(
                $procurementRequest->studio_id,
                $items,
                $procurementRequest->id,
                $validated['inventory_bypass_reason'] ?? $procurementRequest->inventory_bypass_reason
            );
        }

        return DB::transaction(function () use ($procurementRequest, $user, $validated, $items, $action) {
            $estimatedTotal = $this->calculateEstimatedTotal($items);
            $previousStatus = $procurementRequest->status;
            $nextStatus = $action === 'submit'
                ? ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW
                : $previousStatus;

            if ($previousStatus === ProcurementRequestModel::STATUS_DRAFT && $action === 'save_draft') {
                $nextStatus = ProcurementRequestModel::STATUS_DRAFT;
            }

            $procurementRequest->update([
                'status' => $nextStatus,
                'is_urgent' => (bool) ($validated['is_urgent'] ?? false),
                'is_high_value' => $estimatedTotal >= self::HIGH_VALUE_THRESHOLD,
                'required_date' => $validated['required_date'] ?? null,
                'purpose' => $validated['purpose'] ?? null,
                'inventory_bypass_reason' => $validated['inventory_bypass_reason'] ?? null,
                'estimated_total' => $estimatedTotal,
                'cancelled_at' => null,
            ]);

            $this->syncRequestItems($procurementRequest, $items);
            $this->storeDocuments(
                $procurementRequest,
                null,
                $validated['request_attachments'] ?? [],
                'request_attachment',
                $user
            );

            $this->recordAudit(
                $procurementRequest,
                $user,
                $action === 'submit' ? 'request_resubmitted' : 'draft_updated',
                $previousStatus,
                $nextStatus,
                $validated['purpose'] ?? null
            );

            if ($previousStatus !== $nextStatus && $nextStatus === ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW) {
                $this->notifyFinanceOfSubmission($procurementRequest->fresh(['studio', 'requester']));
            }

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Cancel a requester-owned procurement request.
     */
    public function cancelRequest(ProcurementRequestModel $procurementRequest, UserModel $user): ProcurementRequestModel
    {
        if (!$procurementRequest->canBeCancelledByRequester()) {
            throw ValidationException::withMessages([
                'status' => ['This procurement request can no longer be cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $user) {
            $previousStatus = $procurementRequest->status;

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            $this->recordAudit(
                $procurementRequest,
                $user,
                'request_cancelled',
                $previousStatus,
                ProcurementRequestModel::STATUS_CANCELLED,
                'Requester cancelled the procurement request.'
            );

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Process finance review.
     */
    public function reviewByFinance(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW) {
            throw ValidationException::withMessages([
                'status' => ['Only requests pending finance review can be processed.'],
            ]);
        }

        $action = strtolower((string) ($validated['action'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));

        if (!in_array($action, ['approve', 'reject', 'return'], true)) {
            throw ValidationException::withMessages([
                'action' => ['The selected finance action is invalid.'],
            ]);
        }

        if ($action === 'approve') {
            $items = $procurementRequest->items()->get()->map(function (ProcurementRequestItemModel $item): array {
                return [
                    'item_name' => $item->item_name,
                    'normalized_item_name' => $item->normalized_item_name,
                ];
            })->all();

            $this->guardAgainstDuplicatesAndInventory(
                $procurementRequest->studio_id,
                $items,
                $procurementRequest->id,
                $procurementRequest->inventory_bypass_reason ?: $note
            );
        }

        return DB::transaction(function () use ($procurementRequest, $financeUser, $action, $note) {
            $previousStatus = $procurementRequest->status;
            $nextStatus = match ($action) {
                'approve' => ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
                'reject' => ProcurementRequestModel::STATUS_REJECTED,
                default => ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION,
            };

            $procurementRequest->update([
                'status' => $nextStatus,
                'finance_review_note' => $note ?: null,
                'finance_reviewed_by' => $financeUser->id,
                'finance_reviewed_at' => now(),
                'escalated_at' => null,
            ]);

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'finance_' . $action,
                $previousStatus,
                $nextStatus,
                $note
            );

            if ($nextStatus === ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL) {
                $this->notifyOwnerOfFinanceApproval($procurementRequest->fresh(['studio', 'requester']));
            } else {
                $this->notifyRequesterOfStatusUpdate($procurementRequest->fresh(['studio', 'requester']), $action, $note);
            }

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Process owner approval action.
     */
    public function processOwnerApproval(ProcurementRequestModel $procurementRequest, UserModel $ownerUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL) {
            throw ValidationException::withMessages([
                'status' => ['Only requests pending owner approval can be processed.'],
            ]);
        }

        $action = strtolower((string) ($validated['action'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));

        if (!in_array($action, ['approve', 'reject', 'return'], true)) {
            throw ValidationException::withMessages([
                'action' => ['The selected owner action is invalid.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $ownerUser, $action, $note) {
            $previousStatus = $procurementRequest->status;
            $nextStatus = match ($action) {
                'approve' => ProcurementRequestModel::STATUS_APPROVED,
                'reject' => ProcurementRequestModel::STATUS_REJECTED,
                default => ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION,
            };

            $procurementRequest->update([
                'status' => $nextStatus,
                'owner_review_note' => $note ?: null,
                'owner_reviewed_by' => $ownerUser->id,
                'owner_reviewed_at' => now(),
                'escalated_at' => null,
            ]);

            $this->recordAudit(
                $procurementRequest,
                $ownerUser,
                'owner_' . $action,
                $previousStatus,
                $nextStatus,
                $note
            );

            if ($nextStatus === ProcurementRequestModel::STATUS_APPROVED) {
                $this->notifyRequesterAndFinanceOfOwnerApproval($procurementRequest->fresh(['studio', 'requester']));
            } else {
                $this->notifyRequesterOfStatusUpdate($procurementRequest->fresh(['studio', 'requester']), $action, $note);
            }

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Create the purchase order and move the request to ordered.
     */
    public function createPurchaseOrder(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if (!in_array($procurementRequest->status, [
            ProcurementRequestModel::STATUS_APPROVED,
            ProcurementRequestModel::STATUS_ORDERED,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => ['A purchase order can only be generated for approved requests.'],
            ]);
        }

        $requestItems = $procurementRequest->items()->get()->keyBy('id');
        $poItems = $validated['items'] ?? [];

        return DB::transaction(function () use ($procurementRequest, $financeUser, $validated, $requestItems, $poItems) {
            $poNumber = $procurementRequest->purchaseOrder?->po_number ?: $this->generatePurchaseOrderNumber();
            $previousStatus = $procurementRequest->status;
            $approvedTotal = 0;

            $purchaseOrder = ProcurementPurchaseOrderModel::updateOrCreate(
                ['procurement_request_id' => $procurementRequest->id],
                [
                    'po_number' => $poNumber,
                    'supplier_name' => $validated['supplier_name'],
                    'supplier_email' => $validated['supplier_email'] ?? null,
                    'supplier_contact_number' => $validated['supplier_contact_number'] ?? null,
                    'supplier_address' => $validated['supplier_address'] ?? null,
                    'delivery_address' => $validated['delivery_address'],
                    'payment_terms' => $validated['payment_terms'],
                    'order_date' => $validated['order_date'],
                    'notes' => $validated['notes'] ?? null,
                    'ordered_by' => $financeUser->id,
                ]
            );

            ProcurementPurchaseOrderItemModel::where('purchase_order_id', $purchaseOrder->id)->delete();

            foreach ($poItems as $poItem) {
                $itemId = (int) $poItem['procurement_request_item_id'];
                /** @var \App\Models\Procurement\ProcurementRequestItemModel|null $requestItem */
                $requestItem = $requestItems->get($itemId);

                if (!$requestItem) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more procurement items are invalid.'],
                    ]);
                }

                $unitPrice = (float) $poItem['approved_unit_cost'];
                $totalPrice = round($unitPrice * (float) $requestItem->quantity, 2);
                $approvedTotal += $totalPrice;

                $requestItem->update([
                    'approved_unit_cost' => $unitPrice,
                    'approved_total_cost' => $totalPrice,
                ]);

                ProcurementPurchaseOrderItemModel::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'procurement_request_item_id' => $requestItem->id,
                    'item_name' => $requestItem->item_name,
                    'quantity' => $requestItem->quantity,
                    'unit_of_measure' => $requestItem->unit_of_measure,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ]);
            }

            $purchaseOrder->update(['total_amount' => $approvedTotal]);

            $procurementRequest->update([
                'approved_total' => $approvedTotal,
                'status' => ProcurementRequestModel::STATUS_ORDERED,
            ]);

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['purchase_order_attachments'] ?? [],
                'purchase_order_attachment',
                $financeUser
            );

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'purchase_order_generated',
                $previousStatus,
                ProcurementRequestModel::STATUS_ORDERED,
                $validated['notes'] ?? null,
                ['po_number' => $purchaseOrder->po_number]
            );

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Mark the request as delivered and store delivery receipt files.
     */
    public function recordDelivery(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_ORDERED) {
            throw ValidationException::withMessages([
                'status' => ['Only ordered procurement requests can be marked as delivered.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $financeUser, $validated) {
            $previousStatus = $procurementRequest->status;
            $purchaseOrder = $procurementRequest->purchaseOrder;

            if (!$purchaseOrder) {
                throw ValidationException::withMessages([
                    'purchase_order' => ['A purchase order must exist before delivery can be recorded.'],
                ]);
            }

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_DELIVERED,
                'delivered_at' => $validated['delivered_at'],
            ]);

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['delivery_receipt_files'] ?? [],
                'delivery_receipt',
                $financeUser,
                ['delivery_note' => $validated['delivery_note'] ?? null]
            );

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'delivery_recorded',
                $previousStatus,
                ProcurementRequestModel::STATUS_DELIVERED,
                $validated['delivery_note'] ?? null
            );

            $this->notifyRequesterOfDelivery($procurementRequest->fresh(['studio', 'requester']));

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Confirm receipt and write inventory records.
     */
    public function confirmReceipt(ProcurementRequestModel $procurementRequest, UserModel $requesterUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_DELIVERED) {
            throw ValidationException::withMessages([
                'status' => ['Only delivered procurement requests can be confirmed as received.'],
            ]);
        }

        $itemsById = $procurementRequest->items()->with(['defectReturns' => function ($query) {
            $query->orderByDesc('id');
        }])->get()->keyBy('id');
        $receiptItems = collect($validated['items'] ?? []);
        $openDefectReturns = $procurementRequest->defectReturns()
            ->whereIn('status', [
                ProcurementDefectReturnModel::STATUS_REPORTED,
                ProcurementDefectReturnModel::STATUS_RETURN_IN_PROGRESS,
                ProcurementDefectReturnModel::STATUS_REPLACEMENT_DELIVERED,
            ])
            ->get()
            ->keyBy('procurement_request_item_id');
        $expectedItemIds = $openDefectReturns->isNotEmpty()
            ? $openDefectReturns->keys()->map(fn ($id) => (int) $id)->values()
            : $itemsById->keys()->map(fn ($id) => (int) $id)->values();
        $submittedItemIds = $receiptItems->pluck('procurement_request_item_id')->map(fn ($id) => (int) $id)->values();

        if ($submittedItemIds->sort()->values()->all() !== $expectedItemIds->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'items' => [$openDefectReturns->isNotEmpty()
                    ? 'Only replacement items awaiting confirmation can be submitted at this stage.'
                    : 'All delivered procurement items must be reviewed during receipt confirmation.'
                ],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $requesterUser, $itemsById, $receiptItems, $validated, $openDefectReturns) {
            $hasNewDefects = false;

            foreach ($receiptItems as $receiptItem) {
                $itemId = (int) $receiptItem['procurement_request_item_id'];
                /** @var \App\Models\Procurement\ProcurementRequestItemModel|null $requestItem */
                $requestItem = $itemsById->get($itemId);
                /** @var \App\Models\Procurement\ProcurementDefectReturnModel|null $openDefectReturn */
                $openDefectReturn = $openDefectReturns->get($itemId);

                if (!$requestItem) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more receipt line items are invalid.'],
                    ]);
                }

                $receiptAction = $receiptItem['receipt_action'] ?? 'accepted';
                $receivedQuantity = (float) $receiptItem['received_quantity'];
                $expectedQuantity = $openDefectReturn
                    ? (float) $openDefectReturn->reported_quantity
                    : (float) $requestItem->quantity;

                if (round($receivedQuantity, 2) !== round($expectedQuantity, 2)) {
                    throw ValidationException::withMessages([
                        'items' => ['Partial deliveries are not supported in this workflow. Received quantities must match ordered quantities.'],
                    ]);
                }

                if ($receiptAction === 'defective' && $openDefectReturn) {
                    throw ValidationException::withMessages([
                        'items' => ['Repeated defect reporting for replacement items is not supported in this workflow.'],
                    ]);
                }

                if ($receiptAction === 'defective') {
                    $reasonCode = (string) ($receiptItem['defect_reason_code'] ?? '');

                    if (!array_key_exists($reasonCode, self::DEFECT_REASON_OPTIONS)) {
                        throw ValidationException::withMessages([
                            'items' => ['A valid defect reason is required for defective items.'],
                        ]);
                    }

                    if ($reasonCode === 'other' && blank($receiptItem['defect_reason_other'] ?? null)) {
                        throw ValidationException::withMessages([
                            'items' => ['A manual defect reason is required when Other is selected.'],
                        ]);
                    }

                    $requestItem->update([
                        'received_quantity' => 0,
                        'condition_notes' => $receiptItem['condition_notes'] ?? null,
                    ]);

                    ProcurementDefectReturnModel::create([
                        'procurement_request_id' => $procurementRequest->id,
                        'procurement_request_item_id' => $requestItem->id,
                        'reported_by' => $requesterUser->id,
                        'reported_quantity' => $receivedQuantity,
                        'reason_code' => $reasonCode,
                        'reason_other' => $reasonCode === 'other' ? ($receiptItem['defect_reason_other'] ?? null) : null,
                        'requester_note' => $receiptItem['defect_note'] ?? null,
                        'status' => ProcurementDefectReturnModel::STATUS_REPORTED,
                        'reported_at' => now(),
                    ]);

                    $hasNewDefects = true;
                    continue;
                }

                $requestItem->update([
                    'received_quantity' => $receivedQuantity,
                    'condition_notes' => $receiptItem['condition_notes'] ?? null,
                ]);

                if ($openDefectReturn) {
                    $openDefectReturn->update([
                        'status' => ProcurementDefectReturnModel::STATUS_RESOLVED,
                        'resolved_at' => now(),
                    ]);
                }

                if ($requestItem->isEquipment()) {
                    if (
                        empty($receiptItem['serial_number'])
                        || empty($receiptItem['acquisition_cost'])
                        || empty($receiptItem['asset_location'])
                    ) {
                        throw ValidationException::withMessages([
                            'items' => ['Equipment items require serial number, acquisition cost, and asset location before receipt can be confirmed.'],
                        ]);
                    }

                    ProcurementAssetModel::create([
                        'procurement_request_id' => $procurementRequest->id,
                        'procurement_request_item_id' => $requestItem->id,
                        'studio_id' => $procurementRequest->studio_id,
                        'recorded_by' => $requesterUser->id,
                        'asset_name' => $requestItem->item_name,
                        'serial_number' => $receiptItem['serial_number'],
                        'warranty_expires_at' => $receiptItem['warranty_expires_at'] ?? null,
                        'acquisition_cost' => $receiptItem['acquisition_cost'],
                        'location' => $receiptItem['asset_location'],
                        'status' => 'active',
                    ]);

                    continue;
                }

                if (!array_key_exists('reorder_threshold', $receiptItem)) {
                    throw ValidationException::withMessages([
                        'items' => ['Consumable items require a reorder threshold before receipt can be confirmed.'],
                    ]);
                }

                $normalizedName = $this->normalizeItemName($requestItem->item_name);
                $stock = ProcurementInventoryStockModel::firstOrNew([
                    'studio_id' => $procurementRequest->studio_id,
                    'normalized_item_name' => $normalizedName,
                ]);

                $stock->fill([
                    'procurement_request_id' => $procurementRequest->id,
                    'procurement_request_item_id' => $requestItem->id,
                    'item_name' => $requestItem->item_name,
                    'normalized_item_name' => $normalizedName,
                    'description' => $requestItem->description,
                    'unit_of_measure' => $requestItem->unit_of_measure,
                    'stock_quantity' => ((float) ($stock->stock_quantity ?? 0)) + $receivedQuantity,
                    'reorder_threshold' => $receiptItem['reorder_threshold'],
                    'last_recorded_cost' => $requestItem->approved_unit_cost ?? $requestItem->estimated_unit_cost,
                    'last_received_at' => now(),
                    'updated_by' => $requesterUser->id,
                ]);

                if (!$stock->exists) {
                    $stock->created_by = $requesterUser->id;
                }

                $stock->save();
            }

            $previousStatus = $procurementRequest->status;
            $nextStatus = $hasNewDefects
                ? ProcurementRequestModel::STATUS_DEFECT_REPORTED
                : ProcurementRequestModel::STATUS_RECEIVED;

            $procurementRequest->update([
                'status' => $nextStatus,
                'receipt_confirmed_by' => $nextStatus === ProcurementRequestModel::STATUS_RECEIVED ? $requesterUser->id : null,
                'receipt_confirmed_at' => $nextStatus === ProcurementRequestModel::STATUS_RECEIVED ? now() : null,
            ]);

            $this->recordAudit(
                $procurementRequest,
                $requesterUser,
                $hasNewDefects ? 'defect_reported' : 'receipt_confirmed',
                $previousStatus,
                $nextStatus,
                $validated['receipt_note'] ?? null
            );

            if ($hasNewDefects) {
                $this->notifyFinanceOfDefectReport($procurementRequest->fresh(['studio', 'requester']));
            } elseif ($openDefectReturns->isNotEmpty()) {
                $this->notifyDefectResolution($procurementRequest->fresh(['studio', 'requester']));
            }

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Process supplier return handling for defective items.
     */
    public function processDefectReturn(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_DEFECT_REPORTED) {
            throw ValidationException::withMessages([
                'status' => ['Only requests with reported defects can be moved into return processing.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $financeUser, $validated) {
            $previousStatus = $procurementRequest->status;
            $purchaseOrder = $procurementRequest->purchaseOrder;
            $openDefectReturns = $procurementRequest->defectReturns()
                ->where('status', ProcurementDefectReturnModel::STATUS_REPORTED)
                ->get();

            if ($openDefectReturns->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['No reported defective items were found for this procurement request.'],
                ]);
            }

            foreach ($openDefectReturns as $defectReturn) {
                $defectReturn->update([
                    'processed_by' => $financeUser->id,
                    'finance_note' => $validated['finance_note'],
                    'status' => ProcurementDefectReturnModel::STATUS_RETURN_IN_PROGRESS,
                    'processed_at' => now(),
                ]);
            }

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS,
            ]);

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['return_support_files'] ?? [],
                'return_support',
                $financeUser,
                ['finance_note' => $validated['finance_note']]
            );

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['return_receipt_files'] ?? [],
                'return_receipt',
                $financeUser,
                ['finance_note' => $validated['finance_note']]
            );

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'return_processed',
                $previousStatus,
                ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS,
                $validated['finance_note']
            );

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Record replacement delivery for defective returned items.
     */
    public function recordReplacementDelivery(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS) {
            throw ValidationException::withMessages([
                'status' => ['Only requests in return processing can record replacement delivery.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $financeUser, $validated) {
            $previousStatus = $procurementRequest->status;
            $purchaseOrder = $procurementRequest->purchaseOrder;
            $openDefectReturns = $procurementRequest->defectReturns()
                ->where('status', ProcurementDefectReturnModel::STATUS_RETURN_IN_PROGRESS)
                ->get();

            if ($openDefectReturns->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => ['No return items are awaiting replacement delivery.'],
                ]);
            }

            foreach ($openDefectReturns as $defectReturn) {
                $defectReturn->update([
                    'status' => ProcurementDefectReturnModel::STATUS_REPLACEMENT_DELIVERED,
                    'replacement_delivered_at' => $validated['delivered_at'],
                ]);
            }

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_DELIVERED,
                'delivered_at' => $validated['delivered_at'],
                'receipt_confirmed_by' => null,
                'receipt_confirmed_at' => null,
            ]);

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['replacement_delivery_receipt_files'] ?? [],
                'replacement_delivery_receipt',
                $financeUser,
                ['delivery_note' => $validated['delivery_note'] ?? null]
            );

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'replacement_delivered',
                $previousStatus,
                ProcurementRequestModel::STATUS_DELIVERED,
                $validated['delivery_note'] ?? null
            );

            $this->notifyRequesterOfReplacementDelivery($procurementRequest->fresh(['studio', 'requester']));

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Record payment details and move the request into payment processing.
     */
    public function recordPayment(ProcurementRequestModel $procurementRequest, UserModel $financeUser, array $validated): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_RECEIVED) {
            throw ValidationException::withMessages([
                'status' => ['Only received procurement requests can enter payment processing.'],
            ]);
        }

        $this->assertThreeWayMatchRequirements($procurementRequest, (float) $validated['invoice_amount']);

        return DB::transaction(function () use ($procurementRequest, $financeUser, $validated) {
            $previousStatus = $procurementRequest->status;
            $purchaseOrder = $procurementRequest->purchaseOrder;

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_PAYMENT_PROCESSING,
                'invoice_reference' => $validated['invoice_reference'],
                'invoice_amount' => $validated['invoice_amount'],
                'invoice_date' => $validated['invoice_date'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'payment_note' => $validated['payment_note'] ?? null,
                'payment_processed_at' => now(),
            ]);

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['supplier_invoice_files'] ?? [],
                'supplier_invoice',
                $financeUser,
                ['invoice_reference' => $validated['invoice_reference']]
            );

            $this->storeDocuments(
                $procurementRequest,
                $purchaseOrder,
                $validated['payment_proof_files'] ?? [],
                'payment_proof',
                $financeUser,
                ['payment_reference' => $validated['payment_reference'] ?? null]
            );

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'payment_processing_started',
                $previousStatus,
                ProcurementRequestModel::STATUS_PAYMENT_PROCESSING,
                $validated['payment_note'] ?? null
            );

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Complete a payment-processed procurement request.
     */
    public function completePayment(ProcurementRequestModel $procurementRequest, UserModel $financeUser): ProcurementRequestModel
    {
        if ($procurementRequest->status !== ProcurementRequestModel::STATUS_PAYMENT_PROCESSING) {
            throw ValidationException::withMessages([
                'status' => ['Only payment-processing requests can be completed.'],
            ]);
        }

        return DB::transaction(function () use ($procurementRequest, $financeUser) {
            $previousStatus = $procurementRequest->status;

            $procurementRequest->update([
                'status' => ProcurementRequestModel::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            $this->recordAudit(
                $procurementRequest,
                $financeUser,
                'payment_completed',
                $previousStatus,
                ProcurementRequestModel::STATUS_COMPLETED,
                'Finance marked the procurement request as completed.'
            );

            $this->notifyPaymentCompletion($procurementRequest->fresh(['studio', 'requester']));

            return $this->refreshRequest($procurementRequest);
        });
    }

    /**
     * Build a response payload for procurement request details.
     *
     * @return array<string, mixed>
     */
    public function buildDetailPayload(ProcurementRequestModel $procurementRequest, UserModel $viewer): array
    {
        $procurementRequest->loadMissing([
            'studio',
            'requester',
            'items.assets',
            'items.defectReturns',
            'items.inventoryStocks',
            'purchaseOrder.items',
            'documents.uploader',
            'auditTrails.actor',
            'defectReturns.procurementRequestItem',
            'defectReturns.reporter',
            'defectReturns.processor',
            'financeReviewer',
            'ownerReviewer',
            'receiptConfirmer',
        ]);

        $openDefectReturns = $procurementRequest->defectReturns
            ->filter(fn (ProcurementDefectReturnModel $defectReturn) => $defectReturn->isOpen())
            ->values();

        $documentsByType = $procurementRequest->documents
            ->groupBy('document_type')
            ->map(fn (Collection $documents) => $documents->map(function (ProcurementDocumentModel $document): array {
                return [
                    'id' => $document->id,
                    'file_name' => $document->file_name,
                    'file_url' => asset('storage/' . $document->file_path),
                    'uploaded_by' => $document->uploader->full_name ?? 'System',
                    'uploaded_at' => $document->created_at?->format('F d, Y h:i A'),
                    'notes' => $document->notes,
                    'metadata' => $document->metadata ?? [],
                ];
            })->values())
            ->toArray();

        return [
            'id' => $procurementRequest->id,
            'request_reference' => $procurementRequest->request_reference,
            'status' => $procurementRequest->status,
            'status_display' => $procurementRequest->status_label,
            'status_badge_class' => $procurementRequest->status_badge_class,
            'studio_name' => $procurementRequest->studio->studio_name ?? 'N/A',
            'requester_name' => $procurementRequest->requester->full_name ?? 'N/A',
            'requester_email' => $procurementRequest->requester->email ?? 'N/A',
            'requester_role' => $procurementRequest->requester_role,
            'required_date' => $procurementRequest->required_date?->format('Y-m-d'),
            'required_date_display' => $procurementRequest->required_date?->format('M d, Y') ?? 'N/A',
            'purpose' => $procurementRequest->purpose,
            'inventory_bypass_reason' => $procurementRequest->inventory_bypass_reason,
            'finance_review_note' => $procurementRequest->finance_review_note,
            'owner_review_note' => $procurementRequest->owner_review_note,
            'estimated_total' => number_format((float) $procurementRequest->estimated_total, 2),
            'approved_total' => number_format((float) $procurementRequest->approved_total, 2),
            'is_urgent' => $procurementRequest->is_urgent,
            'is_high_value' => $procurementRequest->is_high_value,
            'finance_reviewed_at' => $procurementRequest->finance_reviewed_at?->format('F d, Y h:i A'),
            'owner_reviewed_at' => $procurementRequest->owner_reviewed_at?->format('F d, Y h:i A'),
            'delivered_at' => $procurementRequest->delivered_at?->format('F d, Y h:i A'),
            'receipt_confirmed_at' => $procurementRequest->receipt_confirmed_at?->format('F d, Y h:i A'),
            'payment_processed_at' => $procurementRequest->payment_processed_at?->format('F d, Y h:i A'),
            'completed_at' => $procurementRequest->completed_at?->format('F d, Y h:i A'),
            'invoice_reference' => $procurementRequest->invoice_reference,
            'invoice_amount' => $procurementRequest->invoice_amount ? number_format((float) $procurementRequest->invoice_amount, 2) : null,
            'payment_reference' => $procurementRequest->payment_reference,
            'defect_reason_options' => collect(self::DEFECT_REASON_OPTIONS)->map(function (string $label, string $code): array {
                return [
                    'code' => $code,
                    'label' => $label,
                ];
            })->values(),
            'items' => $procurementRequest->items->map(function (ProcurementRequestItemModel $item): array {
                $openDefectReturn = $item->defectReturns
                    ->first(fn (ProcurementDefectReturnModel $defectReturn) => $defectReturn->isOpen());

                return [
                    'id' => $item->id,
                    'item_name' => $item->item_name,
                    'description' => $item->description,
                    'category' => $item->category,
                    'expense_type' => $item->expense_type,
                    'quantity' => (float) $item->quantity,
                    'unit_of_measure' => $item->unit_of_measure,
                    'estimated_unit_cost' => (float) $item->estimated_unit_cost,
                    'estimated_total_cost' => (float) $item->estimated_total_cost,
                    'approved_unit_cost' => $item->approved_unit_cost !== null ? (float) $item->approved_unit_cost : null,
                    'approved_total_cost' => $item->approved_total_cost !== null ? (float) $item->approved_total_cost : null,
                    'received_quantity' => (float) $item->received_quantity,
                    'condition_notes' => $item->condition_notes,
                    'preferred_supplier' => $item->preferred_supplier,
                    'has_open_return' => $openDefectReturn !== null,
                    'open_return_status' => $openDefectReturn?->status,
                    'open_return_reported_quantity' => $openDefectReturn ? (float) $openDefectReturn->reported_quantity : null,
                ];
            })->values(),
            'purchase_order' => $procurementRequest->purchaseOrder ? [
                'po_number' => $procurementRequest->purchaseOrder->po_number,
                'supplier_name' => $procurementRequest->purchaseOrder->supplier_name,
                'supplier_email' => $procurementRequest->purchaseOrder->supplier_email,
                'supplier_contact_number' => $procurementRequest->purchaseOrder->supplier_contact_number,
                'supplier_address' => $procurementRequest->purchaseOrder->supplier_address,
                'delivery_address' => $procurementRequest->purchaseOrder->delivery_address,
                'payment_terms' => $procurementRequest->purchaseOrder->payment_terms,
                'order_date' => $procurementRequest->purchaseOrder->order_date?->format('Y-m-d'),
                'order_date_display' => $procurementRequest->purchaseOrder->order_date?->format('M d, Y'),
                'notes' => $procurementRequest->purchaseOrder->notes,
                'total_amount' => number_format((float) $procurementRequest->purchaseOrder->total_amount, 2),
                'items' => $procurementRequest->purchaseOrder->items->map(function (ProcurementPurchaseOrderItemModel $item): array {
                    return [
                        'procurement_request_item_id' => $item->procurement_request_item_id,
                        'item_name' => $item->item_name,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                    ];
                })->values(),
            ] : null,
            'documents' => $documentsByType,
            'open_defect_returns' => $openDefectReturns->map(function (ProcurementDefectReturnModel $defectReturn): array {
                return [
                    'id' => $defectReturn->id,
                    'procurement_request_item_id' => $defectReturn->procurement_request_item_id,
                    'item_name' => $defectReturn->procurementRequestItem?->item_name ?? 'N/A',
                    'reported_quantity' => (float) $defectReturn->reported_quantity,
                    'reason_code' => $defectReturn->reason_code,
                    'reason_label' => self::DEFECT_REASON_OPTIONS[$defectReturn->reason_code] ?? str($defectReturn->reason_code)->replace('_', ' ')->title()->toString(),
                    'reason_other' => $defectReturn->reason_other,
                    'requester_note' => $defectReturn->requester_note,
                    'finance_note' => $defectReturn->finance_note,
                    'status' => $defectReturn->status,
                    'status_display' => $defectReturn->status_label,
                    'status_badge_class' => $defectReturn->status_badge_class,
                    'reported_at' => $defectReturn->reported_at?->format('F d, Y h:i A'),
                    'processed_at' => $defectReturn->processed_at?->format('F d, Y h:i A'),
                    'replacement_delivered_at' => $defectReturn->replacement_delivered_at?->format('F d, Y h:i A'),
                ];
            })->values(),
            'audit_trails' => $procurementRequest->auditTrails
                ->sortByDesc('created_at')
                ->values()
                ->map(function (ProcurementAuditTrailModel $audit): array {
                    $timelineDisplay = $this->getTimelineDisplay($audit->action);

                    return [
                        'action' => str($audit->action)->replace('_', ' ')->title()->toString(),
                        'title' => $timelineDisplay['title'],
                        'description' => $audit->note ?: $timelineDisplay['description'],
                        'icon' => $timelineDisplay['icon'],
                        'dot_class' => $timelineDisplay['dot_class'],
                        'icon_class' => $timelineDisplay['icon_class'],
                        'from_status' => $audit->from_status,
                        'to_status' => $audit->to_status,
                        'note' => $audit->note,
                        'actor_name' => $audit->actor->full_name ?? 'System',
                        'created_at' => $audit->created_at?->format('F d, Y h:i A'),
                        'created_at_display' => $audit->created_at?->diffForHumans(),
                    ];
                }),
            'payment_summary' => [
                'request_reference' => $procurementRequest->request_reference,
                'po_number' => $procurementRequest->purchaseOrder?->po_number,
                'supplier_name' => $procurementRequest->purchaseOrder?->supplier_name,
                'supplier_contact_number' => $procurementRequest->purchaseOrder?->supplier_contact_number,
                'supplier_email' => $procurementRequest->purchaseOrder?->supplier_email,
                'invoice_reference' => $procurementRequest->invoice_reference,
                'invoice_amount' => $procurementRequest->invoice_amount ? number_format((float) $procurementRequest->invoice_amount, 2) : number_format((float) ($procurementRequest->purchaseOrder?->total_amount ?? 0), 2),
                'invoice_date' => $procurementRequest->invoice_date?->format('Y-m-d'),
                'invoice_date_display' => $procurementRequest->invoice_date?->format('M d, Y'),
                'approved_total' => number_format((float) $procurementRequest->approved_total, 2),
                'estimated_total' => number_format((float) $procurementRequest->estimated_total, 2),
                'payment_reference' => $procurementRequest->payment_reference,
                'payment_terms' => $procurementRequest->purchaseOrder?->payment_terms,
                'delivery_address' => $procurementRequest->purchaseOrder?->delivery_address,
                'payment_note' => $procurementRequest->payment_note,
            ],
            'permissions' => [
                'can_edit' => $procurementRequest->requester_id === $viewer->id && $procurementRequest->canBeEditedByRequester(),
                'can_cancel' => $procurementRequest->requester_id === $viewer->id && $procurementRequest->canBeCancelledByRequester(),
                'can_confirm_receipt' => $procurementRequest->requester_id === $viewer->id && $procurementRequest->status === ProcurementRequestModel::STATUS_DELIVERED,
                'can_finance_review' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW,
                'can_generate_po' => $viewer->role === 'studio-finance' && in_array($procurementRequest->status, [
                    ProcurementRequestModel::STATUS_APPROVED,
                    ProcurementRequestModel::STATUS_ORDERED,
                ], true),
                'can_record_delivery' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_ORDERED,
                'can_process_returns' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_DEFECT_REPORTED,
                'can_record_replacement_delivery' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS,
                'can_record_payment' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_RECEIVED,
                'can_complete_payment' => $viewer->role === 'studio-finance' && $procurementRequest->status === ProcurementRequestModel::STATUS_PAYMENT_PROCESSING,
                'can_owner_review' => $viewer->role === 'owner' && $procurementRequest->status === ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL,
            ],
        ];
    }

    /**
     * Escalate overdue procurement records.
     */
    public function escalateOverdueRequests(): int
    {
        $requests = ProcurementRequestModel::with(['studio', 'requester'])
            ->overdueForEscalation(self::OVERDUE_HOURS)
            ->get();

        foreach ($requests as $procurementRequest) {
            $owner = $this->getOwnerForStudio($procurementRequest->studio_id);

            $procurementRequest->update(['escalated_at' => now()]);
            $this->recordAudit(
                $procurementRequest,
                null,
                'overdue_escalated',
                $procurementRequest->status,
                $procurementRequest->status,
                'Procurement request escalated for overdue approval.'
            );

            if ($owner) {
                $this->createNotification(
                    $owner->id,
                    'procurement_overdue',
                    'Overdue Procurement Approval',
                    "Procurement request {$procurementRequest->request_reference} requires attention.",
                    [
                        'procurement_request_id' => $procurementRequest->id,
                        'request_reference' => $procurementRequest->request_reference,
                        'route' => route('owner.procurement.index', [], false),
                    ],
                    'alert-triangle',
                    'warning'
                );
            }
        }

        return $requests->count();
    }

    /**
     * Generate a request reference.
     */
    private function generateRequestReference(): string
    {
        do {
            $reference = 'PR-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (ProcurementRequestModel::where('request_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Generate a purchase order number.
     */
    private function generatePurchaseOrderNumber(): string
    {
        do {
            $reference = 'PO-' . now()->format('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (ProcurementPurchaseOrderModel::where('po_number', $reference)->exists());

        return $reference;
    }

    /**
     * Sync request line items.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncRequestItems(ProcurementRequestModel $procurementRequest, array $items): void
    {
        $procurementRequest->items()->delete();

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $estimatedUnitCost = (float) ($item['estimated_unit_cost'] ?? 0);
            $category = strtolower((string) ($item['category'] ?? 'consumable'));

            ProcurementRequestItemModel::create([
                'procurement_request_id' => $procurementRequest->id,
                'item_name' => $item['item_name'],
                'normalized_item_name' => $item['normalized_item_name'] ?? $this->normalizeItemName($item['item_name']),
                'description' => $item['description'] ?? null,
                'category' => $category,
                'expense_type' => $category === 'equipment' ? 'capex' : 'opex',
                'quantity' => $quantity,
                'unit_of_measure' => $item['unit_of_measure'],
                'estimated_unit_cost' => $estimatedUnitCost,
                'estimated_total_cost' => round($quantity * $estimatedUnitCost, 2),
                'preferred_supplier' => $item['preferred_supplier'] ?? null,
            ]);
        }
    }

    /**
     * Calculate the estimated request total.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function calculateEstimatedTotal(array $items): float
    {
        return round(collect($items)->sum(function (array $item): float {
            return (float) ($item['quantity'] ?? 0) * (float) ($item['estimated_unit_cost'] ?? 0);
        }), 2);
    }

    /**
     * Normalize an item name for matching.
     */
    private function normalizeItemName(string $itemName): string
    {
        return Str::of($itemName)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim()
            ->toString();
    }

    /**
     * Guard against duplicates and existing stock suggestions.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function guardAgainstDuplicatesAndInventory(int $studioId, array $items, ?int $ignoreRequestId, ?string $inventoryBypassReason): void
    {
        $duplicateMessages = [];
        $inventoryMessages = [];

        foreach ($items as $item) {
            $normalizedName = $item['normalized_item_name'] ?? $this->normalizeItemName((string) ($item['item_name'] ?? ''));

            if ($normalizedName === '') {
                continue;
            }

            $duplicateItem = ProcurementRequestItemModel::query()
                ->where('normalized_item_name', $normalizedName)
                ->whereHas('procurementRequest', function ($query) use ($studioId, $ignoreRequestId) {
                    $query->where('studio_id', $studioId)
                        ->open()
                        ->where('created_at', '>=', now()->subDays(self::DUPLICATE_LOOKBACK_DAYS));

                    if ($ignoreRequestId !== null) {
                        $query->where('id', '!=', $ignoreRequestId);
                    }
                })
                ->with('procurementRequest')
                ->first();

            if ($duplicateItem) {
                $duplicateMessages[] = sprintf(
                    '%s already exists in %s (%s).',
                    $item['item_name'],
                    $duplicateItem->procurementRequest->request_reference,
                    $duplicateItem->procurementRequest->status_label
                );
            }

            $stock = ProcurementInventoryStockModel::query()
                ->where('studio_id', $studioId)
                ->where('normalized_item_name', $normalizedName)
                ->where('stock_quantity', '>', 0)
                ->first();

            if ($stock) {
                $inventoryMessages[] = sprintf(
                    '%s is already available in stock (%.2f %s available).',
                    $stock->item_name,
                    (float) $stock->stock_quantity,
                    $stock->unit_of_measure
                );
            }
        }

        if ($duplicateMessages !== []) {
            throw ValidationException::withMessages([
                'items' => $duplicateMessages,
            ]);
        }

        if ($inventoryMessages !== [] && blank($inventoryBypassReason)) {
            throw ValidationException::withMessages([
                'inventory_bypass_reason' => array_merge(
                    ['Inventory already contains one or more requested items. Add a bypass reason to continue.'],
                    $inventoryMessages
                ),
            ]);
        }
    }

    /**
     * Store uploaded procurement documents.
     *
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     * @param  array<string, mixed>  $metadata
     */
    private function storeDocuments(
        ProcurementRequestModel $procurementRequest,
        ?ProcurementPurchaseOrderModel $purchaseOrder,
        $files,
        string $documentType,
        UserModel $uploadedBy,
        array $metadata = []
    ): void {
        $fileCollection = collect(is_array($files) ? $files : ($files ? [$files] : []))
            ->filter(fn ($file) => $file instanceof UploadedFile);

        foreach ($fileCollection as $file) {
            /** @var UploadedFile $file */
            $path = $file->storeAs(
                'procurement/' . $procurementRequest->request_reference . '/' . $documentType,
                now()->format('YmdHis') . '-' . Str::uuid() . '.' . $file->getClientOriginalExtension(),
                'public'
            );

            ProcurementDocumentModel::create([
                'procurement_request_id' => $procurementRequest->id,
                'purchase_order_id' => $purchaseOrder?->id,
                'uploaded_by' => $uploadedBy->id,
                'document_type' => $documentType,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'metadata' => $metadata === [] ? null : $metadata,
            ]);
        }
    }

    /**
     * Record an audit entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function recordAudit(
        ProcurementRequestModel $procurementRequest,
        ?UserModel $actor,
        string $action,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $note = null,
        array $metadata = []
    ): void {
        ProcurementAuditTrailModel::create([
            'procurement_request_id' => $procurementRequest->id,
            'actor_id' => $actor?->id,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * Validate three-way matching requirements.
     */
    private function assertThreeWayMatchRequirements(ProcurementRequestModel $procurementRequest, float $invoiceAmount): void
    {
        $procurementRequest->loadMissing(['purchaseOrder.items.procurementRequestItem', 'documents', 'items', 'defectReturns']);

        if (!$procurementRequest->purchaseOrder) {
            throw ValidationException::withMessages([
                'purchase_order' => ['A purchase order is required before payment processing.'],
            ]);
        }

        if ($procurementRequest->defectReturns->contains(fn (ProcurementDefectReturnModel $defectReturn) => $defectReturn->isOpen())) {
            throw ValidationException::withMessages([
                'items' => ['All defect-return items must be resolved before payment processing can begin.'],
            ]);
        }

        $documentTypes = $procurementRequest->documents->pluck('document_type')->unique()->all();

        if (!in_array('delivery_receipt', $documentTypes, true)) {
            throw ValidationException::withMessages([
                'delivery_receipt_files' => ['A delivery receipt document is required before payment processing.'],
            ]);
        }

        $poTotal = round((float) $procurementRequest->purchaseOrder->total_amount, 2);
        $approvedTotal = round((float) $procurementRequest->approved_total, 2);

        if ($poTotal !== $approvedTotal || round($invoiceAmount, 2) !== $poTotal) {
            throw ValidationException::withMessages([
                'invoice_amount' => ['Purchase order, request approved total, and invoice amount must match exactly.'],
            ]);
        }

        foreach ($procurementRequest->purchaseOrder->items as $purchaseOrderItem) {
            if (round((float) $purchaseOrderItem->quantity, 2) !== round((float) ($purchaseOrderItem->procurementRequestItem->received_quantity ?? 0), 2)) {
                throw ValidationException::withMessages([
                    'items' => ['All received quantities must match the purchase order before payment processing.'],
                ]);
            }
        }
    }

    /**
     * Get timeline UI metadata for an audit action.
     *
     * @return array<string, string>
     */
    private function getTimelineDisplay(string $action): array
    {
        return match ($action) {
            'draft_saved', 'draft_updated' => [
                'title' => 'Draft Saved',
                'description' => 'A procurement draft was saved for later completion.',
                'icon' => 'ti ti-edit-circle',
                'dot_class' => 'bg-secondary-subtle',
                'icon_class' => 'text-secondary',
            ],
            'request_submitted', 'request_resubmitted' => [
                'title' => 'Request Submitted',
                'description' => 'The procurement request was submitted into the approval workflow.',
                'icon' => 'ti ti-send',
                'dot_class' => 'bg-warning-subtle',
                'icon_class' => 'text-warning',
            ],
            'finance_approve' => [
                'title' => 'Finance Approved',
                'description' => 'Finance cleared the request for owner approval.',
                'icon' => 'ti ti-checklist',
                'dot_class' => 'bg-success-subtle',
                'icon_class' => 'text-success',
            ],
            'finance_return', 'owner_return' => [
                'title' => 'Returned for Revision',
                'description' => 'The request was returned for updates before it can continue.',
                'icon' => 'ti ti-arrow-back-up',
                'dot_class' => 'bg-info-subtle',
                'icon_class' => 'text-info',
            ],
            'finance_reject', 'owner_reject', 'request_cancelled' => [
                'title' => 'Request Closed',
                'description' => 'The request was rejected or cancelled before completion.',
                'icon' => 'ti ti-circle-x',
                'dot_class' => 'bg-danger-subtle',
                'icon_class' => 'text-danger',
            ],
            'owner_approve' => [
                'title' => 'Owner Approved',
                'description' => 'Owner approval was completed and purchasing may proceed.',
                'icon' => 'ti ti-rosette-discount-check',
                'dot_class' => 'bg-success-subtle',
                'icon_class' => 'text-success',
            ],
            'purchase_order_created' => [
                'title' => 'Purchase Order Generated',
                'description' => 'Finance issued a purchase order for the approved request.',
                'icon' => 'ti ti-file-invoice',
                'dot_class' => 'bg-primary-subtle',
                'icon_class' => 'text-primary',
            ],
            'delivery_recorded', 'replacement_delivered' => [
                'title' => 'Delivery Recorded',
                'description' => 'Delivered items were logged and are awaiting requester confirmation.',
                'icon' => 'ti ti-truck-delivery',
                'dot_class' => 'bg-info-subtle',
                'icon_class' => 'text-info',
            ],
            'receipt_confirmed' => [
                'title' => 'Receipt Confirmed',
                'description' => 'The requester accepted the delivered items.',
                'icon' => 'ti ti-package-export',
                'dot_class' => 'bg-success-subtle',
                'icon_class' => 'text-success',
            ],
            'defect_reported' => [
                'title' => 'Defect Reported',
                'description' => 'One or more delivered items were marked defective.',
                'icon' => 'ti ti-alert-triangle',
                'dot_class' => 'bg-warning-subtle',
                'icon_class' => 'text-warning',
            ],
            'return_processed' => [
                'title' => 'Return Processing Started',
                'description' => 'Finance started the supplier return workflow for defective items.',
                'icon' => 'ti ti-refresh',
                'dot_class' => 'bg-warning-subtle',
                'icon_class' => 'text-warning',
            ],
            'payment_processing_started' => [
                'title' => 'Payment Processing Started',
                'description' => 'Invoice and payment processing were initiated for the procurement.',
                'icon' => 'ti ti-credit-card-pay',
                'dot_class' => 'bg-warning-subtle',
                'icon_class' => 'text-warning',
            ],
            'payment_completed' => [
                'title' => 'Procurement Completed',
                'description' => 'The procurement was fully paid and completed.',
                'icon' => 'ti ti-cash-banknote',
                'dot_class' => 'bg-success-subtle',
                'icon_class' => 'text-success',
            ],
            default => [
                'title' => str($action)->replace('_', ' ')->title()->toString(),
                'description' => 'A procurement workflow update was recorded.',
                'icon' => 'ti ti-history',
                'dot_class' => 'bg-secondary-subtle',
                'icon_class' => 'text-secondary',
            ],
        };
    }

    /**
     * Notify finance users that a request was submitted.
     */
    private function notifyFinanceOfSubmission(ProcurementRequestModel $procurementRequest): void
    {
        foreach ($this->getUsersByStudioRole($procurementRequest->studio_id, 'studio-finance') as $financeUser) {
            $this->createNotification(
                $financeUser->id,
                'procurement_submitted',
                'New Procurement Request',
                "{$procurementRequest->request_reference} was submitted for finance review.",
                [
                    'procurement_request_id' => $procurementRequest->id,
                    'request_reference' => $procurementRequest->request_reference,
                    'route' => route('studio-finance.procurement.index', [], false),
                ],
                'shopping-cart',
                'info'
            );
        }
    }

    /**
     * Notify the owner that finance approved the request.
     */
    private function notifyOwnerOfFinanceApproval(ProcurementRequestModel $procurementRequest): void
    {
        $owner = $this->getOwnerForStudio($procurementRequest->studio_id);

        if (!$owner) {
            return;
        }

        $this->createNotification(
            $owner->id,
            'procurement_pending_owner',
            'Procurement Needs Approval',
            "{$procurementRequest->request_reference} is ready for owner approval.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route('owner.procurement.index', [], false),
            ],
            'clipboard-check',
            'warning'
        );
    }

    /**
     * Notify requester and finance users after owner approval.
     */
    private function notifyRequesterAndFinanceOfOwnerApproval(ProcurementRequestModel $procurementRequest): void
    {
        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_approved',
            'Procurement Approved',
            "{$procurementRequest->request_reference} was approved by the Studio Owner.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            'circle-check',
            'success'
        );

        foreach ($this->getUsersByStudioRole($procurementRequest->studio_id, 'studio-finance') as $financeUser) {
            $this->createNotification(
                $financeUser->id,
                'procurement_approved',
                'Owner Approved Procurement',
                "{$procurementRequest->request_reference} is approved and ready for purchase order generation.",
                [
                    'procurement_request_id' => $procurementRequest->id,
                    'request_reference' => $procurementRequest->request_reference,
                    'route' => route('studio-finance.procurement.index', [], false),
                ],
                'circle-check',
                'success'
            );
        }
    }

    /**
     * Notify requester about a returned or rejected request.
     */
    private function notifyRequesterOfStatusUpdate(ProcurementRequestModel $procurementRequest, string $action, ?string $note): void
    {
        $label = $action === 'return' ? 'Returned for Revision' : ucfirst($action) . 'ed';
        $message = "{$procurementRequest->request_reference} was {$action}ed.";

        if ($action === 'return') {
            $message = "{$procurementRequest->request_reference} was returned for revision.";
        }

        if (!blank($note)) {
            $message .= " Note: {$note}";
        }

        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_status_update',
            "Procurement {$label}",
            $message,
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            $action === 'reject' ? 'circle-x' : 'refresh',
            $action === 'reject' ? 'danger' : 'warning'
        );
    }

    /**
     * Notify the requester that delivery was logged.
     */
    private function notifyRequesterOfDelivery(ProcurementRequestModel $procurementRequest): void
    {
        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_delivered',
            'Procurement Delivered',
            "{$procurementRequest->request_reference} has been marked as delivered. Please confirm receipt.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            'truck-delivery',
            'info'
        );
    }

    /**
     * Notify finance that a requester reported defective items.
     */
    private function notifyFinanceOfDefectReport(ProcurementRequestModel $procurementRequest): void
    {
        foreach ($this->getUsersByStudioRole($procurementRequest->studio_id, 'studio-finance') as $financeUser) {
            $this->createNotification(
                $financeUser->id,
                'procurement_defect_reported',
                'Defective Procurement Items Reported',
                "{$procurementRequest->request_reference} has defective delivered items that need return handling.",
                [
                    'procurement_request_id' => $procurementRequest->id,
                    'request_reference' => $procurementRequest->request_reference,
                    'route' => route('studio-finance.procurement.index', [], false),
                ],
                'alert-triangle',
                'warning'
            );
        }
    }

    /**
     * Notify the requester that replacement items were delivered.
     */
    private function notifyRequesterOfReplacementDelivery(ProcurementRequestModel $procurementRequest): void
    {
        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_replacement_delivered',
            'Replacement Delivery Recorded',
            "{$procurementRequest->request_reference} replacement items were delivered. Please confirm receipt again.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            'refresh',
            'info'
        );
    }

    /**
     * Notify requester and finance that the defect case is resolved.
     */
    private function notifyDefectResolution(ProcurementRequestModel $procurementRequest): void
    {
        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_defect_resolved',
            'Defect Return Resolved',
            "{$procurementRequest->request_reference} defect return items were accepted and the request can continue to payment.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            'circle-check',
            'success'
        );

        foreach ($this->getUsersByStudioRole($procurementRequest->studio_id, 'studio-finance') as $financeUser) {
            $this->createNotification(
                $financeUser->id,
                'procurement_defect_resolved',
                'Defect Return Resolved',
                "{$procurementRequest->request_reference} defect return items were accepted and payment can continue.",
                [
                    'procurement_request_id' => $procurementRequest->id,
                    'request_reference' => $procurementRequest->request_reference,
                    'route' => route('studio-finance.procurement.index', [], false),
                ],
                'circle-check',
                'success'
            );
        }
    }

    /**
     * Notify requester and owner after payment completion.
     */
    private function notifyPaymentCompletion(ProcurementRequestModel $procurementRequest): void
    {
        $owner = $this->getOwnerForStudio($procurementRequest->studio_id);

        $this->createNotification(
            $procurementRequest->requester_id,
            'procurement_completed',
            'Procurement Completed',
            "{$procurementRequest->request_reference} has been completed and paid.",
            [
                'procurement_request_id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'route' => route($this->getRequesterIndexRoute($procurementRequest->requester_role), [], false),
            ],
            'cash',
            'success'
        );

        if ($owner) {
            $this->createNotification(
                $owner->id,
                'procurement_completed',
                'Procurement Payment Completed',
                "{$procurementRequest->request_reference} has been completed and paid.",
                [
                    'procurement_request_id' => $procurementRequest->id,
                    'request_reference' => $procurementRequest->request_reference,
                    'route' => route('owner.procurement.index', [], false),
                ],
                'cash',
                'success'
            );
        }
    }

    /**
     * Get the owner of a studio.
     */
    private function getOwnerForStudio(int $studioId): ?UserModel
    {
        $studio = StudiosModel::find($studioId);

        return $studio ? UserModel::find($studio->user_id) : null;
    }

    /**
     * Get users for a studio and role combination.
     */
    private function getUsersByStudioRole(int $studioId, string $role): Collection
    {
        return UserModel::query()
            ->where('role', $role)
            ->get()
            ->filter(function (UserModel $user) use ($studioId, $role): bool {
                return $this->getAssignedStudioIds($user, $role)->contains($studioId);
            })
            ->values();
    }

    /**
     * Get the requester portal index route name.
     */
    private function getRequesterIndexRoute(string $requesterRole): string
    {
        return match ($requesterRole) {
            'studio-photographer' => 'studio-photographer.procurement.index',
            default => 'studio-hr.procurement.index',
        };
    }

    /**
     * Refresh the request with commonly used relationships.
     */
    private function refreshRequest(ProcurementRequestModel $procurementRequest): ProcurementRequestModel
    {
        return $procurementRequest->fresh([
            'studio',
            'requester',
            'items.defectReturns',
            'purchaseOrder.items',
            'documents',
            'auditTrails.actor',
            'defectReturns.procurementRequestItem',
        ]);
    }
}
