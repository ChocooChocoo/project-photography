<?php

namespace Database\Seeders\Fresh;

use Database\Seeders\CategorySeeder;
use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Entry point for the fresh dataset.
 *
 * Resets every table except tbl_users and tbl_locations, rebuilds the
 * reference data, then writes a complete, media-free platform: ten studios
 * with ten distinct owners and ten photographers each, plus HR and finance
 * staff, clients, freelancers, subscriptions, bookings, payments, payroll,
 * attendance, procurement, and the AI assistant configuration.
 *
 *     php artisan db:seed --class="Database\Seeders\Fresh\FreshSeedSeeder"
 *
 * Preserved rows in tbl_users are never read as owners or staff: every account
 * this seeder needs is inserted alongside them in the 4000-series identity
 * range with a +63918404xxx mobile number, a block no other seeder writes to.
 * Reruns refresh those rows in place and leave everything else alone.
 */
class FreshSeedSeeder extends Seeder
{
    use FreshSeedSupport;

    public function run(): void
    {
        $this->assertLocationsArePresent();
        $this->reportExistingFreshAccounts();

        (new FreshResetSeeder($this->command))->run();

        $this->call([
            CategorySeeder::class,
            RbacSeeder::class,
        ]);

        $graph = (new FreshStudioNetworkSeeder($this->command))->run();
        $graph = (new FreshMarketplaceSeeder($this->command))->run($graph);

        (new FreshOperationsSeeder($this->command))->run($graph);
        (new FreshProcurementSeeder($this->command))->run($graph);

        $this->command?->info('Fresh seed complete.');
    }

    /**
     * tbl_locations is preserved, never seeded here. An empty table means the
     * caller skipped bootstrapping and every location_id would dangle.
     */
    private function assertLocationsArePresent(): void
    {
        if (DB::table('tbl_locations')->count() === 0) {
            throw new RuntimeException(
                'tbl_locations is empty. Run CaviteLocationSeeder once to bootstrap it, then re-run the fresh seed.'
            );
        }
    }

    /**
     * Report how many fresh-seed accounts already exist.
     *
     * This is informational, not a gate: on a rerun every one of them is a row
     * this seeder wrote last time. The guard that actually protects preserved
     * users is per-row, in FreshSeedSupport::upsertFreshUser(), which refuses
     * any email whose existing row carries a mobile number outside the
     * fresh-seed block.
     */
    private function reportExistingFreshAccounts(): void
    {
        $existing = DB::table('tbl_users')->where('mobile_number', 'like', '+63918404%')->count();

        if ($existing > 0) {
            $this->command?->info("Refreshing {$existing} existing fresh-seed account(s); preserved users are untouched.");
        }
    }
}
