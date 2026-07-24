<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Post-seed consistency check: walks every foreign key declared in the schema
 * looking for dangling references, then asserts the invariants the seed data
 * is required to hold.
 */
class VerifySeedIntegrity extends Command
{
    protected $signature = 'db:verify-seed';

    protected $description = 'Verify seeded data has no orphaned foreign keys and meets the seed invariants';

    public function handle(): int
    {
        $failures = $this->checkForeignKeys() + $this->checkInvariants();

        if ($failures > 0) {
            $this->error("Seed verification failed with {$failures} problem(s).");

            return self::FAILURE;
        }

        $this->info('Seed verification passed.');

        return self::SUCCESS;
    }

    /**
     * Count child rows whose non-null FK value has no matching parent row.
     */
    private function checkForeignKeys(): int
    {
        $constraints = DB::select(
            'SELECT TABLE_NAME AS child_table,
                    COLUMN_NAME AS child_column,
                    REFERENCED_TABLE_NAME AS parent_table,
                    REFERENCED_COLUMN_NAME AS parent_column
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             ORDER BY TABLE_NAME, COLUMN_NAME',
            [DB::getDatabaseName()]
        );

        if ($constraints === []) {
            $this->warn('No foreign keys reported by information_schema; skipping orphan scan.');

            return 0;
        }

        $failures = 0;

        foreach ($constraints as $fk) {
            $orphans = DB::table("{$fk->child_table} as child")
                ->leftJoin(
                    "{$fk->parent_table} as parent",
                    "child.{$fk->child_column}",
                    '=',
                    "parent.{$fk->parent_column}"
                )
                ->whereNotNull("child.{$fk->child_column}")
                ->whereNull("parent.{$fk->parent_column}")
                ->count();

            if ($orphans > 0) {
                $failures++;
                $this->error(sprintf(
                    '  %s.%s -> %s.%s: %d orphan(s)',
                    $fk->child_table,
                    $fk->child_column,
                    $fk->parent_table,
                    $fk->parent_column,
                    $orphans
                ));
            }
        }

        $this->info(sprintf('Scanned %d foreign keys.', count($constraints)));

        return $failures;
    }

    /**
     * Assert the seed-specific rules: Cavite-only locations, gmail accounts,
     * verified users, and studio addresses that exist in their own location.
     */
    private function checkInvariants(): int
    {
        $failures = 0;

        $checks = [
            'locations outside Cavite' => fn () => DB::table('tbl_locations')
                ->where('province', '!=', 'Cavite')
                ->count(),

            'users not on @gmail.com' => fn () => DB::table('tbl_users')
                ->where('email', 'not like', '%@gmail.com')
                ->count(),

            'unverified users' => fn () => DB::table('tbl_users')
                ->where('email_verified', false)
                ->count(),
        ];

        foreach ($checks as $label => $check) {
            $count = $check();

            if ($count > 0) {
                $failures++;
                $this->error("  {$count} {$label}");
            }
        }

        $failures += $this->checkStudioBarangays();

        $locations = DB::table('tbl_locations')->get(['municipality', 'barangay']);
        $this->info(sprintf(
            'Locations: %d Cavite LGUs, %d barangays.',
            $locations->count(),
            $locations->sum(fn ($row) => count($this->barangays($row->barangay)))
        ));

        return $failures;
    }

    /**
     * Decode a stored barangay list. Legacy rows were double-encoded, so a
     * string result is decoded once more.
     *
     * @return array<int, string>
     */
    private function barangays(?string $raw): array
    {
        $decoded = json_decode((string) $raw, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * A studio's barangay must be one of the barangays of its own location.
     */
    private function checkStudioBarangays(): int
    {
        $mismatches = DB::table('tbl_studios')
            ->join('tbl_locations', 'tbl_locations.id', '=', 'tbl_studios.location_id')
            ->whereNotNull('tbl_studios.barangay')
            ->get(['tbl_studios.studio_name', 'tbl_studios.barangay', 'tbl_locations.municipality', 'tbl_locations.barangay as barangays'])
            ->filter(function ($row) {
                return ! in_array($row->barangay, $this->barangays($row->barangays), true);
            });

        foreach ($mismatches as $row) {
            $this->error("  {$row->studio_name}: barangay '{$row->barangay}' is not in {$row->municipality}");
        }

        return $mismatches->count();
    }
}
