<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ConfirmProcurementReceiptRequest;
use App\Http\Requests\StudioPhotographer\StoreProcurementRequest;
use App\Http\Requests\StudioPhotographer\UpdateProcurementRequest;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\UserModel;
use App\Services\ProcurementWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProcurementRequestController extends Controller
{
    public function __construct(private readonly ProcurementWorkflowService $procurementWorkflowService)
    {
    }

    public function create(): View|RedirectResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($photographerUser, 'studio-photographer');

        if (!$assignedStudio) {
            return redirect()->route('studio-photographer.dashboard')->with('error', 'No studio assigned to your account.');
        }

        return view('studio-photographer.request-procurement', [
            'portalLabel' => 'Studio Photographer',
            'assignedStudio' => $assignedStudio,
            'existingRequest' => null,
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($photographerUser, 'studio-photographer');

        if (!$assignedStudio) {
            return redirect()->route('studio-photographer.dashboard')->with('error', 'No studio assigned to your account.');
        }

        return view('studio-photographer.request-procurement', [
            'portalLabel' => 'Studio Photographer',
            'assignedStudio' => $assignedStudio,
            'existingRequest' => $this->getManagedRequest($id, $photographerUser, 'studio-photographer'),
        ]);
    }

    public function index(): View|RedirectResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($photographerUser, 'studio-photographer');

        if (!$assignedStudio) {
            return redirect()->route('studio-photographer.dashboard')->with('error', 'No studio assigned to your account.');
        }

        $procurementRequests = $this->procurementWorkflowService->getRequesterRequests($photographerUser, 'studio-photographer');

        return view('studio-photographer.view-requested-procurement', [
            'portalLabel' => 'Studio Photographer',
            'procurementRequests' => $procurementRequests,
            'requestSummary' => [
                'draft' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_DRAFT)->count(),
                'pending' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
                'returned' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION)->count(),
                'completed' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
            ],
            'requestWidgets' => $this->buildRequesterWidgets($procurementRequests),
        ]);
    }

    public function store(StoreProcurementRequest $request): JsonResponse
    {
        $procurementRequest = $this->procurementWorkflowService->createRequest(
            $this->getAuthenticatedPhotographerUser(),
            $request->validated(),
            'studio-photographer'
        );

        return response()->json([
            'status' => 'success',
            'message' => $procurementRequest->status === ProcurementRequestModel::STATUS_DRAFT
                ? 'Procurement draft saved successfully.'
                : 'Procurement request submitted successfully.',
            'data' => [
                'id' => $procurementRequest->id,
                'request_reference' => $procurementRequest->request_reference,
                'status' => $procurementRequest->status,
                'status_display' => $procurementRequest->status_label,
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $procurementRequest = $this->getManagedRequest($id, $photographerUser, 'studio-photographer');

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement request details loaded successfully.',
            'data' => $this->procurementWorkflowService->buildDetailPayload($procurementRequest, $photographerUser),
        ]);
    }

    public function update(UpdateProcurementRequest $request, string $id): JsonResponse
    {
        try {
            $photographerUser = $this->getAuthenticatedPhotographerUser();
            $procurementRequest = $this->getManagedRequest($id, $photographerUser, 'studio-photographer');
            $updatedRequest = $this->procurementWorkflowService->updateRequest($procurementRequest, $photographerUser, $request->validated());

            return response()->json([
                'status' => 'success',
                'message' => $updatedRequest->status === ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW
                    ? 'Procurement request resubmitted successfully.'
                    : 'Procurement draft updated successfully.',
                'data' => [
                    'id' => $updatedRequest->id,
                    'request_reference' => $updatedRequest->request_reference,
                    'status' => $updatedRequest->status,
                    'status_display' => $updatedRequest->status_label,
                ],
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    public function cancel(string $id): JsonResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $procurementRequest = $this->getManagedRequest($id, $photographerUser, 'studio-photographer');
        $cancelledRequest = $this->procurementWorkflowService->cancelRequest($procurementRequest, $photographerUser);

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement request cancelled successfully.',
            'data' => [
                'id' => $cancelledRequest->id,
                'status' => $cancelledRequest->status,
                'status_display' => $cancelledRequest->status_label,
            ],
        ]);
    }

    public function confirmReceipt(ConfirmProcurementReceiptRequest $request, string $id): JsonResponse
    {
        $photographerUser = $this->getAuthenticatedPhotographerUser();
        $procurementRequest = $this->getManagedRequest($id, $photographerUser, 'studio-photographer');
        $receivedRequest = $this->procurementWorkflowService->confirmReceipt($procurementRequest, $photographerUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => $receivedRequest->status === ProcurementRequestModel::STATUS_DEFECT_REPORTED
                ? 'Defective items were reported successfully. Finance has been notified.'
                : 'Procurement receipt confirmed successfully.',
            'data' => [
                'id' => $receivedRequest->id,
                'status' => $receivedRequest->status,
                'status_display' => $receivedRequest->status_label,
            ],
        ]);
    }

    private function getAuthenticatedPhotographerUser(): UserModel
    {
        return UserModel::findOrFail(auth()->id());
    }

    private function getManagedRequest(string $id, UserModel $user, string $portal): ProcurementRequestModel
    {
        $studioIds = $this->procurementWorkflowService->getAssignedStudioIds($user, $portal);

        return ProcurementRequestModel::where('requester_id', $user->id)
            ->whereIn('studio_id', $studioIds)
            ->findOrFail($id);
    }

    private function buildRequesterWidgets($procurementRequests): array
    {
        $counts = [
            'draft' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_DRAFT)->count(),
            'pending' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
            'returned' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION)->count(),
            'completed' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
        ];

        $total = max(1, array_sum($counts));

        return [
            [
                'label' => 'Draft Requests',
                'value' => $counts['draft'],
                'class' => 'secondary',
                'icon' => 'ti ti-edit-circle',
                'progress_label' => 'QUEUE SHARE',
                'progress' => (int) round(($counts['draft'] / $total) * 100),
            ],
            [
                'label' => 'Pending Review',
                'value' => $counts['pending'],
                'class' => 'warning',
                'icon' => 'ti ti-hourglass-empty',
                'progress_label' => 'IN REVIEW',
                'progress' => (int) round(($counts['pending'] / $total) * 100),
            ],
            [
                'label' => 'Returned Items',
                'value' => $counts['returned'],
                'class' => 'info',
                'icon' => 'ti ti-arrow-back-up',
                'progress_label' => 'NEEDS ACTION',
                'progress' => (int) round(($counts['returned'] / $total) * 100),
            ],
            [
                'label' => 'Completed',
                'value' => $counts['completed'],
                'class' => 'success',
                'icon' => 'ti ti-rosette-discount-check',
                'progress_label' => 'FULFILLED',
                'progress' => (int) round(($counts['completed'] / $total) * 100),
            ],
        ];
    }
}
