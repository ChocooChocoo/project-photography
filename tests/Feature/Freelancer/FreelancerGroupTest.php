<?php

namespace Tests\Feature\Freelancer;

use App\Http\Controllers\Freelancer\ProfileController;
use App\Models\Freelancer\ProfileModel;
use App\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Freelancers who share a group name are listed together. Bookings,
 * payments, and reviews stay with each freelancer.
 */
class FreelancerGroupTest extends TestCase
{
    use RefreshDatabase;

    private function freelancer(string $name, ?string $group = null): ProfileModel
    {
        $user = UserModel::create([
            'role' => 'freelancer', 'first_name' => $name, 'last_name' => 'Lens',
            'email' => strtolower($name).'@example.test', 'mobile_number' => '0917'.random_int(1000000, 9999999),
            'password' => 'unused', 'status' => 'active', 'email_verified' => true,
        ]);

        return ProfileModel::create(['user_id' => $user->id, 'brand_name' => $name.' Photos', 'group_name' => $group]);
    }

    public function test_group_mates_match_the_name_case_insensitively(): void
    {
        $ana = $this->freelancer('Ana', 'Golden Hour');
        $ben = $this->freelancer('Ben', 'golden hour ');
        $this->freelancer('Cara', 'Blue Hour');
        $solo = $this->freelancer('Dan');

        $this->assertSame(['Ben Photos'], $ana->groupMates()->pluck('brand_name')->all());
        $this->assertSame(['Ana Photos'], $ben->groupMates()->pluck('brand_name')->all());
        $this->assertTrue($solo->groupMates()->isEmpty());
    }

    public function test_a_freelancer_can_set_and_clear_their_group_name(): void
    {
        $ana = $this->freelancer('Ana');
        $this->actingAs($ana->user);

        app(ProfileController::class)->updateGroup(Request::create('/', 'POST', ['group_name' => '  Golden Hour  ']));
        $this->assertSame('Golden Hour', $ana->fresh()->group_name);

        app(ProfileController::class)->updateGroup(Request::create('/', 'POST', ['group_name' => '']));
        $this->assertNull($ana->fresh()->group_name);
    }
}
