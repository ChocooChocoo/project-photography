<?php

namespace Tests\Feature;

use Database\Seeders\Concerns\SeedSupport;
use Tests\TestCase;

/**
 * Guards the inputs the seed chain depends on. The orphaned-foreign-key scan
 * needs a real MySQL schema and lives in the `db:verify-seed` command instead.
 */
class SeedIntegrityTest extends TestCase
{
    use SeedSupport;

    /**
     * @return array<int, array<string, mixed>>
     */
    private function locations(): array
    {
        return require database_path('data/cavite-locations.php');
    }

    public function test_location_dataset_covers_all_of_cavite(): void
    {
        $locations = $this->locations();

        $this->assertCount(23, $locations, 'Cavite has 23 cities and municipalities.');
        $this->assertSame(
            829,
            array_sum(array_map(fn ($l) => count($l['barangay']), $locations)),
            'Cavite has 829 barangays.'
        );
    }

    public function test_location_dataset_has_no_duplicates(): void
    {
        $locations = $this->locations();

        $municipalities = array_column($locations, 'municipality');
        $zipCodes = array_column($locations, 'zip_code');

        $this->assertSame($municipalities, array_values(array_unique($municipalities)));
        $this->assertSame($zipCodes, array_values(array_unique($zipCodes)));

        foreach ($locations as $location) {
            $this->assertSame(
                $location['barangay'],
                array_values(array_unique($location['barangay'])),
                "Duplicate barangay in {$location['municipality']}."
            );
        }
    }

    public function test_seeded_studio_and_freelancer_barangays_exist_in_their_lgu(): void
    {
        $byMunicipality = [];
        foreach ($this->locations() as $location) {
            $byMunicipality[$location['municipality']] = $location['barangay'];
        }

        // Address literals hardcoded in MultiStudioBundleSeeder and
        // FreelancerMarketplaceBundleSeeder, paired with their location lookup.
        $addresses = [
            ['Imus', 'Bucandala III'],
            ['Silang', 'Mataas na Burol'],
            ['General Trias', 'Manggahan'],
            ['Imus', 'Bayan Luma III'],
            ['Silang', 'Balite II'],
            ['Carmona', 'Maduya'],
            ['Kawit', 'Tabon II'],
        ];

        foreach ($addresses as [$municipality, $barangay]) {
            $this->assertContains(
                $barangay,
                $byMunicipality[$municipality],
                "{$barangay} is not a barangay of {$municipality}."
            );
        }
    }

    public function test_seed_password_matches_the_required_literal(): void
    {
        $this->assertSame('Password_123', self::SEED_PASSWORD);
    }

    public function test_gmail_builder_produces_clean_unique_addresses(): void
    {
        $this->assertSame('althea.navarro@gmail.com', $this->gmail('Althea', 'Navarro'));
        $this->assertSame('luca.deleon112@gmail.com', $this->gmail('Luca', 'De Leon', 112));
        $this->assertSame('jose.dasmarinas@gmail.com', $this->gmail('José', 'Dasmariñas'));
    }
}
