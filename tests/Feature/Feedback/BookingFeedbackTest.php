<?php

namespace Tests\Feature\Feedback;

use App\Http\Controllers\Client\BookingFeedbackController;
use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BookingFeedbackTest extends TestCase
{
    private UserModel $client;

    private BookingModel $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropAllTables();
        $fixture = new \Tests\Feature\Booking\OwnerCompletionGateTest('test_completion_is_allowed_when_fully_paid_and_assignments_delivered');
        (new \ReflectionMethod($fixture, 'createSchema'))->invoke($fixture);
        $this->client = UserModel::create(['role' => 'client', 'first_name' => 'Feedback', 'last_name' => 'Client', 'email' => 'feedback@example.test', 'password' => 'unused', 'status' => 'active']);
        $this->booking = BookingModel::create(['booking_reference' => 'FEEDBACK', 'client_id' => $this->client->id, 'booking_type' => 'studio', 'provider_id' => 1, 'event_name' => 'Feedback event', 'event_date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:00', 'location_type' => 'in-studio', 'total_amount' => 100, 'down_payment' => 100, 'remaining_balance' => 0, 'payment_type' => 'full_payment', 'payment_status' => 'paid', 'status' => 'completed']);
        DB::table('tbl_booking_packages')->insert(['booking_id' => $this->booking->id, 'package_id' => 10, 'package_type' => 'studio', 'package_name' => 'Portrait package', 'package_price' => 100]);
        Route::post('/_feedback/{booking}/{type}', [BookingFeedbackController::class, 'store']);
        Route::get('/_feedback/{booking}', [BookingFeedbackController::class, 'show']);
    }

    public function test_feedback_routes_and_schema_are_installed(): void
    {
        $this->assertNotNull(Route::getRoutes()->getByName('client.feedback.store'));
    }

    private function migrateFeedback(): void
    {
        (require database_path('migrations/2026_10_13_130000_add_booking_feedback.php'))->up();
    }

    public function test_client_can_rate_booked_package_once(): void
    {
        $this->migrateFeedback();
        $payload = ['target_id' => 10, 'rating' => 5, 'review_text' => 'The portrait package met our needs.'];
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/service', $payload)->assertCreated();
        $this->postJson('/_feedback/'.$this->booking->id.'/service', $payload)->assertStatus(409);
        $this->assertDatabaseHas('tbl_service_ratings', ['booking_id' => $this->booking->id, 'package_id' => 10, 'client_id' => $this->client->id, 'rating' => 5, 'status' => 'published']);
    }

    public function test_uncompleted_or_other_clients_cannot_submit_feedback(): void
    {
        $this->migrateFeedback();
        $this->booking->update(['status' => 'in_progress']);
        $payload = ['target_id' => 10, 'rating' => 4];
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/service', $payload)->assertNotFound();
        $this->booking->update(['status' => 'completed']);
        $other = $this->client->replicate();
        $other->email = 'other-feedback@example.test';
        $other->save();
        $this->actingAs($other)->postJson('/_feedback/'.$this->booking->id.'/service', $payload)->assertNotFound();
    }

    public function test_photographer_must_have_an_active_assignment_and_package_must_belong_to_booking(): void
    {
        $this->migrateFeedback();
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/service', ['target_id' => 99, 'rating' => 5])->assertNotFound();
        $assignment = BookingAssignedPhotographerModel::create(['booking_id' => $this->booking->id, 'studio_id' => 1, 'photographer_id' => 7, 'assigned_by' => 1, 'status' => 'cancelled']);
        $this->postJson('/_feedback/'.$this->booking->id.'/photographer', ['target_id' => 7, 'rating' => 5])->assertNotFound();
        $assignment->update(['status' => 'completed']);
        $this->postJson('/_feedback/'.$this->booking->id.'/photographer', ['target_id' => 7, 'rating' => 5])->assertCreated();
        $this->postJson('/_feedback/'.$this->booking->id.'/photographer', ['target_id' => 7, 'rating' => 5])->assertStatus(409);
    }

    public function test_photo_feedback_requires_approved_published_booking_gallery(): void
    {
        $this->migrateFeedback();
        $id = DB::table('tbl_studio_online_gallery')->insertGetId(['booking_id' => $this->booking->id, 'studio_id' => 1, 'client_id' => $this->client->id, 'gallery_status' => 'draft', 'approval_status' => 'approved', 'status' => 'active']);
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/photo', ['target_id' => $id, 'rating' => 5])->assertNotFound();
        DB::table('tbl_studio_online_gallery')->where('id', $id)->update(['gallery_status' => 'published']);
        $this->postJson('/_feedback/'.$this->booking->id.'/photo', ['target_id' => $id, 'review_text' => 'The edits are exactly what we requested.'])->assertCreated();
        $this->assertDatabaseHas('tbl_photo_ratings', ['gallery_id' => $id, 'gallery_type' => 'studio', 'rating' => null]);
    }

    public function test_rating_validation_and_rollback_preserve_booking_data(): void
    {
        $this->migrateFeedback();
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/service', ['target_id' => 10, 'rating' => 6])->assertUnprocessable();
        $this->postJson('/_feedback/'.$this->booking->id.'/photo', ['target_id' => 1])->assertUnprocessable();
        (require database_path('migrations/2026_10_13_130000_add_booking_feedback.php'))->down();
        $this->assertFalse(Schema::hasTable('tbl_service_ratings'));
        $this->assertDatabaseHas('tbl_bookings', ['id' => $this->booking->id]);
    }

    public function test_stored_feedback_aggregate_excludes_flagged_and_removed_ratings(): void
    {
        $this->migrateFeedback();
        $this->actingAs($this->client)->postJson('/_feedback/'.$this->booking->id.'/service', ['target_id' => 10, 'rating' => 5])->assertCreated();
        $rating = \App\Models\ServiceRatingModel::firstOrFail();
        $this->assertDatabaseHas('tbl_feedback_aggregates', ['target_type' => 'service', 'target_scope' => 'studio', 'target_id' => 10, 'avg_rating' => 5, 'total_reviews' => 1]);
        $rating->update(['status' => 'flagged']);
        $this->assertDatabaseHas('tbl_feedback_aggregates', ['target_type' => 'service', 'target_scope' => 'studio', 'target_id' => 10, 'avg_rating' => 0, 'total_reviews' => 0]);
        $rating->update(['status' => 'published']);
        $this->assertDatabaseHas('tbl_feedback_aggregates', ['target_id' => 10, 'avg_rating' => 5, 'total_reviews' => 1]);
    }

    public function test_feedback_page_exposes_only_this_clients_completed_booking_targets(): void
    {
        $this->migrateFeedback();
        (require database_path('migrations/2026_10_13_140000_add_booking_reports.php'))->up();
        $this->actingAs($this->client)->get('/_feedback/'.$this->booking->id)->assertOk()->assertSee('Portrait package')->assertSee('Submit feedback')->assertSee('Report a concern');
        $this->booking->update(['status' => 'in_progress']);
        $this->get('/_feedback/'.$this->booking->id)->assertNotFound();
    }

    public function test_owner_inbox_is_scoped_to_owned_bookings_and_feedback_moderation_refreshes_aggregate(): void
    {
        $this->migrateFeedback();
        Schema::create('tbl_studio_ratings', function ($table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('client_id');
            $table->integer('rating');
            $table->text('review_text');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('tbl_roles', function ($table) {
            $table->id();
            $table->string('status');
            $table->string('portal');
            $table->softDeletes();
        });
        Schema::create('tbl_user_roles', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('studio_id')->nullable();
            $table->timestamps();
        });
        $owner = UserModel::create(['role' => 'owner', 'first_name' => 'Feedback', 'last_name' => 'Owner', 'email' => 'feedback-owner@example.test', 'status' => 'active']);
        DB::table('tbl_studios')->insert(['id' => 1, 'user_id' => $owner->id, 'studio_name' => 'Owned Studio', 'status' => 'verified']);
        $review = \App\Models\ServiceRatingModel::create(['booking_id' => $this->booking->id, 'package_id' => 10, 'package_type' => 'studio', 'client_id' => $this->client->id, 'rating' => 5, 'review_text' => 'Visible owned feedback', 'status' => 'published']);
        Route::get('/_owner_feedback', [\App\Http\Controllers\StudioOwner\FeedbackController::class, 'index']);
        $this->actingAs($owner)->get('/_owner_feedback')->assertOk()->assertSee('Visible owned feedback');
        DB::table('tbl_studios')->where('id', 1)->update(['user_id' => 999]);
        $this->get('/_owner_feedback')->assertOk()->assertDontSee('Visible owned feedback');
        $admin = $owner->replicate();
        $admin->email = 'feedback-admin@example.test';
        $admin->role = 'admin';
        $admin->save();
        $this->actingAs($admin)->postJson(route('admin.reviews.flag', ['service', $review->id]))->assertOk();
        $this->assertDatabaseHas('tbl_feedback_aggregates', ['target_type' => 'service', 'target_id' => 10, 'total_reviews' => 0]);
    }
}
