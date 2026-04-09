<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioPhotographer\PhotographerCheckInRequest;
use App\Http\Requests\StudioPhotographer\PhotographerCheckOutRequest;
use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPhotographer\PhotographerAttendanceModel;
use App\Services\AttendanceGeolocationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PhotographerAttendanceController extends Controller
{
    /**
     * Grace period for check-in in minutes.
     */
    protected const GRACE_PERIOD_MINUTES = 15;

    /**
     * Display the attendance page for the authenticated photographer.
     */
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today('Asia/Manila');
        $monthStart = $today->copy()->startOfMonth()->toDateString();
        $monthEnd = $today->copy()->endOfMonth()->toDateString();

        $assignedStudios = StudioPhotographersModel::with('studio')
            ->where('photographer_id', $user->id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        $attendanceBaseQuery = PhotographerAttendanceModel::forUser($user->id);

        $myAttendance = $this->getPhotographerAttendanceHistory($user->id);

        $defaultStudioId = optional($assignedStudios->first())->studio_id;
        $scheduleInfo = $defaultStudioId ? $this->buildStudioScheduleInfo($defaultStudioId) : null;
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

        return view('studio-photographer.view-attendance', compact(
            'assignedStudios',
            'myAttendance',
            'defaultStudioId',
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
     * Get the selected studio schedule and today's attendance state.
     */
    public function getPhotographerSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $studioId = (int) $request->input('studio_id');
            $now = Carbon::now('Asia/Manila');
            $todayDate = $now->toDateString();

            $assignment = $this->getAssignedStudio($user->id, $studioId);

            if (!$assignment || !$assignment->studio) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'No active assigned studio found for the selected studio.',
                ], 422);
            }

            $schedule = $this->buildStudioSchedulePayload($assignment->studio);
            $todayAttendance = PhotographerAttendanceModel::forUser($user->id)
                ->forDate($todayDate)
                ->first();
            $approvedLeave = $this->getApprovedLeaveForDate($user->id, $todayDate);
            $approvedOvertime = $approvedLeave ? null : $this->getApprovedOvertimeForDate($user->id, $todayDate, $assignment->studio_id);
            $scheduledEnd = !empty($schedule['schedule']['end_time'])
                ? Carbon::parse($todayDate . ' ' . $schedule['schedule']['end_time'], 'Asia/Manila')
                : null;

            return response()->json([
                'status' => 'success',
                'success' => true,
                'has_schedule' => $schedule['has_schedule'],
                'schedule' => $schedule['schedule'],
                'studio_name' => $assignment->studio->studio_name,
                'is_checked_in' => $todayAttendance && $todayAttendance->isCheckedIn(),
                'is_checked_out' => $todayAttendance && $todayAttendance->isCheckedOut(),
                'today_attendance_id' => $todayAttendance?->id,
                'today_attendance' => $todayAttendance,
                'blocked_by_leave' => !is_null($approvedLeave),
                'attendance_blocked' => !is_null($approvedLeave),
                'blocked_message' => $approvedLeave
                    ? 'Attendance is unavailable today because you have an approved leave request.'
                    : null,
                'studio_geofence' => $this->buildStudioGeofenceSummary($assignment->studio),
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
            Log::error('Failed to get photographer schedule.', [
                'user_id' => Auth::id(),
                'message' => $throwable->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Failed to load the selected studio schedule.',
            ], 500);
        }
    }

    /**
     * Store the photographer check-in record.
     */
    public function checkIn(PhotographerCheckInRequest $request)
    {
        try {
            $user = Auth::user();
            $studioId = (int) $request->input('studio_id');
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

            $assignment = $this->getAssignedStudio($user->id, $studioId);

            if (!$assignment || !$assignment->studio) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You are not assigned to the selected studio.',
                ], 422);
            }

            $locationValidation = $this->validateAttendanceLocation(
                $assignment->studio,
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            if (!$locationValidation['allowed']) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => $locationValidation['message'],
                    'errors' => [
                        'location' => [$locationValidation['message']],
                    ],
                    'location_validation' => $locationValidation,
                ], 422);
            }

            $existingAttendance = PhotographerAttendanceModel::forUser($user->id)
                ->forDate($today)
                ->first();

            if ($existingAttendance && $existingAttendance->isCheckedIn()) {
                $studioName = $existingAttendance->studio->studio_name ?? 'another studio';

                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You have already checked in today for ' . $studioName . '.',
                ], 422);
            }

            $schedulePayload = $this->buildStudioSchedulePayload($assignment->studio);
            $schedule = $schedulePayload['schedule'];

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

            $attendance = PhotographerAttendanceModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'attendance_date' => $today,
                ],
                [
                    'studio_id' => $assignment->studio_id,
                    'schedule_id' => null,
                    'scheduled_start_time' => $schedule['start_time'] ?? null,
                    'scheduled_end_time' => $schedule['end_time'] ?? null,
                    'check_in_time' => $now,
                    'check_in_status' => $checkInStatus,
                    'check_in_latitude' => $request->input('latitude'),
                    'check_in_longitude' => $request->input('longitude'),
                    'check_in_distance_meters' => $locationValidation['distance_meters'],
                    'check_in_location_status' => $locationValidation['status'],
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
                'location_validation' => $locationValidation,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Photographer check-in failed.', [
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
     * Store the photographer check-out record.
     */
    public function checkOut(PhotographerCheckOutRequest $request)
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

            $attendance = PhotographerAttendanceModel::with('studio')
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

            $studio = StudiosModel::find($attendance->studio_id);
            if (!$studio) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'No studio associated with your attendance record.',
                ], 422);
            }

            $locationValidation = $this->validateAttendanceLocation(
                $studio,
                (float) $request->input('latitude'),
                (float) $request->input('longitude')
            );

            if (!$locationValidation['allowed']) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => $locationValidation['message'],
                    'errors' => [
                        'location' => [$locationValidation['message']],
                    ],
                    'location_validation' => $locationValidation,
                ], 422);
            }

            $checkOutTime = Carbon::now('Asia/Manila');
            $checkOutStatus = 'ON_TIME';
            $undertimeMinutes = 0;
            $approvedOvertime = $this->getApprovedOvertimeForDate($user->id, $today, $attendance->studio_id);

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
                'check_out_status' => $checkOutStatus,
                'check_out_latitude' => $request->input('latitude'),
                'check_out_longitude' => $request->input('longitude'),
                'check_out_distance_meters' => $locationValidation['distance_meters'],
                'check_out_location_status' => $locationValidation['status'],
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
                'location_validation' => $locationValidation,
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Photographer check-out failed.', [
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
     * Get attendance details for the authenticated photographer.
     *
     * @param int $id
     */
    public function getAttendanceDetails(int $id)
    {
        $attendance = PhotographerAttendanceModel::with('studio')
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
     * Build the displayable studio schedule information block.
     *
     * @param int $studioId
     * @return array<string, mixed>|null
     */
    private function buildStudioScheduleInfo(int $studioId): ?array
    {
        $assignment = $this->getAssignedStudio(Auth::id(), $studioId);

        if (!$assignment || !$assignment->studio) {
            return null;
        }

        $operatingDays = $assignment->studio->operating_days ?? [];
        $operatingDays = is_array($operatingDays) ? $operatingDays : [];

        return [
            'studio_name' => $assignment->studio->studio_name,
            'operating_days' => $operatingDays,
            'start_time' => $assignment->studio->start_time
                ? Carbon::parse($assignment->studio->start_time)->format('h:i A')
                : 'Not set',
            'end_time' => $assignment->studio->end_time
                ? Carbon::parse($assignment->studio->end_time)->format('h:i A')
                : 'Not set',
        ];
    }

    /**
     * Get the active assignment for the authenticated photographer and studio.
     *
     * @param int $photographerId
     * @param int|null $studioId
     */
    private function getAssignedStudio(int $photographerId, ?int $studioId = null): ?StudioPhotographersModel
    {
        $query = StudioPhotographersModel::with('studio')
            ->where('photographer_id', $photographerId)
            ->where('status', 'active');

        if ($studioId) {
            $query->where('studio_id', $studioId);
        }

        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Build the schedule payload based on the studio operating days and hours.
     *
     * @param mixed $studio
     * @return array<string, mixed>
     */
    private function buildStudioSchedulePayload($studio): array
    {
        $today = strtolower(Carbon::now('Asia/Manila')->format('l'));
        $operatingDays = $studio->operating_days ?? [];
        $operatingDays = is_array($operatingDays) ? $operatingDays : [];
        $normalizedDays = array_map('strtolower', $operatingDays);
        $hasSchedule = in_array($today, $normalizedDays, true);

        return [
            'has_schedule' => $hasSchedule,
            'schedule' => [
                'start_time' => $studio->start_time ? Carbon::parse($studio->start_time)->format('H:i:s') : null,
                'end_time' => $studio->end_time ? Carbon::parse($studio->end_time)->format('H:i:s') : null,
                'operating_days' => $operatingDays,
            ],
        ];
    }

    /**
     * Get the approved leave covering the supplied date.
     */
    private function getApprovedLeaveForDate(int $userId, string $date): ?LeaveRequestModel
    {
        return LeaveRequestModel::with('studio')
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
    private function applyAttendancePresentation(PhotographerAttendanceModel $attendance): void
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
    private function buildAttendanceDetailPayload(PhotographerAttendanceModel $attendance): array
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
            'check_in_location' => [
                'latitude' => $attendance->check_in_latitude,
                'longitude' => $attendance->check_in_longitude,
                'distance_meters' => $attendance->check_in_distance_meters,
                'status' => $attendance->check_in_location_status,
            ],
            'check_out_location' => [
                'latitude' => $attendance->check_out_latitude,
                'longitude' => $attendance->check_out_longitude,
                'distance_meters' => $attendance->check_out_distance_meters,
                'status' => $attendance->check_out_location_status,
            ],
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
     * Build leave-aware attendance history for the photographer page.
     */
    private function getPhotographerAttendanceHistory(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        $attendanceRecords = PhotographerAttendanceModel::with('studio')
            ->forUser($userId)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->get()
            ->map(function (PhotographerAttendanceModel $attendance) {
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
        $approvedLeaves = LeaveRequestModel::with('studio')
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
                    'check_in_status_badge' => 'badge-soft-info',
                    'check_out_status_badge' => 'badge-soft-secondary',
                    'late_minutes' => 0,
                    'undertime_minutes' => 0,
                    'late_display' => '—',
                    'undertime_display' => '—',
                    'duration' => $leaveRequest->leave_type_label,
                    'leave_type_label' => $leaveRequest->leave_type_label,
                    'notes' => $leaveRequest->reason,
                    'sort_time' => '23:59:59',
                ]);
            }
        }

        return $leaveEntries;
    }

    /**
     * Build studio geofence metadata for the attendance page.
     */
    private function buildStudioGeofenceSummary(StudiosModel $studio): array
    {
        return [
            'is_configured' => !is_null($studio->attendance_latitude) && !is_null($studio->attendance_longitude),
            'radius_meters' => (int) ($studio->attendance_radius_meters ?? 100),
            'latitude' => $studio->attendance_latitude,
            'longitude' => $studio->attendance_longitude,
        ];
    }

    /**
     * Validate the submitted attendance location against the studio geofence.
     *
     * @return array<string, mixed>
     */
    private function validateAttendanceLocation(StudiosModel $studio, float $latitude, float $longitude): array
    {
        return app(AttendanceGeolocationService::class)->validateStudioGeofence($studio, $latitude, $longitude);
    }
}
