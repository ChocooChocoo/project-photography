<?php

namespace Tests\Feature;

use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Database\Seeders\Fresh\FreshResetSeeder;
use Tests\TestCase;

/**
 * Guards the invariants of the fresh seed that can be checked without a
 * database. The orphaned-foreign-key scan needs a real MySQL schema and lives
 * in the `db:verify-seed` command instead, matching SeedIntegrityTest.
 */
class FreshSeedContractTest extends TestCase
{
    use FreshSeedSupport;

    public function test_preserved_tables_are_never_truncated(): void
    {
        foreach (FreshResetSeeder::PRESERVED as $table) {
            $this->assertNotContains(
                $table,
                FreshResetSeeder::TRUNCATE_TABLES,
                "{$table} is preserved and must not be listed for truncation."
            );
        }

        $this->assertSame(
            FreshResetSeeder::TRUNCATE_TABLES,
            array_values(array_unique(FreshResetSeeder::TRUNCATE_TABLES)),
            'The truncate list has duplicate entries.'
        );
    }

    public function test_media_tables_are_cleared_but_never_written(): void
    {
        foreach (FreshResetSeeder::SKIPPED_MEDIA as $table) {
            $this->assertContains(
                $table,
                FreshResetSeeder::TRUNCATE_TABLES,
                "{$table} must be cleared by the reset even though it is never seeded."
            );
        }

        $sources = glob(database_path('seeders/Fresh/*.php')) ?: [];
        $sources = array_merge($sources, glob(database_path('seeders/Fresh/Concerns/*.php')) ?: []);

        foreach ($sources as $file) {
            $contents = file_get_contents($file);

            foreach ($this->skippedMediaTablesInWrites($contents) as $table) {
                $this->fail(basename($file)." writes to the media table {$table}.");
            }
        }

        $this->assertNotEmpty($sources, 'No fresh seeder sources were found to scan.');
    }

    public function test_no_seeder_source_contains_a_media_reference(): void
    {
        $forbidden = [
            'http://', 'https://', 'storage/', 'public/', 'asset(',
            '.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.mp4', '.pdf', '.docx', '.xlsx',
        ];

        $sources = array_merge(
            glob(database_path('seeders/Fresh/*.php')) ?: [],
            glob(database_path('seeders/Fresh/Concerns/*.php')) ?: []
        );

        foreach ($sources as $file) {
            $contents = file_get_contents($file);

            foreach ($forbidden as $needle) {
                $this->assertStringNotContainsString(
                    $needle,
                    $contents,
                    basename($file)." contains a media reference [{$needle}]."
                );
            }
        }
    }

    public function test_every_studio_address_is_a_real_barangay_of_its_lgu(): void
    {
        foreach ($this->studioCatalog() as $index => $studio) {
            $municipality = $studio['municipality'];
            $barangay = $this->barangayFor($municipality, $index);

            $this->assertContains(
                $barangay,
                $this->barangaysOf($municipality),
                "{$barangay} is not a barangay of {$municipality}."
            );
        }
    }

    public function test_identity_ranges_stay_clear_of_the_legacy_seeders(): void
    {
        $this->assertSame('+63918404101', $this->freshMobile(self::SEQ_STAFF_BASE + 1));
        $this->assertSame('+63918404000', $this->freshMobile(self::SEQ_ADMIN));

        // Legacy seeders use +63917700xxx / +63917500xxx and sequences below 4000.
        $this->assertStringStartsWith('+63918', $this->freshMobile(self::SEQ_CLIENT_BASE));
        $this->assertGreaterThanOrEqual(4000, self::SEQ_ADMIN);
        $this->assertGreaterThanOrEqual(4000, self::SEQ_FREELANCER_BASE);
    }

    /**
     * @return array<int, string>
     */
    private function barangaysOf(string $municipality): array
    {
        foreach (require database_path('data/cavite-locations.php') as $location) {
            if ($location['municipality'] === $municipality) {
                return $location['barangay'];
            }
        }

        $this->fail("Unknown LGU {$municipality}.");
    }

    /**
     * Any DB::table('<media table>') call in the fresh seeders is a violation.
     *
     * @return array<int, string>
     */
    private function skippedMediaTablesInWrites(string $contents): array
    {
        return array_values(array_filter(
            FreshResetSeeder::SKIPPED_MEDIA,
            static fn (string $table): bool => str_contains($contents, "DB::table('{$table}')")
        ));
    }
}
