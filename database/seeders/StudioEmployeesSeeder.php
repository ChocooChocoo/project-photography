<?php

namespace Database\Seeders;

use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudioEmployeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staffStudios = StudiosModel::query()
            ->whereIn('status', ['verified', 'active'])
            ->orderBy('id')
            ->get();

        if ($staffStudios->isEmpty()) {
            $this->command?->warn('No verified or active studios found. Studio employees seeder skipped.');
            return;
        }

        $photographerStudios = $staffStudios
            ->filter(function (StudiosModel $studio) {
                return ServicesModel::where('studio_id', $studio->id)->exists();
            })
            ->values();

        if ($photographerStudios->isEmpty()) {
            $this->command?->warn('No studios with services found. Photographer records will be skipped.');
        }

        $roles = RoleModel::whereIn('name', [
            'studio-hr-manager',
            'studio-hr-staff',
            'studio-finance-manager',
            'studio-finance-staff',
            'studio-photographer',
        ])->get()->keyBy('name');

        $requiredRoles = [
            'studio-hr-manager',
            'studio-hr-staff',
            'studio-finance-manager',
            'studio-finance-staff',
            'studio-photographer',
        ];

        foreach ($requiredRoles as $requiredRole) {
            if (!$roles->has($requiredRole)) {
                $this->command?->error("Missing required role: {$requiredRole}. Run the RBAC seeders first.");
                return;
            }
        }

        $seedDefinitions = $this->buildSeedDefinitions();
        $staffStudioIndex = 0;
        $photographerStudioIndex = 0;
        $serviceIndexes = [];

        foreach ($seedDefinitions as $definition) {
            $isPhotographer = $definition['role_name'] === 'studio-photographer';
            $studioPool = $isPhotographer ? $photographerStudios : $staffStudios;

            if ($studioPool->isEmpty()) {
                $this->command?->warn("Skipped {$definition['email']} because no eligible studio was available.");
                continue;
            }

            $studioIndex = $isPhotographer ? $photographerStudioIndex : $staffStudioIndex;
            /** @var StudiosModel $studio */
            $studio = $studioPool[$studioIndex % $studioPool->count()];

            if ($isPhotographer) {
                $photographerStudioIndex++;
            } else {
                $staffStudioIndex++;
            }

            $role = $roles->get($definition['role_name']);
            [$baseRole, $userType] = $this->resolveBaseRoleAndUserType($definition['role_name']);

            $user = UserModel::updateOrCreate(
                ['email' => $definition['email']],
                [
                    'uuid' => UserModel::where('email', $definition['email'])->value('uuid') ?: (string) Str::uuid(),
                    'role' => $baseRole,
                    'first_name' => $definition['first_name'],
                    'middle_name' => $definition['middle_name'],
                    'last_name' => $definition['last_name'],
                    'user_type' => $userType,
                    'mobile_number' => $definition['mobile_number'],
                    'password' => Hash::make($definition['password']),
                    'status' => 'active',
                    'email_verified' => true,
                    'verification_token' => null,
                    'token_expiry' => null,
                ]
            );

            $user->assignRole($role);

            $operatingDays = $this->normalizeOperatingDays($studio->operating_days);
            $startTime = $studio->start_time ?: '09:00:00';
            $endTime = $studio->end_time ?: '18:00:00';

            EmployeeScheduleModel::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'studio_id' => $studio->id,
                ],
                [
                    'operating_days' => $operatingDays,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_active' => true,
                    'notes' => 'Seeded employee schedule',
                ]
            );

            if ($isPhotographer) {
                $service = $this->pickStudioService($studio->id, $serviceIndexes);

                if (!$service) {
                    $this->command?->warn("Skipped photographer profile for {$definition['email']} because studio {$studio->studio_name} has no service.");
                    continue;
                }

                StudioPhotographersModel::updateOrCreate(
                    [
                        'studio_id' => $studio->id,
                        'photographer_id' => $user->id,
                    ],
                    [
                        'owner_id' => $studio->user_id,
                        'position' => $definition['position'],
                        'specialization' => $service->id,
                        'years_of_experience' => $definition['years_of_experience'],
                        'status' => 'active',
                    ]
                );
            }

            $this->command?->info("Seeded employee: {$definition['email']} ({$definition['role_name']})");
        }
    }

    /**
     * Build employee seed payloads.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildSeedDefinitions(): array
    {
        return [
            $this->makeEmployee('studio-hr-manager', 'Ariana', 'Mae', 'Lopez', 1, 'Password@123'),
            $this->makeEmployee('studio-hr-staff', 'Bianca', 'Joy', 'Reyes', 1, 'Password@123'),
            $this->makeEmployee('studio-hr-staff', 'Camille', 'Rose', 'Torres', 2, 'Password@123'),
            $this->makeEmployee('studio-hr-staff', 'Danica', 'Faith', 'Mendoza', 3, 'Password@123'),
            $this->makeEmployee('studio-hr-staff', 'Elise', 'Anne', 'Garcia', 4, 'Password@123'),
            $this->makeEmployee('studio-finance-manager', 'Felix', 'James', 'Santos', 1, 'Password@123'),
            $this->makeEmployee('studio-finance-staff', 'Gavin', 'Lee', 'Flores', 1, 'Password@123'),
            $this->makeEmployee('studio-finance-staff', 'Hannah', 'Grace', 'Rivera', 2, 'Password@123'),
            $this->makeEmployee('studio-finance-staff', 'Ian', 'Paul', 'Navarro', 3, 'Password@123'),
            $this->makeEmployee('studio-finance-staff', 'Jessa', 'Marie', 'Aquino', 4, 'Password@123'),
            $this->makePhotographer('Kyla', 'May', 'Valdez', 1, 'Lead Photographer', 6, 'Password@123'),
            $this->makePhotographer('Liam', 'Cruz', 'Padilla', 2, 'Senior Photographer', 8, 'Password@123'),
            $this->makePhotographer('Mia', 'Nicole', 'Ramos', 3, 'Event Photographer', 5, 'Password@123'),
            $this->makePhotographer('Noah', 'Dean', 'Castro', 4, 'Portrait Photographer', 7, 'Password@123'),
            $this->makePhotographer('Olivia', 'Jane', 'Morales', 5, 'Lighting Photographer', 4, 'Password@123'),
            $this->makePhotographer('Paolo', 'Luis', 'Gutierrez', 6, 'Creative Photographer', 9, 'Password@123'),
            $this->makePhotographer('Queenie', 'Anne', 'Delos Santos', 7, 'Studio Photographer', 3, 'Password@123'),
            $this->makePhotographer('Rafael', 'Miguel', 'Herrera', 8, 'Product Photographer', 10, 'Password@123'),
            $this->makePhotographer('Sofia', 'Claire', 'Domingo', 9, 'Fashion Photographer', 6, 'Password@123'),
            $this->makePhotographer('Theo', 'Marc', 'Salazar', 10, 'Assistant Photographer', 2, 'Password@123'),
        ];
    }

    /**
     * Create a staff/manager seed entry.
     *
     * @return array<string, mixed>
     */
    private function makeEmployee(
        string $roleName,
        string $firstName,
        string $middleName,
        string $lastName,
        int $sequence,
        string $password
    ): array {
        return [
            'role_name' => $roleName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => "seed.{$roleName}.{$sequence}@lumora.test",
            'mobile_number' => $this->makeMobileNumber($sequence),
            'password' => $password,
        ];
    }

    /**
     * Create a photographer seed entry.
     *
     * @return array<string, mixed>
     */
    private function makePhotographer(
        string $firstName,
        string $middleName,
        string $lastName,
        int $sequence,
        string $position,
        int $yearsOfExperience,
        string $password
    ): array {
        return [
            'role_name' => 'studio-photographer',
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'email' => "seed.studio-photographer.{$sequence}@lumora.test",
            'mobile_number' => $this->makeMobileNumber(100 + $sequence),
            'password' => $password,
            'position' => $position,
            'years_of_experience' => $yearsOfExperience,
        ];
    }

    /**
     * Resolve the base user role and user_type from the RBAC role name.
     *
     * @return array{0: string, 1: string}
     */
    private function resolveBaseRoleAndUserType(string $roleName): array
    {
        if ($roleName === 'studio-photographer') {
            return ['studio-photographer', 'Photographer'];
        }

        $segments = explode('-', $roleName);
        $baseRole = $segments[0] . '-' . $segments[1];
        $userType = ucfirst($segments[2] ?? 'Staff');

        return [$baseRole, $userType];
    }

    /**
     * Normalize studio operating days for the employee schedule table.
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

        if (!is_array($operatingDays) || empty($operatingDays)) {
            return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        return array_values(array_map(static fn ($day) => strtolower((string) $day), $operatingDays));
    }

    /**
     * Pick a valid service for a studio in a round-robin manner.
     */
    private function pickStudioService(int $studioId, array &$serviceIndexes): ?ServicesModel
    {
        /** @var Collection<int, ServicesModel> $services */
        $services = ServicesModel::where('studio_id', $studioId)
            ->orderBy('id')
            ->get();

        if ($services->isEmpty()) {
            return null;
        }

        $currentIndex = $serviceIndexes[$studioId] ?? 0;
        $service = $services[$currentIndex % $services->count()];
        $serviceIndexes[$studioId] = $currentIndex + 1;

        return $service;
    }

    /**
     * Generate a unique-looking seeded mobile number.
     */
    private function makeMobileNumber(int $sequence): string
    {
        return '+63917' . str_pad((string) (500000 + $sequence), 6, '0', STR_PAD_LEFT);
    }
}
