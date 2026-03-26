<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class EventPackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing studio and category IDs
        $studio = DB::table('tbl_studios')->where('studio_name', 'Prism & Pine Creative Spaces')->first();
        $eventCategory = DB::table('tbl_categories')->where('category_name', 'Event Photography')->first();

        // If no studio or category exists, skip or handle accordingly
        if (!$studio) {
            $this->command->error('No studio found. Please ensure a studio exists before running this seeder.');
            return;
        }

        if (!$eventCategory) {
            $this->command->error('Event Photography category not found.');
            return;
        }

        $packages = [
            [
                'package_name' => 'Corporate Event - Basic Coverage',
                'package_description' => 'Perfect for seminars, conferences, and corporate gatherings. Professional documentation of speakers, attendees, and key moments.',
                'package_inclusions' => json_encode([
                    '4 hours of event coverage',
                    '1 professional photographer',
                    '200+ edited high-resolution photos',
                    'Candid shots of speakers and attendees',
                    'Stage/lectern coverage',
                    'Group photo documentation',
                    'Online gallery with download access',
                    '3-day turnaround time'
                ]),
                'duration' => 4,
                'maximum_edited_photos' => 200,
                'coverage_scope' => json_encode(['Metro Manila', 'Cavite', 'Laguna']),
                'package_location' => json_encode(['On-Location']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 18000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Birthday Celebration - Family Package',
                'package_description' => 'Capture the joy and laughter of your child\'s special day. From preparations to the party, we document every smile, every cake moment, and every fun activity.',
                'package_inclusions' => json_encode([
                    '5 hours of event coverage',
                    '1 main photographer',
                    '300+ edited high-resolution photos',
                    'Preparation shots (before party)',
                    'Cake cutting ceremony',
                    'Candid guest interactions',
                    'Family portraits',
                    'Online gallery with download access',
                    'USB drive with selected photos',
                    '10 printed 4x6 photos in keepsake box'
                ]),
                'duration' => 5,
                'maximum_edited_photos' => 300,
                'coverage_scope' => json_encode(['Metro Manila', 'Cavite']),
                'package_location' => json_encode(['On-Location']),
                'allow_multiple_locations' => 0,
                'max_locations' => 1,
                'allow_time_customization' => 0,
                'package_price' => 22000.00,
                'online_gallery' => 1,
                'photographer_count' => 1,
                'status' => 'active',
            ],
            [
                'package_name' => 'Corporate Gala & Awards Night - Premium',
                'package_description' => 'Comprehensive coverage for formal corporate events, anniversary celebrations, and awards nights. Includes red carpet coverage, formal portraits, and full event documentation.',
                'package_inclusions' => json_encode([
                    '8 hours of event coverage',
                    '2 professional photographers',
                    'Red carpet arrival coverage',
                    '600+ edited high-resolution photos',
                    'Award ceremony documentation',
                    'Executive portraits',
                    'Sponsor booth coverage',
                    'Event highlights slideshow',
                    'Online gallery with print rights',
                    'Premium leather album (30 pages)',
                    'All photos on custom USB',
                    '5-day turnaround with priority editing'
                ]),
                'duration' => 8,
                'maximum_edited_photos' => 600,
                'coverage_scope' => json_encode(['Metro Manila', 'Cavite', 'Laguna', 'Rizal', 'Bulacan', 'Pampanga']),
                'package_location' => json_encode(['On-Location']),
                'allow_multiple_locations' => 1,
                'max_locations' => 3,
                'allow_time_customization' => 1,
                'package_price' => 55000.00,
                'online_gallery' => 1,
                'photographer_count' => 2,
                'status' => 'active',
            ],
        ];

        $now = Carbon::now();

        foreach ($packages as $package) {
            // Check if package already exists to avoid duplicates
            $exists = DB::table('tbl_packages')
                ->where('studio_id', $studio->id)
                ->where('category_id', $eventCategory->id)
                ->where('package_name', $package['package_name'])
                ->exists();

            if (!$exists) {
                DB::table('tbl_packages')->insert([
                    'studio_id' => $studio->id,
                    'category_id' => $eventCategory->id,
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

                $this->command->info("Added event package: {$package['package_name']}");
            } else {
                $this->command->warn("Event package already exists: {$package['package_name']}");
            }
        }

        $this->command->info('Event photography packages seeding completed!');
    }
}