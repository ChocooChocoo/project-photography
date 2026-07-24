<?php

namespace Database\Seeders;

use App\Models\Admin\LocationModel;
use Illuminate\Database\Seeder;

/**
 * Seeds tbl_locations with the complete administrative hierarchy of Cavite:
 * all 23 LGUs and their 829 barangays. Runs first in the chain because
 * studios, studio schedules, users, and freelancer profiles all carry a
 * location_id foreign key.
 */
class CaviteLocationSeeder extends Seeder
{
    public function run(): void
    {
        // Nothing outside Cavite belongs in this table.
        LocationModel::where('province', '!=', 'Cavite')->delete();

        $locations = require database_path('data/cavite-locations.php');
        $barangayCount = 0;

        foreach ($locations as $location) {
            LocationModel::updateOrCreate(
                ['municipality' => $location['municipality']],
                [
                    'province' => 'Cavite',
                    'barangay' => $location['barangay'],
                    'zip_code' => $location['zip_code'],
                    'status' => 'active',
                ]
            );

            $barangayCount += count($location['barangay']);
        }

        $this->command?->info(sprintf(
            'Seeded %d Cavite LGUs with %d barangays.',
            count($locations),
            $barangayCount
        ));
    }
}
