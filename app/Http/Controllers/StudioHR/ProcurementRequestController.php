<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ConfirmProcurementReceiptRequest;
use App\Http\Requests\StudioHR\StoreProcurementRequest;
use App\Http\Requests\StudioHR\UpdateProcurementRequest;
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
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($hrUser, 'studio-hr');

        if (!$assignedStudio) {
            return redirect()->route('studio-hr.dashboard')->with('error', 'No studio assigned to your account.');
        }

        return view('studio-hr.request-procurement', [
            'portalLabel' => 'Human Resource',
            'assignedStudio' => $assignedStudio,
            'existingRequest' => null,
        ]);
    }

    public function edit(string $id): View|RedirectResponse
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($hrUser, 'studio-hr');

        if (!$assignedStudio) {
            return redirect()->route('studio-hr.dashboard')->with('error', 'No studio assigned to your account.');
        }

        return view('studio-hr.request-procurement', [
            'portalLabel' => 'Human Resource',
            'assignedStudio' => $assignedStudio,
            'existingRequest' => $this->getManagedRequest($id, $hrUser, 'studio-hr'),
        ]);
    }

    public function index(): View|RedirectResponse
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudio = $this->procurementWorkflowService->getPrimaryAssignedStudio($hrUser, 'studio-hr');

        if (!$assignedStudio) {
            return redirect()->route('studio-hr.dashboard')->with('error', 'No studio assigned to your account.');
        }

        $procurementRequests = $this->procurementWorkflowService->getRequesterRequests($hrUser, 'studio-hr');

        return view('studio-hr.view-requested-procurement', [
            'portalLabel' => 'Human Resource',
            'procurementRequests' => $procurementRequests,
            'requestSummary' => [
                'draft' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_DRAFT)->count(),
                'pending' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
                'returned' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_RETURNED_FOR_REVISION)->count(),
                'completed' => $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
            ],
        ]);
    }

    public function store(StoreProcurementRequest $request): JsonResponse
    {
        $procurementRequest = $this->procurementWorkflowService->createRequest(
            $this->getAuthenticatedHrUser(),
            $request->validated(),
            'studio-hr'
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
        $hrUser = $this->getAuthenticatedHrUser();
        $procurementRequest = $this->getManagedRequest($id, $hrUser, 'studio-hr');

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement request details loaded successfully.',
            'data' => $this->procurementWorkflowService->buildDetailPayload($procurementRequest, $hrUser),
        ]);
    }

    public function update(UpdateProcurementRequest $request, string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $procurementRequest = $this->getManagedRequest($id, $hrUser, 'studio-hr');
            $updatedRequest = $this->procurementWorkflowService->updateRequest($procurementRequest, $hrUser, $request->validated());

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
        $hrUser = $this->getAuthenticatedHrUser();
        $procurementRequest = $this->getManagedRequest($id, $hrUser, 'studio-hr');
        $cancelledRequest = $this->procurementWorkflowService->cancelRequest($procurementRequest, $hrUser);

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
        $hrUser = $this->getAuthenticatedHrUser();
        $procurementRequest = $this->getManagedRequest($id, $hrUser, 'studio-hr');
        $receivedRequest = $this->procurementWorkflowService->confirmReceipt($procurementRequest, $hrUser, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Procurement receipt confirmed successfully.',
            'data' => [
                'id' => $receivedRequest->id,
                'status' => $receivedRequest->status,
                'status_display' => $receivedRequest->status_label,
            ],
        ]);
    }

    private function getAuthenticatedHrUser(): UserModel
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
}
