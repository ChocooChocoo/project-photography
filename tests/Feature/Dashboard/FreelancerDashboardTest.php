<?php

namespace Tests\Feature\Dashboard;

use App\Http\Controllers\Freelancer\DashboardController;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Gallery\PhotographerGalleryPublishTest;
use Tests\TestCase;

class FreelancerDashboardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new PhotographerGalleryPublishTest('test_assigned_photographer_cannot_publish_while_approval_is_pending');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        Schema::create('tbl_payments', function ($table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->string('payment_reference');
            $table->decimal('amount', 10, 2);
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    private function booking(int $provider, string $type = 'freelancer', int $client = 100): BookingModel
    {
        return BookingModel::create(['client_id' => $client, 'provider_id' => $provider, 'booking_type' => $type, 'booking_reference' => uniqid('DASH-'), 'event_name' => 'Test', 'event_date' => '2026-11-04', 'total_amount' => 1000, 'down_payment' => 300, 'payment_type' => 'downpayment', 'status' => 'confirmed', 'payment_status' => 'unpaid']);
    }

    private function summary(int $id): array
    {
        $user = UserModel::create(['first_name' => 'Test', 'last_name' => 'Freelancer', 'email' => "dashboard-{$id}@example.test", 'mobile_number' => '09123456789', 'password' => 'test', 'role' => 'freelancer', 'status' => 'active']);
        $request = Request::create('/freelancer/dashboard');
        $request->setUserResolver(fn () => $user);
        $this->actingAs($user);

        return app(DashboardController::class)->index($request)->getData()['summary'] ?? [];
    }

    public function test_empty_account_has_real_zero_values(): void
    {
        $this->assertSame(['payments_received' => 0.0, 'bookings' => 0, 'clients' => 0, 'refunds_pending' => 0], $this->summary(1));
    }

    public function test_metrics_exclude_other_providers_studio_bookings_failed_payments_and_deleted_bookings(): void
    {
        $one = $this->booking(1);
        $two = $this->booking(1);
        $two->update(['payment_status' => 'refund_pending']);
        $this->booking(1, 'freelancer', 101);
        $other = $this->booking(2);
        $studio = $this->booking(1, 'studio');
        $deleted = $this->booking(1);
        foreach ([[$one, 'succeeded', 800, 100], [$two, 'succeeded', 300, 0], [$one, 'failed', 999, 0], [$other, 'succeeded', 999, 0], [$studio, 'succeeded', 999, 0], [$deleted, 'succeeded', 999, 0]] as [$booking, $status, $amount, $refund]) {
            PaymentModel::create(['booking_id' => $booking->id, 'payment_reference' => uniqid('PAY-'), 'status' => $status, 'amount' => $amount, 'refunded_amount' => $refund]);
        }
        $deleted->delete();

        $this->assertSame(['payments_received' => 1000.0, 'bookings' => 3, 'clients' => 2, 'refunds_pending' => 1], $this->summary(1));
    }

    public function test_dashboard_template_has_no_demo_dollar_metrics(): void
    {
        $source = file_get_contents(resource_path('views/freelancer/dashboard.blade.php'));
        $this->assertStringNotContainsString('data-target=', $source);
        $this->assertStringContainsString('₱', $source);
    }
}
