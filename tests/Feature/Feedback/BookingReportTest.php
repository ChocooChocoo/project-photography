<?php

namespace Tests\Feature\Feedback;

use App\Http\Controllers\BookingReportController;
use App\Models\BookingModel;
use App\Models\UserModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingReportTest extends TestCase
{
    private UserModel $client;

    private UserModel $admin;

    private UserModel $owner;

    private BookingModel $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new \Tests\Feature\Booking\OwnerCompletionGateTest('test_completion_is_allowed_when_fully_paid_and_assignments_delivered');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        foreach (['client', 'admin', 'owner'] as $role) {
            $this->{$role} = UserModel::create(['role' => $role, 'first_name' => 'Report', 'last_name' => $role, 'email' => $role.'-report@example.test', 'status' => 'active', 'password' => 'unused']);
        }
        DB::table('tbl_studios')->insert(['id' => 1, 'user_id' => $this->owner->id, 'studio_name' => 'Report Studio', 'status' => 'verified']);
        $this->booking = BookingModel::create(['booking_reference' => 'REPORT', 'client_id' => $this->client->id, 'booking_type' => 'studio', 'provider_id' => 1, 'event_name' => 'Report event', 'event_date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:00', 'location_type' => 'in-studio', 'total_amount' => 100, 'down_payment' => 100, 'remaining_balance' => 0, 'payment_type' => 'full_payment', 'status' => 'completed', 'payment_status' => 'paid']);
        Route::post('/_report/{booking}', [BookingReportController::class, 'store']);
        Route::post('/_report/{report}/status', [BookingReportController::class, 'update']);
        Route::get('/_report/{report}/evidence', [BookingReportController::class, 'evidence'])->middleware(\App\Http\Middleware\AdminMiddleware::class);
        Route::get('/_reports', [BookingReportController::class, 'index']);
        Route::get('/_reports/{report}', [BookingReportController::class, 'show']);
        Route::get('/_feedback_page/{booking}', fn () => 'Feedback')->name('client.feedback.show');
        Route::get('/_owner_feedback', fn () => 'Feedback')->name('owner.feedback.index');
        Route::getRoutes()->refreshNameLookups();
        Storage::fake('local');
    }

    private function migrateReports(): void
    {
        (require database_path('migrations/2026_10_13_140000_add_booking_reports.php'))->up();
    }

    private function payload(): array
    {
        return ['target_type' => 'studio', 'target_id' => 1, 'reason' => 'Delivery concern', 'details' => 'The service did not match the agreed delivery terms.'];
    }

    public function test_reporting_route_is_installed_with_rate_limit(): void
    {
        $route = Route::getRoutes()->getByName('client.reports.store');
        $this->assertNotNull($route);
        $this->assertContains('throttle:5,1', $route->middleware());
    }

    public function test_report_requires_completed_booking_ownership_and_matching_target(): void
    {
        $this->migrateReports();
        $this->actingAs($this->owner)->postJson('/_report/'.$this->booking->id, $this->payload())->assertNotFound();
        $this->booking->update(['status' => 'in_progress']);
        $this->actingAs($this->client)->postJson('/_report/'.$this->booking->id, $this->payload())->assertNotFound();
        $this->booking->update(['status' => 'completed']);
        $this->postJson('/_report/'.$this->booking->id, array_replace($this->payload(), ['target_id' => 99]))->assertNotFound();
        $this->postJson('/_report/'.$this->booking->id, array_replace($this->payload(), ['target_type' => 'photographer', 'target_id' => 99]))->assertNotFound();
    }

    public function test_duplicate_active_report_is_blocked_until_admin_resolves_with_note(): void
    {
        $this->migrateReports();
        $report = $this->actingAs($this->client)->postJson('/_report/'.$this->booking->id, $this->payload())->assertCreated()->json('report.id');
        $this->postJson('/_report/'.$this->booking->id, $this->payload())->assertStatus(409);
        $this->postJson('/_report/'.$report.'/status', ['status' => 'resolved', 'resolution_note' => 'Reviewed'])->assertForbidden();
        $this->actingAs($this->admin)->postJson('/_report/'.$report.'/status', ['status' => 'resolved'])->assertUnprocessable();
        $this->postJson('/_report/'.$report.'/status', ['status' => 'reviewing'])->assertOk();
        $this->postJson('/_report/'.$report.'/status', ['status' => 'resolved', 'resolution_note' => 'The concern was reviewed and resolved.'])->assertOk();
        $this->assertDatabaseCount('tbl_notifications', 4);
        $this->postJson('/_report/'.$report.'/status', ['status' => 'resolved', 'resolution_note' => 'The concern was reviewed and resolved.'])->assertOk();
        $this->assertDatabaseCount('tbl_notifications', 4);
        $this->actingAs($this->client)->postJson('/_report/'.$this->booking->id, $this->payload())->assertCreated();
    }

    public function test_evidence_is_private_and_accessible_only_to_reporter_or_admin(): void
    {
        $this->migrateReports();
        $report = $this->actingAs($this->client)->post('/_report/'.$this->booking->id, $this->payload() + ['evidence' => UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated()->json('report.id');
        $path = DB::table('tbl_reports')->where('id', $report)->value('evidence');
        Storage::disk('local')->assertExists($path);
        $this->get(route('client.reports.evidence', $report))->assertOk()->assertDownload();
        $this->actingAs($this->owner)->get(route('client.reports.evidence', $report), ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($this->admin)->get('/_report/'.$report.'/evidence')->assertOk();
        $this->actingAs($this->client)->post('/_report/'.$this->booking->id, $this->payload() + ['evidence' => UploadedFile::fake()->create('script.php', 1)], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_admin_can_review_report_pages_and_nonadmin_cannot(): void
    {
        $this->migrateReports();
        $id = $this->actingAs($this->client)->postJson('/_report/'.$this->booking->id, $this->payload())->assertCreated()->json('report.id');
        $this->get('/_reports')->assertForbidden();
        $this->actingAs($this->admin)->get('/_reports')->assertOk()->assertSee('Delivery concern');
        $this->get('/_reports/'.$id)->assertOk()->assertSee('Update report')->assertSee('The service did not match');
    }

    public function test_failed_evidence_storage_does_not_create_a_report(): void
    {
        $this->migrateReports();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($this->client)->post('/_report/'.$this->booking->id, $this->payload() + ['evidence' => UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf')], ['Accept' => 'application/json'])->assertStatus(500);
        $this->assertDatabaseCount('tbl_reports', 0);
    }

    public function test_reports_migration_rolls_back_without_removing_bookings(): void
    {
        $this->migrateReports();
        (require database_path('migrations/2026_10_13_140000_add_booking_reports.php'))->down();
        $this->assertFalse(Schema::hasTable('tbl_reports'));
        $this->assertDatabaseHas('tbl_bookings', ['id' => $this->booking->id]);
    }
}
