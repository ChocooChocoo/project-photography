<?php

namespace Database\Seeders;

use App\Models\Admin\CategoriesModel;
use Illuminate\Database\Seeder;

/**
 * Seeds the platform's photography categories. Runs near the front of the
 * chain: studios, freelancers, services, packages, and client budgets all key
 * off these rows by name.
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Wedding Photography', 'Records moments and emotions from a couple\'s wedding day.'],
            ['Event Photography', 'Documents occasions like weddings, concerts, and corporate gatherings.'],
            ['Family Portrait', 'Family and group portrait sessions.'],
            ['Product Photography', 'Photos for online selling and ads.'],
            ['Street Photography', 'Captures candid moments of everyday life in public places.'],
            ['Fashion Photography', 'Displays clothing, accessories, and style, often for magazines or advertising.'],
            ['Documentary Photography', 'Tells real-life stories through images, often with social or historical focus.'],
            ['Food Photography', 'Makes dishes and drinks look appealing for menus, ads, or social media.'],
            ['Real Estate Photography', 'Highlights properties and interiors for listings and marketing.'],
            ['Pet Photography', 'Focuses on animals in domestic or stylized environments.'],
        ];

        foreach ($categories as [$name, $description]) {
            CategoriesModel::updateOrCreate(
                ['category_name' => $name],
                [
                    'description' => $description,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Seeded '.count($categories).' photography categories.');
    }
}
