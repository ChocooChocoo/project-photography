<?php

namespace Database\Seeders\Concerns;

use App\Models\Admin\LocationModel;
use RuntimeException;

/**
 * Shared helpers for the seeder chain: the uniform seed credentials and
 * municipality-name lookups against tbl_locations.
 */
trait SeedSupport
{
    /**
     * Plain-text password given to every seeded account.
     */
    public const SEED_PASSWORD = 'Password_123';

    /**
     * Memoised municipality => id map.
     *
     * @var array<string, int>
     */
    private array $locationIdCache = [];

    /**
     * Resolve a tbl_locations id by municipality name.
     *
     * Fails loudly rather than writing a dangling foreign key, since
     * tbl_studio_schedules.location_id is a non-nullable constrained column.
     */
    protected function locationId(string $municipality): int
    {
        if (! isset($this->locationIdCache[$municipality])) {
            $id = LocationModel::where('municipality', $municipality)->value('id');

            if (! $id) {
                throw new RuntimeException(
                    "Missing location [{$municipality}]. Run CaviteLocationSeeder first."
                );
            }

            $this->locationIdCache[$municipality] = (int) $id;
        }

        return $this->locationIdCache[$municipality];
    }

    /**
     * Build a realistic, collision-free @gmail.com address from a person's name.
     *
     * The optional sequence keeps addresses unique where the same name is
     * reused across studios (e.g. the bundled employee roster).
     */
    protected function gmail(string $firstName, string $lastName, ?int $sequence = null): string
    {
        return sprintf(
            '%s.%s%s@gmail.com',
            $this->emailSlug($firstName),
            $this->emailSlug($lastName),
            $sequence !== null ? $sequence : ''
        );
    }

    /**
     * Lowercase, strip diacritics, and remove everything that is not a-z0-9.
     */
    protected function emailSlug(string $value): string
    {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;

        return preg_replace('/[^a-z0-9]/', '', strtolower($ascii)) ?: 'user';
    }
}
