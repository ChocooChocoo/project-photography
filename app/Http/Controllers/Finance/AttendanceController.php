<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\CheckInRequest;
use App\Http\Requests\Finance\CheckOutRequest;
use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    /**
     * Grace period for check-in in minutes.
     */
    protected const GRACE_PERIOD_MINUTES = 15;

    /**
     * Display the finance attendance page.
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today('Asia/Manila');
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();

        $attendanceBaseQuery = EmployeeAttendanceModel::query()->where('user_id', $user->id);
        $myAttendance = $this->getFinanceAttendanceHistory($user->id);
        $scheduleInfo = $this->buildScheduleInfo($user->id);
        $attendanceStats = [
            'today' => [
                'checked_in' => (clone $attendanceBaseQuery)
                    ->whereDate('attendance_date', $today->toDateString())
                    ->whereNotNull('check_in_time')
                    ->count(),
                'checked_out' => (clone $attendanceBaseQuery)
                    ->whereDate('attendance_date', $today->toDateString())
                    ->whereNotNull('check_out_time')
                    ->count(),
            ],
            'month' => [
                'total' => (clone $attendanceBaseQuery)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->count(),
                'late' => (clone $attendanceBaseQuery)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->where('check_in_status', 'LATE')
                    ->count(),
                'undertime' => (clone $attendanceBaseQuery)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->where('check_out_status', 'UNDERTIME')
                    ->count(),
                'completed' => (clone $attendanceBaseQuery)
                    ->whereBetween('attendance_date', [$monthStart, $monthEnd])
                    ->whereNotNull('check_out_time')
                    ->count(),
            ],
        ];

        return view('studio-finance.view-attendance', compact(
            'myAttendance',
            'scheduleInfo',
            'attendanceStats'
        ));
    }

    /**
     * Return the current server time.
     */
    public function getCurrentTime()
    {
        return response()->json([
            'status' => 'success',
            'success' => true,
            'time' => now('Asia/Manila')->format('h:i:s A'),
            'date' => now('Asia/Manila')->format('l, F d, Y'),
            'timestamp' => now('Asia/Manila')->toDateTimeString(),
        ]);
    }

    /**
     * Get the finance schedule and today's attendance state.
     */
    public function getFinanceSchedule()
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila');
            $todayDate = $now->toDateString();
            $schedule = $this->getTodaySchedule($user->id);
            $studio = $this->getAssignedStudio($user->id);
            $approvedLeave = $this->getApprovedLeaveForDate($user->id, $todayDate);
            $approvedOvertime = $approvedLeave ? null : $this->getApprovedOvertimeForDate($user->id, $todayDate, $studio?->id);
            $todayAttendance = EmployeeAttendanceModel::query()
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', $todayDate)
                ->first();
            $scheduledEnd = $schedule && !empty($schedule['end_time'])
                ? Carbon::parse($todayDate . ' ' . $schedule['end_time'], 'Asia/Manila')
                : null;

            return response()->json([
                'status' => 'success',
                'success' => true,
                'has_schedule' => !is_null($schedule),
                'schedule' => $schedule,
                'studio_name' => $studio?->studio_name,
                'is_checked_in' => $todayAttendance && $todayAttendance->isCheckedIn(),
                'is_checked_out' => $todayAttendance && $todayAttendance->isCheckedOut(),
                'today_attendance_id' => $todayAttendance?->id,
                'today_attendance' => $todayAttendance,
                'blocked_by_leave' => !is_null($approvedLeave),
                'attendance_blocked' => !is_null($approvedLeave),
                'blocked_message' => $approvedLeave
                    ? 'Attendance is unavailable today because you have an approved leave request.'
                    : null,
                'leave_summary' => $approvedLeave ? [
                    'request_reference' => $approvedLeave->request_reference,
                    'leave_type' => $approvedLeave->leave_type_label,
                    'start_date' => $approvedLeave->start_date?->format('F d, Y'),
                    'end_date' => $approvedLeave->end_date?->format('F d, Y'),
                ] : null,
                'has_approved_overtime' => !is_null($approvedOvertime),
                'overtime_summary' => $this->buildOvertimeSummary($approvedOvertime, $scheduledEnd, $todayDate),
                'current_time' => $now->format('h:i:s A'),
                'current_date' => $now->format('l, F d, Y'),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to get finance attendance schedule.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Failed to load your attendance schedule.',
            ], 500);
        }
    }

    /**
     * Store the finance check-in record.
     */
    public function checkIn(CheckInRequest $request)
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila');
            $today = $now->toDateString();
            $approvedLeave = $this->getApprovedLeaveForDate($user->id, $today);

            if ($approvedLeave) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Attendance is unavailable today because you have an approved leave request.',
                    'errors' => [
                        'attendance' => ['You cannot check in on an approved leave date.'],
                    ],
                ], 422);
            }

            $existingAttendance = EmployeeAttendanceModel::query()
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->first();

            if ($existingAttendance && $existingAttendance->isCheckedIn()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You have already checked in today.',
                ], 422);
            }

            $studio = $this->getAssignedStudio($user->id);
            if (!$studio) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'No studio associated with your account.',
                ], 422);
            }

            $schedule = $this->getTodaySchedule($user->id);
            $imagePath = $this->storeAttendanceImage($request->file('image'), 'check-in');
            $checkInStatus = 'ON_TIME';
            $lateMinutes = 0;

            if ($schedule && !empty($schedule['start_time'])) {
                $scheduledStart = Carbon::parse($today . ' ' . $schedule['start_time'], 'Asia/Manila');
                $gracePeriodEnd = $scheduledStart->copy()->addMinutes(self::GRACE_PERIOD_MINUTES);

                if ($now->gt($gracePeriodEnd)) {
                    $checkInStatus = 'LATE';
                    $lateMinutes = (int) $scheduledStart->diffInMinutes($now);
                }
            }

            $attendance = EmployeeAttendanceModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $today,
                ],
                [
                    'studio_id' => $studio->id,
                    'schedule_id' => $schedule['schedule_id'] ?? null,
                    'scheduled_start_time' => $schedule['start_time'] ?? null,
                    'scheduled_end_time' => $schedule['end_time'] ?? null,
                    'check_in_time' => $now,
                    'check_in_image' => $imagePath,
                    'check_in_status' => $checkInStatus,
                    'late_minutes' => $lateMinutes,
                    'check_in_ip' => $request->ip(),
                    'check_in_user_agent' => $request->userAgent(),
                    'notes' => $request->input('notes'),
                ]
            );

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => $checkInStatus === 'LATE'
                    ? 'Check-in successful. You are ' . $lateMinutes . ' minute(s) late.'
                    : 'Check-in successful.',
                'attendance' => $attendance,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Finance check-in failed.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Failed to process your check-in.',
            ], 500);
        }
    }

    /**
     * Store the finance check-out record.
     */
    public function checkOut(CheckOutRequest $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::now('Asia/Manila')->toDateString();
            $approvedLeave = $this->getApprovedLeaveForDate($user->id, $today);

            if ($approvedLeave) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Attendance is unavailable today because you have an approved leave request.',
                    'errors' => [
                        'attendance' => ['You cannot check out on an approved leave date.'],
                    ],
                ], 422);
            }

            $attendance = EmployeeAttendanceModel::query()
                ->where('id', $request->input('attendance_id'))
                ->where('user_id', $user->id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'Attendance record not found.',
                ], 404);
            }

            if (!$attendance->isCheckedIn()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You must check in first before checking out.',
                ], 422);
            }

            if ($attendance->isCheckedOut()) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You have already checked out today.',
                ], 422);
            }

            $checkOutTime = Carbon::now('Asia/Manila');
            $checkOutStatus = 'ON_TIME';
            $undertimeMinutes = 0;
            $imagePath = $attendance->check_out_image;
            $approvedOvertime = $this->getApprovedOvertimeForDate($user->id, $today, $attendance->studio_id);

            if ($request->hasFile('image')) {
                $imagePath = $this->storeAttendanceImage($request->file('image'), 'check-out');
            }

            if ($attendance->scheduled_end_time) {
                $scheduledEnd = Carbon::parse(
                    $attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->scheduled_end_time,
                    'Asia/Manila'
                );

                if ($checkOutTime->lt($scheduledEnd)) {
                    $checkOutStatus = 'UNDERTIME';
                    $undertimeMinutes = $checkOutTime->diffInMinutes($scheduledEnd);
                } elseif ($approvedOvertime && $checkOutTime->gt($scheduledEnd)) {
                    $checkOutStatus = 'ON_TIME';
                    $undertimeMinutes = 0;
                }
            }

            $attendance->update([
                'check_out_time' => $checkOutTime,
                'check_out_image' => $imagePath,
                'check_out_status' => $checkOutStatus,
                'undertime_minutes' => $undertimeMinutes,
                'check_out_ip' => $request->ip(),
                'check_out_user_agent' => $request->userAgent(),
                'notes' => $request->input('notes', $attendance->notes),
            ]);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'message' => $checkOutStatus === 'UNDERTIME'
                    ? 'Check-out successful. You left ' . $undertimeMinutes . ' minute(s) early.'
                    : 'Check-out successful.',
                'attendance' => $this->buildAttendanceDetailPayload($attendance->fresh('studio')),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Finance check-out failed.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Failed to process your check-out.',
            ], 500);
        }
    }

    /**
     * Get attendance details for the authenticated finance user.
     */
    public function getAttendanceDetails(int $id)
    {
        $attendance = EmployeeAttendanceModel::with('studio')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$attendance) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Attendance record not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'success' => true,
            'attendance' => $this->buildAttendanceDetailPayload($attendance),
        ]);
    }

    /**
     * Build finance schedule info.
     */
    private function buildScheduleInfo(int $userId): ?array
    {
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$schedule) {
            return null;
        }

        return [
            'studio_name' => $schedule->studio->studio_name ?? 'Assigned Studio',
            'operating_days' => $schedule->operating_days ?? [],
            'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('h:i A') : 'Not set',
            'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('h:i A') : 'Not set',
        ];
    }

    /**
     * Get the assigned studio of the finance user.
     */
    private function getAssignedStudio(int $userId): ?StudiosModel
    {
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        return $schedule ? StudiosModel::find($schedule->studio_id) : null;
    }

    /**
     * Get today's active schedule for the finance user.
     */
    private function getTodaySchedule(int $userId): ?array
    {
        $today = strtolower(Carbon::now('Asia/Manila')->format('l'));
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();

        if (!$schedule || !$schedule->worksOnDay($today)) {
            return null;
        }

        $startTime = $schedule->start_time instanceof Carbon
            ? $schedule->start_time->format('H:i:s')
            : Carbon::parse($schedule->start_time)->format('H:i:s');
        $endTime = $schedule->end_time instanceof Carbon
            ? $schedule->end_time->format('H:i:s')
            : Carbon::parse($schedule->end_time)->format('H:i:s');

        return [
            'schedule_id' => $schedule->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'operating_days' => $schedule->operating_days ?? [],
        ];
    }

    /**
     * Get an approved leave for the given date.
     */
    private function getApprovedLeaveForDate(int $userId, string $date): ?LeaveRequestModel
    {
        return LeaveRequestModel::with(['studio', 'approver', 'rejector'])
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    /**
     * Get an approved overtime request for the supplied date.
     */
    private function getApprovedOvertimeForDate(int $userId, string $date, ?int $studioId = null): ?OvertimeRequestModel
    {
        $query = OvertimeRequestModel::with('studio')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->whereDate('overtime_date', $date);

        if (!is_null($studioId)) {
            $query->where('studio_id', $studioId);
        }

        return $query->orderByDesc('approved_at')->first();
    }

    /**
     * Build the overtime summary payload for attendance endpoints.
     */
    private function buildOvertimeSummary(?OvertimeRequestModel $overtimeRequest, ?Carbon $scheduledEnd, string $date): ?array
    {
        if (!$overtimeRequest) {
            return null;
        }

        $effectiveCutoff = $scheduledEnd
            ? $scheduledEnd->copy()->addMinutes((int) round((float) $overtimeRequest->total_hours * 60))
            : null;

        return [
            'request_reference' => $overtimeRequest->request_reference,
            'time_range' => $overtimeRequest->start_time?->format('h:i A') . ' - ' . $overtimeRequest->end_time?->format('h:i A'),
            'total_hours' => rtrim(rtrim(number_format((float) $overtimeRequest->total_hours, 2), '0'), '.'),
            'overtime_date' => Carbon::parse($date)->format('F d, Y'),
            'effective_checkout_cutoff' => $effectiveCutoff?->format('h:i A'),
        ];
    }

    /**
     * Apply overtime-aware presentation fields to an attendance record.
     */
    private function applyAttendancePresentation(EmployeeAttendanceModel $attendance): void
    {
        $attendance->display_duration = $attendance->duration;
        $attendance->actual_duration = $attendance->duration;
        $attendance->counted_duration = $attendance->duration;
        $attendance->display_check_out = $attendance->formatted_check_out;
        $attendance->actual_check_out = $attendance->formatted_check_out;
        $attendance->counted_check_out = $attendance->formatted_check_out;
        $attendance->is_overtime_applied = false;
        $attendance->has_approved_overtime = false;
        $attendance->overtime_summary = null;

        if (!$attendance->scheduled_end_time || !$attendance->check_out_time || !$attendance->check_in_time) {
            return;
        }

        $attendanceDate = $attendance->attendance_date?->toDateString();
        if (!$attendanceDate) {
            return;
        }

        $scheduledEnd = Carbon::parse($attendanceDate . ' ' . $attendance->scheduled_end_time, 'Asia/Manila');
        $approvedOvertime = $this->getApprovedOvertimeForDate($attendance->user_id, $attendanceDate, $attendance->studio_id);
        $attendance->has_approved_overtime = !is_null($approvedOvertime);
        $attendance->overtime_summary = $this->buildOvertimeSummary($approvedOvertime, $scheduledEnd, $attendanceDate);

        if (!$approvedOvertime || !$attendance->check_out_time->gt($scheduledEnd)) {
            return;
        }

        $effectiveCutoff = $scheduledEnd->copy()->addMinutes((int) round((float) $approvedOvertime->total_hours * 60));
        $countedCheckOut = $attendance->check_out_time->gt($effectiveCutoff)
            ? $effectiveCutoff
            : $attendance->check_out_time->copy();

        $attendance->is_overtime_applied = true;
        $attendance->counted_check_out = $countedCheckOut->format('h:i A');
        $attendance->display_check_out = $countedCheckOut->format('h:i A');
        $attendance->actual_check_out = $attendance->check_out_time->format('h:i A');
        $attendance->counted_duration = $this->formatMinutesAsDuration($attendance->check_in_time->diffInMinutes($countedCheckOut));
        $attendance->actual_duration = $this->formatMinutesAsDuration($attendance->check_in_time->diffInMinutes($attendance->check_out_time));
        $attendance->display_duration = $attendance->counted_duration;
    }

    /**
     * Build the attendance detail payload with overtime-aware fields.
     */
    private function buildAttendanceDetailPayload(EmployeeAttendanceModel $attendance): array
    {
        $this->applyAttendancePresentation($attendance);

        return [
            'id' => $attendance->id,
            'studio_name' => $attendance->studio->studio_name ?? 'N/A',
            'attendance_date' => $attendance->attendance_date?->format('F d, Y') ?? 'N/A',
            'scheduled_start_time' => $attendance->scheduled_start_time
                ? Carbon::parse($attendance->scheduled_start_time)->format('h:i A')
                : '-',
            'scheduled_end_time' => $attendance->scheduled_end_time
                ? Carbon::parse($attendance->scheduled_end_time)->format('h:i A')
                : '-',
            'formatted_check_in' => $attendance->formatted_check_in,
            'formatted_check_out' => $attendance->formatted_check_out,
            'display_check_out' => $attendance->display_check_out,
            'actual_check_out' => $attendance->actual_check_out,
            'counted_check_out' => $attendance->counted_check_out,
            'check_in_status' => $attendance->check_in_status,
            'check_out_status' => $attendance->check_out_status,
            'late_display' => $attendance->late_display,
            'undertime_display' => $attendance->undertime_display,
            'duration' => $attendance->display_duration,
            'actual_duration' => $attendance->actual_duration,
            'counted_duration' => $attendance->counted_duration,
            'has_approved_overtime' => $attendance->has_approved_overtime,
            'is_overtime_applied' => $attendance->is_overtime_applied,
            'overtime_summary' => $attendance->overtime_summary,
            'check_in_image' => $attendance->check_in_image,
            'check_out_image' => $attendance->check_out_image,
            'notes' => $attendance->notes,
        ];
    }

    /**
     * Format a minute count into HH:MM:SS.
     */
    private function formatMinutesAsDuration(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return sprintf('%02d:%02d:00', $hours, $remainingMinutes);
    }

    /**
     * Build finance attendance history with approved leave rows.
     */
    private function getFinanceAttendanceHistory(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $attendanceRecords = EmployeeAttendanceModel::with(['studio'])
            ->where('user_id', $userId)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get()
            ->map(function (EmployeeAttendanceModel $attendance) {
                $attendance->record_type = 'attendance';
                $this->applyAttendancePresentation($attendance);

                return $attendance;
            });

        $attendanceDates = $attendanceRecords
            ->pluck('attendance_date')
            ->filter()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->values()
            ->all();

        $leaveEntries = $this->buildApprovedLeaveHistoryEntries($userId, $attendanceDates);
        $mergedRecords = $attendanceRecords
            ->concat($leaveEntries)
            ->sortByDesc(fn ($record) => Carbon::parse($record->attendance_date)->toDateString() . ' ' . ($record->sort_time ?? '00:00:00'))
            ->values();

        $currentPage = (int) request()->input('page', 1);
        $items = $mergedRecords->forPage($currentPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $mergedRecords->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Build synthetic approved leave history entries.
     */
    private function buildApprovedLeaveHistoryEntries(int $userId, array $attendanceDates): Collection
    {
        $approvedLeaves = LeaveRequestModel::with('studio')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->orderByDesc('start_date')
            ->get();

        $leaveEntries = collect();

        foreach ($approvedLeaves as $leaveRequest) {
            $period = Carbon::parse($leaveRequest->start_date)->daysUntil(Carbon::parse($leaveRequest->end_date)->addDay());

            foreach ($period as $leaveDate) {
                $leaveDateString = $leaveDate->toDateString();

                if (in_array($leaveDateString, $attendanceDates, true)) {
                    continue;
                }

                $leaveEntries->push((object) [
                    'id' => null,
                    'record_type' => 'leave',
                    'attendance_date' => Carbon::parse($leaveDateString),
                    'studio' => $leaveRequest->studio,
                    'scheduled_start_time' => null,
                    'scheduled_end_time' => null,
                    'formatted_check_in' => 'On Leave',
                    'formatted_check_out' => 'On Leave',
                    'check_in_status' => 'ON_LEAVE',
                    'check_out_status' => null,
                    'late_minutes' => 0,
                    'undertime_minutes' => 0,
                    'late_display' => '—',
                    'undertime_display' => '—',
                    'duration' => 'Approved Leave',
                    'leave_type_label' => $leaveRequest->leave_type_label,
                    'notes' => $leaveRequest->reason,
                    'sort_time' => '23:59:59',
                ]);
            }
        }

        return $leaveEntries;
    }

    /**
     * Store attendance image.
     */
    private function storeAttendanceImage($image, string $type): string
    {
        return $image->store('employee-attendance/' . $type . '/' . now()->format('Y/m/d'), 'public');
    }
}
