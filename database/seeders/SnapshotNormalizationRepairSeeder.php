<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SnapshotNormalizationRepairSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->normalizeOperatingDays('tbl_studios');
        $this->normalizeOperatingDays('tbl_studio_schedules');
        $this->repairFreelancerSchedules();
        $this->repairPrismPineInstagramUrl();

        $this->command?->info('Repaired normalization issues in seeded snapshot-backed records.');
    }

    /**
     * Normalize double-encoded operating_days JSON values to plain arrays.
     */
    private function normalizeOperatingDays(string $table): void
    {
        $rows = DB::table($table)
            ->select('id', 'operating_days')
            ->get();

        foreach ($rows as $row) {
            $normalized = $this->normalizeOperatingDaysValue($row->operating_days);

            if ($normalized === null) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'operating_days' => json_encode($normalized, JSON_THROW_ON_ERROR),
                ]);
        }
    }

    /**
     * Remove duplicate freelancer schedules and normalize the surviving row.
     */
    private function repairFreelancerSchedules(): void
    {
        /** @var Collection<int, object> $duplicateUsers */
        $duplicateUsers = DB::table('tbl_freelancer_schedules')
            ->select('user_id', DB::raw('COUNT(*) as schedule_count'))
            ->groupBy('user_id')
            ->having('schedule_count', '>', 1)
            ->get();

        foreach ($duplicateUsers as $duplicateUser) {
            $schedules = DB::table('tbl_freelancer_schedules')
                ->where('user_id', $duplicateUser->user_id)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($schedules->isEmpty()) {
                continue;
            }

            $canonicalSchedule = $schedules->first();
            $normalizedDays = $this->normalizeOperatingDaysValue($canonicalSchedule->operating_days);

            DB::table('tbl_freelancer_schedules')
                ->where('id', $canonicalSchedule->id)
                ->update([
                    'operating_days' => json_encode($normalizedDays ?? [], JSON_THROW_ON_ERROR),
                ]);

            DB::table('tbl_freelancer_schedules')
                ->where('user_id', $duplicateUser->user_id)
                ->where('id', '!=', $canonicalSchedule->id)
                ->delete();
        }
    }

    /**
     * Fix the malformed Prism & Pine Instagram URL.
     */
    private function repairPrismPineInstagramUrl(): void
    {
        DB::table('tbl_studios')
            ->where('studio_name', 'Prism & Pine Creative Spaces')
            ->where('instagram_url', 'https://insgram.com/prismandpine')
            ->update([
                'instagram_url' => 'https://instagram.com/prismandpine',
            ]);
    }

    /**
     * Normalize an operating_days value into a lowercase string array.
     *
     * @return array<int, string>|null
     */
    private function normalizeOperatingDaysValue(mixed $value): ?array
    {
        $decoded = $value;

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);

            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
        }

        if (!is_array($decoded)) {
            return null;
        }

        return array_values(array_map(static fn ($day) => strtolower((string) $day), $decoded));
    }
}
