<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\UserModel;
use App\Rules\PhilippineMobileNumber;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Client test C-18: after C-10, saving a profile whose stored number is a digit
 * short (seed data such as +63918404400) failed with "Enter a complete mobile
 * number", even when the number was not touched.
 */
class ProfileMobileNumberTest extends TestCase
{
    public function test_the_number_on_file_passes_unchanged_and_a_new_number_must_be_complete(): void
    {
        $rule = new PhilippineMobileNumber('+63918404400');

        $this->assertTrue(Validator::make(['m' => '+63918404400'], ['m' => [$rule]])->passes());
        $this->assertTrue(Validator::make(['m' => '+(63)918 404 400'], ['m' => [$rule]])->passes());
        $this->assertTrue(Validator::make(['m' => '09171234567'], ['m' => [$rule]])->passes());
        $this->assertTrue(Validator::make(['m' => '+63917'], ['m' => [$rule]])->fails());
        $this->assertTrue(Validator::make(['m' => '+63917'], ['m' => [new PhilippineMobileNumber]])->fails());
    }

    public function test_profile_form_rules_pass_the_logged_in_users_number(): void
    {
        $this->actingAs(new UserModel(['id' => 1, 'mobile_number' => '+63918404400']));

        $rules = (new UpdateProfileRequest)->rules();
        $data = ['first_name' => 'B', 'last_name' => 'I', 'mobile_number' => '+63918404400'];

        $this->assertTrue(Validator::make($data, ['mobile_number' => $rules['mobile_number']])->passes());
        $this->assertTrue(Validator::make(['mobile_number' => '+63917'], ['mobile_number' => $rules['mobile_number']])->fails());
    }
}
