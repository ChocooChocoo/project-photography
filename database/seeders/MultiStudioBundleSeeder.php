<?php

namespace Database\Seeders;

use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MultiStudioBundleSeeder extends Seeder
{
    /**
     * Seeded bundled studio names.
     *
     * @var array<int, string>
     */
    public const STUDIO_NAMES = [
        'Aurora Wedding House',
        'North Frame Collective',
        'Golden Lens House',
    ];

    /**
     * Default seeded password.
     */
    private const DEFAULT_PASSWORD = 'password';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $roleIds = DB::table('tbl_roles')
            ->whereIn('name', [
                'owner-super-admin',
                'studio-hr-manager',
                'studio-hr-staff',
                'studio-finance-manager',
                'studio-finance-staff',
                'studio-photographer',
            ])
            ->pluck('id', 'name');

        $requiredRoles = [
            'owner-super-admin',
            'studio-hr-manager',
            'studio-hr-staff',
            'studio-finance-manager',
            'studio-finance-staff',
            'studio-photographer',
        ];

        foreach ($requiredRoles as $roleName) {
            if (!$roleIds->has($roleName)) {
                $this->command?->error("Missing required RBAC role [{$roleName}]. Run RBAC seeders first.");
                return;
            }
        }

        $studioDefinitions = $this->buildStudioDefinitions();
        $requiredCategoryNames = collect($studioDefinitions)
            ->pluck('categories')
            ->flatten(1)
            ->pluck('name')
            ->unique()
            ->values();

        $categoryIds = DB::table('tbl_categories')
            ->whereIn('category_name', $requiredCategoryNames)
            ->pluck('id', 'category_name');

        foreach ($requiredCategoryNames as $categoryName) {
            if (!$categoryIds->has($categoryName)) {
                $this->command?->error("Missing required category [{$categoryName}].");
                return;
            }
        }

        foreach ($studioDefinitions as $studioIndex => $definition) {
            $owner = $this->upsertUser($definition['owner']);
            $studio = $this->upsertStudio($definition, $owner->id);

            $this->removeLegacyStudioEmployeeAssignments($studio->id);
            $this->syncUserRole($owner->id, (int) $roleIds['owner-super-admin'], $studio->id, $now);

            $resolvedCategories = [];
            foreach ($definition['categories'] as $categoryDefinition) {
                $categoryName = $categoryDefinition['name'];
                $categoryId = (int) $categoryIds[$categoryName];

                $resolvedCategories[] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'services' => $categoryDefinition['services'],
                    'package_family' => $categoryDefinition['package_family'],
                    'coverage_scope' => $categoryDefinition['coverage_scope'],
                ];

                DB::table('pvt_studio_categories')->updateOrInsert(
                    [
                        'user_id' => $owner->id,
                        'studio_id' => $studio->id,
                        'category_id' => $categoryId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }

            if ($resolvedCategories !== []) {
                $studio->update([
                    'category_id' => $resolvedCategories[0]['id'],
                ]);
            }

            $serviceMap = $this->seedServices($studio->id, $resolvedCategories, $now);
            $this->seedStudioSchedule($studio, $definition);
            $this->seedEmployees($definition['code'], $studioIndex, $studio->id, $owner->id, $roleIds->all(), $serviceMap, $definition['operating_days'], $definition['start_time'], $definition['end_time'], $now);
            $this->seedPackages($studio->id, $resolvedCategories, $now);

            $this->command?->info("Seeded bundled studio: {$studio->studio_name}");
        }
    }

    /**
     * Build deterministic studio definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildStudioDefinitions(): array
    {
        return [
            [
                'code' => 'aurora',
                'location_id' => 6,
                'street' => '2F Solis Arcade, Aguinaldo Highway',
                'barangay' => 'Bucandala III',
                'attendance_latitude' => 14.4091123,
                'attendance_longitude' => 120.9402451,
                'attendance_radius_meters' => 50,
                'contact_number' => '09172010001',
                'studio_email' => 'hello@auroraweddinghouse.test',
                'facebook_url' => 'https://facebook.com/auroraweddinghouse',
                'instagram_url' => 'https://instagram.com/auroraweddinghouse',
                'website_url' => 'https://auroraweddinghouse.test',
                'studio_name' => 'Aurora Wedding House',
                'studio_type' => 'photography_studio',
                'year_established' => 2021,
                'studio_description' => 'A bright, full-service studio brand focused on elegant wedding stories and polished event documentation for modern celebrations across Cavite.',
                'studio_logo' => 'studio_logo/seed-aurora-wedding-house.png',
                'starting_price' => '6500',
                'downpayment_percentage' => 40.00,
                'operating_days' => ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'max_clients_per_day' => 4,
                'advance_booking_days' => 3,
                'business_permit' => 'studio_documents/seed-aurora-business-permit.pdf',
                'owner_id_document' => 'studio_documents/seed-aurora-owner-id.pdf',
                'owner' => [
                    'role' => 'owner',
                    'user_type' => 'Photographer',
                    'first_name' => 'Althea',
                    'middle_name' => 'Marie',
                    'last_name' => 'Navarro',
                    'email' => 'seed.owner.aurora@lumora.test',
                    'mobile_number' => '+639188100001',
                    'location_id' => 6,
                ],
                'categories' => [
                    [
                        'name' => 'Wedding Photography',
                        'package_family' => 'Signature Wedding Story',
                        'coverage_scope' => ['Cavite', 'Metro Manila', 'Tagaytay'],
                        'services' => [
                            'Full-Day Wedding Coverage',
                            'Engagement Session Direction',
                            'Bridal Preparation Storytelling',
                        ],
                    ],
                    [
                        'name' => 'Event Photography',
                        'package_family' => 'Celebration Event Coverage',
                        'coverage_scope' => ['Cavite', 'Metro Manila'],
                        'services' => [
                            'Corporate Event Documentation',
                            'Private Celebration Coverage',
                            'Stage and Guest Highlights',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'northframe',
                'location_id' => 7,
                'street' => 'The Courtyard, Emilio Aguinaldo Highway',
                'barangay' => 'Mataas Na Burol',
                'attendance_latitude' => 14.2318821,
                'attendance_longitude' => 120.9743015,
                'attendance_radius_meters' => 45,
                'contact_number' => '09172010002',
                'studio_email' => 'contact@northframecollective.test',
                'facebook_url' => 'https://facebook.com/northframecollective',
                'instagram_url' => 'https://instagram.com/northframecollective',
                'website_url' => 'https://northframecollective.test',
                'studio_name' => 'North Frame Collective',
                'studio_type' => 'photography_studio',
                'year_established' => 2020,
                'studio_description' => 'A lifestyle-forward creative studio known for warm family portrait sessions, fashion editorials, and clean art direction for growing brands.',
                'studio_logo' => 'studio_logo/seed-north-frame-collective.png',
                'starting_price' => '4200',
                'downpayment_percentage' => 35.00,
                'operating_days' => ['monday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'start_time' => '10:00:00',
                'end_time' => '19:00:00',
                'max_clients_per_day' => 5,
                'advance_booking_days' => 2,
                'business_permit' => 'studio_documents/seed-northframe-business-permit.pdf',
                'owner_id_document' => 'studio_documents/seed-northframe-owner-id.pdf',
                'owner' => [
                    'role' => 'owner',
                    'user_type' => 'Photographer',
                    'first_name' => 'Cedric',
                    'middle_name' => 'Lane',
                    'last_name' => 'Rivera',
                    'email' => 'seed.owner.northframe@lumora.test',
                    'mobile_number' => '+639188100002',
                    'location_id' => 7,
                ],
                'categories' => [
                    [
                        'name' => 'Family Portrait',
                        'package_family' => 'Family Legacy Session',
                        'coverage_scope' => ['Silang', 'Tagaytay', 'Cavite'],
                        'services' => [
                            'In-Studio Family Portraits',
                            'Outdoor Family Lifestyle Session',
                            'Multi-Generation Portrait Direction',
                        ],
                    ],
                    [
                        'name' => 'Fashion Photography',
                        'package_family' => 'Editorial Fashion Session',
                        'coverage_scope' => ['Cavite', 'Metro Manila', 'Tagaytay'],
                        'services' => [
                            'Lookbook Production',
                            'Campaign Editorial Shoots',
                            'Designer Capsule Feature Session',
                        ],
                    ],
                ],
            ],
            [
                'code' => 'goldenlens',
                'location_id' => 2,
                'street' => 'Ground Floor, Evo Marketplace, Arnaldo Highway',
                'barangay' => 'Manggahan',
                'attendance_latitude' => 14.3612334,
                'attendance_longitude' => 120.8824543,
                'attendance_radius_meters' => 60,
                'contact_number' => '09172010003',
                'studio_email' => 'studio@goldenlenshouse.test',
                'facebook_url' => 'https://facebook.com/goldenlenshouse',
                'instagram_url' => 'https://instagram.com/goldenlenshouse',
                'website_url' => 'https://goldenlenshouse.test',
                'studio_name' => 'Golden Lens House',
                'studio_type' => 'mixed_media',
                'year_established' => 2023,
                'studio_description' => 'A compact production house built for product staging and pet portrait sessions, combining commercial discipline with playful, client-friendly studio handling.',
                'studio_logo' => 'studio_logo/seed-golden-lens-house.png',
                'starting_price' => '3800',
                'downpayment_percentage' => 30.00,
                'operating_days' => ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                'start_time' => '08:30:00',
                'end_time' => '17:30:00',
                'max_clients_per_day' => 6,
                'advance_booking_days' => 2,
                'business_permit' => 'studio_documents/seed-goldenlens-business-permit.pdf',
                'owner_id_document' => 'studio_documents/seed-goldenlens-owner-id.pdf',
                'owner' => [
                    'role' => 'owner',
                    'user_type' => 'Photographer',
                    'first_name' => 'Marion',
                    'middle_name' => 'Cruz',
                    'last_name' => 'Santiago',
                    'email' => 'seed.owner.goldenlens@lumora.test',
                    'mobile_number' => '+639188100003',
                    'location_id' => 2,
                ],
                'categories' => [
                    [
                        'name' => 'Product Photography',
                        'package_family' => 'Commerce Product Suite',
                        'coverage_scope' => ['Cavite', 'Metro Manila'],
                        'services' => [
                            'Catalog Product Setups',
                            'Marketplace Listing Photography',
                            'Styled Brand Asset Production',
                        ],
                    ],
                    [
                        'name' => 'Pet Photography',
                        'package_family' => 'Pawtrait Experience',
                        'coverage_scope' => ['General Trias', 'Cavite', 'Metro Manila'],
                        'services' => [
                            'Pet Personality Portraits',
                            'Owner and Pet Lifestyle Session',
                            'Seasonal Pet Mini Sessions',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create or update a user record for the seed bundle.
     *
     * @param array<string, string> $definition
     */
    private function upsertUser(array $definition): UserModel
    {
        $existingUuid = UserModel::query()
            ->where('email', $definition['email'])
            ->value('uuid');

        return UserModel::updateOrCreate(
            ['email' => $definition['email']],
            [
                'uuid' => $existingUuid ?: (string) Str::uuid(),
                'role' => $definition['role'],
                'user_type' => $definition['user_type'],
                'first_name' => $definition['first_name'],
                'middle_name' => $definition['middle_name'],
                'last_name' => $definition['last_name'],
                'mobile_number' => $definition['mobile_number'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
                'location_id' => $definition['location_id'] ?? null,
            ]
        );
    }

    /**
     * Create or update one studio record.
     *
     * @param array<string, mixed> $definition
     */
    private function upsertStudio(array $definition, int $ownerId): StudiosModel
    {
        return StudiosModel::updateOrCreate(
            ['studio_name' => $definition['studio_name']],
            [
                'user_id' => $ownerId,
                'category_id' => null,
                'location_id' => $definition['location_id'],
                'street' => $definition['street'],
                'barangay' => $definition['barangay'],
                'attendance_latitude' => $definition['attendance_latitude'],
                'attendance_longitude' => $definition['attendance_longitude'],
                'attendance_radius_meters' => $definition['attendance_radius_meters'],
                'contact_number' => $definition['contact_number'],
                'studio_email' => $definition['studio_email'],
                'facebook_url' => $definition['facebook_url'],
                'instagram_url' => $definition['instagram_url'],
                'website_url' => $definition['website_url'],
                'studio_type' => $definition['studio_type'],
                'year_established' => $definition['year_established'],
                'studio_description' => $definition['studio_description'],
                'studio_logo' => $definition['studio_logo'],
                'starting_price' => $definition['starting_price'],
                'downpayment_percentage' => $definition['downpayment_percentage'],
                'operating_days' => $definition['operating_days'],
                'start_time' => $definition['start_time'],
                'end_time' => $definition['end_time'],
                'max_clients_per_day' => $definition['max_clients_per_day'],
                'advance_booking_days' => $definition['advance_booking_days'],
                'business_permit' => $definition['business_permit'],
                'owner_id_document' => $definition['owner_id_document'],
                'status' => 'verified',
                'rejection_note' => null,
            ]
        );
    }

    /**
     * Seed service rows by category for one studio.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, ServicesModel>
     */
    private function seedServices(int $studioId, array $categories, Carbon $now): array
    {
        $serviceMap = [];

        foreach ($categories as $category) {
            $service = ServicesModel::updateOrCreate(
                [
                    'studio_id' => $studioId,
                    'category_id' => $category['id'],
                ],
                [
                    'service_name' => $category['services'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $serviceMap[$category['id']] = $service;
        }

        return $serviceMap;
    }

    /**
     * Seed one studio-level operating schedule.
     *
     * @param array<string, mixed> $definition
     */
    private function seedStudioSchedule(StudiosModel $studio, array $definition): void
    {
        StudioScheduleModel::updateOrCreate(
            [
                'studio_id' => $studio->id,
                'location_id' => $definition['location_id'],
            ],
            [
                'operating_days' => $definition['operating_days'],
                'opening_time' => $definition['start_time'],
                'closing_time' => $definition['end_time'],
                'booking_limit' => $definition['max_clients_per_day'],
                'advance_booking' => $definition['advance_booking_days'],
            ]
        );
    }

    /**
     * Remove generic legacy seed users that were previously attached to bundled studios.
     */
    private function removeLegacyStudioEmployeeAssignments(int $studioId): void
    {
        $legacySeedUserIds = DB::table('tbl_user_roles as user_roles')
            ->join('tbl_users as users', 'users.id', '=', 'user_roles.user_id')
            ->where('user_roles.studio_id', $studioId)
            ->where('users.email', 'like', 'seed.studio-%@lumora.test')
            ->pluck('users.id')
            ->unique()
            ->values();

        if ($legacySeedUserIds->isEmpty()) {
            return;
        }

        DB::table('tbl_employee_attendance')
            ->where('studio_id', $studioId)
            ->whereIn('user_id', $legacySeedUserIds)
            ->delete();

        DB::table('tbl_employee_payroll')
            ->where('studio_id', $studioId)
            ->whereIn('user_id', $legacySeedUserIds)
            ->delete();

        DB::table('tbl_studio_photographers')
            ->where('studio_id', $studioId)
            ->whereIn('photographer_id', $legacySeedUserIds)
            ->delete();

        DB::table('tbl_studio_employee_schedule')
            ->where('studio_id', $studioId)
            ->whereIn('user_id', $legacySeedUserIds)
            ->delete();

        DB::table('tbl_user_roles')
            ->where('studio_id', $studioId)
            ->whereIn('user_id', $legacySeedUserIds)
            ->delete();
    }

    /**
     * Seed all employees, schedules, studio-photographer rows, and scoped role assignments.
     *
     * @param array<string, int> $roleIds
     * @param array<int, ServicesModel> $serviceMap
     * @param array<int, string> $operatingDays
     */
    private function seedEmployees(
        string $studioCode,
        int $studioIndex,
        int $studioId,
        int $ownerId,
        array $roleIds,
        array $serviceMap,
        array $operatingDays,
        string $startTime,
        string $endTime,
        Carbon $now
    ): void {
        $serviceIds = array_values(array_map(static fn (ServicesModel $service) => (int) $service->id, $serviceMap));
        $employeeDefinitions = $this->buildEmployeeDefinitions($studioCode, $studioIndex);

        foreach ($employeeDefinitions as $employeeIndex => $definition) {
            $user = $this->upsertUser($definition);

            EmployeeScheduleModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'studio_id' => $studioId,
                ],
                [
                    'operating_days' => $operatingDays,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_active' => true,
                    'notes' => 'Seeded bundled studio employee schedule',
                ]
            );

            $scopedRoleName = $this->resolveScopedRoleName($definition['role'], $definition['user_type']);
            $this->syncUserRole($user->id, $roleIds[$scopedRoleName], $studioId, $now);

            if ($definition['role'] !== 'studio-photographer') {
                continue;
            }

            $specializationId = $serviceIds[$employeeIndex % count($serviceIds)];

            StudioPhotographersModel::updateOrCreate(
                [
                    'studio_id' => $studioId,
                    'photographer_id' => $user->id,
                ],
                [
                    'owner_id' => $ownerId,
                    'position' => $definition['position'],
                    'specialization' => $specializationId,
                    'years_of_experience' => $definition['years_of_experience'],
                    'status' => 'active',
                ]
            );
        }
    }

    /**
     * Build deterministic employee definitions per studio.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildEmployeeDefinitions(string $studioCode, int $studioIndex): array
    {
        $seedBase = ($studioIndex + 1) * 100;

        return [
            $this->makeEmployeeDefinition($studioCode, 'studio-hr', 'Manager', 'Hannah', 'Joy', 'Velasco', $seedBase + 1),
            $this->makeEmployeeDefinition($studioCode, 'studio-hr', 'Staff', 'Bianca', 'Mae', 'Torres', $seedBase + 2),
            $this->makeEmployeeDefinition($studioCode, 'studio-hr', 'Staff', 'Celine', 'Rose', 'Atienza', $seedBase + 3),
            $this->makeEmployeeDefinition($studioCode, 'studio-hr', 'Staff', 'Dana', 'Faith', 'Mercado', $seedBase + 4),
            $this->makeEmployeeDefinition($studioCode, 'studio-hr', 'Staff', 'Erika', 'Lou', 'Salvador', $seedBase + 5),
            $this->makeEmployeeDefinition($studioCode, 'studio-finance', 'Manager', 'Felix', 'James', 'Natividad', $seedBase + 6),
            $this->makeEmployeeDefinition($studioCode, 'studio-finance', 'Staff', 'Gail', 'Marie', 'Aurelio', $seedBase + 7),
            $this->makeEmployeeDefinition($studioCode, 'studio-finance', 'Staff', 'Harold', 'Dean', 'Ramos', $seedBase + 8),
            $this->makeEmployeeDefinition($studioCode, 'studio-finance', 'Staff', 'Ivy', 'Nicole', 'Lazaro', $seedBase + 9),
            $this->makeEmployeeDefinition($studioCode, 'studio-finance', 'Staff', 'Jules', 'Paolo', 'Estrada', $seedBase + 10),
            $this->makePhotographerDefinition($studioCode, 'Kyla', 'Anne', 'Montes', $seedBase + 11, 'Lead Photographer', 7),
            $this->makePhotographerDefinition($studioCode, 'Luca', 'Miguel', 'De Leon', $seedBase + 12, 'Senior Photographer', 6),
            $this->makePhotographerDefinition($studioCode, 'Mira', 'Santos', 'Valerio', $seedBase + 13, 'Creative Photographer', 5),
            $this->makePhotographerDefinition($studioCode, 'Noel', 'Cruz', 'Ferrer', $seedBase + 14, 'Editorial Photographer', 4),
            $this->makePhotographerDefinition($studioCode, 'Orla', 'Mae', 'Gutierrez', $seedBase + 15, 'Associate Photographer', 3),
        ];
    }

    /**
     * Create a deterministic HR or finance employee payload.
     *
     * @return array<string, mixed>
     */
    private function makeEmployeeDefinition(
        string $studioCode,
        string $role,
        string $userType,
        string $firstName,
        string $middleName,
        string $lastName,
        int $sequence
    ): array {
        return [
            'role' => $role,
            'user_type' => $userType,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => sprintf('seed.%s.%s.%03d@lumora.test', $studioCode, $role, $sequence),
            'mobile_number' => $this->makeMobileNumber($sequence),
        ];
    }

    /**
     * Create a deterministic photographer payload.
     *
     * @return array<string, mixed>
     */
    private function makePhotographerDefinition(
        string $studioCode,
        string $firstName,
        string $middleName,
        string $lastName,
        int $sequence,
        string $position,
        int $yearsOfExperience
    ): array {
        return [
            'role' => 'studio-photographer',
            'user_type' => 'Photographer',
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => sprintf('seed.%s.photographer.%03d@lumora.test', $studioCode, $sequence),
            'mobile_number' => $this->makeMobileNumber($sequence),
            'position' => $position,
            'years_of_experience' => $yearsOfExperience,
        ];
    }

    /**
     * Seed 3 tiered packages for each seeded category.
     *
     * @param array<int, array<string, mixed>> $categories
     */
    private function seedPackages(int $studioId, array $categories, Carbon $now): void
    {
        $tierDefinitions = [
            [
                'tier' => 'Basic',
                'duration' => 3,
                'maximum_edited_photos' => 120,
                'photographer_count' => 1,
                'allow_multiple_locations' => false,
                'max_locations' => 1,
                'allow_time_customization' => false,
                'location' => ['In-Studio'],
                'price_multiplier' => 1.00,
            ],
            [
                'tier' => 'Essentials',
                'duration' => 5,
                'maximum_edited_photos' => 220,
                'photographer_count' => 1,
                'allow_multiple_locations' => false,
                'max_locations' => 1,
                'allow_time_customization' => false,
                'location' => ['In-Studio', 'On-Location'],
                'price_multiplier' => 1.45,
            ],
            [
                'tier' => 'Premium',
                'duration' => 8,
                'maximum_edited_photos' => 400,
                'photographer_count' => 2,
                'allow_multiple_locations' => true,
                'max_locations' => 3,
                'allow_time_customization' => true,
                'location' => ['On-Location'],
                'price_multiplier' => 2.10,
            ],
        ];

        foreach ($categories as $categoryIndex => $category) {
            $basePrice = 6500 + ($categoryIndex * 1800);

            foreach ($tierDefinitions as $tierDefinition) {
                $packageName = sprintf('%s - %s', $category['package_family'], $tierDefinition['tier']);

                PackagesModel::updateOrCreate(
                    [
                        'studio_id' => $studioId,
                        'category_id' => $category['id'],
                        'package_name' => $packageName,
                    ],
                    [
                        'package_description' => $this->buildPackageDescription($category['name'], $tierDefinition['tier']),
                        'package_inclusions' => $this->buildPackageInclusions($category['name'], $tierDefinition['tier']),
                        'duration' => $tierDefinition['duration'],
                        'maximum_edited_photos' => $tierDefinition['maximum_edited_photos'],
                        'coverage_scope' => json_encode($category['coverage_scope'], JSON_THROW_ON_ERROR),
                        'package_location' => $tierDefinition['location'],
                        'allow_multiple_locations' => $tierDefinition['allow_multiple_locations'],
                        'max_locations' => $tierDefinition['max_locations'],
                        'allow_time_customization' => $tierDefinition['allow_time_customization'],
                        'package_price' => round($basePrice * $tierDefinition['price_multiplier'], 2),
                        'online_gallery' => true,
                        'photographer_count' => $tierDefinition['photographer_count'],
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Build a short package description by category and tier.
     */
    private function buildPackageDescription(string $categoryName, string $tier): string
    {
        return match ($categoryName) {
            'Wedding Photography' => "{$tier} coverage for intimate ceremonies and wedding milestones with polished storytelling and guided portraits.",
            'Event Photography' => "{$tier} event coverage for launches, private celebrations, and branded gatherings with strong guest and stage documentation.",
            'Family Portrait' => "{$tier} portrait coverage designed for relaxed family storytelling, guided posing, and timeless frame-ready image sets.",
            'Fashion Photography' => "{$tier} editorial coverage for lookbooks, campaigns, and designer drops with art direction support and clean post-processing.",
            'Product Photography' => "{$tier} commercial coverage for catalog-ready product assets, storefront listings, and promotional brand visuals.",
            'Pet Photography' => "{$tier} pet portrait coverage that balances comfort, playful direction, and polished keepsake imagery for owners and brands.",
            default => "{$tier} coverage tailored for {$categoryName} sessions with curated deliverables and polished post-processing.",
        };
    }

    /**
     * Build package inclusions by category tier.
     *
     * @return array<int, string>
     */
    private function buildPackageInclusions(string $categoryName, string $tier): array
    {
        $common = match ($tier) {
            'Basic' => [
                'Pre-session planning call',
                'Curated shot list alignment',
                'Private online gallery',
                'High-resolution edited image delivery',
            ],
            'Essentials' => [
                'Planning and creative alignment call',
                'Expanded guided coverage',
                'Private online gallery with downloads',
                'Priority image curation and editing',
                'Delivery-ready social media crops',
            ],
            default => [
                'Full creative planning support',
                'Extended coverage with backup pacing',
                'Private premium online gallery',
                'Priority editing queue',
                'Multi-use export set for web and print',
                'Post-session review and final delivery handoff',
            ],
        };

        $categorySpecific = match ($categoryName) {
            'Wedding Photography' => [
                'Timeline coordination for key moments',
                'Ceremony and portrait highlights',
            ],
            'Event Photography' => [
                'Guest candid documentation',
                'Speaker and stage highlight coverage',
            ],
            'Family Portrait' => [
                'Guided family posing flow',
                'Group and individual portrait variations',
            ],
            'Fashion Photography' => [
                'Styling and composition direction',
                'Editorial framing set',
            ],
            'Product Photography' => [
                'Clean-background product captures',
                'Detail and texture highlight frames',
            ],
            'Pet Photography' => [
                'Temperament-friendly pacing',
                'Owner-and-pet portrait set',
            ],
            default => [
                'Category-specific session planning',
                'Curated final image selection',
            ],
        };

        return array_merge($common, $categorySpecific);
    }

    /**
     * Resolve the scoped RBAC role name from base role and user type.
     */
    private function resolveScopedRoleName(string $role, string $userType): string
    {
        $isManager = strtolower($userType) === 'manager';

        return match ($role) {
            'studio-hr' => $isManager ? 'studio-hr-manager' : 'studio-hr-staff',
            'studio-finance' => $isManager ? 'studio-finance-manager' : 'studio-finance-staff',
            'studio-photographer' => 'studio-photographer',
            default => throw new \InvalidArgumentException("Unsupported scoped role resolution for [{$role}]"),
        };
    }

    /**
     * Insert or refresh one scoped role assignment.
     */
    private function syncUserRole(int $userId, int $roleId, int $studioId, Carbon $now): void
    {
        DB::table('tbl_user_roles')->updateOrInsert(
            [
                'user_id' => $userId,
                'role_id' => $roleId,
                'studio_id' => $studioId,
            ],
            [
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    /**
     * Generate a deterministic seeded mobile number.
     */
    private function makeMobileNumber(int $sequence): string
    {
        return '+63917' . str_pad((string) (700000 + $sequence), 6, '0', STR_PAD_LEFT);
    }
}
