<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\ProcessLeaveRequest;
use App\Http\Requests\StudioHR\StoreLeaveRequest;
use App\Http\Requests\StudioHR\UpdateLeaveRequest;
use App\Models\LeaveRequestModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handle HR leave request workflows.
 */
class LeaveRequestController extends Controller
{
    /**
     * Display the HR leave request form page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudio = $this->getPrimaryAssignedStudio($hrUser->id);

        if (!$assignedStudio) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $leaveTypes = LeaveRequestModel::getAvailableLeaveTypes();

        return view('studio-hr.request-leave', compact(
            'hrUser',
            'assignedStudio',
            'leaveTypes'
        ));
    }

    /**
     * Display the authenticated HR user's leave requests.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudio = $this->getPrimaryAssignedStudio($hrUser->id);

        if (!$assignedStudio) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $leaveRequests = LeaveRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $hrUser->id)
            ->where('studio_id', $assignedStudio->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $leaveRequestSummary = [
            'pending' => $leaveRequests->where('status', 'pending')->count(),
            'approved' => $leaveRequests->where('status', 'approved')->count(),
            'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            'cancelled' => $leaveRequests->where('status', 'cancelled')->count(),
        ];

        $leaveTypes = LeaveRequestModel::getAvailableLeaveTypes();

        return view('studio-hr.view-requested-leave', compact(
            'assignedStudio',
            'leaveRequests',
            'leaveRequestSummary',
            'leaveTypes'
        ));
    }

    /**
     * Store a new HR leave request.
     *
     * @param  \App\Http\Requests\StudioHR\StoreLeaveRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $assignedStudio = $this->getPrimaryAssignedStudio($hrUser->id);

            if (!$assignedStudio) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No studio assigned to your account.',
                    'errors' => [],
                ], 422);
            }

            $validated = $request->validated();
            $totalDays = $this->calculateTotalDays($validated['start_date'], $validated['end_date']);

            $leaveRequest = LeaveRequestModel::create([
                'request_reference' => $this->generateRequestReference(),
                'studio_id' => $assignedStudio->id,
                'user_id' => $hrUser->id,
                'leave_type' => $validated['leave_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request submitted successfully. Your request is now pending Studio Owner approval.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'request_reference' => $leaveRequest->request_reference,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to store HR leave request.', [
                'exception' => $exception,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display the selected HR-owned leave request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $hrUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request details loaded successfully.',
                'data' => $this->buildOwnLeaveRequestPayload($leaveRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load HR leave request details.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load leave request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Update a pending HR-owned leave request.
     *
     * @param  \App\Http\Requests\StudioHR\UpdateLeaveRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateLeaveRequest $request, string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $hrUser->id);

            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending leave requests can be updated.',
                    'errors' => [],
                ], 422);
            }

            $validated = $request->validated();
            $totalDays = $this->calculateTotalDays($validated['start_date'], $validated['end_date']);

            $leaveRequest->update([
                'leave_type' => $validated['leave_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request updated successfully.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'request_reference' => $leaveRequest->request_reference,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to update HR leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Cancel a pending HR-owned leave request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $hrUser->id);

            if ($leaveRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending leave requests can be cancelled.',
                    'errors' => [],
                ], 422);
            }

            $leaveRequest->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request cancelled successfully.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to cancel HR leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Delete an HR-owned leave request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $hrUser->id);

            if (!in_array($leaveRequest->status, ['pending', 'cancelled', 'rejected'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Approved leave requests cannot be deleted.',
                    'errors' => [],
                ], 422);
            }

            $leaveRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request deleted successfully.',
                'data' => [
                    'id' => (int) $id,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to delete HR leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display all employee leave requests for the assigned HR studio.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function employeesIndex(): View|RedirectResponse
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);

        if ($assignedStudioIds->isEmpty()) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $employeeLeaveRequests = LeaveRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'studio-hr');
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $leaveRequestSummary = [
            'pending' => $employeeLeaveRequests->where('status', 'pending')->count(),
            'approved' => $employeeLeaveRequests->where('status', 'approved')->count(),
            'rejected' => $employeeLeaveRequests->where('status', 'rejected')->count(),
            'cancelled' => $employeeLeaveRequests->where('status', 'cancelled')->count(),
        ];

        return view('studio-hr.employees-leave-requests', compact(
            'employeeLeaveRequests',
            'leaveRequestSummary'
        ));
    }

    /**
     * Display the selected employee leave request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function employeeShow(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getEmployeeManagedLeaveRequest($id, $hrUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Employee leave request details loaded successfully.',
                'data' => $this->buildEmployeeLeaveRequestPayload($leaveRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load employee leave request details.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load employee leave request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Process an employee leave request approval or rejection.
     *
     * @param  \App\Http\Requests\StudioHR\ProcessLeaveRequest  $request
     * @param  string  $id
     * @param  string  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(ProcessLeaveRequest $request, string $id, string $action): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $leaveRequest = $this->getEmployeeManagedLeaveRequest($id, $hrUser->id);
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
                    'approved_by' => $hrUser->id,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'cancelled_at' => null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Leave request approved successfully.',
                    'data' => [
                        'id' => $leaveRequest->id,
                        'status' => $leaveRequest->status,
                        'status_display' => $leaveRequest->status_label,
                        'processed_by' => $hrUser->full_name,
                        'processed_at' => $leaveRequest->approved_at?->format('F d, Y h:i A'),
                    ],
                ]);
            }

            $leaveRequest->update([
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => $hrUser->id,
                'rejected_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
                'cancelled_at' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request rejected successfully.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                    'processed_by' => $hrUser->full_name,
                    'processed_at' => $leaveRequest->rejected_at?->format('F d, Y h:i A'),
                    'rejection_reason' => $leaveRequest->rejection_reason,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to process employee leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'action' => $action,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Get the authenticated HR user.
     *
     * @return \App\Models\UserModel
     */
    private function getAuthenticatedHrUser(): UserModel
    {
        return UserModel::findOrFail(auth()->id());
    }

    /**
     * Get the primary assigned studio of the HR user.
     *
     * @param  int  $hrUserId
     * @return \App\Models\StudioOwner\StudiosModel|null
     */
    private function getPrimaryAssignedStudio(int $hrUserId): ?StudiosModel
    {
        $assignedStudioIds = $this->getAssignedStudioIds($hrUserId);

        if ($assignedStudioIds->isEmpty()) {
            return null;
        }

        return StudiosModel::whereIn('id', $assignedStudioIds)
            ->orderBy('id')
            ->first();
    }

    /**
     * Get the assigned studio IDs of the HR user.
     *
     * @param  int  $hrUserId
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getAssignedStudioIds(int $hrUserId)
    {
        $studioIds = EmployeeScheduleModel::where('user_id', $hrUserId)
            ->pluck('studio_id');

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $hrUserId)->pluck('id');
        }

        return $studioIds->unique()->values();
    }

    /**
     * Get an HR-owned leave request.
     *
     * @param  string  $id
     * @param  int  $hrUserId
     * @return \App\Models\LeaveRequestModel
     */
    private function getOwnedLeaveRequest(string $id, int $hrUserId): LeaveRequestModel
    {
        return LeaveRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $hrUserId)
            ->findOrFail($id);
    }

    /**
     * Get a managed employee leave request for the authenticated HR user.
     *
     * @param  string  $id
     * @param  int  $hrUserId
     * @return \App\Models\LeaveRequestModel
     */
    private function getEmployeeManagedLeaveRequest(string $id, int $hrUserId): LeaveRequestModel
    {
        $assignedStudioIds = $this->getAssignedStudioIds($hrUserId);

        return LeaveRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'studio-hr');
            })
            ->findOrFail($id);
    }

    /**
     * Build the response payload for an HR-owned leave request.
     *
     * @param  \App\Models\LeaveRequestModel  $leaveRequest
     * @return array<string, mixed>
     */
    private function buildOwnLeaveRequestPayload(LeaveRequestModel $leaveRequest): array
    {
        return [
            'id' => $leaveRequest->id,
            'request_reference' => $leaveRequest->request_reference,
            'studio_name' => $leaveRequest->studio->studio_name ?? 'N/A',
            'leave_type' => $leaveRequest->leave_type,
            'leave_type_display' => $leaveRequest->leave_type_label,
            'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
            'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
            'period_display' => $leaveRequest->start_date?->format('M d, Y') . ' - ' . $leaveRequest->end_date?->format('M d, Y'),
            'total_days' => (float) $leaveRequest->total_days,
            'total_days_display' => $this->formatTotalDays((float) $leaveRequest->total_days),
            'reason' => $leaveRequest->reason,
            'status' => $leaveRequest->status,
            'status_display' => $leaveRequest->status_label,
            'rejection_reason' => $leaveRequest->rejection_reason,
            'approved_at' => $leaveRequest->approved_at?->format('F d, Y h:i A'),
            'rejected_at' => $leaveRequest->rejected_at?->format('F d, Y h:i A'),
            'cancelled_at' => $leaveRequest->cancelled_at?->format('F d, Y h:i A'),
            'submitted_at' => $leaveRequest->created_at?->format('F d, Y h:i A'),
            'can_edit' => $leaveRequest->status === 'pending',
            'can_cancel' => $leaveRequest->status === 'pending',
            'can_delete' => in_array($leaveRequest->status, ['pending', 'cancelled', 'rejected'], true),
        ];
    }

    /**
     * Build the response payload for an employee leave request.
     *
     * @param  \App\Models\LeaveRequestModel  $leaveRequest
     * @return array<string, mixed>
     */
    private function buildEmployeeLeaveRequestPayload(LeaveRequestModel $leaveRequest): array
    {
        return [
            'id' => $leaveRequest->id,
            'request_reference' => $leaveRequest->request_reference,
            'employee_name' => $leaveRequest->user->full_name ?? 'N/A',
            'employee_email' => $leaveRequest->user->email ?? 'N/A',
            'employee_role' => $this->getRoleDisplay((string) ($leaveRequest->user->role ?? '')),
            'employee_photo' => $leaveRequest->user->profile_photo_url ?? asset('assets/images/users/user-3.jpg'),
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
     * Calculate the total leave days inclusively.
     *
     * @param  string  $startDate
     * @param  string  $endDate
     * @return float
     */
    private function calculateTotalDays(string $startDate, string $endDate): float
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        return (float) $start->diffInDays($end) + 1;
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

    /**
     * Generate a unique leave request reference.
     *
     * @return string
     */
    private function generateRequestReference(): string
    {
        do {
            $reference = 'LR-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5));
        } while (LeaveRequestModel::where('request_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the display label of a role.
     *
     * @param  string  $role
     * @return string
     */
    private function getRoleDisplay(string $role): string
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
            'studio-staff' => 'Studio Staff',
        ];

        return $roles[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }
}
