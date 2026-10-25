<?php

namespace Tests\Feature\Freelancer;

use App\Models\Freelancer\PackagesModel;
use App\Models\Freelancer\ServiceModel;
use App\Models\UserModel;
use Tests\TestCase;

/**
 * Client test C-07: opening a freelancer service showed a server error,
 * because the owner relation named App\Models\User, which does not exist.
 */
class FreelancerModelOwnerRelationTest extends TestCase
{
    public function test_freelancer_service_and_package_belong_to_a_user_model(): void
    {
        $this->assertInstanceOf(UserModel::class, (new ServiceModel)->user()->getRelated());
        $this->assertInstanceOf(UserModel::class, (new PackagesModel)->user()->getRelated());
    }
}
