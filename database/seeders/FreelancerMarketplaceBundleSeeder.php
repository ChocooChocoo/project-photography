<?php

namespace Database\Seeders;

use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\Freelancer\FreelancerScheduleModel;
use App\Models\Freelancer\PackagesModel as FreelancerPackagesModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\Freelancer\ServiceModel;
use App\Models\PaymentModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FreelancerMarketplaceBundleSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Password@123';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $freelancerDefinitions = $this->buildFreelancerDefinitions();
        $requiredCategoryNames = collect($freelancerDefinitions)
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
                $this->command?->error("Missing required freelancer category [{$categoryName}].");
                return;
            }
        }

        $client = $this->upsertClient();

        foreach ($freelancerDefinitions as $freelancerDefinition) {
            $user = $this->upsertFreelancerUser($freelancerDefinition);
            $profile = $this->upsertFreelancerProfile($user->id, $freelancerDefinition);

            $resolvedCategories = [];
            foreach ($freelancerDefinition['categories'] as $categoryDefinition) {
                $categoryId = (int) $categoryIds[$categoryDefinition['name']];

                DB::table('pvt_freelancer_categories')->updateOrInsert(
                    [
                        'user_id' => $user->id,
                        'category_id' => $categoryId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $resolvedCategories[] = [
                    'id' => $categoryId,
                    'name' => $categoryDefinition['name'],
                    'service_family' => $categoryDefinition['service_family'],
                    'services' => $categoryDefinition['services'],
                    'coverage_scope' => $categoryDefinition['coverage_scope'],
                ];
            }

            $this->upsertSchedule($user->id, $freelancerDefinition['schedule']);
            $packages = $this->seedServicesAndPackages($user->id, $resolvedCategories, $now);
            $this->seedBookingsAndPayments($user, $profile, $client, $freelancerDefinition['schedule'], $packages, $now);

            $this->command?->info("Seeded freelancer marketplace bundle: {$user->email}");
        }
    }

    /**
     * Build deterministic freelancer payloads.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildFreelancerDefinitions(): array
    {
        return [
            [
                'email' => 'seed.freelancer.everlight@lumora.test',
                'mobile_number' => '+639188200001',
                'first_name' => 'Elena',
                'middle_name' => 'Joy',
                'last_name' => 'Mendoza',
                'location_id' => 6,
                'brand_name' => 'Everlight Stories',
                'tagline' => 'Warm portraits for milestone celebrations',
                'bio' => 'Elena specializes in wedding and family portrait sessions with a bright, timeless editing style that balances guided posing and candid storytelling.',
                'years_experience' => 7,
                'brand_logo' => 'brand-logos/seed-everlight-stories.png',
                'street' => 'Blk 8 Lot 14, Garden Villas',
                'barangay' => 'Bayan Luma III',
                'service_area' => 'Cavite and nearby Metro Manila cities',
                'starting_price' => 4500.00,
                'deposit_policy' => 'required',
                'deposit_type' => 'percentage',
                'deposit_amount' => 30.00,
                'portfolio_works' => [
                    'portfolio-works/seed-everlight-1.png',
                    'portfolio-works/seed-everlight-2.png',
                    'portfolio-works/seed-everlight-3.png',
                ],
                'facebook_url' => 'https://facebook.com/everlightstories',
                'instagram_url' => 'https://instagram.com/everlightstories',
                'website_url' => 'https://everlightstories.test',
                'valid_id' => 'valid-ids/seed-everlight-id.jpg',
                'schedule' => [
                    'operating_days' => ['tuesday', 'thursday', 'friday', 'saturday'],
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'booking_limit' => 2,
                    'advance_booking' => 4,
                ],
                'categories' => [
                    [
                        'name' => 'Wedding Photography',
                        'service_family' => 'Wedding Story Collection',
                        'coverage_scope' => 'Cavite, Tagaytay, Metro Manila',
                        'services' => [
                            'Intimate Wedding Coverage',
                            'Engagement Session Direction',
                            'Bridal Portrait Session',
                        ],
                    ],
                    [
                        'name' => 'Family Portrait',
                        'service_family' => 'Heirloom Family Session',
                        'coverage_scope' => 'Cavite, Alabang, Tagaytay',
                        'services' => [
                            'Family Studio Portraits',
                            'Outdoor Lifestyle Portraits',
                            'Milestone Keepsake Sessions',
                        ],
                    ],
                ],
            ],
            [
                'email' => 'seed.freelancer.streetframe@lumora.test',
                'mobile_number' => '+639188200002',
                'first_name' => 'Marco',
                'middle_name' => 'Luis',
                'last_name' => 'Castillo',
                'location_id' => 2,
                'brand_name' => 'Streetframe Works',
                'tagline' => 'Fast, candid coverage for live events and brands',
                'bio' => 'Marco focuses on event and street-inspired brand coverage, delivering high-energy documentary style imagery for launches, activations, and celebrations.',
                'years_experience' => 6,
                'brand_logo' => 'brand-logos/seed-streetframe-works.png',
                'street' => 'Unit 5, Ferrer Business Strip',
                'barangay' => 'Manggahan',
                'service_area' => 'General Trias, Cavite, Metro Manila',
                'starting_price' => 3800.00,
                'deposit_policy' => 'required',
                'deposit_type' => 'fixed',
                'deposit_amount' => 2000.00,
                'portfolio_works' => [
                    'portfolio-works/seed-streetframe-1.png',
                    'portfolio-works/seed-streetframe-2.png',
                    'portfolio-works/seed-streetframe-3.png',
                ],
                'facebook_url' => 'https://facebook.com/streetframeworks',
                'instagram_url' => 'https://instagram.com/streetframeworks',
                'website_url' => 'https://streetframeworks.test',
                'valid_id' => 'valid-ids/seed-streetframe-id.jpg',
                'schedule' => [
                    'operating_days' => ['monday', 'wednesday', 'friday', 'sunday'],
                    'start_time' => '10:00:00',
                    'end_time' => '19:00:00',
                    'booking_limit' => 2,
                    'advance_booking' => 3,
                ],
                'categories' => [
                    [
                        'name' => 'Event Photography',
                        'service_family' => 'Live Event Coverage',
                        'coverage_scope' => 'Cavite, Metro Manila',
                        'services' => [
                            'Corporate Event Documentation',
                            'Birthday and Private Events',
                            'Launch and Activation Coverage',
                        ],
                    ],
                    [
                        'name' => 'Street Photography',
                        'service_family' => 'Urban Story Session',
                        'coverage_scope' => 'Metro Manila, Cavite',
                        'services' => [
                            'Lifestyle Street Portraits',
                            'Urban Editorial Sessions',
                            'Candid Day-in-the-Life Shoots',
                        ],
                    ],
                ],
            ],
            [
                'email' => 'seed.freelancer.modestudio@lumora.test',
                'mobile_number' => '+639188200003',
                'first_name' => 'Sofia',
                'middle_name' => 'Anne',
                'last_name' => 'Reyes',
                'location_id' => 7,
                'brand_name' => 'Mode Studio Social',
                'tagline' => 'Editorial fashion and campaign imagery',
                'bio' => 'Sofia works with designers, small labels, and personal brands to produce sharp editorial visuals and lookbook-ready sets with clean, premium retouching.',
                'years_experience' => 8,
                'brand_logo' => 'brand-logos/seed-mode-studio-social.png',
                'street' => 'Lot 2, Creative Grove',
                'barangay' => 'Balite II',
                'service_area' => 'Silang, Tagaytay, Metro Manila',
                'starting_price' => 5200.00,
                'deposit_policy' => 'required',
                'deposit_type' => 'percentage',
                'deposit_amount' => 40.00,
                'portfolio_works' => [
                    'portfolio-works/seed-mode-1.png',
                    'portfolio-works/seed-mode-2.png',
                    'portfolio-works/seed-mode-3.png',
                ],
                'facebook_url' => 'https://facebook.com/modestudiosocial',
                'instagram_url' => 'https://instagram.com/modestudiosocial',
                'website_url' => 'https://modestudiosocial.test',
                'valid_id' => 'valid-ids/seed-mode-id.jpg',
                'schedule' => [
                    'operating_days' => ['tuesday', 'wednesday', 'friday', 'saturday'],
                    'start_time' => '11:00:00',
                    'end_time' => '20:00:00',
                    'booking_limit' => 2,
                    'advance_booking' => 5,
                ],
                'categories' => [
                    [
                        'name' => 'Fashion Photography',
                        'service_family' => 'Editorial Fashion Set',
                        'coverage_scope' => 'Silang, Tagaytay, Metro Manila',
                        'services' => [
                            'Lookbook Photography',
                            'Campaign Editorial Shoots',
                            'Designer Capsule Sessions',
                        ],
                    ],
                    [
                        'name' => 'Product Photography',
                        'service_family' => 'Styled Commerce Studio',
                        'coverage_scope' => 'Cavite, Metro Manila',
                        'services' => [
                            'Styled Product Flats',
                            'Catalog Product Photography',
                            'Brand Social Media Assets',
                        ],
                    ],
                ],
            ],
            [
                'email' => 'seed.freelancer.homelight@lumora.test',
                'mobile_number' => '+639188200004',
                'first_name' => 'Paolo',
                'middle_name' => 'Miguel',
                'last_name' => 'Dizon',
                'location_id' => 18,
                'brand_name' => 'HomeLight Portrait Co.',
                'tagline' => 'Comfort-first portraits for families and pets',
                'bio' => 'Paolo creates relaxed portrait sessions for families, kids, and pets, with an emphasis on natural expressions, gentle pacing, and polished delivery sets.',
                'years_experience' => 5,
                'brand_logo' => 'brand-logos/seed-homelight-portrait-co.png',
                'street' => '12 Paseo Carmona Residences',
                'barangay' => 'Maduya',
                'service_area' => 'Carmona, Cavite, Alabang',
                'starting_price' => 4000.00,
                'deposit_policy' => 'not_required',
                'deposit_type' => 'fixed',
                'deposit_amount' => 0.00,
                'portfolio_works' => [
                    'portfolio-works/seed-homelight-1.png',
                    'portfolio-works/seed-homelight-2.png',
                    'portfolio-works/seed-homelight-3.png',
                ],
                'facebook_url' => 'https://facebook.com/homelightportraitco',
                'instagram_url' => 'https://instagram.com/homelightportraitco',
                'website_url' => 'https://homelightportraitco.test',
                'valid_id' => 'valid-ids/seed-homelight-id.jpg',
                'schedule' => [
                    'operating_days' => ['monday', 'thursday', 'saturday', 'sunday'],
                    'start_time' => '08:00:00',
                    'end_time' => '17:00:00',
                    'booking_limit' => 3,
                    'advance_booking' => 2,
                ],
                'categories' => [
                    [
                        'name' => 'Family Portrait',
                        'service_family' => 'Home Portrait Session',
                        'coverage_scope' => 'Carmona, Cavite, Alabang',
                        'services' => [
                            'In-Home Family Portraits',
                            'Outdoor Family Sessions',
                            'Milestone Kid Portraits',
                        ],
                    ],
                    [
                        'name' => 'Pet Photography',
                        'service_family' => 'Pet Pawtrait Session',
                        'coverage_scope' => 'Cavite, Alabang',
                        'services' => [
                            'Pet Personality Portraits',
                            'Owner and Pet Session',
                            'Seasonal Pet Studio Minis',
                        ],
                    ],
                ],
            ],
            [
                'email' => 'seed.freelancer.lensandland@lumora.test',
                'mobile_number' => '+639188200005',
                'first_name' => 'Tessa',
                'middle_name' => 'Claire',
                'last_name' => 'Morales',
                'location_id' => 21,
                'brand_name' => 'Lens & Land Creative',
                'tagline' => 'Places, products, and polished campaign frames',
                'bio' => 'Tessa delivers structured commercial sessions for property, product, and food clients who need consistent image sets for catalogs, listings, and promotional campaigns.',
                'years_experience' => 9,
                'brand_logo' => 'brand-logos/seed-lens-and-land.png',
                'street' => 'Harbor View Commercial Row',
                'barangay' => 'Tabon II',
                'service_area' => 'Kawit, Cavite, Metro Manila',
                'starting_price' => 5600.00,
                'deposit_policy' => 'required',
                'deposit_type' => 'fixed',
                'deposit_amount' => 3000.00,
                'portfolio_works' => [
                    'portfolio-works/seed-lensland-1.png',
                    'portfolio-works/seed-lensland-2.png',
                    'portfolio-works/seed-lensland-3.png',
                ],
                'facebook_url' => 'https://facebook.com/lensandlandcreative',
                'instagram_url' => 'https://instagram.com/lensandlandcreative',
                'website_url' => 'https://lensandlandcreative.test',
                'valid_id' => 'valid-ids/seed-lensland-id.jpg',
                'schedule' => [
                    'operating_days' => ['wednesday', 'thursday', 'friday', 'saturday'],
                    'start_time' => '09:30:00',
                    'end_time' => '18:30:00',
                    'booking_limit' => 2,
                    'advance_booking' => 4,
                ],
                'categories' => [
                    [
                        'name' => 'Product Photography',
                        'service_family' => 'Commerce Product Suite',
                        'coverage_scope' => 'Cavite, Metro Manila',
                        'services' => [
                            'Catalog Product Photography',
                            'Marketplace Listing Images',
                            'Styled Brand Asset Production',
                        ],
                    ],
                    [
                        'name' => 'Food Photography',
                        'service_family' => 'Menu Story Collection',
                        'coverage_scope' => 'Kawit, Cavite, Metro Manila',
                        'services' => [
                            'Menu Photography',
                            'Restaurant Campaign Shoots',
                            'Styled Food Content Sessions',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Create or update the freelancer user.
     *
     * @param array<string, mixed> $definition
     */
    private function upsertFreelancerUser(array $definition): UserModel
    {
        $existingUuid = UserModel::query()
            ->where('email', $definition['email'])
            ->value('uuid');

        return UserModel::updateOrCreate(
            ['email' => $definition['email']],
            [
                'uuid' => $existingUuid ?: (string) Str::uuid(),
                'role' => 'freelancer',
                'user_type' => 'Photographer',
                'first_name' => $definition['first_name'],
                'middle_name' => $definition['middle_name'],
                'last_name' => $definition['last_name'],
                'mobile_number' => $definition['mobile_number'],
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'location_id' => $definition['location_id'],
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
            ]
        );
    }

    /**
     * Create or update the freelancer profile.
     *
     * @param array<string, mixed> $definition
     */
    private function upsertFreelancerProfile(int $userId, array $definition): ProfileModel
    {
        return ProfileModel::updateOrCreate(
            ['user_id' => $userId],
            [
                'location_id' => $definition['location_id'],
                'brand_name' => $definition['brand_name'],
                'tagline' => $definition['tagline'],
                'bio' => $definition['bio'],
                'years_experience' => $definition['years_experience'],
                'brand_logo' => $definition['brand_logo'],
                'street' => $definition['street'],
                'barangay' => $definition['barangay'],
                'service_area' => $definition['service_area'],
                'starting_price' => $definition['starting_price'],
                'deposit_policy' => $definition['deposit_policy'],
                'deposit_type' => $definition['deposit_type'],
                'deposit_amount' => $definition['deposit_amount'],
                'portfolio_works' => $definition['portfolio_works'],
                'facebook_url' => $definition['facebook_url'],
                'instagram_url' => $definition['instagram_url'],
                'website_url' => $definition['website_url'],
                'valid_id' => $definition['valid_id'],
            ]
        );
    }

    /**
     * Create or update the freelancer schedule.
     *
     * @param array<string, mixed> $scheduleDefinition
     */
    private function upsertSchedule(int $userId, array $scheduleDefinition): void
    {
        FreelancerScheduleModel::updateOrCreate(
            ['user_id' => $userId],
            [
                'operating_days' => $scheduleDefinition['operating_days'],
                'start_time' => $scheduleDefinition['start_time'],
                'end_time' => $scheduleDefinition['end_time'],
                'booking_limit' => $scheduleDefinition['booking_limit'],
                'advance_booking' => $scheduleDefinition['advance_booking'],
            ]
        );
    }

    /**
     * Seed freelancer services and 6 tiered packages.
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, FreelancerPackagesModel>
     */
    private function seedServicesAndPackages(int $userId, array $categories, Carbon $now): array
    {
        $packages = [];
        $tierDefinitions = [
            [
                'tier' => 'Basic',
                'duration' => 2,
                'maximum_edited_photos' => 20,
                'allow_time_customization' => false,
                'online_gallery' => true,
                'allow_multiple_locations' => false,
                'max_locations' => 1,
                'price_multiplier' => 1.00,
            ],
            [
                'tier' => 'Essentials',
                'duration' => 4,
                'maximum_edited_photos' => 60,
                'allow_time_customization' => false,
                'online_gallery' => true,
                'allow_multiple_locations' => false,
                'max_locations' => 1,
                'price_multiplier' => 1.55,
            ],
            [
                'tier' => 'Premium',
                'duration' => null,
                'maximum_edited_photos' => 120,
                'allow_time_customization' => true,
                'online_gallery' => true,
                'allow_multiple_locations' => true,
                'max_locations' => 3,
                'price_multiplier' => 2.20,
            ],
        ];

        foreach ($categories as $index => $category) {
            ServiceModel::updateOrCreate(
                [
                    'user_id' => $userId,
                    'category_id' => $category['id'],
                ],
                [
                    'services_name' => $category['services'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );

            $basePrice = 3500 + ($index * 1500);

            foreach ($tierDefinitions as $tierDefinition) {
                $packageName = $category['service_family'] . ' - ' . $tierDefinition['tier'];

                $packages[] = FreelancerPackagesModel::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'category_id' => $category['id'],
                        'package_name' => $packageName,
                    ],
                    [
                        'package_description' => $this->buildPackageDescription($category['name'], $tierDefinition['tier']),
                        'package_inclusions' => $this->buildPackageInclusions($category['name'], $tierDefinition['tier']),
                        'allow_time_customization' => $tierDefinition['allow_time_customization'],
                        'duration' => $tierDefinition['duration'],
                        'maximum_edited_photos' => $tierDefinition['maximum_edited_photos'],
                        'coverage_scope' => $category['coverage_scope'],
                        'package_price' => round($basePrice * $tierDefinition['price_multiplier'], 2),
                        'online_gallery' => $tierDefinition['online_gallery'],
                        'allow_multiple_locations' => $tierDefinition['allow_multiple_locations'],
                        'max_locations' => $tierDefinition['max_locations'],
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        return $packages;
    }

    /**
     * Seed three completed bookings and payments per freelancer.
     *
     * @param array<string, mixed> $scheduleDefinition
     * @param array<int, FreelancerPackagesModel> $packages
     */
    private function seedBookingsAndPayments(
        UserModel $freelancer,
        ProfileModel $profile,
        UserModel $client,
        array $scheduleDefinition,
        array $packages,
        Carbon $now
    ): void {
        $operatingDays = $scheduleDefinition['operating_days'];
        $scheduleDates = [];
        $cursor = Carbon::create(2026, 5, 1, 0, 0, 0, 'Asia/Manila')->startOfMonth();

        while (count($scheduleDates) < 3) {
            if (in_array(strtolower($cursor->format('l')), $operatingDays, true)) {
                $scheduleDates[] = $cursor->copy();
            }
            $cursor->addDay();
        }

        $packageGroups = array_chunk($packages, 3);

        foreach ($scheduleDates as $sequence => $bookingDate) {
            $packageGroup = $packageGroups[$sequence % count($packageGroups)];
            $package = $packageGroup[$sequence % count($packageGroup)];
            $bookingReference = sprintf('SEED-FRL-%d-%s-%d', $freelancer->id, $bookingDate->format('Ymd'), $sequence + 1);
            $start = Carbon::parse($bookingDate->toDateString() . ' ' . $scheduleDefinition['start_time'], 'Asia/Manila')
                ->addHours($sequence);

            $durationHours = $package->duration ?: (3 + $sequence);
            $end = $start->copy()->addHours($durationHours);
            $totalAmount = (float) $package->package_price;

            $depositAmount = $profile->calculateDepositAmount($totalAmount);
            $downPayment = $depositAmount === null ? $totalAmount : min($totalAmount, (float) $depositAmount);
            $paymentType = $depositAmount === null ? 'full_payment' : ($sequence === 1 ? 'downpayment' : 'full_payment');
            $paidAmount = $paymentType === 'full_payment' ? $totalAmount : $downPayment;
            $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : 'partially_paid';

            $booking = BookingModel::updateOrCreate(
                ['booking_reference' => $bookingReference],
                [
                    'client_id' => $client->id,
                    'booking_type' => 'freelancer',
                    'provider_id' => $freelancer->id,
                    'category_id' => $package->category_id,
                    'event_name' => $package->package_name,
                    'event_date' => $bookingDate->toDateString(),
                    'start_time' => $start->format('H:i:s'),
                    'end_time' => $end->format('H:i:s'),
                    'location_type' => $sequence === 2 ? 'on-location' : 'in-studio',
                    'venue_name' => $sequence === 2 ? $profile->brand_name . ' Client Venue Session' : $profile->brand_name,
                    'street' => $profile->street,
                    'barangay' => $profile->barangay,
                    'city' => optional($profile->location)->municipality ?? 'General Trias',
                    'province' => optional($profile->location)->province ?? 'Cavite',
                    'multiple_locations' => $package->allow_multiple_locations ? [
                        [
                            'venue_name' => $profile->brand_name . ' Main Location',
                            'street' => $profile->street,
                            'barangay' => $profile->barangay,
                            'city' => optional($profile->location)->municipality ?? 'General Trias',
                            'province' => optional($profile->location)->province ?? 'Cavite',
                        ],
                        [
                            'venue_name' => 'Secondary Creative Stop',
                            'street' => 'Satellite Session Street',
                            'barangay' => $profile->barangay,
                            'city' => optional($profile->location)->municipality ?? 'General Trias',
                            'province' => optional($profile->location)->province ?? 'Cavite',
                        ],
                    ] : null,
                    'special_requests' => 'Seeded freelancer booking for marketplace demos and booking history coverage.',
                    'total_amount' => $totalAmount,
                    'down_payment' => $downPayment,
                    'remaining_balance' => max(0, $totalAmount - $paidAmount),
                    'deposit_policy' => $profile->deposit_policy === 'required'
                        ? ($profile->deposit_type === 'percentage' ? rtrim(rtrim(number_format((float) $profile->deposit_amount, 2, '.', ''), '0'), '.') . '%' : number_format((float) $profile->deposit_amount, 2, '.', ''))
                        : 'full_payment',
                    'payment_type' => $paymentType,
                    'status' => 'completed',
                    'payment_status' => $paymentStatus,
                    'created_at' => $bookingDate->copy()->subDays(7)->setTime(11, 0)->toDateTimeString(),
                    'updated_at' => $bookingDate->copy()->setTime(19, 30)->toDateTimeString(),
                ]
            );

            BookingPackageModel::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'package_id' => $package->id,
                ],
                [
                    'package_type' => 'freelancer',
                    'package_name' => $package->package_name,
                    'package_price' => $package->package_price,
                    'package_inclusions' => $package->package_inclusions,
                    'duration' => $package->duration,
                    'maximum_edited_photos' => $package->maximum_edited_photos,
                    'coverage_scope' => $package->coverage_scope,
                ]
            );

            PaymentModel::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'payment_reference' => 'SEED-PAY-' . $bookingReference,
                ],
                [
                    'stripe_payment_intent_id' => null,
                    'stripe_session_id' => null,
                    'amount' => $paidAmount,
                    'payment_method' => 'manual',
                    'status' => 'succeeded',
                    'payment_details' => [
                        'type' => 'seeded_freelancer_payment',
                        'notes' => $paymentType === 'full_payment' ? 'Seeded full payment record.' : 'Seeded initial down payment record.',
                    ],
                    'paid_at' => $bookingDate->copy()->subDays(3)->setTime(14, 0)->toDateTimeString(),
                    'created_at' => $bookingDate->copy()->subDays(3)->setTime(14, 0)->toDateTimeString(),
                    'updated_at' => $bookingDate->copy()->subDays(3)->setTime(14, 5)->toDateTimeString(),
                ]
            );

            $booking->updatePaymentStatus();
            $booking->status = 'completed';
            $booking->saveQuietly();
        }
    }

    /**
     * Create or update one reusable seeded client.
     */
    private function upsertClient(): UserModel
    {
        $email = 'seed.freelancer.booking.client@lumora.test';
        $existingUuid = UserModel::query()->where('email', $email)->value('uuid');

        return UserModel::updateOrCreate(
            ['email' => $email],
            [
                'uuid' => $existingUuid ?: (string) Str::uuid(),
                'role' => 'client',
                'user_type' => 'Customer',
                'first_name' => 'Freya',
                'middle_name' => 'Booking',
                'last_name' => 'Client',
                'mobile_number' => '+639188299999',
                'password' => Hash::make(self::DEFAULT_PASSWORD),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
                'location_id' => 2,
            ]
        );
    }

    /**
     * Build tier-sensitive descriptions.
     */
    private function buildPackageDescription(string $categoryName, string $tier): string
    {
        return match ($categoryName) {
            'Wedding Photography' => "{$tier} wedding coverage tailored for heartfelt moments, polished portraits, and clean delivery sets.",
            'Family Portrait' => "{$tier} portrait coverage for milestone families, guided posing, and warm storytelling frames.",
            'Event Photography' => "{$tier} event coverage for fast-paced programs, candid crowd moments, and key-stage highlights.",
            'Street Photography' => "{$tier} urban portrait coverage with documentary pacing and editorial framing.",
            'Fashion Photography' => "{$tier} fashion coverage for lookbooks, capsule launches, and editorial-ready campaign visuals.",
            'Product Photography' => "{$tier} commercial product coverage built for catalogs, listings, and polished social assets.",
            'Pet Photography' => "{$tier} pet portrait coverage with comfort-first pacing and owner-friendly delivery sets.",
            'Food Photography' => "{$tier} food and menu coverage with styled plating, texture highlights, and brand-ready frames.",
            default => "{$tier} {$categoryName} coverage with curated deliverables and polished editing.",
        };
    }

    /**
     * Build tier-sensitive inclusions.
     *
     * @return array<int, string>
     */
    private function buildPackageInclusions(string $categoryName, string $tier): array
    {
        $common = match ($tier) {
            'Basic' => [
                'Pre-session coordination',
                'High-resolution edited files',
                'Private online gallery',
            ],
            'Essentials' => [
                'Creative planning call',
                'Expanded guided coverage',
                'Private online gallery with downloads',
                'Priority image curation',
            ],
            default => [
                'Full creative planning support',
                'Flexible coverage window',
                'Priority editing turnaround',
                'Private premium online gallery',
                'Web and print export set',
            ],
        };

        $categorySpecific = match ($categoryName) {
            'Wedding Photography' => ['Timeline guidance for key moments', 'Couple portrait direction'],
            'Family Portrait' => ['Guided group posing', 'Individual and group frame set'],
            'Event Photography' => ['Program and candid coverage', 'Speaker and guest highlight frames'],
            'Street Photography' => ['Location walk-through', 'Mood-led candid portrait direction'],
            'Fashion Photography' => ['Styling-aware direction', 'Editorial retouching pass'],
            'Product Photography' => ['Clean catalog shots', 'Detail and feature closeups'],
            'Pet Photography' => ['Comfort-led pacing', 'Owner-and-pet portrait set'],
            'Food Photography' => ['Styled plating setup', 'Menu and social media crop set'],
            default => ['Curated image selection', 'Final delivery handoff'],
        };

        return array_merge($common, $categorySpecific);
    }
}
