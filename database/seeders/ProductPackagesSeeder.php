<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $category = DB::table('tbl_categories')->where('category_name', 'Product Photography')->first();

        if (!$studio || !$category) {
            $this->command->error('Studio or Product Photography category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'E-Commerce Starter Pack',
                'package_description' => 'Perfect for online sellers and small businesses. Clean, professional product photos optimized for e-commerce platforms.',
                'package_inclusions' => json_encode([
                    'Up to 20 products',
                    '5 shots per product (different angles)',
                    'White background standard',
                    'Basic color correction',
                    'High-resolution JPEG files',
                    'Watermark-free images',
                    '2-day turnaround',
                    'Simple styling assistance'
                ]),
                'duration' => 3,
                'maximum_edited_photos' => 100,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 8500.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Lifestyle Product Suite',
                'package_description' => 'Showcase your products in real-life settings. Perfect for social media content, lookbooks, and marketing materials.',
                'package_inclusions' => json_encode([
                    'Up to 15 products',
                    'Lifestyle shots with models (2 models included)',
                    'Studio and outdoor setup options',
                    'Creative prop styling',
                    '10 detail shots per product',
                    'Advanced retouching included',
                    'Social media ready (square/portrait formats)',
                    'Commercial usage rights',
                    'Online gallery with download access'
                ]),
                'duration' => 5,
                'maximum_edited_photos' => 150,
                'coverage_scope' => json_encode(['Metro Manila area']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 1,
                'package_price' => 28000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Catalog & Lookbook Production',
                'package_description' => 'Full-scale product photography for catalogs, lookbooks, and advertising campaigns. Professional styling, models, and post-production included.',
                'package_inclusions' => json_encode([
                    'Full day shoot (8 hours)',
                    'Up to 50 products',
                    '2 professional photographers',
                    'Professional stylist included',
                    '2 models included',
                    'Studio rental included',
                    'Advanced retouching and color grading',
                    '200+ final images',
                    'Full commercial usage rights',
                    'Customized background options',
                    'Video content add-on available',
                    '5-day priority turnaround'
                ]),
                'duration' => 8,
                'maximum_edited_photos' => 200,
                'coverage_scope' => json_encode(['Nationwide, travel fees apply outside Metro Manila']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 3,
                'allow_time_customization' => 1,
                'package_price' => 65000.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
        ];

        $this->insertPackages($studio->id, $category->id, $packages);
        $this->command->info('Product photography packages seeded!');
    }

    private function insertPackages($studioId, $categoryId, $packages): void
    {
        $now = Carbon::now();

        foreach ($packages as $package) {
            $exists = DB::table('tbl_packages')
                ->where('studio_id', $studioId)
                ->where('category_id', $categoryId)
                ->where('package_name', $package['package_name'])
                ->exists();

            if (!$exists) {
                DB::table('tbl_packages')->insert(array_merge([
                    'studio_id' => $studioId,
                    'category_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $package));
                $this->command->info("Added: {$package['package_name']}");
            }
        }
    }
}