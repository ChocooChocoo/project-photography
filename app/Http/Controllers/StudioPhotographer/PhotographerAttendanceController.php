<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioPhotographer\PhotographerCheckInRequest;
use App\Http\Requests\StudioPhotographer\PhotographerCheckOutRequest;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioPhotographer\PhotographerAttendanceModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        $myAttendance = PhotographerAttendanceModel::with('studio')
            ->forUser($user->id)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate(10);

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

            $assignment = $this->getAssignedStudio($user->id, $studioId);

            if (!$assignment || !$assignment->studio) {
                return response()->json([
                    'status' => 'error',
                    'success' => false,
                    'message' => 'You are not assigned to the selected studio.',
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

            $checkOutTime = Carbon::now('Asia/Manila');
            $checkOutStatus = 'ON_TIME';
            $undertimeMinutes = 0;
            $imagePath = $attendance->check_out_image;

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
                'attendance' => $attendance->fresh('studio'),
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
            'attendance' => [
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
                'check_in_status' => $attendance->check_in_status,
                'check_out_status' => $attendance->check_out_status,
                'late_display' => $attendance->late_display,
                'undertime_display' => $attendance->undertime_display,
                'duration' => $attendance->duration,
                'check_in_image' => $attendance->check_in_image,
                'check_out_image' => $attendance->check_out_image,
                'notes' => $attendance->notes,
            ],
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
     * Store the uploaded attendance image.
     *
     * @param mixed $image
     * @param string $type
     */
    private function storeAttendanceImage($image, string $type): string
    {
        return $image->store('photographer-attendance/' . $type . '/' . now()->format('Y/m/d'), 'public');
    }
}
