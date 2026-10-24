<?php

namespace Tests\Feature\Booking;

use App\Models\BookingModel;
use App\Models\BookingRescheduleRequestModel;
use App\Models\NotificationModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\PermissionModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T02 enhancement 3: a client asks for a new date or time; the owner or
 * freelancer approves, rejects or offers other dates after an availability
 * check; the client is notified of every response.
 */
class RescheduleRequestTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $owner;

    private UserModel $client;

    private StudiosModel $studio;

    private BookingModel $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-02 09:00:00');

        $this->owner = $this->user('owner', 'reschedule-owner@example.com');
        $this->client = $this->user('client', 'reschedule-client@example.com');
        $this->studio = $this->studio($this->owner, 'Reschedule Studio');
        $this->booking = $this->booking($this->studio, '2026-11-20', '10:00', '13:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_client_requests_a_new_time_and_the_owner_is_notified(): void
    {
        $this->actingAs($this->client)
            ->postJson(route('client.booking.reschedule.store', $this->booking->id), [
                'requested_date' => '2026-11-27', 'start_time' => '14:00', 'end_time' => '17:00',
                'reason' => 'The venue moved our reception by one week.',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $request = BookingRescheduleRequestModel::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame('2026-11-27', $request->requested_date->toDateString());
        $this->assertSame(1, NotificationModel::where('user_id', $this->owner->id)->where('type', 'reschedule_requested')->count());

        // One open request per booking.
        $this->postJson(route('client.booking.reschedule.store', $this->booking->id), [
            'requested_date' => '2026-11-28', 'start_time' => '14:00', 'end_time' => '17:00',
        ])->assertStatus(422)->assertJsonPath('message', 'This booking already has a reschedule request waiting for an answer.');

        // The client sees it on the booking.
        $this->get(route('client.booking.reschedule.show', $this->booking->id))
            ->assertOk()->assertSee('Waiting for the studio to answer')->assertSee('Nov 27, 2026');
    }

    public function test_requests_are_refused_for_past_or_closed_bookings_and_other_clients(): void
    {
        $other = $this->user('client', 'reschedule-other@example.com');
        $this->actingAs($other)
            ->postJson(route('client.booking.reschedule.store', $this->booking->id), ['requested_date' => '2026-11-27', 'start_time' => '14:00', 'end_time' => '17:00'])
            ->assertNotFound();

        $this->actingAs($this->client)
            ->postJson(route('client.booking.reschedule.store', $this->booking->id), ['requested_date' => '2026-11-01', 'start_time' => '14:00', 'end_time' => '17:00'])
            ->assertStatus(422)->assertJsonValidationErrors('requested_date');

        $this->booking->update(['status' => 'cancelled']);
        $this->postJson(route('client.booking.reschedule.store', $this->booking->id), ['requested_date' => '2026-11-27', 'start_time' => '14:00', 'end_time' => '17:00'])
            ->assertStatus(422)->assertJsonPath('message', 'Only pending or confirmed bookings can be rescheduled.');
    }

    public function test_owner_approves_and_the_booking_moves_and_everyone_is_told(): void
    {
        $photographer = $this->user('studio-photographer', 'reschedule-photo@example.com');
        BookingAssignedPhotographerModel::create(['booking_id' => $this->booking->id, 'studio_id' => $this->studio->id, 'photographer_id' => $photographer->id, 'assigned_by' => $this->owner->id, 'status' => 'confirmed', 'assigned_at' => now()]);
        $request = $this->pendingRequest('2026-11-27', '14:00', '17:00');

        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.approve', $request->id))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->booking->refresh();
        $this->assertSame('2026-11-27', $this->booking->event_date->toDateString());
        $this->assertSame('14:00', substr($this->booking->start_time, 0, 5));
        $this->assertSame('17:00', substr($this->booking->end_time, 0, 5));
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->owner->id, $request->fresh()->responded_by);
        $this->assertSame(1, NotificationModel::where('user_id', $this->client->id)->where('type', 'reschedule_approved')->whereNull('read_at')->count());
        $this->assertSame(1, NotificationModel::where('user_id', $photographer->id)->where('type', 'booking_rescheduled')->count());
    }

    public function test_approving_into_a_taken_slot_or_busy_photographer_fails_and_changes_nothing(): void
    {
        $this->booking($this->studio, '2026-11-27', '13:00', '16:00');
        $request = $this->pendingRequest('2026-11-27', '14:00', '17:00');

        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.approve', $request->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Another booking already uses Nov 27, 2026 2:00 PM to 5:00 PM.');
        $this->assertSame('2026-11-20', $this->booking->fresh()->event_date->toDateString());
        $this->assertSame('pending', $request->fresh()->status);

        // A photographer assigned to this booking who is busy elsewhere at the new time.
        $photographer = $this->user('studio-photographer', 'reschedule-busy@example.com');
        BookingAssignedPhotographerModel::create(['booking_id' => $this->booking->id, 'studio_id' => $this->studio->id, 'photographer_id' => $photographer->id, 'assigned_by' => $this->owner->id, 'status' => 'confirmed', 'assigned_at' => now()]);
        $otherStudio = $this->studio($this->owner, 'Second Studio');
        $elsewhere = $this->booking($otherStudio, '2026-11-26', '09:00', '12:00');
        BookingAssignedPhotographerModel::create(['booking_id' => $elsewhere->id, 'studio_id' => $otherStudio->id, 'photographer_id' => $photographer->id, 'assigned_by' => $this->owner->id, 'status' => 'confirmed', 'assigned_at' => now()]);
        $request->update(['requested_date' => '2026-11-26', 'start_time' => '10:00', 'end_time' => '13:00']);

        $this->postJson(route('owner.booking.reschedule.approve', $request->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Reschedule User is not available at the new time.');
    }

    public function test_owner_offers_other_dates_and_the_client_accepts_one(): void
    {
        $request = $this->pendingRequest('2026-11-27', '14:00', '17:00');

        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.offer', $request->id), [
                'alternatives' => [
                    ['date' => '2026-11-25', 'start_time' => '09:00', 'end_time' => '12:00'],
                    ['date' => '2026-11-26', 'start_time' => '13:00', 'end_time' => '16:00'],
                ],
                'response_note' => 'The 27th is fully booked.',
            ])
            ->assertOk();

        $this->assertSame('alternatives_offered', $request->fresh()->status);
        $notice = NotificationModel::where('user_id', $this->client->id)->where('type', 'reschedule_alternatives')->firstOrFail();
        $this->assertStringContainsString('Nov 25, 2026 9:00 AM to 12:00 PM', $notice->message);
        $this->assertCount(2, $notice->data['alternatives']);

        $this->actingAs($this->client)
            ->get(route('client.booking.reschedule.show', $this->booking->id))
            ->assertOk()->assertSee('The 27th is fully booked.')->assertSee('Choose Nov 26, 2026 1:00 PM to 4:00 PM');

        $this->postJson(route('client.booking.reschedule.accept', $request->id), ['alternative' => 1])->assertOk();

        $this->assertSame('2026-11-26', $this->booking->fresh()->event_date->toDateString());
        $this->assertSame('accepted', $request->fresh()->status);
        $this->assertSame(1, NotificationModel::where('user_id', $this->owner->id)->where('type', 'reschedule_accepted')->count());

        // An unavailable offer is refused up front.
        $this->booking($this->studio, '2026-11-30', '09:00', '12:00');
        $next = $this->pendingRequest('2026-12-01', '09:00', '12:00');
        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.offer', $next->id), ['alternatives' => [['date' => '2026-11-30', 'start_time' => '10:00', 'end_time' => '11:00']]])
            ->assertStatus(422)->assertJsonPath('message', 'Another booking already uses Nov 30, 2026 10:00 AM to 11:00 AM.');
    }

    public function test_owner_rejects_with_a_note_and_other_owners_cannot_answer(): void
    {
        $request = $this->pendingRequest('2026-11-27', '14:00', '17:00');

        $stranger = $this->user('owner', 'reschedule-stranger@example.com');
        $this->subscribe($this->studio($stranger, 'Stranger Studio'));
        $this->actingAs($stranger)->postJson(route('owner.booking.reschedule.reject', $request->id))->assertNotFound();

        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.reject', $request->id), ['response_note' => 'We cannot move this date.'])
            ->assertOk();

        $this->assertSame('rejected', $request->fresh()->status);
        $notice = NotificationModel::where('user_id', $this->client->id)->where('type', 'reschedule_rejected')->firstOrFail();
        $this->assertStringContainsString('We cannot move this date.', $notice->message);
        $this->assertSame('2026-11-20', $this->booking->fresh()->event_date->toDateString());
    }

    public function test_closed_days_are_refused(): void
    {
        $locationId = \Illuminate\Support\Facades\DB::table('tbl_locations')->insertGetId(['municipality' => 'Dasmarinas', 'barangay' => '[]', 'zip_code' => '4114', 'created_at' => now(), 'updated_at' => now()]);
        \App\Models\StudioOwner\StudioScheduleModel::create([
            'studio_id' => $this->studio->id, 'location_id' => $locationId, 'operating_days' => ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'opening_time' => '09:00', 'closing_time' => '18:00', 'booking_limit' => 5, 'advance_booking' => 1,
        ]);
        $request = $this->pendingRequest('2026-11-23', '10:00', '13:00'); // a Monday

        $this->actingAs($this->owner)
            ->postJson(route('owner.booking.reschedule.approve', $request->id))
            ->assertStatus(422)
            ->assertJsonPath('message', 'The studio does not work on Mondays.');
    }

    public function test_freelancer_answers_requests_for_their_own_bookings(): void
    {
        $freelancer = $this->user('freelancer', 'reschedule-freelancer@example.com');
        $booking = BookingModel::create([
            'booking_reference' => 'RS-FREELANCE', 'client_id' => $this->client->id, 'booking_type' => 'freelancer',
            'provider_id' => $freelancer->id, 'event_name' => 'Freelance event', 'event_date' => '2026-11-20',
            'start_time' => '10:00', 'end_time' => '12:00', 'location_type' => 'on-location', 'total_amount' => 3000,
            'down_payment' => 3000, 'remaining_balance' => 0, 'payment_type' => 'full_payment', 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);

        $this->actingAs($this->client)
            ->postJson(route('client.booking.reschedule.store', $booking->id), ['requested_date' => '2026-11-21', 'start_time' => '15:00', 'end_time' => '17:00'])
            ->assertOk();
        $this->assertSame(1, NotificationModel::where('user_id', $freelancer->id)->where('type', 'reschedule_requested')->count());
        $request = BookingRescheduleRequestModel::where('booking_id', $booking->id)->firstOrFail();

        // The studio owner cannot answer a freelancer's booking.
        $this->actingAs($this->owner)->postJson(route('owner.booking.reschedule.approve', $request->id))->assertNotFound();

        $this->actingAs($freelancer)->get(route('freelancer.booking.reschedule.show', $booking->id))->assertOk()->assertSee('Approve new time');
        $this->postJson(route('freelancer.booking.reschedule.approve', $request->id))->assertOk();
        $this->assertSame('2026-11-21', $booking->fresh()->event_date->toDateString());
    }

    private function pendingRequest(string $date, string $start, string $end): BookingRescheduleRequestModel
    {
        return BookingRescheduleRequestModel::create([
            'booking_id' => $this->booking->id, 'requested_by' => $this->client->id,
            'requested_date' => $date, 'start_time' => $start, 'end_time' => $end, 'status' => 'pending',
        ]);
    }

    private function user(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role, 'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Reschedule', 'last_name' => 'User', 'email' => $email,
            'mobile_number' => '09170000020', 'password' => 'secret', 'status' => 'active',
            'email_verified' => true, 'onboarding_completed_at' => now(),
        ]);
    }

    private function studio(UserModel $owner, string $name): StudiosModel
    {
        $studio = StudiosModel::create([
            'user_id' => $owner->id, 'studio_name' => $name, 'year_established' => 2020,
            'studio_description' => 'Reschedule test studio.', 'starting_price' => 1500, 'status' => 'verified',
        ]);
        $this->subscribe($studio);
        $role = RoleModel::firstOrCreate(['name' => 'owner-bookings-'.$owner->id], ['portal' => 'owner', 'status' => 'active']);
        $permission = PermissionModel::firstOrCreate(['name' => 'owner.bookings.manage'], ['permission_string' => 'owner.bookings.manage', 'portal' => 'owner', 'status' => 'active']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $owner->assignRole($role, $studio->id);

        return $studio;
    }

    private function subscribe(StudiosModel $studio): void
    {
        $plan = SubscriptionPlanModel::firstOrCreate(['plan_code' => 'STU_RESCHEDULE'], [
            'user_type' => 'studio', 'plan_type' => 'basic', 'billing_cycle' => 'monthly', 'name' => 'Basic',
            'price' => 590, 'commission_rate' => 5, 'trial_days' => 0, 'priority_level' => 1, 'status' => 'active',
        ]);
        StudioPlanModel::create([
            'studio_id' => $studio->id, 'plan_id' => $plan->id, 'subscription_reference' => 'SUB-'.str()->upper(str()->random(10)),
            'start_date' => now()->subDays(5)->toDateString(), 'end_date' => now()->addDays(60)->toDateString(),
            'next_billing_date' => now()->addDays(60)->toDateString(), 'amount_paid' => 590, 'payment_status' => 'paid', 'status' => 'active',
        ]);
    }

    private function booking(StudiosModel $studio, string $date, string $start, string $end): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'RS-'.str()->upper(str()->random(8)), 'client_id' => $this->client->id,
            'booking_type' => 'studio', 'provider_id' => $studio->id, 'event_name' => 'Reschedule event',
            'event_date' => $date, 'start_time' => $start, 'end_time' => $end, 'location_type' => 'in-studio',
            'total_amount' => 5000, 'down_payment' => 1500, 'remaining_balance' => 3500, 'payment_type' => 'downpayment',
            'status' => 'confirmed', 'payment_status' => 'partially_paid',
        ]);
    }
}
