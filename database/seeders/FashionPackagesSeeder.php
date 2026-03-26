<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FashionPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $category = DB::table('tbl_categories')->where('category_name', 'Fashion Photography')->first();

        if (!$studio || !$category) {
            $this->command->error('Studio or Fashion Photography category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'Portfolio Builder',
                'package_description' => 'Perfect for aspiring models, designers, and fashion students. Build your portfolio with professional, editorial-quality images.',
                'package_inclusions' => json_encode([
                    '2-hour studio session',
                    '1 photographer',
                    '3 outfit changes',
                    'Professional styling advice',
                    'Basic makeup touch-up',
                    '40 edited high-resolution photos',
                    'Online gallery with download access',
                    'Portfolio-ready digital files'
                ]),
                'duration' => 2,
                'maximum_edited_photos' => 40,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 12000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Editorial Fashion Shoot',
                'package_description' => 'High-end fashion photography suitable for magazines, lookbooks, and advertising campaigns. Professional styling and makeup team included.',
                'package_inclusions' => json_encode([
                    '4-hour session',
                    '1 lead fashion photographer',
                    'Professional HMUA included',
                    'Professional stylist included',
                    'Up to 5 outfit changes',
                    'Studio rental with premium backdrops',
                    '100+ edited high-resolution photos',
                    'Advanced retouching and color grading',
                    'Online gallery with commercial rights',
                    'Behind-the-scenes video highlights',
                    'Print-ready files for publication'
                ]),
                'duration' => 4,
                'maximum_edited_photos' => 100,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 1,
                'package_price' => 35000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Campaign Production',
                'package_description' => 'Full-scale fashion campaign production for brands and designers. Includes creative direction, multiple models, and video content.',
                'package_inclusions' => json_encode([
                    'Full day shoot (8-10 hours)',
                    '2 fashion photographers',
                    'Creative director included',
                    'Professional HMUA team (2 stylists)',
                    'Models (up to 3 included)',
                    'Custom set design and props',
                    '200+ edited high-resolution photos',
                    '30-second campaign video',
                    'Behind-the-scenes documentary',
                    'Full commercial usage rights',
                    'Digital press kit',
                    'Social media campaign assets',
                    'Campaign launch consultation'
                ]),
                'duration' => 10,
                'maximum_edited_photos' => 200,
                'coverage_scope' => json_encode(['Metro Manila, travel fees for outside locations']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 3,
                'allow_time_customization' => 1,
                'package_price' => 120000.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
        ];

        $this->insertPackages($studio->id, $category->id, $packages);
        $this->command->info('Fashion photography packages seeded!');
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