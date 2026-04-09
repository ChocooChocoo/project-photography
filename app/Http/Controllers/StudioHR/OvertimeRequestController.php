<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\ProcessOvertimeRequest;
use App\Http\Requests\StudioHR\StoreOvertimeRequest;
use App\Http\Requests\StudioHR\UpdateOvertimeRequest;
use App\Models\OvertimeRequestModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handle HR overtime request workflows.
 */
class OvertimeRequestController extends Controller
{
    /**
     * Display the HR overtime request form page.
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

        return view('studio-hr.request-overtime', compact(
            'hrUser',
            'assignedStudio'
        ));
    }

    /**
     * Display the authenticated HR user's overtime requests.
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

        $overtimeRequests = OvertimeRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $hrUser->id)
            ->where('studio_id', $assignedStudio->id)
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $overtimeRequestSummary = [
            'pending' => $overtimeRequests->where('status', 'pending')->count(),
            'approved' => $overtimeRequests->where('status', 'approved')->count(),
            'rejected' => $overtimeRequests->where('status', 'rejected')->count(),
            'cancelled' => $overtimeRequests->where('status', 'cancelled')->count(),
        ];

        return view('studio-hr.view-requested-overtime', compact(
            'assignedStudio',
            'overtimeRequests',
            'overtimeRequestSummary'
        ));
    }

    /**
     * Store a new HR overtime request.
     *
     * @param  \App\Http\Requests\StudioHR\StoreOvertimeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOvertimeRequest $request): JsonResponse
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
            $totalHours = $this->calculateTotalHours($validated['start_time'], $validated['end_time']);

            $overtimeRequest = OvertimeRequestModel::create([
                'request_reference' => $this->generateRequestReference(),
                'studio_id' => $assignedStudio->id,
                'user_id' => $hrUser->id,
                'overtime_date' => $validated['overtime_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'total_hours' => $totalHours,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request submitted successfully. Your request is now pending Studio Owner approval.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'request_reference' => $overtimeRequest->request_reference,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to store HR overtime request.', [
                'exception' => $exception,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display the selected HR-owned overtime request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $hrUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request details loaded successfully.',
                'data' => $this->buildOwnOvertimeRequestPayload($overtimeRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load HR overtime request details.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load overtime request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Update a pending HR-owned overtime request.
     *
     * @param  \App\Http\Requests\StudioHR\UpdateOvertimeRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateOvertimeRequest $request, string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $hrUser->id);

            if ($overtimeRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending overtime requests can be updated.',
                    'errors' => [],
                ], 422);
            }

            $validated = $request->validated();
            $totalHours = $this->calculateTotalHours($validated['start_time'], $validated['end_time']);

            $overtimeRequest->update([
                'overtime_date' => $validated['overtime_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'total_hours' => $totalHours,
                'reason' => $validated['reason'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request updated successfully.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'request_reference' => $overtimeRequest->request_reference,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to update HR overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Cancel a pending HR-owned overtime request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $hrUser->id);

            if ($overtimeRequest->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending overtime requests can be cancelled.',
                    'errors' => [],
                ], 422);
            }

            $overtimeRequest->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request cancelled successfully.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to cancel HR overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Delete an HR-owned overtime request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $hrUser->id);

            if (!in_array($overtimeRequest->status, ['pending', 'cancelled', 'rejected'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Approved overtime requests cannot be deleted.',
                    'errors' => [],
                ], 422);
            }

            $overtimeRequest->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request deleted successfully.',
                'data' => [
                    'id' => (int) $id,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to delete HR overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display all employee overtime requests for the assigned HR studio.
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

        $employeeOvertimeRequests = OvertimeRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'studio-hr');
            })
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'rejected' THEN 1 WHEN status = 'cancelled' THEN 2 ELSE 3 END")
            ->orderByDesc('created_at')
            ->get();

        $overtimeRequestSummary = [
            'pending' => $employeeOvertimeRequests->where('status', 'pending')->count(),
            'approved' => $employeeOvertimeRequests->where('status', 'approved')->count(),
            'rejected' => $employeeOvertimeRequests->where('status', 'rejected')->count(),
            'cancelled' => $employeeOvertimeRequests->where('status', 'cancelled')->count(),
        ];

        return view('studio-hr.employees-overtime-requests', compact(
            'employeeOvertimeRequests',
            'overtimeRequestSummary'
        ));
    }

    /**
     * Display the selected employee overtime request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function employeeShow(string $id): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getEmployeeManagedOvertimeRequest($id, $hrUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Employee overtime request details loaded successfully.',
                'data' => $this->buildEmployeeOvertimeRequestPayload($overtimeRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load employee overtime request details.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load employee overtime request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Process an employee overtime request approval or rejection.
     *
     * @param  \App\Http\Requests\StudioHR\ProcessOvertimeRequest  $request
     * @param  string  $id
     * @param  string  $action
     * @return \Illuminate\Http\JsonResponse
     */
    public function process(ProcessOvertimeRequest $request, string $id, string $action): JsonResponse
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();
            $overtimeRequest = $this->getEmployeeManagedOvertimeRequest($id, $hrUser->id);
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
                    'approved_by' => $hrUser->id,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                    'cancelled_at' => null,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Overtime request approved successfully.',
                    'data' => [
                        'id' => $overtimeRequest->id,
                        'status' => $overtimeRequest->status,
                        'status_display' => $overtimeRequest->status_label,
                        'processed_by' => $hrUser->full_name,
                        'processed_at' => $overtimeRequest->approved_at?->format('F d, Y h:i A'),
                    ],
                ]);
            }

            $overtimeRequest->update([
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
                'message' => 'Overtime request rejected successfully.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                    'processed_by' => $hrUser->full_name,
                    'processed_at' => $overtimeRequest->rejected_at?->format('F d, Y h:i A'),
                    'rejection_reason' => $overtimeRequest->rejection_reason,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to process employee overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'action' => $action,
                'hr_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process the overtime request.',
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
     * Get an HR-owned overtime request.
     *
     * @param  string  $id
     * @param  int  $hrUserId
     * @return \App\Models\OvertimeRequestModel
     */
    private function getOwnedOvertimeRequest(string $id, int $hrUserId): OvertimeRequestModel
    {
        return OvertimeRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $hrUserId)
            ->findOrFail($id);
    }

    /**
     * Get a managed employee overtime request for the authenticated HR user.
     *
     * @param  string  $id
     * @param  int  $hrUserId
     * @return \App\Models\OvertimeRequestModel
     */
    private function getEmployeeManagedOvertimeRequest(string $id, int $hrUserId): OvertimeRequestModel
    {
        $assignedStudioIds = $this->getAssignedStudioIds($hrUserId);

        return OvertimeRequestModel::with(['user', 'studio', 'approver', 'rejector'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->whereHas('user', function ($query) {
                $query->where('role', '!=', 'studio-hr');
            })
            ->findOrFail($id);
    }

    /**
     * Build the response payload for an HR-owned overtime request.
     *
     * @param  \App\Models\OvertimeRequestModel  $overtimeRequest
     * @return array<string, mixed>
     */
    private function buildOwnOvertimeRequestPayload(OvertimeRequestModel $overtimeRequest): array
    {
        return [
            'id' => $overtimeRequest->id,
            'request_reference' => $overtimeRequest->request_reference,
            'studio_name' => $overtimeRequest->studio->studio_name ?? 'N/A',
            'overtime_date' => $overtimeRequest->overtime_date?->format('Y-m-d'),
            'overtime_date_display' => $overtimeRequest->overtime_date?->format('M d, Y'),
            'start_time' => $overtimeRequest->start_time?->format('H:i'),
            'end_time' => $overtimeRequest->end_time?->format('H:i'),
            'time_range_display' => $overtimeRequest->start_time?->format('h:i A') . ' - ' . $overtimeRequest->end_time?->format('h:i A'),
            'total_hours' => (float) $overtimeRequest->total_hours,
            'total_hours_display' => $this->formatTotalHours((float) $overtimeRequest->total_hours),
            'reason' => $overtimeRequest->reason,
            'status' => $overtimeRequest->status,
            'status_display' => $overtimeRequest->status_label,
            'rejection_reason' => $overtimeRequest->rejection_reason,
            'approved_at' => $overtimeRequest->approved_at?->format('F d, Y h:i A'),
            'rejected_at' => $overtimeRequest->rejected_at?->format('F d, Y h:i A'),
            'cancelled_at' => $overtimeRequest->cancelled_at?->format('F d, Y h:i A'),
            'submitted_at' => $overtimeRequest->created_at?->format('F d, Y h:i A'),
            'can_edit' => $overtimeRequest->status === 'pending',
            'can_cancel' => $overtimeRequest->status === 'pending',
            'can_delete' => in_array($overtimeRequest->status, ['pending', 'cancelled', 'rejected'], true),
        ];
    }

    /**
     * Build the response payload for an employee overtime request.
     *
     * @param  \App\Models\OvertimeRequestModel  $overtimeRequest
     * @return array<string, mixed>
     */
    private function buildEmployeeOvertimeRequestPayload(OvertimeRequestModel $overtimeRequest): array
    {
        return [
            'id' => $overtimeRequest->id,
            'request_reference' => $overtimeRequest->request_reference,
            'employee_name' => $overtimeRequest->user->full_name ?? 'N/A',
            'employee_email' => $overtimeRequest->user->email ?? 'N/A',
            'employee_role' => $this->getRoleDisplay((string) ($overtimeRequest->user->role ?? '')),
            'employee_photo' => $overtimeRequest->user->profile_photo_url ?? asset('assets/images/users/user-3.jpg'),
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
     * Calculate total overtime hours.
     *
     * @param  string  $startTime
     * @param  string  $endTime
     * @return float
     */
    private function calculateTotalHours(string $startTime, string $endTime): float
    {
        $start = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        return round($start->diffInMinutes($end) / 60, 2);
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

    /**
     * Generate a unique overtime request reference.
     *
     * @return string
     */
    private function generateRequestReference(): string
    {
        do {
            $reference = 'OT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(5));
        } while (OvertimeRequestModel::where('request_reference', $reference)->exists());

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
        return RoleModel::getFriendlyRoleName($role);
    }
}
