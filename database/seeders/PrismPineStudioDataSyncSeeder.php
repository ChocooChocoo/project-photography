<?php

namespace Database\Seeders;

use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrismPineStudioDataSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studio = StudiosModel::query()
            ->where('studio_name', 'Prism & Pine Creative Spaces')
            ->first();

        if (!$studio) {
            $this->command?->warn('Prism & Pine Creative Spaces not found. Studio sync skipped.');
            return;
        }

        $normalizedDays = $this->normalizeOperatingDays($studio->operating_days);

        $studio->update([
            'operating_days' => $normalizedDays,
        ]);

        StudioScheduleModel::updateOrCreate(
            [
                'studio_id' => $studio->id,
                'location_id' => $studio->location_id,
            ],
            [
                'operating_days' => $normalizedDays,
                'opening_time' => $studio->start_time ?: '09:00:00',
                'closing_time' => $studio->end_time ?: '18:00:00',
                'booking_limit' => $studio->max_clients_per_day ?: 1,
                'advance_booking' => $studio->advance_booking_days ?: 1,
            ]
        );

        $categoryIds = DB::table('tbl_packages')
            ->where('studio_id', $studio->id)
            ->pluck('category_id')
            ->filter()
            ->unique()
            ->values();

        foreach ($categoryIds as $categoryId) {
            DB::table('pvt_studio_categories')->updateOrInsert(
                [
                    'user_id' => $studio->user_id,
                    'studio_id' => $studio->id,
                    'category_id' => $categoryId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            ServicesModel::updateOrCreate(
                [
                    'studio_id' => $studio->id,
                    'category_id' => $categoryId,
                ],
                [
                    'service_name' => $this->defaultServicesForCategory((int) $categoryId),
                ]
            );
        }

        if ($categoryIds->isNotEmpty()) {
            $primaryCategoryId = (int) $categoryIds->first();

            if ((int) $studio->category_id !== $primaryCategoryId) {
                $studio->update([
                    'category_id' => $primaryCategoryId,
                ]);
            }
        }

        $this->command?->info('Synced Prism & Pine category, service, and schedule data.');
    }

    /**
     * Normalize operating days so the column stores a plain array value.
     *
     * @param mixed $operatingDays
     * @return array<int, string>
     */
    private function normalizeOperatingDays($operatingDays): array
    {
        if (is_string($operatingDays)) {
            $decoded = json_decode($operatingDays, true);

            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $operatingDays = $decoded;
        }

        if (!is_array($operatingDays) || $operatingDays === []) {
            return ['monday', 'wednesday', 'friday', 'saturday'];
        }

        return array_values(array_map(static fn ($day) => strtolower((string) $day), $operatingDays));
    }

    /**
     * Build a deterministic default studio service list per category.
     *
     * @return array<int, string>
     */
    private function defaultServicesForCategory(int $categoryId): array
    {
        $categoryName = (string) DB::table('tbl_categories')
            ->where('id', $categoryId)
            ->value('category_name');

        return match ($categoryName) {
            'Wedding Photography' => [
                'Full-Day Wedding Coverage',
                'Engagement Photo Session',
                'Bridal Portrait Shoot',
            ],
            'Event Photography' => [
                'Corporate Event Coverage',
                'Birthday Party Photography',
                'Conference & Seminar Documentation',
            ],
            'Family Portrait' => [
                'Studio Family Portrait Session',
                'Outdoor Family Lifestyle Session',
                'Multi-Generation Family Portraits',
            ],
            'Fashion Photography' => [
                'Editorial Fashion Shoot',
                'Model Portfolio Session',
                'Brand Lookbook Production',
            ],
            'Product Photography' => [
                'E-Commerce Product Shoot',
                'Styled Product Setup',
                'Catalog Item Photography',
            ],
            'Pet Photography' => [
                'Pet Portrait Session',
                'Owner and Pet Lifestyle Session',
                'Seasonal Pet Mini Shoot',
            ],
            default => [
                'Studio Photography Session',
                'On-Location Photography Coverage',
                'Edited Image Delivery',
            ],
        };
    }
}
