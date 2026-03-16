<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\CheckInRequest;
use App\Http\Requests\StudioHR\CheckOutRequest;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EmployeeAttendanceController extends Controller
{
    /**
     * @var int Grace period in minutes
     */
    protected const GRACE_PERIOD_MINUTES = 15;

    /**
     * Display the attendance page with user's attendance records.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get logged-in user's attendance records
        $myAttendance = EmployeeAttendanceModel::with(['schedule'])
            ->where('user_id', $user->id)
            ->orderBy('attendance_date', 'desc')
            ->orderBy('check_in_time', 'desc')
            ->paginate(10);
        
        // Get user's schedule for the alert box
        $schedule = EmployeeScheduleModel::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
        
        $scheduleInfo = null;
        if ($schedule && $schedule->operating_days) {
            $operatingDays = is_array($schedule->operating_days) 
                ? $schedule->operating_days 
                : json_decode($schedule->operating_days, true) ?? [];
            
            $scheduleInfo = [
                'operating_days' => $operatingDays,
                'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('h:i A') : 'Not set',
                'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('h:i A') : 'Not set',
            ];
        }
        
        return view('studio-hr.view-attendance', compact('myAttendance', 'scheduleInfo'));
    }

    /**
     * Get current server time for real-time display.
     */
    public function getCurrentTime()
    {
        return response()->json([
            'success' => true,
            'time' => now()->format('h:i:s A'),
            'date' => now()->format('l, F d, Y'),
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Get employee schedule for today.
     */
    public function getEmployeeSchedule()
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila'); // Use Asia/Manila timezone
            $today = $now->format('l'); // Get current day name (Monday, Tuesday, etc.)
            $todayLower = strtolower($today);
            $todayDate = $now->toDateString(); // Get today's date in Y-m-d format
            
            \Log::info('getEmployeeSchedule - Current time with timezone', [
                'user_id' => $user->id,
                'current_time' => $now->toDateTimeString(),
                'timezone' => $now->timezoneName,
                'today' => $today,
                'today_date' => $todayDate
            ]);
            
            // Get employee's schedule for today
            $schedule = EmployeeScheduleModel::where('user_id', $user->id)
                ->where('is_active', true)
                ->first();
            
            $hasSchedule = false;
            $scheduleData = null;
            
            if ($schedule && $schedule->operating_days) {
                // Decode operating days - handle various JSON formats
                $operatingDays = $schedule->operating_days;
                
                if (is_string($operatingDays)) {
                    // Clean up the string - remove extra quotes if present
                    $cleaned = str_replace('""', '"', $operatingDays);
                    $operatingDays = json_decode($cleaned, true) ?? [];
                }
                
                // Ensure we have an array
                if (!is_array($operatingDays)) {
                    $operatingDays = [];
                }
                
                // Convert all to lowercase for comparison
                $operatingDaysLower = array_map('strtolower', $operatingDays);
                
                // Check if today is in operating days
                $hasSchedule = in_array($todayLower, $operatingDaysLower);
                
                \Log::info('Schedule check', [
                    'operating_days_raw' => $schedule->operating_days,
                    'operating_days_parsed' => $operatingDays,
                    'operating_days_lower' => $operatingDaysLower,
                    'today_lower' => $todayLower,
                    'has_schedule' => $hasSchedule
                ]);
                
                if ($hasSchedule) {
                    $scheduleData = [
                        'start_time' => $schedule->start_time ? Carbon::parse($schedule->start_time)->format('H:i:s') : null,
                        'end_time' => $schedule->end_time ? Carbon::parse($schedule->end_time)->format('H:i:s') : null,
                        'schedule_id' => $schedule->id,
                    ];
                }
            }
            
            // Check if already checked in today (using the same timezone date)
            $todayAttendance = EmployeeAttendanceModel::where('user_id', $user->id)
                ->whereDate('attendance_date', $todayDate)
                ->first();
            
            \Log::info('Today attendance check', [
                'user_id' => $user->id,
                'attendance_date' => $todayDate,
                'has_attendance' => $todayAttendance ? true : false,
                'is_checked_in' => $todayAttendance ? $todayAttendance->isCheckedIn() : false,
                'is_checked_out' => $todayAttendance ? $todayAttendance->isCheckedOut() : false
            ]);
            
            return response()->json([
                'success' => true,
                'has_schedule' => $hasSchedule,
                'schedule' => $scheduleData,
                'is_checked_in' => $todayAttendance && $todayAttendance->isCheckedIn(),
                'is_checked_out' => $todayAttendance && $todayAttendance->isCheckedOut(),
                'today_attendance_id' => $todayAttendance?->id,
                'today_attendance' => $todayAttendance,
                'current_time' => $now->format('h:i:s A'), // Send formatted time to frontend
                'current_date' => $now->format('l, F d, Y'),
                'timezone' => $now->timezoneName,
                'debug' => [
                    'user_id' => $user->id,
                    'today' => $today,
                    'today_date' => $todayDate,
                    'operating_days' => $schedule?->operating_days ?? null,
                    'parsed_operating_days' => isset($operatingDays) ? $operatingDays : null,
                    'server_time' => $now->toDateTimeString(),
                    'server_timezone' => $now->timezoneName
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error getting employee schedule: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id() ?? 'unknown'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle check-in with photo.
     */
    public function checkIn(CheckInRequest $request)
    {
        try {
            $user = Auth::user();
            $now = Carbon::now('Asia/Manila');
            $today = $now->toDateString(); // This gives '2026-03-16'
            
            \Log::info('Check-in attempt', [
                'user_id' => $user->id,
                'current_time' => $now->toDateTimeString(),
                'timezone' => $now->timezoneName,
                'today' => $today
            ]);
            
            // Check if already checked in today
            $existingAttendance = EmployeeAttendanceModel::where('user_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->first();
                
            if ($existingAttendance && $existingAttendance->isCheckedIn()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked in today.'
                ], 422);
            }
            
            // Get employee's studio
            $studio = $this->getUserStudio($user);
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated with your account.'
                ], 422);
            }
            
            // Get today's schedule
            $schedule = $this->getTodaySchedule($user);
            
            // DEBUG: Log the actual schedule values
            \Log::info('Schedule data for check-in', [
                'schedule_exists' => $schedule ? true : false,
                'schedule_data' => $schedule,
                'start_time_raw' => $schedule['start_time'] ?? null,
                'start_time_type' => $schedule ? gettype($schedule['start_time']) : 'null'
            ]);
            
            // Process and store the image
            $imagePath = $this->storeAttendanceImage($request->file('image'), 'check-in');
            
            // Calculate check-in status with proper timezone
            $checkInTime = $now;
            $checkInStatus = 'ON_TIME';
            $lateMinutes = 0;
            
            // Only calculate lateness if we have a schedule
            if ($schedule && !empty($schedule['start_time'])) {
                // Get the date part from check-in time
                $datePart = $checkInTime->format('Y-m-d');
                $timePart = $schedule['start_time'];
                
                // DEBUG: Log what we're about to parse
                \Log::info('Parsing scheduled time', [
                    'date_part' => $datePart,
                    'time_part' => $timePart,
                    'full_string' => $datePart . ' ' . $timePart
                ]);
                
                // FIXED: Ensure we're not double-dating
                // If the time part already contains the date, extract just the time
                if (strpos($timePart, $datePart) !== false) {
                    // Time part contains the date, extract just the time portion
                    $timePart = substr($timePart, strpos($timePart, ' ') + 1);
                    \Log::info('Extracted time part', ['extracted' => $timePart]);
                }
                
                $scheduledStart = Carbon::parse(
                    $datePart . ' ' . $timePart,
                    'Asia/Manila'
                );
                
                $gracePeriodEnd = $scheduledStart->copy()->addMinutes(self::GRACE_PERIOD_MINUTES);
                
                \Log::info('Check-in time calculation', [
                    'scheduled_start' => $scheduledStart->toDateTimeString(),
                    'grace_period_end' => $gracePeriodEnd->toDateTimeString(),
                    'check_in_time' => $checkInTime->toDateTimeString(),
                    'timezone' => $checkInTime->timezoneName,
                    'is_after_grace' => $checkInTime->gt($gracePeriodEnd)
                ]);
                
                if ($checkInTime->gt($gracePeriodEnd)) {
                    $checkInStatus = 'LATE';
                    $lateMinutes = (int) $scheduledStart->diffInMinutes($checkInTime);
                    
                    \Log::info('Employee is late', [
                        'late_minutes' => $lateMinutes,
                        'scheduled_start' => $scheduledStart->toDateTimeString(),
                        'check_in' => $checkInTime->toDateTimeString()
                    ]);
                }
            } else {
                \Log::info('No schedule found for today, skipping lateness calculation', [
                    'schedule' => $schedule
                ]);
            }
            
            // Create attendance record
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
                    'check_in_time' => $checkInTime,
                    'check_in_image' => $imagePath,
                    'check_in_status' => $checkInStatus,
                    'late_minutes' => $lateMinutes,
                    'check_in_ip' => $request->ip(),
                    'check_in_user_agent' => $request->userAgent(),
                    'notes' => $request->input('notes'),
                ]
            );
            
            Log::info('Employee check-in recorded', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'status' => $checkInStatus,
                'late_minutes' => $lateMinutes,
                'has_schedule' => $schedule ? true : false
            ]);
            
            $message = 'Check-in successful!';
            if ($checkInStatus === 'LATE') {
                $message = "Check-in successful! You are {$lateMinutes} minute(s) late.";
            } else if (!$schedule) {
                $message = 'Check-in successful! (No schedule found for today)';
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'attendance' => $attendance,
                'status' => $checkInStatus,
                'late_minutes' => $lateMinutes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check-in failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process check-in: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle check-out.
     */
    public function checkOut(CheckOutRequest $request)
    {
        try {
            $user = Auth::user();
            
            // Find the attendance record
            $attendance = EmployeeAttendanceModel::where('id', $request->attendance_id)
                ->where('user_id', $user->id)
                ->first();
                
            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Attendance record not found.'
                ], 404);
            }
            
            if ($attendance->isCheckedOut()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already checked out today.'
                ], 422);
            }
            
            // Process image if provided
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $this->storeAttendanceImage($request->file('image'), 'check-out');
            }
            
            // Calculate check-out status - FIXED VERSION
            $checkOutTime = Carbon::now('Asia/Manila');
            $checkOutStatus = 'ON_TIME';
            $undertimeMinutes = 0;

            if ($attendance->scheduled_end_time) {
                $attendanceDate = $attendance->attendance_date->format('Y-m-d');
                $scheduledEnd = Carbon::parse($attendanceDate . ' ' . $attendance->scheduled_end_time, 'Asia/Manila');
                
                if ($checkOutTime->lt($scheduledEnd)) {
                    $checkOutStatus = 'UNDERTIME';
                    $undertimeMinutes = $checkOutTime->diffInMinutes($scheduledEnd);
                }
            }
            
            // Update attendance record
            $attendance->update([
                'check_out_time' => $checkOutTime,
                'check_out_image' => $imagePath ?? $attendance->check_out_image,
                'check_out_status' => $checkOutStatus,
                'undertime_minutes' => $undertimeMinutes,
                'check_out_ip' => $request->ip(),
                'check_out_user_agent' => $request->userAgent(),
                'notes' => $request->input('notes', $attendance->notes),
            ]);
            
            Log::info('Employee check-out recorded', [
                'user_id' => $user->id,
                'attendance_id' => $attendance->id,
                'status' => $checkOutStatus,
                'undertime_minutes' => $undertimeMinutes
            ]);
            
            return response()->json([
                'success' => true,
                'message' => $checkOutStatus === 'ON_TIME'
                    ? 'Check-out successful! Have a great day!'
                    : "Check-out successful! You left {$undertimeMinutes} minute(s) early.",
                'attendance' => $attendance,
                'status' => $checkOutStatus,
                'undertime_minutes' => $undertimeMinutes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Check-out failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process check-out: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's attendance records.
     */
    public function getTodaysAttendance()
    {
        try {
            $user = Auth::user();
            
            // Get user's studio
            $studio = $this->getUserStudio($user);
            
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated.'
                ]);
            }
            
            // Get all employees in this studio
            $employeeIds = EmployeeScheduleModel::where('studio_id', $studio->id)
                ->where('is_active', true)
                ->pluck('user_id')
                ->toArray();
            
            // Get today's attendance for these employees
            $attendance = EmployeeAttendanceModel::with(['employee'])
                ->whereIn('user_id', $employeeIds)
                ->whereDate('attendance_date', now()->toDateString())
                ->orderBy('check_in_time', 'desc')
                ->get()
                ->map(function ($record) {
                    return [
                        'id' => $record->id,
                        'employee_name' => $record->employee->full_name ?? 'Unknown',
                        'formatted_check_in' => $record->formatted_check_in,
                        'formatted_check_out' => $record->formatted_check_out,
                        'check_in_status' => $record->check_in_status,
                        'check_out_status' => $record->check_out_status,
                        'late_display' => $record->late_display,
                        'undertime_display' => $record->undertime_display,
                        'duration' => $record->duration,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'attendance' => $attendance,
                'total' => $attendance->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get today\'s attendance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data'
            ], 500);
        }
    }

    /**
     * Get attendance history with filters.
     */
    public function getAttendanceHistory(Request $request)
    {
        try {
            $user = Auth::user();
            
            // Get user's studio
            $studio = $this->getUserStudio($user);
            
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated.'
                ]);
            }
            
            $query = EmployeeAttendanceModel::with(['employee'])
                ->where('studio_id', $studio->id);
            
            // Apply filters
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('attendance_date', '>=', $request->date_from);
            }
            
            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('attendance_date', '<=', $request->date_to);
            }
            
            if ($request->has('employee_id') && $request->employee_id) {
                $query->where('user_id', $request->employee_id);
            }
            
            if ($request->has('status') && $request->status) {
                if ($request->status === 'LATE') {
                    $query->late();
                } elseif ($request->status === 'UNDERTIME') {
                    $query->undertime();
                }
            }
            
            $attendance = $query->orderBy('attendance_date', 'desc')
                ->orderBy('check_in_time', 'desc')
                ->paginate($request->input('per_page', 15));
            
            return response()->json([
                'success' => true,
                'attendance' => $attendance
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get attendance history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance history'
            ], 500);
        }
    }

    /**
     * Get attendance statistics.
     */
    public function getAttendanceStats()
    {
        try {
            $user = Auth::user();
            
            // Get user's studio
            $studio = $this->getUserStudio($user);
            
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio associated.'
                ]);
            }
            
            $today = now()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();
            
            $stats = [
                'today' => [
                    'total' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->count(),
                    'checked_in' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->whereNotNull('check_in_time')
                        ->count(),
                    'checked_out' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->whereNotNull('check_out_time')
                        ->count(),
                    'late' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', $today)
                        ->where('check_in_status', 'LATE')
                        ->count(),
                ],
                'month' => [
                    'total' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->count(),
                    'late' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->where('check_in_status', 'LATE')
                        ->count(),
                    'undertime' => EmployeeAttendanceModel::where('studio_id', $studio->id)
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->where('check_out_status', 'UNDERTIME')
                        ->count(),
                ]
            ];
            
            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get attendance stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load statistics'
            ], 500);
        }
    }

    /**
     * Get attendance details by ID.
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
                    'message' => 'Attendance record not found.'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'attendance' => [
                    'id' => $attendance->id,
                    'employee_name' => $attendance->employee->full_name ?? 'Unknown',
                    'attendance_date' => $attendance->attendance_date->format('F d, Y'),
                    'scheduled_start_time' => $attendance->scheduled_start_time ? Carbon::parse($attendance->scheduled_start_time)->format('h:i A') : '—',
                    'scheduled_end_time' => $attendance->scheduled_end_time ? Carbon::parse($attendance->scheduled_end_time)->format('h:i A') : '—',
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
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get attendance details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance details'
            ], 500);
        }
    }

    /**
     * Helper: Get user's studio
     */
    private function getUserStudio($user)
    {
        // First, try to find via studio_photographers (for photographers)
        $studio = StudiosModel::whereHas('studioPhotographers', function ($query) use ($user) {
            $query->where('photographer_id', $user->id);
        })->first();
        
        if ($studio) {
            return $studio;
        }
        
        // Second, try to find via RBAC table (for HR, Finance, Staff)
        $rbac = \App\Models\StudioOwner\RbacModel::where('user_id', $user->id)->first();
        
        if ($rbac) {
            return StudiosModel::find($rbac->studio_id);
        }
        
        // Third, try to find via employee schedule
        $schedule = \App\Models\StudioOwner\EmployeeScheduleModel::where('user_id', $user->id)->first();
        
        if ($schedule) {
            return StudiosModel::find($schedule->studio_id);
        }
        
        return null;
    }

    /**
     * Helper: Get today's schedule for user
     */
    private function getTodaySchedule($user)
    {
        $now = Carbon::now('Asia/Manila');
        $today = strtolower($now->format('l'));
        
        \Log::info('getTodaySchedule - Input', [
            'user_id' => $user->id,
            'today' => $today,
            'current_time' => $now->toDateTimeString()
        ]);
        
        $schedule = EmployeeScheduleModel::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();
            
        if (!$schedule) {
            \Log::info('No active schedule found for user', ['user_id' => $user->id]);
            return null;
        }
        
        \Log::info('Schedule found', [
            'schedule_id' => $schedule->id,
            'operating_days_raw' => $schedule->operating_days,
            'start_time_raw' => $schedule->start_time,
            'end_time_raw' => $schedule->end_time,
            'start_time_type' => gettype($schedule->start_time)
        ]);
        
        // Parse operating days
        $operatingDays = $schedule->operating_days;
        
        if (is_string($operatingDays)) {
            $operatingDays = json_decode($operatingDays, true);
        }
        
        // Ensure we have an array
        if (!is_array($operatingDays)) {
            $operatingDays = [];
        }
        
        // Convert to lowercase for comparison
        $operatingDaysLower = array_map('strtolower', $operatingDays);
        
        \Log::info('Parsed operating days', [
            'original' => $schedule->operating_days,
            'parsed' => $operatingDays,
            'lowercase' => $operatingDaysLower,
            'today' => $today
        ]);
        
        $hasSchedule = in_array($today, $operatingDaysLower);
        
        if (!$hasSchedule) {
            \Log::info('Today is not a working day', [
                'today' => $today,
                'working_days' => $operatingDaysLower
            ]);
            return null;
        }
        
        // Get the time values - ensure they're just time strings
        $startTime = $schedule->start_time;
        $endTime = $schedule->end_time;
        
        // If they're Carbon instances or DateTime objects, format them
        if ($startTime instanceof \Carbon\Carbon || $startTime instanceof \DateTime) {
            $startTime = $startTime->format('H:i:s');
        }
        if ($endTime instanceof \Carbon\Carbon || $endTime instanceof \DateTime) {
            $endTime = $endTime->format('H:i:s');
        }
        
        \Log::info('Returning schedule', [
            'schedule_id' => $schedule->id,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        
        return [
            'schedule_id' => $schedule->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    /**
     * Helper: Store attendance image
     */
    private function storeAttendanceImage($image, $type)
    {
        $path = $image->store('employee-attendance/' . now()->format('Y/m/d'), 'public');
        return $path;
    }
}