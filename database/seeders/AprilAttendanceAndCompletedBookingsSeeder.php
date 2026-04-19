<?php

namespace Database\Seeders;

use App\Models\BookingModel;
use App\Models\StudioHR\EmployeeAttendanceModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AprilAttendanceAndCompletedBookingsSeeder extends Seeder
{
    /**
     * Seed attendance and booking data for April 2026.
     */
    private const TARGET_YEAR = 2026;

    /**
     * Seed attendance and booking data for April 2026.
     */
    private const TARGET_MONTH = 4;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodStart = Carbon::create(self::TARGET_YEAR, self::TARGET_MONTH, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $employeeSchedules = EmployeeScheduleModel::with(['user', 'studio'])
            ->where('is_active', true)
            ->whereHas('user', function ($query) {
                $query->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
                    ->where('status', 'active');
            })
            ->orderBy('user_id')
            ->get();

        if ($employeeSchedules->isEmpty()) {
            $this->command?->warn('No active employee schedules found. April attendance seeder skipped.');
            return;
        }

        foreach ($employeeSchedules as $schedule) {
            $user = $schedule->user;
            $studio = $schedule->studio;

            if (!$user || !$studio) {
                continue;
            }

            $workingDates = $this->getWorkingDates($schedule, $periodStart, $periodEnd);

            if ($workingDates === []) {
                $this->command?->warn("No April working dates found for {$user->email}.");
                continue;
            }

            $absenceIndexes = $this->getAbsenceIndexes($user->role, (string) $user->user_type, count($workingDates), (int) $user->id);

            foreach ($workingDates as $index => $attendanceDate) {
                if (in_array($index, $absenceIndexes, true)) {
                    EmployeeAttendanceModel::query()
                        ->where('user_id', $user->id)
                        ->whereDate('attendance_date', $attendanceDate->toDateString())
                        ->delete();

                    continue;
                }

                $attendancePayload = $this->buildAttendancePayload($schedule, $attendanceDate, $index, (int) $user->id);

                EmployeeAttendanceModel::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'attendance_date' => $attendanceDate->toDateString(),
                    ],
                    [
                        'studio_id' => $studio->id,
                        'schedule_id' => $schedule->id,
                        'scheduled_start_time' => $attendancePayload['scheduled_start_time'],
                        'scheduled_end_time' => $attendancePayload['scheduled_end_time'],
                        'check_in_time' => $attendancePayload['check_in_time'],
                        'check_out_time' => $attendancePayload['check_out_time'],
                        'check_in_image' => null,
                        'check_out_image' => null,
                        'check_in_status' => $attendancePayload['check_in_status'],
                        'check_out_status' => $attendancePayload['check_out_status'],
                        'late_minutes' => $attendancePayload['late_minutes'],
                        'undertime_minutes' => $attendancePayload['undertime_minutes'],
                        'check_in_ip' => '10.20.1.' . (20 + ($user->id % 150)),
                        'check_out_ip' => '10.20.1.' . (20 + ($user->id % 150)),
                        'check_in_user_agent' => 'Seeded April 2026 attendance record',
                        'check_out_user_agent' => 'Seeded April 2026 attendance record',
                        'notes' => "Seeded April 2026 {$user->role} attendance record.",
                    ]
                );
            }

            $this->command?->info("Seeded April attendance: {$user->email}");
        }

        $this->seedCompletedPhotographerBookings($periodStart, $periodEnd);
    }

    /**
     * Build all working dates in the target month.
     *
     * @return array<int, Carbon>
     */
    private function getWorkingDates(EmployeeScheduleModel $schedule, Carbon $periodStart, Carbon $periodEnd): array
    {
        $workingDates = [];
        $period = CarbonPeriod::create($periodStart->copy(), $periodEnd->copy());

        foreach ($period as $date) {
            if ($schedule->worksOnDay(strtolower($date->format('l')))) {
                $workingDates[] = $date->copy();
            }
        }

        return $workingDates;
    }

    /**
     * Pick deterministic absences for the April attendance dataset.
     *
     * @return array<int, int>
     */
    private function getAbsenceIndexes(string $role, string $userType, int $workingDayCount, int $userId): array
    {
        if ($workingDayCount < 5) {
            return [];
        }

        $absenceCount = match (true) {
            strtolower($userType) === 'manager' => 1,
            $role === 'studio-photographer' => 2,
            default => 2,
        };

        $candidateIndexes = [
            2 + ($userId % 3),
            10 + ($userId % 4),
            18 + ($userId % 3),
        ];

        $indexes = [];

        foreach ($candidateIndexes as $candidateIndex) {
            if ($candidateIndex < $workingDayCount) {
                $indexes[] = $candidateIndex;
            }

            if (count($indexes) >= $absenceCount) {
                break;
            }
        }

        return array_values(array_unique($indexes));
    }

    /**
     * Build a deterministic attendance row for one work day.
     *
     * @return array<string, mixed>
     */
    private function buildAttendancePayload(
        EmployeeScheduleModel $schedule,
        Carbon $attendanceDate,
        int $workingDayIndex,
        int $userId
    ): array {
        $scheduledStartTime = $this->normalizeScheduleTime($schedule->start_time, '09:00:00');
        $scheduledEndTime = $this->normalizeScheduleTime($schedule->end_time, '18:00:00');

        $scheduledStart = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledStartTime, 'Asia/Manila');
        $scheduledEnd = Carbon::parse($attendanceDate->toDateString() . ' ' . $scheduledEndTime, 'Asia/Manila');

        $isLate = (($workingDayIndex + $userId) % 5) === 0;
        $isUndertime = (($workingDayIndex + $userId) % 7) === 0;

        $earlyArrivalMinutes = 5 + (($workingDayIndex + $userId) % 8);
        $lateMinutes = $isLate ? 10 + (($workingDayIndex + $userId) % 18) : 0;
        $undertimeMinutes = $isUndertime ? 15 + (($workingDayIndex + $userId) % 25) : 0;
        $overtimeMinutes = $isUndertime ? 0 : 15 + (($workingDayIndex + $userId) % 28);

        $checkInTime = $isLate
            ? $scheduledStart->copy()->addMinutes($lateMinutes)
            : $scheduledStart->copy()->subMinutes($earlyArrivalMinutes);
        $checkOutTime = $isUndertime
            ? $scheduledEnd->copy()->subMinutes($undertimeMinutes)
            : $scheduledEnd->copy()->addMinutes($overtimeMinutes);

        return [
            'scheduled_start_time' => $scheduledStart->format('H:i:s'),
            'scheduled_end_time' => $scheduledEnd->format('H:i:s'),
            'check_in_time' => $checkInTime->toDateTimeString(),
            'check_out_time' => $checkOutTime->toDateTimeString(),
            'check_in_status' => $isLate ? 'LATE' : 'ON_TIME',
            'check_out_status' => $isUndertime ? 'UNDERTIME' : 'ON_TIME',
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
        ];
    }

    /**
     * Seed completed April bookings for every active studio photographer.
     */
    private function seedCompletedPhotographerBookings(Carbon $periodStart, Carbon $periodEnd): void
    {
        $client = $this->resolveSeedClient();
        $photographerAssignments = StudioPhotographersModel::with(['photographer', 'studio'])
            ->where('status', 'active')
            ->orderBy('photographer_id')
            ->get();

        if ($photographerAssignments->isEmpty()) {
            $this->command?->warn('No active studio photographer assignments found. April completed bookings skipped.');
            return;
        }

        foreach ($photographerAssignments as $assignment) {
            $photographer = $assignment->photographer;
            $studio = $assignment->studio;

            if (!$photographer || !$studio) {
                continue;
            }

            $schedule = EmployeeScheduleModel::query()
                ->where('user_id', $photographer->id)
                ->where('studio_id', $studio->id)
                ->where('is_active', true)
                ->first();

            if (!$schedule) {
                $this->command?->warn("No active employee schedule found for photographer {$photographer->email}. Booking seeding skipped.");
                continue;
            }

            $workingDates = $this->getWorkingDates($schedule, $periodStart, $periodEnd);

            if (count($workingDates) < 4) {
                $this->command?->warn("Not enough April working dates found for photographer {$photographer->email}. Booking seeding skipped.");
                continue;
            }

            $bookingIndexes = [
                min(3, count($workingDates) - 1),
                min(10, count($workingDates) - 1),
                min(17, count($workingDates) - 1),
            ];
            $bookingIndexes = array_values(array_unique($bookingIndexes));

            foreach ($bookingIndexes as $sequence => $workingDayIndex) {
                $bookingDate = $workingDates[$workingDayIndex];
                $booking = $this->upsertCompletedBooking($assignment, $client, $bookingDate, $sequence + 1);

                BookingAssignedPhotographerModel::updateOrCreate(
                    [
                        'booking_id' => $booking->id,
                        'photographer_id' => $photographer->id,
                    ],
                    $this->buildCompletedAssignmentPayload($assignment, $bookingDate, $sequence + 1)
                );
            }

            $this->command?->info("Seeded April completed bookings: {$photographer->email}");
        }
    }

    /**
     * Ensure there is a reusable seeded client for April studio bookings.
     */
    private function resolveSeedClient(): UserModel
    {
        return UserModel::updateOrCreate(
            ['email' => 'seed.april.booking.client@lumora.test'],
            [
                'uuid' => UserModel::query()
                    ->where('email', 'seed.april.booking.client@lumora.test')
                    ->value('uuid') ?: (string) Str::uuid(),
                'role' => 'client',
                'user_type' => 'Customer',
                'first_name' => 'April',
                'middle_name' => 'Booking',
                'last_name' => 'Client',
                'mobile_number' => '+639179999901',
                'password' => Hash::make('Password@123'),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
            ]
        );
    }

    /**
     * Create or update one completed studio booking for a photographer.
     */
    private function upsertCompletedBooking(
        StudioPhotographersModel $assignment,
        UserModel $client,
        Carbon $bookingDate,
        int $sequence
    ): BookingModel {
        $studio = $assignment->studio;
        $photographer = $assignment->photographer;
        $bookingReference = sprintf(
            'SEED-APR-%d-%s-%d',
            $photographer->id,
            $bookingDate->format('Ymd'),
            $sequence
        );
        $totalAmount = round(6500 + (((int) ($assignment->years_of_experience ?? 1)) * 750) + ($sequence * 250), 2);
        $startTime = Carbon::parse($bookingDate->toDateString() . ' 10:00:00', 'Asia/Manila');
        $endTime = $startTime->copy()->addHours(3);
        $createdAt = $bookingDate->copy()->subDays(8)->setTime(11, 0);

        return BookingModel::updateOrCreate(
            ['booking_reference' => $bookingReference],
            [
                'client_id' => $client->id,
                'booking_type' => 'studio',
                'provider_id' => $studio->id,
                'category_id' => $studio->category_id,
                'event_name' => trim(($assignment->position ?? 'Studio Session') . ' April Shoot'),
                'event_date' => $bookingDate->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'location_type' => 'in-studio',
                'venue_name' => $studio->studio_name,
                'street' => $studio->street,
                'barangay' => $studio->barangay,
                'city' => optional($studio->location)->municipality ?? 'General Trias',
                'province' => optional($studio->location)->province ?? 'Cavite',
                'multiple_locations' => null,
                'special_requests' => 'Seeded April completed booking for photographer payroll testing.',
                'total_amount' => $totalAmount,
                'down_payment' => $totalAmount,
                'remaining_balance' => 0.00,
                'deposit_policy' => 'full_payment',
                'payment_type' => 'full_payment',
                'status' => 'completed',
                'payment_status' => 'paid',
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $bookingDate->copy()->setTime(16, 30)->toDateTimeString(),
                'deleted_at' => null,
            ]
        );
    }

    /**
     * Build the completed booking-assignment payload.
     *
     * @return array<string, mixed>
     */
    private function buildCompletedAssignmentPayload(
        StudioPhotographersModel $assignment,
        Carbon $bookingDate,
        int $sequence
    ): array {
        $assignedAt = $bookingDate->copy()->subDays(7)->setTime(9, 0);
        $confirmedAt = $assignedAt->copy()->addDay()->setTime(10, 0);
        $onSiteAt = $bookingDate->copy()->setTime(9, 45)->addMinutes($sequence * 3);
        $clientConfirmedAt = $onSiteAt->copy()->addMinutes(10);
        $startedAt = $clientConfirmedAt->copy()->addMinutes(5);
        $completedAt = $bookingDate->copy()->setTime(15, 30)->addMinutes($sequence * 7);

        return [
            'studio_id' => $assignment->studio_id,
            'assigned_by' => $assignment->owner_id,
            'status' => 'completed',
            'assignment_notes' => 'Seeded April completed assignment for photographer payroll testing.',
            'cancellation_reason' => null,
            'assigned_at' => $assignedAt->toDateTimeString(),
            'confirmed_at' => $confirmedAt->toDateTimeString(),
            'on_site_at' => $onSiteAt->toDateTimeString(),
            'client_confirmed_at' => $clientConfirmedAt->toDateTimeString(),
            'client_confirmation_notes' => 'Seeded client confirmation for completed photographer assignment.',
            'started_at' => $startedAt->toDateTimeString(),
            'completed_at' => $completedAt->toDateTimeString(),
            'cancelled_at' => null,
        ];
    }

    /**
     * Normalize schedule time values from model casts.
     */
    private function normalizeScheduleTime(mixed $value, string $fallback): string
    {
        if ($value instanceof Carbon) {
            return $value->format('H:i:s');
        }

        if (is_string($value) && trim($value) !== '') {
            return Carbon::parse($value)->format('H:i:s');
        }

        return $fallback;
    }
}
