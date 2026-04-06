<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreOvertimeRequest;
use App\Http\Requests\Finance\UpdateOvertimeRequest;
use App\Models\OvertimeRequestModel;
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
 * Handle finance overtime request workflows.
 */
class OvertimeRequestController extends Controller
{
    /**
     * Display the overtime request form page.
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

        return view('studio-finance.request-overtime', compact(
            'financeUser',
            'assignedStudio'
        ));
    }

    /**
     * Display the authenticated finance user's overtime requests.
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

        $overtimeRequests = OvertimeRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $financeUser->id)
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

        return view('studio-finance.view-requested-overtime', compact(
            'assignedStudio',
            'overtimeRequests',
            'overtimeRequestSummary'
        ));
    }

    /**
     * Store a new overtime request for the authenticated finance user.
     *
     * @param  \App\Http\Requests\Finance\StoreOvertimeRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOvertimeRequest $request): JsonResponse
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
            $totalHours = $this->calculateTotalHours($validated['start_time'], $validated['end_time']);

            $overtimeRequest = OvertimeRequestModel::create([
                'request_reference' => $this->generateRequestReference(),
                'studio_id' => $assignedStudio->id,
                'user_id' => $financeUser->id,
                'overtime_date' => $validated['overtime_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'total_hours' => $totalHours,
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request submitted successfully. Your request is now pending HR approval.',
                'data' => [
                    'id' => $overtimeRequest->id,
                    'request_reference' => $overtimeRequest->request_reference,
                    'status' => $overtimeRequest->status,
                    'status_display' => $overtimeRequest->status_label,
                ],
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to store finance overtime request.', [
                'exception' => $exception,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to submit the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Display the selected overtime request details.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $financeUser->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Overtime request details loaded successfully.',
                'data' => $this->buildOwnOvertimeRequestPayload($overtimeRequest),
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to load finance overtime request details.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load overtime request details.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Update a pending overtime request owned by the authenticated finance user.
     *
     * @param  \App\Http\Requests\Finance\UpdateOvertimeRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateOvertimeRequest $request, string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $financeUser->id);

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
            Log::error('Failed to update finance overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Cancel a pending overtime request.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $financeUser->id);

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
            Log::error('Failed to cancel finance overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel the overtime request.',
                'errors' => [],
            ], 500);
        }
    }

    /**
     * Delete an overtime request owned by the authenticated finance user.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $financeUser = $this->getAuthenticatedFinanceUser();
            $overtimeRequest = $this->getOwnedOvertimeRequest($id, $financeUser->id);

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
            Log::error('Failed to delete finance overtime request.', [
                'exception' => $exception,
                'overtime_request_id' => $id,
                'finance_user_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete the overtime request.',
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
        $financeUser = UserModel::find($financeUserId);
        $studioIds = $financeUser ? $financeUser->getAssignedStudioIds('studio-finance') : collect();

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::where('user_id', $financeUserId)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $financeUserId)->pluck('id');
        }

        if ($studioIds->isEmpty()) {
            return null;
        }

        return StudiosModel::whereIn('id', $studioIds->unique()->values())
            ->orderBy('id')
            ->first();
    }

    /**
     * Get an overtime request owned by the authenticated finance user.
     *
     * @param  string  $id
     * @param  int  $financeUserId
     * @return \App\Models\OvertimeRequestModel
     */
    private function getOwnedOvertimeRequest(string $id, int $financeUserId): OvertimeRequestModel
    {
        return OvertimeRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $financeUserId)
            ->findOrFail($id);
    }

    /**
     * Build the response payload for a finance overtime request.
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
}
