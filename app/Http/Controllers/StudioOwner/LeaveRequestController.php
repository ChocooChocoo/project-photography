<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\ProcessLeaveRequest;
use App\Models\LeaveRequestModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Handle Studio Owner leave request workflows for HR employees.
 */
class LeaveRequestController extends Controller
{
    /**
     * Display all HR leave requests under the authenticated Studio Owner.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        $ownerUser = $this->getAuthenticatedOwnerUser();
        $ownedStudioIds = $this->getOwnedStudioIds($ownerUser->id);

        if ($ownedStudioIds->isEmpty()) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'No owned studio is available for leave request approval.');
        }

        $hrLeaveRequests = LeaveRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $ownedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', 'studio-hr');
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $leaveRequestSummary = [
            'pending' => $hrLeaveRequests->where('status', 'pending')->count(),
            'approved' => $hrLeaveRequests->where('status', 'approved')->count(),
            'rejected' => $hrLeaveRequests->where('status', 'rejected')->count(),
            'cancelled' => $hrLeaveRequests->where('status', 'cancelled')->count(),
        ];

        return view('owner.hr-leave-requests', compact(
            'hrLeaveRequests',
            'leaveRequestSummary'
        ));
    }

    /**
     * Display the selected HR leave request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $ownerUser = $this->getAuthenticatedOwnerUser();
            $leaveRequest = $this->getManagedHrLeaveRequest($id, $ownerUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'HR leave request details loaded successfully.',
                'data' => $this->buildHrLeaveRequestPayload($leaveRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load HR leave request details for Studio Owner.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'owner_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load HR leave request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Process an HR leave request approval or rejection.
     *
     * @param  \App\Http\Requests\StudioOwner\ProcessLeaveRequest  $request
     * @param  string  $id
     * @param  string  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(ProcessLeaveRequest $request, string $id, string $action): JsonResponse
    {
        try {
            $ownerUser = $this->getAuthenticatedOwnerUser();
            $leaveRequest = $this->getManagedHrLeaveRequest($id, $ownerUser->id);
            $validated = $request->validated();
            $normalizedAction = strtolower($action);

            if (!in_array($normalizedAction, ['approve', 'reject'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected leave request action is invalid.',
                    'errors' => [],
                ], 422);
            }

            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending leave requests can be processed.',
                    'errors' => [],
                ], 422);
            }

            if ($normalizedAction === 'approve') {
                $leaveRequest->update([
                    'status' => 'approved',
                    'approved_by' => $ownerUser->id,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'cancelled_at' => null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'HR leave request approved successfully.',
                    'data' => [
                        'id' => $leaveRequest->id,
                        'status' => $leaveRequest->status,
                        'status_display' => $leaveRequest->status_label,
                        'processed_by' => $ownerUser->full_name,
                        'processed_at' => $leaveRequest->approved_at?->format('F d, Y h:i A'),
                    ],
                ]);
            }

            $leaveRequest->update([
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => $ownerUser->id,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'cancelled_at' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'HR leave request rejected successfully.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                    'processed_by' => $ownerUser->full_name,
                    'processed_at' => $leaveRequest->rejected_at?->format('F d, Y h:i A'),
                    'rejection_reason' => $leaveRequest->rejection_reason,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to process HR leave request for Studio Owner.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'action' => $action,
                'owner_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Get the authenticated Studio Owner user.
     *
     * @return \App\Models\UserModel
     */
    private function getAuthenticatedOwnerUser(): UserModel
    {
        return UserModel::findOrFail(auth()->id());
    }

    /**
     * Get the owned studio IDs of the Studio Owner.
     *
     * @param  int  $ownerUserId
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getOwnedStudioIds(int $ownerUserId)
    {
        return StudiosModel::where('user_id', $ownerUserId)
            ->pluck('id')
            ->unique()
            ->values();
    }

    /**
     * Get a managed HR leave request for the authenticated Studio Owner.
     *
     * @param  string  $id
     * @param  int  $ownerUserId
     * @return \App\Models\LeaveRequestModel
     */
    private function getManagedHrLeaveRequest(string $id, int $ownerUserId): LeaveRequestModel
    {
        $ownedStudioIds = $this->getOwnedStudioIds($ownerUserId);

        return LeaveRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $ownedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', 'studio-hr');
            })
            ->findOrFail($id);
    }

    /**
     * Build the response payload for an HR leave request.
     *
     * @param  \App\Models\LeaveRequestModel  $leaveRequest
     * @return array<string, mixed>
     */
    private function buildHrLeaveRequestPayload(LeaveRequestModel $leaveRequest): array
    {
        return [
            'id' => $leaveRequest->id,
            'request_reference' => $leaveRequest->request_reference,
            'hr_name' => $leaveRequest->user->full_name ?? 'N/A',
            'hr_email' => $leaveRequest->user->email ?? 'N/A',
            'hr_role' => 'Human Resource',
            'hr_photo' => $leaveRequest->user->profile_photo_url ?? asset('assets/images/users/user-3.jpg'),
            'studio_name' => $leaveRequest->studio->studio_name ?? 'N/A',
            'leave_type_display' => $leaveRequest->leave_type_label,
            'period_display' => $leaveRequest->start_date?->format('M d, Y') . ' - ' . $leaveRequest->end_date?->format('M d, Y'),
            'start_date' => $leaveRequest->start_date?->format('M d, Y'),
            'end_date' => $leaveRequest->end_date?->format('M d, Y'),
            'total_days_display' => $this->formatTotalDays((float) $leaveRequest->total_days),
            'reason' => $leaveRequest->reason,
            'status' => $leaveRequest->status,
            'status_display' => $leaveRequest->status_label,
            'rejection_reason' => $leaveRequest->rejection_reason,
            'submitted_at' => $leaveRequest->created_at?->format('F d, Y h:i A'),
            'approved_at' => $leaveRequest->approved_at?->format('F d, Y h:i A'),
            'rejected_at' => $leaveRequest->rejected_at?->format('F d, Y h:i A'),
            'processed_by' => $leaveRequest->approver->full_name
                ?? $leaveRequest->rejector->full_name
                ?? 'Not processed yet.',
            'can_approve' => $leaveRequest->status === 'pending',
            'can_reject' => $leaveRequest->status === 'pending',
        ];
    }

    /**
     * Format the total days display.
     *
     * @param  float  $totalDays
     * @return string
     */
    private function formatTotalDays(float $totalDays): string
    {
        $normalizedDays = rtrim(rtrim(number_format($totalDays, 2, '.', ''), '0'), '.');
        $label = (float) $totalDays === 1.0 ? 'day' : 'days';

        return $normalizedDays . ' ' . $label;
    }
}
