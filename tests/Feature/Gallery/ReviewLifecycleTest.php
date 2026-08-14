<?php

namespace Tests\Feature\Gallery;

use App\Http\Controllers\Client\StudioRatingController;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioRatingModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewLifecycleTest extends TestCase
{
    private UserModel $owner;
    private StudiosModel $studio;
    private UserModel $client;
    private BookingModel $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();
        $this->createSchema();
        $this->owner = $this->createUser('owner', 'review-owner@example.com');
        $this->studio = $this->createStudio();
        $this->client = $this->createUser('client', 'review-client@example.com');
        $this->booking = $this->createCompletedBooking();

        Route::post('/_test/client/review/store', [StudioRatingController::class, 'store']);
        Route::get('/_test/client/review/can-review/{booking}', [StudioRatingController::class, 'checkCanReview']);
    }

    public function test_client_can_review_completed_booking_and_aggregates_update(): void
    {
        $this->actingAs($this->client)
            ->post('/_test/client/review/store', [
                'booking_id' => $this->booking->id,
                'rating' => 5,
                'review_text' => 'Amazing experience, the photos turned out beautifully!',
                'is_recommend' => 1,
            ])->assertOk()->assertJsonPath('success', true);

        $review = StudioRatingModel::where('booking_id', $this->booking->id)->firstOrFail();
        $this->assertSame(5, $review->rating);
        $this->assertSame('positive', $review->review_type);
        $this->assertSame('published', $review->status);
        $this->assertSame($this->studio->id, $review->studio_id);

        $this->studio->refresh();
        $this->assertSame(5.0, (float) $this->studio->avg_rating);
        $this->assertSame(1, $this->studio->total_reviews);
    }

    public function test_can_review_is_false_after_review_and_duplicate_is_rejected(): void
    {
        $this->assertTrue(StudioRatingModel::canReview($this->booking->id, $this->client->id));

        $this->actingAs($this->client)
            ->getJson("/_test/client/review/can-review/{$this->booking->id}")
            ->assertOk()
            ->assertJsonPath('can_review', true);

        StudioRatingModel::create([
            'booking_id' => $this->booking->id,
            'client_id' => $this->client->id,
            'studio_id' => $this->studio->id,
            'rating' => 4,
            'review_text' => 'Great photos and very professional service.',
            'review_type' => 'positive',
            'is_recommend' => true,
        ]);

        $this->assertFalse(StudioRatingModel::canReview($this->booking->id, $this->client->id));

        $this->actingAs($this->client)
            ->getJson("/_test/client/review/can-review/{$this->booking->id}")
            ->assertOk()
            ->assertJsonPath('can_review', false);

        $this->actingAs($this->client)
            ->post('/_test/client/review/store', [
                'booking_id' => $this->booking->id,
                'rating' => 5,
                'review_text' => 'A second attempt to review the same booking.',
                'is_recommend' => 1,
            ])->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You have already reviewed this booking.');

        $this->assertSame(1, StudioRatingModel::count());
    }

    private function createUser(string $role, string $email): UserModel
    {
        return UserModel::create([
            'role' => $role,
            'user_type' => $role === 'client' ? 'customer' : 'photographer',
            'first_name' => 'Review',
            'last_name' => 'User',
            'email' => $email,
            'mobile_number' => '09170000004',
            'password' => 'secret',
            'status' => 'active',
            'email_verified' => true,
        ]);
    }

    private function createStudio(): StudiosModel
    {
        return StudiosModel::create([
            'user_id' => $this->owner->id,
            'studio_name' => 'Review Studio',
            'status' => 'verified',
        ]);
    }

    private function createCompletedBooking(): BookingModel
    {
        return BookingModel::create([
            'booking_reference' => 'BK-'.str()->upper(str()->random(10)),
            'client_id' => $this->client->id,
            'booking_type' => 'studio',
            'provider_id' => $this->studio->id,
            'event_name' => 'Test Event',
            'event_date' => '2026-08-10',
            'total_amount' => 1000,
            'down_payment' => 0,
            'payment_type' => 'full_payment',
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('role');
            $table->string('user_type')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('studio_name');
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->string('status');
            
            $table->softDeletes();$table->timestamps();
        });
        Schema::create('tbl_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->foreignId('client_id');
            $table->string('booking_type');
            $table->unsignedBigInteger('provider_id');
            $table->string('event_name');
            $table->date('event_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('down_payment', 10, 2);
            $table->string('payment_type');
            $table->string('status');
            $table->string('payment_status');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('tbl_studio_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('review_text');
            $table->enum('review_type', ['positive', 'neutral', 'negative'])->nullable();
            $table->string('preset_used')->nullable();
            $table->boolean('is_recommend')->default(true);
            $table->enum('status', ['published', 'flagged', 'removed'])->default('published');
            $table->timestamps();
        });
    }
}
