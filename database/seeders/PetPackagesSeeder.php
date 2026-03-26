<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PetPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $category = DB::table('tbl_categories')->where('category_name', 'Pet Photography')->first();

        if (!$studio || !$category) {
            $this->command->error('Studio or Pet Photography category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'Pawsome Portrait Session',
                'package_description' => 'A fun and relaxed session to capture your furry friend\'s unique personality. Perfect for dogs, cats, and small pets.',
                'package_inclusions' => json_encode([
                    '1-hour studio or outdoor session',
                    '1 photographer experienced with pets',
                    '25 edited high-resolution photos',
                    'Patience and treat-motivated approach',
                    'Simple backdrop options',
                    'Owner can join for couple of shots',
                    'Online gallery with download access',
                    'Digital photo release'
                ]),
                'duration' => 1,
                'maximum_edited_photos' => 25,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 0,
                'package_price' => 5500.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Pet & Family Bonding',
                'package_description' => 'Capture the special bond between your family and your beloved pet. Includes candid moments and posed portraits.',
                'package_inclusions' => json_encode([
                    '2-hour session at your home or park',
                    '1 photographer',
                    '50+ edited high-resolution photos',
                    'Candid and posed shots',
                    'Family portraits with pet',
                    'Solo pet portraits',
                    'Action shots (running, playing)',
                    'Online gallery with print rights',
                    'Canvas print (11x14 inches)',
                    'Custom pet-themed USB drive'
                ]),
                'duration' => 2,
                'maximum_edited_photos' => 50,
                'coverage_scope' => json_encode(['Metro Manila', 'Cavite']),
                'package_location' => json_encode(['On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 1,
                'package_price' => 12500.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Premium Pet Portfolio',
                'package_description' => 'The ultimate pet photography experience. Professional styling, multiple locations, and a custom album to cherish forever.',
                'package_inclusions' => json_encode([
                    '3-hour session',
                    '2 photographers',
                    '2 location options (studio + outdoor)',
                    'Pet stylist on-site (grooming touch-ups)',
                    '100+ edited high-resolution photos',
                    'Professional props and accessories',
                    'Action and slow-motion video clips',
                    'Custom leather album (20 pages)',
                    'Framed print (16x20 inches)',
                    'Online gallery with unlimited downloads',
                    'Mobile app access for easy sharing',
                    'Premium USB in keepsake box'
                ]),
                'duration' => 3,
                'maximum_edited_photos' => 100,
                'coverage_scope' => json_encode(['Metro Manila, Cavite, Laguna']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 2,
                'allow_time_customization' => 1,
                'package_price' => 25000.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
        ];

        $this->insertPackages($studio->id, $category->id, $packages);
        $this->command->info('Pet photography packages seeded!');
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