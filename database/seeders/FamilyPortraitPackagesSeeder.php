<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FamilyPortraitPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $category = DB::table('tbl_categories')->where('category_name', 'Family Portrait')->first();

        if (!$studio || !$category) {
            $this->command->error('Studio or Family Portrait category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'Family Mini Session',
                'package_description' => 'A quick and fun 30-minute session perfect for families with young children. Ideal for holiday cards, updated family photos, or simple portraits.',
                'package_inclusions' => json_encode([
                    '30-minute studio session',
                    '1 photographer',
                    '20 edited high-resolution photos',
                    '5 digital downloads with print release',
                    'Simple backdrop setup',
                    'Online gallery access',
                    '2-week turnaround'
                ]),
                'duration' => 1,
                'maximum_edited_photos' => 20,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 4500.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Family Legacy Session',
                'package_description' => 'A comprehensive family portrait experience for extended families. Perfect for reunions, multi-generational gatherings, and milestone celebrations.',
                'package_inclusions' => json_encode([
                    '2-hour studio or outdoor session',
                    '1 lead photographer + 1 assistant',
                    '60+ edited high-resolution photos',
                    'Individual family group shots',
                    'Extended family group portraits',
                    'Candid lifestyle moments',
                    'Online gallery with download & print rights',
                    'Canvas print (16x20 inches)',
                    'Premium photo album (20 pages)',
                    'All photos on custom USB'
                ]),
                'duration' => 2,
                'maximum_edited_photos' => 60,
                'coverage_scope' => json_encode(['Studio or on-location within Metro Manila']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 1,
                'package_price' => 18500.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
            [
                'package_name' => 'Newborn & Maternity Bundle',
                'package_description' => 'Capture the beautiful journey from pregnancy to welcoming your little one. Includes both maternity and newborn sessions with a cohesive style.',
                'package_inclusions' => json_encode([
                    'Maternity session (1 hour)',
                    'Newborn session (2-3 hours)',
                    '1 photographer specializing in newborns',
                    '80+ edited high-resolution photos (combined)',
                    'Props, wraps, and accessories included',
                    'Parent and sibling shots',
                    'Online gallery with download access',
                    'Premium leather album (30 pages)',
                    '2 framed prints (8x10 inches)',
                    'USB with all images'
                ]),
                'duration' => 4,
                'maximum_edited_photos' => 80,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 22000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
        ];

        $this->insertPackages($studio->id, $category->id, $packages);
        $this->command->info('Family portrait packages seeded!');
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