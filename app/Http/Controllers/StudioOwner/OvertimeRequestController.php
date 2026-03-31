<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\ProcessOvertimeRequest;
use App\Models\OvertimeRequestModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Handle Studio Owner overtime request workflows for HR employees.
 */
class OvertimeRequestController extends Controller
{
    /**
     * Display all HR overtime requests under the authenticated Studio Owner.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        $ownerUser = $this->getAuthenticatedOwnerUser();
        $ownedStudioIds = $this->getOwnedStudioIds($ownerUser->id);

        if ($ownedStudioIds->isEmpty()) {
            return redirect()->route('owner.dashboard')
                ->with('error', 'No owned studio is available for overtime request approval.');
        }

        $hrOvertimeRequests = OvertimeRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $ownedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', 'studio-hr');
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $overtimeRequestSummary = [
            'pending' => $hrOvertimeRequests->where('status', 'pending')->count(),
            'approved' => $hrOvertimeRequests->where('status', 'approved')->count(),
            'rejected' => $hrOvertimeRequests->where('status', 'rejected')->count(),
            'cancelled' => $hrOvertimeRequests->where('status', 'cancelled')->count(),
        ];

        return view('owner.hr-overtime-requests', compact(
            'hrOvertimeRequests',
            'overtimeRequestSummary'
        ));
    }

    /**
     * Display the selected HR overtime request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $ownerUser = $this->getAuthenticatedOwnerUser();
            $overtimeRequest = $this->getManagedHrOvertimeRequest($id, $ownerUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'HR overtime request details loaded successfully.',
                'data' => $this->buildHrOvertimeRequestPayload($overtimeRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load HR overtime request details for Studio Owner.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'owner_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load HR overtime request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Process an HR overtime request approval or rejection.
     *
     * @param  \App\Http\Requests\StudioOwner\ProcessOvertimeRequest  $request
     * @param  string  $id
     * @param  string  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(ProcessOvertimeRequest $request, string $id, string $action): JsonResponse
    {
        try {
            $ownerUser = $this->getAuthenticatedOwnerUser();
            $overtimeRequest = $this->getManagedHrOvertimeRequest($id, $ownerUser->id);
            $validated = $request->validated();
            $normalizedAction = strtolower($action);

            if (!in_array($normalizedAction, ['approve', 'reject'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The selected overtime request action is invalid.',
                    'errors' => [],
                ], 422);
            }

            if ($overtimeRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending overtime requests can be processed.',
                    'errors' => [],
                ], 422);
            }

            if ($normalizedAction === 'approve') {
                $overtimeRequest->update([
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
                    'message' => 'HR overtime request approved successfully.',
                    'data' => [
                        'id' => $overtimeRequest->id,
                        'status' => $overtimeRequest->status,
                        'status_display' => $overtimeRequest->status_label,
                        'processed_by' => $ownerUser->full_name,
                        'processed_at' => $overtimeRequest->approved_at?->format('F d, Y h:i A'),
                    ],
                ]);
            }

            $overtimeRequest->update([
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
                'message' => 'HR overtime request rejected successfully.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                    'processed_by' => $ownerUser->full_name,
                    'processed_at' => $overtimeRequest->rejected_at?->format('F d, Y h:i A'),
                    'rejection_reason' => $overtimeRequest->rejection_reason,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to process HR overtime request for Studio Owner.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'action' => $action,
                'owner_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process the overtime request.',
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
     * Get a managed HR overtime request for the authenticated Studio Owner.
     *
     * @param  string  $id
     * @param  int  $ownerUserId
     * @return \App\Models\OvertimeRequestModel
     */
    private function getManagedHrOvertimeRequest(string $id, int $ownerUserId): OvertimeRequestModel
    {
        $ownedStudioIds = $this->getOwnedStudioIds($ownerUserId);

        return OvertimeRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $ownedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', 'studio-hr');
            })
            ->findOrFail($id);
    }

    /**
     * Build the response payload for an HR overtime request.
     *
     * @param  \App\Models\OvertimeRequestModel  $overtimeRequest
     * @return array<string, mixed>
     */
    private function buildHrOvertimeRequestPayload(OvertimeRequestModel $overtimeRequest): array
    {
        return [
            'id' => $overtimeRequest->id,
            'request_reference' => $overtimeRequest->request_reference,
            'hr_name' => $overtimeRequest->user->full_name ?? 'N/A',
            'hr_email' => $overtimeRequest->user->email ?? 'N/A',
            'hr_role' => 'Human Resource',
            'hr_photo' => $overtimeRequest->user->profile_photo_url ?? asset('assets/images/users/user-3.jpg'),
            'studio_name' => $overtimeRequest->studio->studio_name ?? 'N/A',
            'overtime_date_display' => $overtimeRequest->overtime_date?->format('M d, Y'),
            'time_range_display' => $overtimeRequest->start_time?->format('h:i A') . ' - ' . $overtimeRequest->end_time?->format('h:i A'),
            'start_time' => $overtimeRequest->start_time?->format('h:i A'),
            'end_time' => $overtimeRequest->end_time?->format('h:i A'),
            'total_hours_display' => $this->formatTotalHours((float) $overtimeRequest->total_hours),
            'reason' => $overtimeRequest->reason,
            'status' => $overtimeRequest->status,
            'status_display' => $overtimeRequest->status_label,
            'rejection_reason' => $overtimeRequest->rejection_reason,
            'submitted_at' => $overtimeRequest->created_at?->format('F d, Y h:i A'),
            'approved_at' => $overtimeRequest->approved_at?->format('F d, Y h:i A'),
            'rejected_at' => $overtimeRequest->rejected_at?->format('F d, Y h:i A'),
            'processed_by' => $overtimeRequest->approver->full_name
                ?? $overtimeRequest->rejector->full_name
                ?? 'Not processed yet.',
            'can_approve' => $overtimeRequest->status === 'pending',
            'can_reject' => $overtimeRequest->status === 'pending',
        ];
    }

    /**
     * Format the total hours display.
     *
     * @param  float  $totalHours
     * @return string
     */
    private function formatTotalHours(float $totalHours): string
    {
        $normalizedHours = rtrim(rtrim(number_format($totalHours, 2, '.', ''), '0'), '.');
        $label = (float) $totalHours === 1.0 ? 'hour' : 'hours';

        return $normalizedHours . ' ' . $label;
    }
}
