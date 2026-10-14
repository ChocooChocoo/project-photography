<?php

namespace Tests\Feature\Client;

use App\Models\Admin\CategoriesModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\StudioRatingModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudioProfileTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $client;

    private StudiosModel $studio;

    protected function setUp(): void
    {
        parent::setUp();
        // The existing portfolio migration makes these nullable only on MySQL.
        Schema::table('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            $table->unsignedBigInteger('client_id')->nullable()->change();
        });
        Storage::fake('public');
        $this->client = UserModel::create([
            'role' => 'client', 'first_name' => 'Gallery', 'last_name' => 'Client',
            'email' => 'gallery-client@example.test', 'mobile_number' => '09170000001',
            'password' => 'unused', 'status' => 'active', 'email_verified' => true,
        ]);
        $this->studio = StudiosModel::create([
            'user_id' => $this->client->id, 'studio_name' => 'Portfolio Studio',
            'year_established' => 2020, 'studio_description' => 'Studio profile description.',
            'status' => 'verified', 'starting_price' => 1500,
        ]);
        $plan = SubscriptionPlanModel::create([
            'user_type' => 'studio', 'plan_type' => 'basic', 'billing_cycle' => 'monthly',
            'plan_code' => 'PROFILE', 'name' => 'Profile plan', 'price' => 590,
            'commission_rate' => 5, 'trial_days' => 0, 'status' => 'active',
        ]);
        StudioPlanModel::create([
            'studio_id' => $this->studio->id, 'plan_id' => $plan->id,
            'subscription_reference' => 'PROFILE-SUB', 'start_date' => today(),
            'end_date' => today()->addMonth(), 'next_billing_date' => today()->addMonth(), 'amount_paid' => 590,
            'payment_status' => 'paid', 'status' => 'active',
        ]);
    }

    public function test_client_profile_shows_only_active_portfolio_images(): void
    {
        $this->gallery('Portfolio work', 'portfolio', 'active', 'portfolio/visible.jpg');
        $this->gallery('Private booking', 'booking', 'active', 'booking/private.jpg');
        $this->gallery('Inactive portfolio', 'portfolio', 'inactive', 'portfolio/inactive.jpg');
        $other = $this->studio->replicate();
        $other->studio_name = 'Other studio';
        $other->save();
        $this->gallery('Other portfolio', 'portfolio', 'active', 'portfolio/other.jpg', $other->id);

        $response = $this->actingAs($this->client)->get('/client/studios/'.$this->studio->id);
        $response->assertOk()->assertSee('Portfolio Studio')->assertSee('Studio profile description.')
            ->assertSee('portfolio/visible.jpg')->assertDontSee('booking/private.jpg')
            ->assertDontSee('portfolio/inactive.jpg')->assertDontSee('portfolio/other.jpg')
            ->assertSee('List of Packages')->assertSee('Reviews')
            ->assertSee(route('client.booking-forms', ['type' => 'studio', 'id' => $this->studio->id]));
        $response->assertViewHas('studioProfile', true);
        $response->assertSee('btn btn-primary studio-profile-link', false);
    }

    public function test_empty_portfolio_is_explicit_and_missing_files_are_not_broken_images(): void
    {
        $this->gallery('Missing file', 'portfolio', 'active', 'portfolio/missing.jpg');
        Storage::disk('public')->delete('portfolio/missing.jpg');
        $this->actingAs($this->client)->get('/client/studios/'.$this->studio->id)
            ->assertOk()->assertSee('No portfolio images yet.')->assertDontSee('src="'.asset('storage/portfolio/missing.jpg').'"', false);
    }

    public function test_portfolio_images_have_loading_and_decode_error_feedback(): void
    {
        $this->gallery('Loadable portfolio', 'portfolio', 'active', 'portfolio/loading.jpg');
        $this->actingAs($this->client)->get('/client/studios/'.$this->studio->id)
            ->assertOk()->assertSee('Loading photo…')->assertSee('data-portfolio-loading', false)
            ->assertSee('onload=', false)->assertSee('Photo unavailable. Reload this page to try again.');
    }

    public function test_guest_and_non_client_cannot_access_studio_profile(): void
    {
        $this->get('/client/studios/'.$this->studio->id)->assertRedirect(route('login'));
        $this->client->update(['role' => 'owner']);
        $this->actingAs($this->client)->getJson('/client/studios/'.$this->studio->id)->assertForbidden();
    }

    public function test_unavailable_or_deleted_studio_is_not_discoverable(): void
    {
        $this->studio->update(['status' => 'pending']);
        $this->actingAs($this->client)->get('/client/studios/'.$this->studio->id)->assertNotFound();
        $this->studio->update(['status' => 'verified']);
        StudioPlanModel::query()->update(['status' => 'expired']);
        $this->get('/client/studios/'.$this->studio->id)->assertNotFound();
        $this->studio->delete();
        $this->get('/client/studios/'.$this->studio->id)->assertNotFound();
    }

    public function test_malformed_and_out_of_range_profile_ids_do_not_throw_type_errors(): void
    {
        $this->actingAs($this->client)->get('/client/studios/not-a-number')
            ->assertRedirect(route('client.dashboard'));
        $this->get('/client/studios/999999999999999999999999999999')->assertNotFound();
    }

    public function test_listing_and_booking_form_link_to_studio_profile(): void
    {
        $url = route('client.studios.show', $this->studio->id);
        $this->actingAs($this->client)->get(route('client.dashboard'))->assertOk()->assertSee($url);
        $this->get(route('client.booking-forms', ['type' => 'studio', 'id' => $this->studio->id]))
            ->assertOk()->assertSee($url);
        $this->postJson(route('client.dashboard.filter'))->assertOk()
            ->assertJsonPath('results.0.profile_url', $url);
    }

    public function test_profile_shows_active_packages_and_only_published_ratings(): void
    {
        $category = CategoriesModel::create(['category_name' => 'Portrait', 'status' => 'active']);
        foreach (['active', 'inactive'] as $status) {
            PackagesModel::create([
                'studio_id' => $this->studio->id, 'category_id' => $category->id,
                'package_name' => ucfirst($status).' portrait package', 'package_description' => 'Portrait session.',
                'duration' => 1, 'maximum_edited_photos' => 10, 'package_price' => 1500, 'status' => $status,
            ]);
        }
        foreach (['published' => 5, 'removed' => 1] as $status => $rating) {
            $booking = BookingModel::create([
                'booking_reference' => 'PROFILE-'.$status, 'client_id' => $this->client->id,
                'booking_type' => 'studio', 'provider_id' => $this->studio->id,
                'event_name' => 'Portrait', 'event_date' => today(), 'start_time' => '09:00', 'end_time' => '10:00',
                'location_type' => 'in-studio', 'total_amount' => 1500, 'down_payment' => 1500,
                'remaining_balance' => 0, 'payment_type' => 'full_payment', 'payment_status' => 'paid', 'status' => 'completed',
            ]);
            StudioRatingModel::create([
                'booking_id' => $booking->id, 'client_id' => $this->client->id, 'studio_id' => $this->studio->id,
                'rating' => $rating, 'review_text' => ucfirst($status).' review content', 'status' => $status,
            ]);
        }
        StudioRatingModel::updateAggregate($this->studio->id);
        $response = $this->actingAs($this->client)->get('/client/studios/'.$this->studio->id);
        $response->assertOk()->assertSee('Active portrait package')->assertDontSee('Inactive portrait package')
            ->assertSee('Published review content')->assertDontSee('Removed review content');
        $this->assertSame([5 => 1, 4 => 0, 3 => 0, 2 => 0, 1 => 0], $response->viewData('ratingDistribution'));
    }

    private function gallery(string $name, string $type, string $status, string $path, ?int $studioId = null): void
    {
        Storage::disk('public')->put($path, 'image-fixture');
        StudioOnlineGalleryModel::create([
            'studio_id' => $studioId ?? $this->studio->id, 'gallery_type' => $type,
            'gallery_reference' => str()->uuid(), 'gallery_name' => $name,
            'status' => $status, 'images' => [$path],
        ]);
    }
}
