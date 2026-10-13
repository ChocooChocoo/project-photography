<?php

namespace Tests\Feature\Booking;

use App\Http\Controllers\Freelancer\BookingController as FreelancerController;
use App\Http\Controllers\StudioOwner\BookingController as OwnerController;
use App\Models\BookingContractModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingReadinessEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $schema = new OwnerCompletionGateTest('test_completion_is_allowed_when_fully_paid_and_assignments_delivered');
        (new \ReflectionMethod($schema, 'createSchema'))->invoke($schema);
        Schema::create('tbl_booking_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->longText('terms_html');
            $table->timestamp('studio_signed_at')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Route::put('/test/readiness/owner/{id}', [OwnerController::class, 'updateStatus']);
        Route::put('/test/readiness/freelancer/{id}', [FreelancerController::class, 'updateStatus']);
    }

    public function test_long_term_confirmation_requires_both_contract_signatures_in_each_portal(): void
    {
        $client = $this->user('client');
        $owner = $this->user('owner');
        $freelancer = $this->user('freelancer');
        DB::table('tbl_studios')->insert(['id' => 1, 'user_id' => $owner->id, 'studio_name' => 'Readiness Studio', 'status' => 'verified']);
        foreach (['unsigned', 'client_only', 'provider_only', 'fully_signed'] as $state) {
            foreach (['studio', 'freelancer'] as $type) {
                $provider = $type === 'studio' ? $owner : $freelancer;
                $booking = BookingModel::create([
                    'booking_reference' => 'BK-READY-'.$type.'-'.$state, 'client_id' => $client->id,
                    'booking_type' => $type, 'provider_id' => $type === 'studio' ? 1 : $provider->id,
                    'start_time' => '09:00', 'end_time' => '17:00', 'location_type' => 'in-studio',
                    'total_amount' => 10000, 'down_payment' => 10000, 'remaining_balance' => 0,
                    'payment_type' => 'full_payment', 'payment_status' => 'pending', 'status' => 'pending',
                    'booking_duration_type' => 'long_term', 'event_name' => 'Readiness event',
                    'event_date' => '2026-12-20', 'event_end_date' => '2026-12-21', 'start_time' => '09:00', 'end_time' => '17:00',
                ]);
                BookingContractModel::create(['booking_id' => $booking->id, 'terms_html' => 'Coverage and delivery terms.',
                    'studio_signed_at' => in_array($state, ['provider_only', 'fully_signed'], true) ? now() : null,
                    'client_signed_at' => in_array($state, ['client_only', 'fully_signed'], true) ? now() : null]);
                $ready = $state === 'fully_signed';
                $this->assertSame($ready, $booking->fresh()->canBeConfirmed(), $type.' '.$state);
                $portal = $type === 'studio' ? 'owner' : 'freelancer';
                $this->actingAs($provider)->putJson('/test/readiness/'.$portal.'/'.$booking->id, ['status' => 'confirmed'])
                    ->assertStatus($ready ? 200 : ($type === 'studio' ? 422 : 400));
                $this->assertSame($ready ? 'confirmed' : 'pending', $booking->fresh()->status);
            }
        }
    }

    public function test_completion_remains_blocked_by_an_unpaid_balance_in_each_portal(): void
    {
        $client = $this->user('client');
        $owner = $this->user('owner');
        $freelancer = $this->user('freelancer');
        DB::table('tbl_studios')->insert(['id' => 1, 'user_id' => $owner->id, 'studio_name' => 'Readiness Studio', 'status' => 'verified']);
        foreach (['studio', 'freelancer'] as $type) {
            $provider = $type === 'studio' ? $owner : $freelancer;
            $booking = BookingModel::create(['booking_reference' => 'UNPAID-'.$type, 'client_id' => $client->id,
                'booking_type' => $type, 'provider_id' => $type === 'studio' ? 1 : $provider->id,
                'event_name' => 'Unpaid event', 'event_date' => now()->addDays(5), 'start_time' => '09:00', 'end_time' => '17:00',
                'location_type' => 'in-studio', 'total_amount' => 1000, 'down_payment' => 300, 'remaining_balance' => 700,
                'payment_type' => 'downpayment', 'payment_status' => 'partially_paid', 'status' => 'in_progress']);
            PaymentModel::create(['booking_id' => $booking->id, 'payment_reference' => 'PAY-'.$type, 'amount' => 300, 'payment_method' => 'fixture', 'status' => 'succeeded']);
            $portal = $type === 'studio' ? 'owner' : 'freelancer';
            $this->actingAs($provider)->putJson('/test/readiness/'.$portal.'/'.$booking->id, ['status' => 'completed'])
                ->assertStatus($type === 'studio' ? 403 : 400);
            $this->assertSame('in_progress', $booking->fresh()->status);
        }
    }

    private function user(string $role): UserModel
    {
        return UserModel::create(['role' => $role, 'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Readiness', 'last_name' => $role, 'email' => $role.'@example.test',
            'mobile_number' => '09170000000', 'password' => 'unused', 'status' => 'active', 'email_verified' => true]);
    }
}
