<?php

namespace Tests\Feature\Freelancer;

use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\Freelancer\FreelancerCategoryModel;
use App\Models\Freelancer\FreelancerScheduleModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * "Setup Profile" opens the same form prefilled when a profile exists,
 * and saving it updates the profile instead of creating a second one.
 */
class FreelancerProfileEditTest extends TestCase
{
    use RefreshDatabase;

    private function freelancerWithProfile(): array
    {
        $location = LocationModel::create([
            'province' => 'Cavite', 'municipality' => 'Imus',
            'barangay' => ['Alapan', 'Bucandala'], 'zip_code' => '4103', 'status' => 'active',
        ]);
        $weddings = CategoriesModel::create(['category_name' => 'Weddings', 'status' => 'active']);
        $portraits = CategoriesModel::create(['category_name' => 'Portraits', 'status' => 'active']);

        $user = UserModel::create([
            'role' => 'freelancer', 'first_name' => 'Ana', 'last_name' => 'Lens',
            'email' => 'ana@example.test', 'mobile_number' => '09171234567',
            'password' => 'unused', 'status' => 'active', 'email_verified' => true,
        ]);

        $profile = ProfileModel::create([
            'user_id' => $user->id, 'location_id' => $location->id,
            'brand_name' => 'Golden Frames Studio', 'bio' => str_repeat('Natural light photographer. ', 3),
            'years_experience' => 4, 'brand_logo' => 'brand-logos/old.png',
            'street' => 'Main St', 'barangay' => 'Alapan', 'service_area' => 'Within my city only',
            'starting_price' => 5000, 'deposit_policy' => 'not_required',
            'portfolio_works' => json_encode(['portfolio-works/old.jpg']), 'valid_id' => 'valid-ids/old.png',
        ]);
        FreelancerScheduleModel::create([
            'user_id' => $user->id, 'operating_days' => json_encode(['monday', 'friday']),
            'start_time' => '09:00', 'end_time' => '17:00', 'booking_limit' => 2, 'advance_booking' => 3,
        ]);
        FreelancerCategoryModel::create(['user_id' => $user->id, 'category_id' => $weddings->id]);

        return [$user, $profile, $weddings, $portraits];
    }

    public function test_setup_page_opens_prefilled_when_a_profile_exists(): void
    {
        [$user] = $this->freelancerWithProfile();

        $this->actingAs($user)->get(route('freelancer.profile.setup'))
            ->assertOk()
            ->assertSee('value="Golden Frames Studio"', false)
            ->assertSee('Update profile')
            ->assertSee('storage/brand-logos/old.png', false);
    }

    public function test_setup_page_still_renders_for_a_new_freelancer(): void
    {
        $user = UserModel::create([
            'role' => 'freelancer', 'first_name' => 'Ben', 'last_name' => 'New',
            'email' => 'ben@example.test', 'mobile_number' => '09179876543',
            'password' => 'unused', 'status' => 'active', 'email_verified' => true,
        ]);

        $this->actingAs($user)->get(route('freelancer.profile.setup'))
            ->assertOk()
            ->assertSee('Submit Form');
    }

    public function test_saving_updates_the_profile_without_new_files(): void
    {
        Storage::fake('public');
        [$user, $profile, , $portraits] = $this->freelancerWithProfile();

        $this->actingAs($user)->postJson(route('freelancer.profile.store'), [
            'freelancer_mobile_number' => '09171234567',
            'brand_name' => 'Silver Frames',
            'bio' => str_repeat('Natural light photographer. ', 3),
            'years_of_experience' => 5,
            'municipality' => 'Imus',
            'barangay' => 'Bucandala',
            'street' => 'Second St',
            'service_area' => 'Within Cavite province',
            'category_services' => [$portraits->id],
            'starting_price' => 6000,
            'deposit_policy' => 'not_required',
            'operating_days' => ['saturday'],
            'start_time' => '08:00',
            'end_time' => '16:00',
            'max_clients_per_day' => 3,
            'advance_booking_days' => 5,
        ])->assertOk()->assertJson(['success' => true, 'redirect' => route('freelancer.profile.index')]);

        $this->assertSame(1, ProfileModel::where('user_id', $user->id)->count());
        $fresh = $profile->fresh();
        $this->assertSame('Silver Frames', $fresh->brand_name);
        $this->assertSame('Bucandala', $fresh->barangay);
        $this->assertSame('brand-logos/old.png', $fresh->brand_logo);
        $this->assertSame('valid-ids/old.png', $fresh->valid_id);

        $schedule = FreelancerScheduleModel::where('user_id', $user->id)->sole();
        $this->assertSame(3, (int) $schedule->booking_limit);
        $this->assertSame(5, (int) $schedule->advance_booking);

        $this->assertSame(
            [$portraits->id],
            FreelancerCategoryModel::where('user_id', $user->id)->pluck('category_id')->map(fn ($id) => (int) $id)->all()
        );
    }
}
