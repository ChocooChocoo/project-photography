<?php

namespace Tests\Feature\StudioEmployee;

use App\Models\LeaveRequestModel;
use App\Models\OvertimeRequestModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Rules\NoOverlappingTimeOff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Client test C-26: the same photographer filed the same leave dates twice (and
 * overlapping dates) and every request was accepted.
 */
class TimeOffOverlapTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $staff;

    private int $studioId;

    protected function setUp(): void
    {
        parent::setUp();
        $owner = UserModel::create(['role' => 'owner', 'user_type' => 'photographer', 'first_name' => 'O', 'last_name' => 'W', 'email' => 'o@example.com', 'mobile_number' => '09170000001', 'password' => 'x', 'status' => 'active', 'email_verified' => true]);
        $this->studioId = StudiosModel::create(['user_id' => $owner->id, 'studio_name' => 'S', 'status' => 'verified', 'year_established' => 2020, 'studio_description' => 'Test studio.', 'starting_price' => 1000])->id;
        $this->staff = UserModel::create(['role' => 'studio-photographer', 'user_type' => 'photographer', 'first_name' => 'P', 'last_name' => 'H', 'email' => 'p@example.com', 'mobile_number' => '09170000002', 'password' => 'x', 'status' => 'active', 'email_verified' => true]);
        $this->actingAs($this->staff);
    }

    private function leave(string $start, string $end, string $status = 'pending'): LeaveRequestModel
    {
        return LeaveRequestModel::create(['request_reference' => 'LR-'.uniqid(), 'studio_id' => $this->studioId, 'user_id' => $this->staff->id, 'leave_type' => 'vacation_leave', 'start_date' => $start, 'end_date' => $end, 'total_days' => 1, 'reason' => 'Family trip, three days.', 'status' => $status]);
    }

    private function passes(array $data, NoOverlappingTimeOff $rule, string $field): bool
    {
        return Validator::make($data, [$field => [$rule]])->passes();
    }

    public function test_overlapping_leave_is_refused_but_a_rejected_one_or_the_same_request_is_not(): void
    {
        $first = $this->leave('2026-12-01', '2026-12-03');

        $this->assertFalse($this->passes(['start_date' => '2026-12-01', 'end_date' => '2026-12-03'], NoOverlappingTimeOff::leave(), 'start_date'));
        $this->assertFalse($this->passes(['start_date' => '2026-12-03', 'end_date' => '2026-12-05'], NoOverlappingTimeOff::leave(), 'start_date'));
        $this->assertTrue($this->passes(['start_date' => '2026-12-04', 'end_date' => '2026-12-05'], NoOverlappingTimeOff::leave(), 'start_date'));
        // Editing the same request is not a clash with itself.
        $this->assertTrue($this->passes(['start_date' => '2026-12-01', 'end_date' => '2026-12-02'], NoOverlappingTimeOff::leave($first->id), 'start_date'));

        $first->update(['status' => 'rejected']);
        $this->assertTrue($this->passes(['start_date' => '2026-12-01', 'end_date' => '2026-12-03'], NoOverlappingTimeOff::leave(), 'start_date'));
    }

    public function test_overlapping_overtime_hours_are_refused(): void
    {
        OvertimeRequestModel::create(['request_reference' => 'OT-'.uniqid(), 'studio_id' => $this->studioId, 'user_id' => $this->staff->id, 'overtime_date' => '2026-12-01', 'start_time' => '18:00', 'end_time' => '20:00', 'total_hours' => 2, 'reason' => 'Event wrap-up and backup.', 'status' => 'approved']);

        $this->assertFalse($this->passes(['overtime_date' => '2026-12-01', 'start_time' => '19:00', 'end_time' => '21:00'], NoOverlappingTimeOff::overtime(), 'start_time'));
        $this->assertTrue($this->passes(['overtime_date' => '2026-12-01', 'start_time' => '20:00', 'end_time' => '22:00'], NoOverlappingTimeOff::overtime(), 'start_time'));
        $this->assertTrue($this->passes(['overtime_date' => '2026-12-02', 'start_time' => '18:00', 'end_time' => '20:00'], NoOverlappingTimeOff::overtime(), 'start_time'));
    }

    public function test_every_leave_and_overtime_form_uses_the_rule(): void
    {
        foreach (['Finance', 'StudioHR', 'StudioPhotographer'] as $portal) {
            foreach (['Store', 'Update'] as $kind) {
                foreach (['Leave', 'Overtime'] as $type) {
                    $class = "App\\Http\\Requests\\{$portal}\\{$kind}{$type}Request";
                    $rules = collect((new $class)->rules())->flatten()->filter(fn ($r) => $r instanceof NoOverlappingTimeOff);
                    $this->assertCount(1, $rules, $class);
                }
            }
        }
    }
}
