<?php

namespace Database\Seeders;

use Database\Seeders\Fresh\FreshSeedSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The seed chain is a single pass: bootstrap tbl_locations only if it is
 * empty, then hand off to the fresh seeder, which resets every other table
 * (tbl_users and tbl_locations excepted) and rebuilds the whole dataset.
 *
 * The legacy per-feature seeders are still on disk and still runnable with
 * `db:seed --class=...`, but none of them is part of the default chain. Note
 * that several of them (MultiStudioBundleSeeder, ProcurementWorkflowSeeder,
 * CoverageGapsAndAdminSeeder, FreelancerMarketplaceBundleSeeder,
 * PrismPineStudioSeeder) write media paths; running one after the fresh seed
 * reintroduces media rows the fresh dataset deliberately omits.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // tbl_locations is a preserved table. Seed it only to bootstrap an
        // empty database; CaviteLocationSeeder rewrites existing rows, so it
        // must never run against a populated one.
        if (DB::table('tbl_locations')->count() === 0) {
            $this->call(CaviteLocationSeeder::class);
        } else {
            $this->command?->info('tbl_locations already populated — preserved untouched.');
        }

        // Categories and RBAC are rebuilt inside FreshSeedSeeder, after the
        // reset clears them, so the ordering stays in one place.
        $this->call(FreshSeedSeeder::class);
    }
}
