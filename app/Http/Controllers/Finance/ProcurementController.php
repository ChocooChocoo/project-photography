<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\ProcessProcurementReturnRequest;
use App\Http\Requests\Finance\ReviewProcurementRequest;
use App\Http\Requests\Finance\StoreProcurementDeliveryRequest;
use App\Http\Requests\Finance\StoreProcurementPaymentRequest;
use App\Http\Requests\Finance\StorePurchaseOrderRequest;
use App\Http\Requests\Finance\StoreProcurementReplacementDeliveryRequest;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\UserModel;
use App\Services\ProcurementWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function __construct(private readonly ProcurementWorkflowService $procurementWorkflowService)
    {
    }

    public function index(): View|RedirectResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $studioIds = $this->procurementWorkflowService->getAssignedStudioIds($financeUser, 'studio-finance');

        if ($studioIds->isEmpty()) {
            return redirect()->route('studio-finance.dashboard')->with('error', 'No studio assigned to your account.');
        }

        $procurementRequests = $this->procurementWorkflowService->getFinanceRequests($financeUser);

        return view('studio-finance.procurement-requests', [
            'procurementRequests' => $procurementRequests,
            'requestSummary' => [
                'pending_review' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
                'approved' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_APPROVED)->count(),
                'ordered' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_ORDERED)->count(),
                'delivered' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_DELIVERED)->count(),
                'defect_reported' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_DEFECT_REPORTED)->count(),
                'return_in_progress' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS)->count(),
                'received' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_RECEIVED)->count(),
                'payment_processing' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PAYMENT_PROCESSING)->count(),
            ],
            'requestWidgets' => $this->buildFinanceWidgets($procurementRequests),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement details loaded successfully.',
            'data' => $this->procurementWorkflowService->buildDetailPayload($procurementRequest, $financeUser),
        ]);
    }

    public function review(ReviewProcurementRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->reviewByFinance($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Finance review action completed successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    public function storePurchaseOrder(StorePurchaseOrderRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->createPurchaseOrder($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Purchase order generated successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
                'po_number' => $updatedRequest->purchaseOrder?->po_number,
            ],
        ]);
    }

    public function recordDelivery(StoreProcurementDeliveryRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->recordDelivery($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery recorded successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    public function processReturn(ProcessProcurementReturnRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->processDefectReturn($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Defective item return processing started successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    public function recordReplacementDelivery(StoreProcurementReplacementDeliveryRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->recordReplacementDelivery($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Replacement delivery recorded successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    public function recordPayment(StoreProcurementPaymentRequest $request, string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->recordPayment($procurementRequest, $financeUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Payment processing started successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    public function complete(string $id): JsonResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();

        if (!$financeUser->hasPermission('studio-finance.procurement.payment')) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to complete procurement payments.',
                'errors' => [],
            ], 403);
        }

        $procurementRequest = $this->getManagedRequest($id, $financeUser);
        $updatedRequest = $this->procurementWorkflowService->completePayment($procurementRequest, $financeUser);

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement request completed successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    private function getAuthenticatedFinanceUser(): UserModel
    {
        return UserModel::with('roles.permissions')->findOrFail(auth()->id());
    }

    private function getManagedRequest(string $id, UserModel $financeUser): ProcurementRequestModel
    {
        $studioIds = $this->procurementWorkflowService->getAssignedStudioIds($financeUser, 'studio-finance');

        return ProcurementRequestModel::whereIn('studio_id', $studioIds)->findOrFail($id);
    }

    private function buildFinanceWidgets($procurementRequests): array
    {
        $counts = [
            'pending_review' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
            'approved' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_APPROVED)->count(),
            'ordered_delivery' => $procurementRequests->whereIn('status', [
                ProcurementRequestModel::STATUS_ORDERED,
                ProcurementRequestModel::STATUS_DELIVERED,
            ])->count(),
            'payment_defect' => $procurementRequests->whereIn('status', [
                ProcurementRequestModel::STATUS_DEFECT_REPORTED,
                ProcurementRequestModel::STATUS_RETURN_IN_PROGRESS,
                ProcurementRequestModel::STATUS_RECEIVED,
                ProcurementRequestModel::STATUS_PAYMENT_PROCESSING,
            ])->count(),
        ];

        $total = max(1, $procurementRequests->count());

        return [
            [
                'label' => 'Pending Review',
                'value' => $counts['pending_review'],
                'class' => 'warning',
                'icon' => 'ti ti-clipboard-search',
                'progress_label' => 'REVIEW LOAD',
                'progress' => (int) round(($counts['pending_review'] / $total) * 100),
            ],
            [
                'label' => 'Approved to Order',
                'value' => $counts['approved'],
                'class' => 'success',
                'icon' => 'ti ti-checklist',
                'progress_label' => 'READY TO BUY',
                'progress' => (int) round(($counts['approved'] / $total) * 100),
            ],
            [
                'label' => 'Order & Delivery',
                'value' => $counts['ordered_delivery'],
                'class' => 'info',
                'icon' => 'ti ti-truck-delivery',
                'progress_label' => 'SUPPLIER STAGE',
                'progress' => (int) round(($counts['ordered_delivery'] / $total) * 100),
            ],
            [
                'label' => 'Payment & Returns',
                'value' => $counts['payment_defect'],
                'class' => 'warning',
                'icon' => 'ti ti-file-invoice',
                'progress_label' => 'CLOSEOUT LOAD',
                'progress' => (int) round(($counts['payment_defect'] / $total) * 100),
            ],
        ];
    }
}
