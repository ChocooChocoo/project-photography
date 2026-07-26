<?php

namespace Database\Seeders\Fresh;

use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Ten studios, ten distinct owners, ten photographers each.
 *
 * Owns tbl_studios, pvt_studio_categories, tbl_services, tbl_packages,
 * tbl_studio_schedules, the staff rows in tbl_users, tbl_user_roles,
 * tbl_studio_employee_schedule, and tbl_studio_photographers.
 *
 * Every media column is written as an explicit null so the intent survives a
 * future migration that adds a default.
 */
class FreshStudioNetworkSeeder
{
    use FreshSeedSupport;

    /** Staff per studio: 2 HR + 2 finance + 10 photographers. */
    private const STAFF_PER_STUDIO = 14;

    /**
     * Photographer job titles, longest tenure first.
     *
     * @var array<int, array{position: string, years: int}>
     */
    private const PHOTOGRAPHER_ROLES = [
        ['position' => 'Lead Photographer', 'years' => 10],
        ['position' => 'Senior Photographer', 'years' => 9],
        ['position' => 'Creative Photographer', 'years' => 8],
        ['position' => 'Editorial Photographer', 'years' => 7],
        ['position' => 'Portrait Photographer', 'years' => 6],
        ['position' => 'Event Photographer', 'years' => 5],
        ['position' => 'Product Photographer', 'years' => 4],
        ['position' => 'Documentary Photographer', 'years' => 3],
        ['position' => 'Associate Photographer', 'years' => 2],
        ['position' => 'Assistant Photographer', 'years' => 1],
    ];

    public function __construct(private ?Command $command = null) {}

    /**
     * @return array<string, mixed> the studio graph consumed by the later seeders
     */
    public function run(): array
    {
        $now = Carbon::now();
        $roleIds = $this->resolveRoleIds();
        $categoryIds = $this->resolveCategoryIds();
        $categoryNames = $this->categoryNames();

        $studios = [];

        foreach ($this->studioCatalog() as $studioIndex => $entry) {
            $locationId = $this->locationId($entry['municipality']);
            $owner = $this->upsertFreshUser(self::SEQ_OWNER_BASE + $studioIndex + 1, 'owner', 'Manager', $locationId);
            $studioId = $this->createStudio($studioIndex, $entry, $owner->id, $locationId, $now);

            $categories = $this->attachCategories($studioIndex, $studioId, $owner->id, $categoryNames, $categoryIds, $now);
            $serviceIds = $this->createServices($studioId, $categories, $now);
            $packages = $this->createPackages($studioId, $categories, $now);

            $operatingDays = $this->operatingDaysFor($studioIndex);
            $startTime = '09:00:00';
            $endTime = '18:00:00';

            $this->createStudioSchedule($studioId, $locationId, $studioIndex, $operatingDays, $startTime, $endTime, $now);

            $staff = $this->createStaff(
                $studioIndex,
                $studioId,
                $owner->id,
                $locationId,
                $roleIds,
                array_values($serviceIds),
                $operatingDays,
                $startTime,
                $endTime,
                $now
            );

            $this->assignRole($owner->id, $roleIds['owner-super-admin'], $studioId, $now);

            $studios[] = [
                'index' => $studioIndex,
                'id' => $studioId,
                'code' => $entry['code'],
                'name' => $entry['studio_name'],
                'owner_id' => $owner->id,
                'location_id' => $locationId,
                'municipality' => $entry['municipality'],
                'barangay' => $this->barangayFor($entry['municipality'], $studioIndex),
                'categories' => $categories,
                'service_ids' => $serviceIds,
                'packages' => $packages,
                'operating_days' => $operatingDays,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hr' => $staff['hr'],
                'finance' => $staff['finance'],
                'photographers' => $staff['photographers'],
                'schedule_ids' => $staff['schedule_ids'],
            ];
        }

        $this->command?->info(sprintf(
            'Seeded %d studios, %d owners, %d staff (%d photographers).',
            count($studios),
            count($studios),
            count($studios) * self::STAFF_PER_STUDIO,
            count($studios) * self::PHOTOGRAPHERS_PER_STUDIO
        ));

        return [
            'studios' => $studios,
            'category_ids' => $categoryIds,
            'person_cursor' => $this->personCursor,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function resolveRoleIds(): array
    {
        $required = [
            'owner-super-admin',
            'studio-hr-manager',
            'studio-hr-staff',
            'studio-finance-manager',
            'studio-finance-staff',
            'studio-photographer',
        ];

        $roleIds = DB::table('tbl_roles')->whereIn('name', $required)->pluck('id', 'name');

        foreach ($required as $name) {
            if (! $roleIds->has($name)) {
                throw new RuntimeException("Missing RBAC role [{$name}]. RbacSeeder must run before the studio network.");
            }
        }

        return array_map('intval', $roleIds->all());
    }

    /**
     * @return array<string, int>
     */
    private function resolveCategoryIds(): array
    {
        $categoryIds = DB::table('tbl_categories')->pluck('id', 'category_name');

        foreach ($this->categoryNames() as $name) {
            if (! $categoryIds->has($name)) {
                throw new RuntimeException("Missing category [{$name}]. CategorySeeder must run before the studio network.");
            }
        }

        return array_map('intval', $categoryIds->all());
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function createStudio(int $studioIndex, array $entry, int $ownerId, int $locationId, Carbon $now): int
    {
        return (int) DB::table('tbl_studios')->insertGetId([
            'user_id' => $ownerId,
            'category_id' => null, // set once the first category is attached
            'location_id' => $locationId,
            'street' => $entry['street'],
            'barangay' => $this->barangayFor($entry['municipality'], $studioIndex),
            'attendance_latitude' => $entry['attendance_latitude'],
            'attendance_longitude' => $entry['attendance_longitude'],
            'attendance_radius_meters' => 60 + ($studioIndex % 4) * 20,
            'contact_number' => '0918'.str_pad((string) (4200000 + $studioIndex), 7, '0', STR_PAD_LEFT),
            'studio_email' => $entry['code'].'studio@gmail.com',
            // Social and website columns are redirection targets: left unset.
            'facebook_url' => null,
            'instagram_url' => null,
            'website_url' => null,
            'studio_name' => $entry['studio_name'],
            'studio_type' => $entry['studio_type'],
            'year_established' => $entry['year_established'],
            'studio_description' => $entry['studio_description'],
            'studio_logo' => null,
            'business_permit' => null,
            'owner_id_document' => null,
            'starting_price' => (string) (5800 + $studioIndex * 350),
            'downpayment_percentage' => 30.00 + ($studioIndex % 3) * 5,
            'operating_days' => json_encode($this->operatingDaysFor($studioIndex), JSON_THROW_ON_ERROR),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'max_clients_per_day' => 3 + ($studioIndex % 3),
            'advance_booking_days' => 2 + ($studioIndex % 3),
            'status' => 'verified',
            'rejection_note' => null,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Three categories per studio, taken from a rotating window so all ten
     * seeded categories end up in use across the network.
     *
     * @param  array<int, string>  $categoryNames
     * @param  array<string, int>  $categoryIds
     * @return array<int, array{id: int, name: string}>
     */
    private function attachCategories(
        int $studioIndex,
        int $studioId,
        int $ownerId,
        array $categoryNames,
        array $categoryIds,
        Carbon $now
    ): array {
        $categories = [];
        $rows = [];

        for ($i = 0; $i < 3; $i++) {
            $name = $categoryNames[($studioIndex * 3 + $i) % count($categoryNames)];
            $categoryId = $categoryIds[$name];

            $categories[] = ['id' => $categoryId, 'name' => $name];
            $rows[] = [
                'user_id' => $ownerId,
                'studio_id' => $studioId,
                'category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('pvt_studio_categories')->insert($rows);
        DB::table('tbl_studios')->where('id', $studioId)->update(['category_id' => $categories[0]['id']]);

        return $categories;
    }

    /**
     * One service row per studio category. service_name is a TEXT column
     * holding a JSON array of labels.
     *
     * @param  array<int, array{id: int, name: string}>  $categories
     * @return array<int, int> category id => service id
     */
    private function createServices(int $studioId, array $categories, Carbon $now): array
    {
        $serviceIds = [];

        foreach ($categories as $index => $category) {
            $serviceIds[$category['id']] = (int) DB::table('tbl_services')->insertGetId([
                'studio_id' => $studioId,
                'category_id' => $category['id'],
                'service_name' => json_encode($this->servicesFor($category['name']), JSON_THROW_ON_ERROR),
                'starting_from' => 4500 + $index * 900,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $serviceIds;
    }

    /**
     * Three tiers per category, nine packages per studio.
     *
     * @param  array<int, array{id: int, name: string}>  $categories
     * @return array<int, array{id: int, category_id: int, name: string, price: float, duration: int, maximum_edited_photos: int, inclusions: array<int, string>}>
     */
    private function createPackages(int $studioId, array $categories, Carbon $now): array
    {
        $tiers = [
            ['tier' => 'Basic', 'duration' => 3, 'photos' => 120, 'photographers' => 1, 'multiplier' => 1.00, 'locations' => ['In-Studio'], 'multi' => false, 'max' => 1, 'custom_time' => false],
            ['tier' => 'Essentials', 'duration' => 5, 'photos' => 220, 'photographers' => 1, 'multiplier' => 1.45, 'locations' => ['In-Studio', 'On-Location'], 'multi' => false, 'max' => 1, 'custom_time' => false],
            ['tier' => 'Premium', 'duration' => 8, 'photos' => 400, 'photographers' => 2, 'multiplier' => 2.10, 'locations' => ['On-Location'], 'multi' => true, 'max' => 3, 'custom_time' => true],
        ];

        $packages = [];

        foreach ($categories as $categoryIndex => $category) {
            $basePrice = 6200 + $categoryIndex * 1700;
            $family = $this->packageFamilyFor($category['name']);

            foreach ($tiers as $tier) {
                $name = sprintf('%s - %s', $family, $tier['tier']);
                $inclusions = $this->packageInclusions($category['name'], $tier['tier']);
                $price = round($basePrice * $tier['multiplier'], 2);

                $id = (int) DB::table('tbl_packages')->insertGetId([
                    'studio_id' => $studioId,
                    'category_id' => $category['id'],
                    'package_name' => $name,
                    'package_description' => $this->packageDescription($category['name'], $tier['tier']),
                    'package_inclusions' => json_encode($inclusions, JSON_THROW_ON_ERROR),
                    'duration' => $tier['duration'],
                    'maximum_edited_photos' => $tier['photos'],
                    'coverage_scope' => json_encode(['Cavite', 'Laguna', 'Metro Manila'], JSON_THROW_ON_ERROR),
                    'package_location' => json_encode($tier['locations'], JSON_THROW_ON_ERROR),
                    'allow_multiple_locations' => $tier['multi'],
                    'max_locations' => $tier['max'],
                    'allow_time_customization' => $tier['custom_time'],
                    'package_price' => $price,
                    // The online gallery tables stay empty, so no package may
                    // advertise a gallery deliverable.
                    'online_gallery' => false,
                    'cover_images' => null,
                    'photographer_count' => $tier['photographers'],
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $packages[] = [
                    'id' => $id,
                    'category_id' => $category['id'],
                    'name' => $name,
                    'price' => $price,
                    'duration' => $tier['duration'],
                    'maximum_edited_photos' => $tier['photos'],
                    'inclusions' => $inclusions,
                ];
            }
        }

        return $packages;
    }

    /**
     * @param  array<int, string>  $operatingDays
     */
    private function createStudioSchedule(
        int $studioId,
        int $locationId,
        int $studioIndex,
        array $operatingDays,
        string $startTime,
        string $endTime,
        Carbon $now
    ): void {
        DB::table('tbl_studio_schedules')->insert([
            'studio_id' => $studioId,
            'location_id' => $locationId,
            'operating_days' => json_encode($operatingDays, JSON_THROW_ON_ERROR),
            'opening_time' => $startTime,
            'closing_time' => $endTime,
            'booking_limit' => 3 + ($studioIndex % 3),
            'advance_booking' => 2 + ($studioIndex % 3),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Fourteen staff per studio: an HR pair, a finance pair, and ten
     * photographers, each with a scoped role and a working schedule.
     *
     * @param  array<string, int>  $roleIds
     * @param  array<int, int>  $serviceIds
     * @param  array<int, string>  $operatingDays
     * @return array{hr: array<int, array<string, mixed>>, finance: array<int, array<string, mixed>>, photographers: array<int, array<string, mixed>>, schedule_ids: array<int, int>}
     */
    private function createStaff(
        int $studioIndex,
        int $studioId,
        int $ownerId,
        int $locationId,
        array $roleIds,
        array $serviceIds,
        array $operatingDays,
        string $startTime,
        string $endTime,
        Carbon $now
    ): array {
        $hr = [];
        $finance = [];
        $photographers = [];
        $scheduleIds = [];

        $roleAssignments = [];
        $photographerRows = [];

        for ($k = 1; $k <= self::STAFF_PER_STUDIO; $k++) {
            $sequence = self::SEQ_STAFF_BASE + $studioIndex * self::SEQ_STAFF_STRIDE + $k;
            [$baseRole, $userType, $scopedRole] = $this->staffRoleFor($k);

            $user = $this->upsertFreshUser($sequence, $baseRole, $userType, $locationId);

            $scheduleIds[$user->id] = (int) DB::table('tbl_studio_employee_schedule')->insertGetId([
                'user_id' => $user->id,
                'studio_id' => $studioId,
                'operating_days' => json_encode($operatingDays, JSON_THROW_ON_ERROR),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_active' => true,
                'notes' => 'Fresh seed studio staff schedule',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $roleAssignments[] = [
                'user_id' => $user->id,
                'role_id' => $roleIds[$scopedRole],
                'studio_id' => $studioId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $record = [
                'id' => $user->id,
                'sequence' => $sequence,
                'role' => $baseRole,
                'user_type' => $userType,
                'scoped_role' => $scopedRole,
            ];

            if ($baseRole === 'studio-hr') {
                $hr[] = $record;

                continue;
            }

            if ($baseRole === 'studio-finance') {
                $finance[] = $record;

                continue;
            }

            $photographerIndex = $k - 5;
            $profile = self::PHOTOGRAPHER_ROLES[$photographerIndex];

            $photographerRows[] = [
                'studio_id' => $studioId,
                'owner_id' => $ownerId,
                'photographer_id' => $user->id,
                'position' => $profile['position'],
                // specialization is a foreign key into tbl_services, not a label.
                'specialization' => $serviceIds[$photographerIndex % count($serviceIds)],
                'years_of_experience' => $profile['years'],
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $photographers[] = $record + [
                'position' => $profile['position'],
                'years_of_experience' => $profile['years'],
            ];
        }

        DB::table('tbl_user_roles')->insert($roleAssignments);
        DB::table('tbl_studio_photographers')->insert($photographerRows);

        return [
            'hr' => $hr,
            'finance' => $finance,
            'photographers' => $photographers,
            'schedule_ids' => $scheduleIds,
        ];
    }

    /**
     * Map a staff slot to its base role, user type, and scoped RBAC role.
     *
     * @return array{0: string, 1: string, 2: string}
     */
    private function staffRoleFor(int $slot): array
    {
        return match (true) {
            $slot === 1 => ['studio-hr', 'Manager', 'studio-hr-manager'],
            $slot === 2 => ['studio-hr', 'Staff', 'studio-hr-staff'],
            $slot === 3 => ['studio-finance', 'Manager', 'studio-finance-manager'],
            $slot === 4 => ['studio-finance', 'Staff', 'studio-finance-staff'],
            default => ['studio-photographer', 'Photographer', 'studio-photographer'],
        };
    }

    private function assignRole(int $userId, int $roleId, int $studioId, Carbon $now): void
    {
        DB::table('tbl_user_roles')->insert([
            'user_id' => $userId,
            'role_id' => $roleId,
            'studio_id' => $studioId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function packageDescription(string $categoryName, string $tier): string
    {
        return match ($categoryName) {
            'Wedding Photography' => "{$tier} wedding coverage with guided timeline planning, ceremony documentation, and a curated portrait set.",
            'Event Photography' => "{$tier} event coverage for programmes and celebrations, balancing stage documentation with guest candids.",
            'Family Portrait' => "{$tier} family session with relaxed direction, group and individual variations, and print-ready selects.",
            'Product Photography' => "{$tier} product coverage for catalogue and storefront use, with consistent lighting and colour handling.",
            'Street Photography' => "{$tier} street session covering candid neighbourhood scenes with a documentary edit pass.",
            'Fashion Photography' => "{$tier} editorial coverage for lookbooks and campaigns, with styling direction and controlled colour grading.",
            'Documentary Photography' => "{$tier} long-form documentary coverage built around sustained access and a sequenced final edit.",
            'Food Photography' => "{$tier} food coverage for menus and features, covering plating, ambience, and step sequences.",
            'Real Estate Photography' => "{$tier} property coverage across interiors, exteriors, and listing highlights with corrected verticals.",
            default => "{$tier} pet session paced around the animal's comfort, with owner portraits and playful motion frames.",
        };
    }

    /**
     * @return array<int, string>
     */
    private function packageInclusions(string $categoryName, string $tier): array
    {
        $common = match ($tier) {
            'Basic' => [
                'Pre-session planning call',
                'Agreed shot list',
                'Colour-corrected final selects',
            ],
            'Essentials' => [
                'Planning and creative alignment call',
                'Expanded guided coverage',
                'Priority editing queue',
                'Social-ready crops of the final selects',
            ],
            default => [
                'Full creative planning support',
                'Extended coverage with a second shooter',
                'Priority editing queue',
                'Print and web export sets',
                'Post-session review and handoff call',
            ],
        };

        return array_merge($common, match ($categoryName) {
            'Wedding Photography' => ['Ceremony timeline coordination', 'Couple portrait session'],
            'Event Photography' => ['Programme and stage coverage', 'Guest candid set'],
            'Family Portrait' => ['Guided family posing flow', 'Individual portrait variations'],
            'Product Photography' => ['Consistent background treatment', 'Detail and texture frames'],
            'Street Photography' => ['Route planning for the session', 'Sequenced documentary edit'],
            'Fashion Photography' => ['Styling and composition direction', 'Editorial framing set'],
            'Documentary Photography' => ['Subject access scheduling', 'Narrative sequence build'],
            'Food Photography' => ['Plating and styling support', 'Ambience context frames'],
            'Real Estate Photography' => ['Room-by-room interior set', 'Facade and approach frames'],
            default => ['Temperament-friendly pacing', 'Owner and pet portrait set'],
        });
    }
}
