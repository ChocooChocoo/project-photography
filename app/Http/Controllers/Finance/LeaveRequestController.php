<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreLeaveRequest;
use App\Http\Requests\Finance\UpdateLeaveRequest;
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
 * Handle finance leave request workflows.
 */
class LeaveRequestController extends Controller
{
    /**
     * Display the leave request form page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(): View|RedirectResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $assignedStudio = $this->getAssignedStudio($financeUser->id);

        if (!$assignedStudio) {
            return redirect()->route('studio-finance.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $leaveTypes = LeaveRequestModel::getAvailableLeaveTypes();

        return view('studio-finance.request-leave', compact(
            'financeUser',
            'assignedStudio',
            'leaveTypes'
        ));
    }

    /**
     * Display the authenticated finance user's requested leaves.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(): View|RedirectResponse
    {
        $financeUser = $this->getAuthenticatedFinanceUser();
        $assignedStudio = $this->getAssignedStudio($financeUser->id);

        if (!$assignedStudio) {
            return redirect()->route('studio-finance.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $leaveRequests = LeaveRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $financeUser->id)
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

        return view('studio-finance.view-requested-leave', compact(
            'assignedStudio',
            'leaveRequests',
            'leaveRequestSummary',
            'leaveTypes'
        ));
    }

    /**
     * Store a new leave request for the authenticated finance user.
     *
     * @param  \App\Http\Requests\Finance\StoreLeaveRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreLeaveRequest $request): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $assignedStudio = $this->getAssignedStudio($financeUser->id);

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
                'user_id' => $financeUser->id,
                'leave_type' => $validated['leave_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request submitted successfully. Your request is now pending HR approval.',
                'data' => [
                    'id' => $leaveRequest->id,
                    'request_reference' => $leaveRequest->request_reference,
                    'status' => $leaveRequest->status,
                    'status_display' => $leaveRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to store finance leave request.', [
                'exception' => $exception,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display the selected leave request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $financeUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request details loaded successfully.',
                'data' => [
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
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load finance leave request details.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load leave request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Update a pending leave request owned by the authenticated finance user.
     *
     * @param  \App\Http\Requests\Finance\UpdateLeaveRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateLeaveRequest $request, string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $financeUser->id);

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
            Log::error('Failed to update finance leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Cancel a pending leave request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $financeUser->id);

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
            Log::error('Failed to cancel finance leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Delete a leave request owned by the authenticated finance user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $leaveRequest = $this->getOwnedLeaveRequest($id, $financeUser->id);

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
            Log::error('Failed to delete finance leave request.', [
                'exception' => $exception,
                'leave_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete the leave request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Get the authenticated finance user.
     *
     * @return \App\Models\UserModel
     */
    private function getAuthenticatedFinanceUser(): UserModel
    {
        return UserModel::findOrFail(auth()->id());
    }

    /**
     * Get the assigned studio of the finance user.
     *
     * @param  int  $financeUserId
     * @return \App\Models\StudioOwner\StudiosModel|null
     */
    private function getAssignedStudio(int $financeUserId): ?StudiosModel
    {
        $studioIds = EmployeeScheduleModel::where('user_id', $financeUserId)
            ->pluck('studio_id')
            ->unique()
            ->values();

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $financeUserId)
                ->pluck('id')
                ->unique()
                ->values();
        }

        if ($studioIds->isEmpty()) {
            return null;
        }

        return StudiosModel::whereIn('id', $studioIds)
            ->orderBy('id')
            ->first();
    }

    /**
     * Get a leave request owned by the authenticated finance user.
     *
     * @param  string  $id
     * @param  int  $financeUserId
     * @return \App\Models\LeaveRequestModel
     */
    private function getOwnedLeaveRequest(string $id, int $financeUserId): LeaveRequestModel
    {
        return LeaveRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $financeUserId)
            ->findOrFail($id);
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
}
