<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\ProcessProcurementApprovalRequest;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\UserModel;
use App\Services\ProcurementWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProcurementApprovalController extends Controller
{
    public function __construct(private readonly ProcurementWorkflowService $procurementWorkflowService)
    {
    }

    public function index(): View|RedirectResponse
    {
        $ownerUser = $this->getAuthenticatedOwnerUser();
        $studioIds = $this->procurementWorkflowService->getAssignedStudioIds($ownerUser, 'owner');

        if ($studioIds->isEmpty()) {
            return redirect()->route('owner.dashboard')->with('error', 'No owned studio is available for procurement oversight.');
        }

        $procurementRequests = $this->procurementWorkflowService->getOwnerRequests($ownerUser);

        return view('owner.procurement-requests', [
            'procurementRequests' => $procurementRequests,
            'requestSummary' => [
                'pending_owner' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL)->count(),
                'approved' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_APPROVED)->count(),
                'ordered' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_ORDERED)->count(),
                'completed' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
            ],
            'requestWidgets' => $this->buildOwnerWidgets($procurementRequests),
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $ownerUser = $this->getAuthenticatedOwnerUser();
        $procurementRequest = $this->getManagedRequest($id, $ownerUser);

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement details loaded successfully.',
            'data' => $this->procurementWorkflowService->buildDetailPayload($procurementRequest, $ownerUser),
        ]);
    }

    public function process(ProcessProcurementApprovalRequest $request, string $id): JsonResponse
    {
        $ownerUser = $this->getAuthenticatedOwnerUser();
        $procurementRequest = $this->getManagedRequest($id, $ownerUser);
        $updatedRequest = $this->procurementWorkflowService->processOwnerApproval($procurementRequest, $ownerUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Owner procurement action completed successfully.',
            'data' => [
                'id' => $updatedRequest->id,
                'status' => $updatedRequest->status,
                'status_display' => $updatedRequest->status_label,
            ],
        ]);
    }

    private function getAuthenticatedOwnerUser(): UserModel
    {
        return UserModel::with('roles.permissions')->findOrFail(auth()->id());
    }

    private function getManagedRequest(string $id, UserModel $ownerUser): ProcurementRequestModel
    {
        $studioIds = $this->procurementWorkflowService->getAssignedStudioIds($ownerUser, 'owner');

        return ProcurementRequestModel::whereIn('studio_id', $studioIds)->findOrFail($id);
    }

    private function buildOwnerWidgets($procurementRequests): array
    {
        $counts = [
            'pending_owner' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_OWNER_APPROVAL)->count(),
            'approved' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_APPROVED)->count(),
            'ordered' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_ORDERED)->count(),
            'completed' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
        ];

        $total = max(1, $procurementRequests->count());

        return [
            [
                'label' => 'Pending Approval',
                'value' => $counts['pending_owner'],
                'class' => 'warning',
                'icon' => 'ti ti-scale',
                'progress_label' => 'OWNER ACTION',
                'progress' => (int) round(($counts['pending_owner'] / $total) * 100),
            ],
            [
                'label' => 'Approved',
                'value' => $counts['approved'],
                'class' => 'success',
                'icon' => 'ti ti-circle-check',
                'progress_label' => 'CLEARED',
                'progress' => (int) round(($counts['approved'] / $total) * 100),
            ],
            [
                'label' => 'Ordered',
                'value' => $counts['ordered'],
                'class' => 'info',
                'icon' => 'ti ti-package-export',
                'progress_label' => 'IN FULFILLMENT',
                'progress' => (int) round(($counts['ordered'] / $total) * 100),
            ],
            [
                'label' => 'Completed',
                'value' => $counts['completed'],
                'class' => 'success',
                'icon' => 'ti ti-cash-banknote',
                'progress_label' => 'CLOSED',
                'progress' => (int) round(($counts['completed'] / $total) * 100),
            ],
        ];
    }
}
