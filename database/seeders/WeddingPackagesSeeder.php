<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class WeddingPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing studio and category IDs
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $weddingCategory = DB::table('tbl_categories')->where('category_name', 'Wedding Photography')->first();

        // If no studio or category exists, skip or handle accordingly
        if (!$studio) {
            $this->command->error('No studio found. Please ensure a studio exists before running this seeder.');
            return;
        }

        if (!$weddingCategory) {
            $this->command->error('Wedding Photography category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'Intimate Wedding - Essential',
                'package_description' => 'Perfect for intimate weddings with up to 50 guests. Captures the heartfelt moments of your special day without overwhelming coverage.',
                'package_inclusions' => json_encode([
                    '6 hours of wedding day coverage',
                    '1 professional photographer',
                    '300+ edited high-resolution photos',
                    'Online gallery with download access',
                    'USB keepsake box with selected photos',
                    'Pre-wedding consultation'
                ]),
                'duration' => 6,
                'maximum_edited_photos' => 300,
                'coverage_scope' => json_encode([]),
                'package_location' => json_encode(['In-Studio']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 35000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Classic Wedding - Premium',
                'package_description' => 'Comprehensive coverage for traditional weddings with full-day documentation. Includes pre-nuptial shoot and all major wedding events.',
                'package_inclusions' => json_encode([
                    '10 hours of wedding day coverage',
                    '1 main photographer + 1 assistant photographer',
                    'Pre-nuptial photoshoot (4 hours, 1 location)',
                    '500+ edited high-resolution photos',
                    'Same-day edit (SDE) video highlights',
                    'Online gallery with download & print rights',
                    'Premium leather album (20 pages)',
                    'USB box with wooden case',
                    '2 pre-wedding consultations'
                ]),
                'duration' => 10,
                'maximum_edited_photos' => 500,
                'coverage_scope' => json_encode(['Metro Manila', 'Cavite', 'Laguna', 'Rizal', 'Bulacan']),
                'package_location' => json_encode(['On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 3,
                'allow_time_customization' => 0,
                'package_price' => 75000.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
            [
                'package_name' => 'Luxury Wedding - Elite',
                'package_description' => 'Ultimate luxury coverage for grand weddings. Two photographers capturing every angle, full-day coverage, pre-nuptial destination shoot, and premium album.',
                'package_inclusions' => json_encode([
                    '12+ hours of wedding day coverage (unlimited until after reception)',
                    '2 main photographers + 1 videographer',
                    'Pre-nuptial destination shoot (full day, up to 3 locations)',
                    '800+ edited high-resolution photos',
                    '5-7 minute cinematic wedding film',
                    'Full ceremony & speeches video (raw + edited)',
                    'Online gallery with print rights & mobile app',
                    'Premium coffee table album (40 pages)',
                    'Canvas print (24x36 inches)',
                    'All photos in custom wooden box with USB',
                    'Unlimited pre-wedding consultations',
                    'Wedding coordinator assistance on photo timeline'
                ]),
                'duration' => null,
                'maximum_edited_photos' => 800,
                'coverage_scope' => json_encode(['Nationwide (Luzon, Visayas, Mindanao)', 'Travel fees included for Luzon']),
                'package_location' => json_encode(['In-Studio', 'On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 5,
                'allow_time_customization' => 1,
                'package_price' => 150000.00,
                'online_gallery' => 1,
                'photographer_count' => 3,
                'status' => 'active',
            ],
        ];

        $now = Carbon::now();

        foreach ($packages as $package) {
            // Check if package already exists to avoid duplicates
            $exists = DB::table('tbl_packages')
                ->where('studio_id', $studio->id)
                ->where('category_id', $weddingCategory->id)
                ->where('package_name', $package['package_name'])
                ->exists();

            if (!$exists) {
                DB::table('tbl_packages')->insert([
                    'studio_id' => $studio->id,
                    'category_id' => $weddingCategory->id,
                    'package_name' => $package['package_name'],
                    'package_description' => $package['package_description'],
                    'package_inclusions' => $package['package_inclusions'],
                    'duration' => $package['duration'],
                    'maximum_edited_photos' => $package['maximum_edited_photos'],
                    'coverage_scope' => $package['coverage_scope'],
                    'package_location' => $package['package_location'],
                    'allow_multiple_locations' => $package['allow_multiple_locations'],
                    'max_locations' => $package['max_locations'],
                    'allow_time_customization' => $package['allow_time_customization'],
                    'package_price' => $package['package_price'],
                    'online_gallery' => $package['online_gallery'],
                    'photographer_count' => $package['photographer_count'],
                    'status' => $package['status'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->command->info("Added package: {$package['package_name']}");
            } else {
                $this->command->warn("Package already exists: {$package['package_name']}");
            }
        }

        $this->command->info('Wedding packages seeding completed!');
    }
}