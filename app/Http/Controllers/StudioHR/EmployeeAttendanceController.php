<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\CheckInRequest;
use App\Http\Requests\StudioHR\CheckOutRequest;
use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EmployeeAttendanceController extends Controller
{
    /**
     * Grace period for check-in in minutes.
     */
    protected const GRACE_PERIOD_MINUTES = 15;

    /**
     * Display the attendance page with leave-aware personal history.
     */
    public function index()
    {
        $user = Auth::user();
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        $scheduleInfo = null;
        if ($schedule) {
            $scheduleInfo = [
                'operating_days' => $schedule->operating_days ?? [],
                'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('h:i A') : 'Not set',
                'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('h:i A') : 'Not set',
            ];
        }

        $myAttendance = $this->buildSelfAttendanceHistory($user->id);

        return view('studio-hr.view-attendance', compact('myAttendance', 'scheduleInfo'));
    }

    /**
     * Return the current server time for the attendance page.
     */
    public function getCurrentTime()
    {
        return response()->json([
            'success' => true,
            'time' => now('Asia/Manila')->format('h:i:s A'),
            'date' => now('Asia/Manila')->format('l, F d, Y'),
            'timestamp' => now('Asia/Manila')->toDateTimeString(),
        ]);
    }

    /**
     * Get the authenticated employee schedule and leave-block state.
     */
    public function getEmployeeSchedule()
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila');
            $todayDate = $now->toDateString();
            $schedule = $this->getTodaySchedule($user);
            $todayAttendance = EmployeeAttendanceModel::query()
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', $todayDate)
                ->first();
            $approvedLeave = $this->getApprovedLeaveForDate($user->id, $todayDate);
            $studio = $this->getUserStudio($user);
            $approvedOvertime = $approvedLeave ? null : $this->getApprovedOvertimeForDate($user->id, $todayDate, $studio?->id);
            $scheduledEnd = $schedule && !empty($schedule['end_time'])
                ? Carbon::parse($todayDate . ' ' . $schedule['end_time'], 'Asia/Manila')
                : null;

            return response()->json([
                'success' => true,
                'has_schedule' => !is_null($schedule),
                'schedule' => $schedule,
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
            Log::error('Failed to get HR attendance schedule.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load your attendance schedule.',
            ], 500);
        }
    }

    /**
     * Store a new check-in record unless the day is blocked by approved leave.
     */
    public function checkIn(CheckInRequest $request)
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila');
            $today = $now->toDateString();

            if ($this->getApprovedLeaveForDate($user->id, $today)) {
                return response()->json([
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
                    'success' => false,
                    'message' => 'You have already checked in today.',
                ], 422);
            }

            $studio = $this->getUserStudio($user);
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated with your account.',
                ], 422);
            }

            $schedule = $this->getTodaySchedule($user);
            $imagePath = $this->storeAttendanceImage($request->file('image'), 'check-in');
            $checkInStatus = 'ON_TIME';
            $lateMinutes = 0;

            if (!empty($schedule['start_time'])) {
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
                'success' => true,
                'message' => $checkInStatus === 'LATE'
                    ? 'Check-in successful! You are ' . $lateMinutes . ' minute(s) late.'
                    : 'Check-in successful!',
                'attendance' => $attendance,
                'status' => $checkInStatus,
                'late_minutes' => $lateMinutes,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('HR attendance check-in failed.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process check-in.',
            ], 500);
        }
    }

    /**
     * Store a new check-out record unless the day is blocked by approved leave.
     */
    public function checkOut(CheckOutRequest $request)
    {
        try {
            $user = Auth::user();
            $today = Carbon::now('Asia/Manila')->toDateString();

            if ($this->getApprovedLeaveForDate($user->id, $today)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance is unavailable today because you have an approved leave request.',
                    'errors' => [
                        'attendance' => ['You cannot check out on an approved leave date.'],
                    ],
                ], 422);
            }

            $attendance = EmployeeAttendanceModel::query()
                ->where('id', $request->attendance_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found.',
                ], 404);
            }

            if (!$attendance->isCheckedIn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must check in first before checking out.',
                ], 422);
            }

            if ($attendance->isCheckedOut()) {
                return response()->json([
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
                'success' => true,
                'message' => $checkOutStatus === 'UNDERTIME'
                    ? 'Check-out successful! You left ' . $undertimeMinutes . ' minute(s) early.'
                    : 'Check-out successful! Have a great day!',
                'attendance' => $this->buildAttendanceDetailPayload($attendance->fresh(['employee', 'schedule'])),
                'status' => $checkOutStatus,
                'undertime_minutes' => $undertimeMinutes,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('HR attendance check-out failed.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process check-out.',
            ], 500);
        }
    }

    /**
     * Get the studio's attendance rows for today, including synthetic leave rows.
     */
    public function getTodaysAttendance()
    {
        $request = request();
        $request->merge(['filter_date' => 'today']);

        return $this->getAttendanceHistory($request);
    }

    /**
     * Get attendance history with approved leave rows merged in.
     */
    public function getAttendanceHistory(Request $request)
    {
        try {
            $studio = $this->getUserStudio(Auth::user());

            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated.',
                ]);
            }

            $records = $this->buildStudioAttendanceRows($studio->id, $request);

            return response()->json([
                'success' => true,
                'attendance' => $records->values(),
                'total' => $records->count(),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to get attendance history.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance history',
            ], 500);
        }
    }

    /**
     * Get attendance statistics for the current HR studio.
     */
    public function getAttendanceStats()
    {
        try {
            $studio = $this->getUserStudio(Auth::user());

            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated.',
                ]);
            }

            $today = Carbon::today('Asia/Manila')->toDateString();
            $monthStart = Carbon::today('Asia/Manila')->startOfMonth()->toDateString();

            $stats = [
                'today' => [
                    'total' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->count(),
                    'checked_in' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->whereNotNull('check_in_time')
                        ->count(),
                    'checked_out' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->whereNotNull('check_out_time')
                        ->count(),
                    'late' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->where('check_in_status', 'LATE')
                        ->count(),
                ],
                'month' => [
                    'total' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->count(),
                    'late' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->where('check_in_status', 'LATE')
                        ->count(),
                    'undertime' => EmployeeAttendanceModel::query()
                        ->where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->where('check_out_status', 'UNDERTIME')
                        ->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to get attendance stats.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics',
            ], 500);
        }
    }

    /**
     * Get attendance details for a real attendance record.
     */
    public function getAttendanceDetails($id)
    {
        try {
            $attendance = EmployeeAttendanceModel::with(['employee', 'schedule'])
                ->where('id', $id)
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'attendance' => $this->buildAttendanceDetailPayload($attendance),
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Failed to get attendance details.', [
                'attendance_id' => $id,
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance details',
            ], 500);
        }
    }

    /**
     * Resolve the studio associated with the authenticated user.
     */
    private function getUserStudio(UserModel $user): ?StudiosModel
    {
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        return $schedule ? StudiosModel::find($schedule->studio_id) : null;
    }

    /**
     * Get today's schedule payload for the authenticated user.
     */
    private function getTodaySchedule(UserModel $user): ?array
    {
        $today = strtolower(Carbon::now('Asia/Manila')->format('l'));
        $schedule = EmployeeScheduleModel::query()
            ->where('user_id', $user->id)
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
        ];
    }

    /**
     * Store an attendance image in public storage.
     */
    private function storeAttendanceImage($image, string $type): string
    {
        return $image->store('employee-attendance/' . $type . '/' . now()->format('Y/m/d'), 'public');
    }

    /**
     * Get the approved leave covering the supplied date.
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
            'employee_name' => $attendance->employee->full_name ?? 'Unknown',
            'attendance_date' => $attendance->attendance_date->format('F d, Y'),
            'scheduled_start_time' => $attendance->scheduled_start_time ? Carbon::parse($attendance->scheduled_start_time)->format('h:i A') : '—',
            'scheduled_end_time' => $attendance->scheduled_end_time ? Carbon::parse($attendance->scheduled_end_time)->format('h:i A') : '—',
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
            'check_in_ip' => $attendance->check_in_ip,
            'check_out_ip' => $attendance->check_out_ip,
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
     * Build leave-aware self attendance history for pagination.
     */
    private function buildSelfAttendanceHistory(int $userId, int $perPage = 10): LengthAwarePaginator
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
            ->sortByDesc(fn ($record) => Carbon::parse($record->attendance_date)->format('Y-m-d') . ' ' . ($record->sort_time ?? '00:00:00'))
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
     * Build synthetic approved leave rows for the self-history table.
     */
    private function buildApprovedLeaveHistoryEntries(int $userId, array $attendanceDates): Collection
    {
        $approvedLeaves = LeaveRequestModel::with(['studio'])
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->orderByDesc('start_date')
            ->get();

        $leaveEntries = collect();

        foreach ($approvedLeaves as $leaveRequest) {
            $period = CarbonPeriod::create(
                Carbon::parse($leaveRequest->start_date)->startOfDay(),
                Carbon::parse($leaveRequest->end_date)->startOfDay()
            );

            foreach ($period as $leaveDate) {
                $leaveDateString = $leaveDate->toDateString();

                if (in_array($leaveDateString, $attendanceDates, true)) {
                    continue;
                }

                $leaveEntries->push($this->makeSyntheticLeaveRecord([
                    'employee_name' => 'You',
                    'attendance_date' => $leaveDateString,
                    'studio_name' => $leaveRequest->studio->studio_name ?? 'N/A',
                    'leave_type_label' => $leaveRequest->leave_type_label,
                    'notes' => $leaveRequest->reason,
                ]));
            }
        }

        return $leaveEntries;
    }

    /**
     * Build attendance rows for the HR studio employee table.
     */
    private function buildStudioAttendanceRows(int $studioId, Request $request): Collection
    {
        $employeeSchedules = EmployeeScheduleModel::query()
            ->with('user')
            ->where('studio_id', $studioId)
            ->where('is_active', true)
            ->get();

        $employeeIds = $employeeSchedules->pluck('user_id')->unique()->values();
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        [$dateFrom, $dateTo] = $this->resolveHistoryDateRange($request);
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', '');
        $employeeIdFilter = $request->input('employee_id');

        $attendanceQuery = EmployeeAttendanceModel::with('employee')
            ->where('studio_id', $studioId)
            ->whereIn('user_id', $employeeIds);

        if ($dateFrom && $dateTo) {
            $attendanceQuery->whereBetween('attendance_date', [$dateFrom, $dateTo]);
        }

        if (!empty($employeeIdFilter)) {
            $attendanceQuery->where('user_id', $employeeIdFilter);
        }

        if ($search !== '') {
            $attendanceQuery->whereHas('employee', function ($employeeQuery) use ($search) {
                $employeeQuery->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'LATE') {
            $attendanceQuery->where('check_in_status', 'LATE');
        } elseif ($status === 'UNDERTIME') {
            $attendanceQuery->where('check_out_status', 'UNDERTIME');
        } elseif ($status === 'ON_TIME') {
            $attendanceQuery->where('check_in_status', 'ON_TIME');
        }

        $attendanceRecords = $attendanceQuery
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get();

        $attendanceDateMap = [];
        foreach ($attendanceRecords as $record) {
            $attendanceDateMap[$record->user_id][] = $record->attendance_date?->toDateString();
        }

        $formattedAttendance = $attendanceRecords->map(function (EmployeeAttendanceModel $record) {
            $this->applyAttendancePresentation($record);

            return [
                'id' => $record->id,
                'record_type' => 'attendance',
                'employee_name' => $record->employee->full_name ?? 'Unknown',
                'attendance_date' => $record->attendance_date?->format('M d, Y') ?? 'N/A',
                'formatted_check_in' => $record->formatted_check_in,
                'formatted_check_out' => $record->display_check_out ?? $record->formatted_check_out,
                'actual_check_out' => $record->actual_check_out ?? $record->formatted_check_out,
                'check_in_status' => $record->check_in_status,
                'check_out_status' => $record->check_out_status,
                'late_display' => $record->late_minutes > 0 ? $record->late_display : null,
                'undertime_display' => $record->undertime_minutes > 0 ? $record->undertime_display : null,
                'duration' => $record->display_duration ?? $record->duration,
                'actual_duration' => $record->actual_duration ?? $record->duration,
                'is_overtime_applied' => $record->is_overtime_applied ?? false,
            ];
        });

        $leaveRows = collect();
        if ($dateFrom && $dateTo) {
            $approvedLeaves = LeaveRequestModel::with('user')
                ->where('studio_id', $studioId)
                ->where('status', 'approved')
                ->whereIn('user_id', $employeeIds)
                ->whereDate('start_date', '<=', $dateTo)
                ->whereDate('end_date', '>=', $dateFrom)
                ->get();

            foreach ($approvedLeaves as $leaveRequest) {
                if (!empty($employeeIdFilter) && (int) $employeeIdFilter !== (int) $leaveRequest->user_id) {
                    continue;
                }

                $employeeName = $leaveRequest->user->full_name ?? 'Unknown';
                $employeeEmail = $leaveRequest->user->email ?? '';

                if ($search !== '' && !str_contains(strtolower($employeeName . ' ' . $employeeEmail), strtolower($search))) {
                    continue;
                }

                if ($status !== '' && $status !== 'ON_LEAVE') {
                    continue;
                }

                $period = CarbonPeriod::create(
                    Carbon::parse($leaveRequest->start_date)->startOfDay(),
                    Carbon::parse($leaveRequest->end_date)->startOfDay()
                );

                foreach ($period as $leaveDate) {
                    $leaveDateString = $leaveDate->toDateString();

                    if ($leaveDateString < $dateFrom || $leaveDateString > $dateTo) {
                        continue;
                    }

                    if (in_array($leaveDateString, $attendanceDateMap[$leaveRequest->user_id] ?? [], true)) {
                        continue;
                    }

                    $leaveRows->push([
                        'id' => null,
                        'record_type' => 'leave',
                        'employee_name' => $employeeName,
                        'attendance_date' => $leaveDate->format('M d, Y'),
                        'formatted_check_in' => 'On Leave',
                        'formatted_check_out' => 'On Leave',
                        'check_in_status' => 'ON_LEAVE',
                        'check_out_status' => null,
                        'late_display' => null,
                        'undertime_display' => null,
                        'duration' => $leaveRequest->leave_type_label,
                    ]);
                }
            }
        }

        return $formattedAttendance
            ->concat($leaveRows)
            ->sortByDesc(function (array $record) {
                return Carbon::parse($record['attendance_date'])->format('Y-m-d') . ' ' . ($record['record_type'] === 'leave' ? '23:59:59' : '12:00:00');
            })
            ->values();
    }

    /**
     * Resolve the date window used by the studio attendance filters.
     */
    private function resolveHistoryDateRange(Request $request): array
    {
        $today = Carbon::today('Asia/Manila');
        $filterDate = (string) $request->input('filter_date', 'today');

        if ($filterDate === 'yesterday') {
            $date = $today->copy()->subDay()->toDateString();

            return [$date, $date];
        }

        if ($filterDate === 'this-week') {
            return [
                $today->copy()->startOfWeek()->toDateString(),
                $today->copy()->endOfWeek()->toDateString(),
            ];
        }

        if ($filterDate === 'this-month') {
            return [
                $today->copy()->startOfMonth()->toDateString(),
                $today->copy()->endOfMonth()->toDateString(),
            ];
        }

        if ($filterDate === 'custom') {
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            return [$dateFrom ?: $today->toDateString(), $dateTo ?: $today->toDateString()];
        }

        $date = $today->toDateString();

        return [$date, $date];
    }

    /**
     * Build a synthetic leave row with attendance-like fields for Blade rendering.
     */
    private function makeSyntheticLeaveRecord(array $data): object
    {
        return (object) [
            'id' => null,
            'record_type' => 'leave',
            'attendance_date' => Carbon::parse($data['attendance_date']),
            'studio' => (object) ['studio_name' => $data['studio_name']],
            'scheduled_start_time' => null,
            'scheduled_end_time' => null,
            'formatted_check_in' => 'On Leave',
            'formatted_check_out' => 'On Leave',
            'check_in_status' => 'ON_LEAVE',
            'check_out_status' => null,
            'check_in_status_badge' => 'badge-soft-info',
            'check_out_status_badge' => 'badge-soft-secondary',
            'late_minutes' => 0,
            'undertime_minutes' => 0,
            'late_display' => '—',
            'undertime_display' => '—',
            'duration' => $data['leave_type_label'],
            'leave_type_label' => $data['leave_type_label'],
            'notes' => $data['notes'],
            'sort_time' => '23:59:59',
            'employee_name' => $data['employee_name'] ?? 'Unknown',
        ];
    }
}
