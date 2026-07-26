<?php

namespace Database\Seeders\Fresh;

use Database\Seeders\Fresh\Concerns\FreshSeedSupport;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The demand side of the platform plus everything money touches.
 *
 * Owns the admin, client, and freelancer users; client budgets; the freelancer
 * profile tables; subscription plans and both plan tables; studio membership
 * invitations; bookings with their package snapshots, photographer
 * assignments, payments, and platform revenue; reviews; and notifications.
 *
 * No media: freelancer brand logos, portfolios, and IDs are explicit nulls,
 * package cover images stay unset, and notification payloads carry scalar ids
 * only — never a link, route, or path.
 */
class FreshMarketplaceSeeder
{
    use FreshSeedSupport;

    public function __construct(private ?Command $command = null) {}

    /**
     * @param  array<string, mixed>  $graph
     * @return array<string, mixed>
     */
    public function run(array $graph): array
    {
        $this->personCursor = (int) $graph['person_cursor'];

        $now = Carbon::now();
        $today = Carbon::today();
        $studios = $graph['studios'];
        $categoryIds = $graph['category_ids'];

        $adminId = $this->createAdmin();
        $clients = $this->createClients();
        $this->createClientBudgets($clients, $categoryIds, $now);

        $planIds = $this->createSubscriptionPlans($now);
        $freelancers = $this->createFreelancers($categoryIds, $now);

        $this->createStudioPlans($studios, $planIds, $today, $now);
        $this->createFreelancerPlans($freelancers, $planIds, $today, $now);
        $this->createStudioMembers($studios, $freelancers, $now);

        $bookings = $this->createStudioBookings($studios, $clients, $today, $now);
        $bookings = array_merge($bookings, $this->createFreelancerBookings($freelancers, $clients, $today, $now));

        $this->createPaymentsAndRevenue($bookings, $now);
        $this->createReviews($bookings, $now);
        $this->createNotifications($studios, $clients, $bookings, $now);

        $this->command?->info(sprintf(
            'Seeded 1 admin, %d clients, %d freelancers, %d bookings.',
            count($clients),
            count($freelancers),
            count($bookings)
        ));

        return $graph + [
            'admin_id' => $adminId,
            'clients' => $clients,
            'freelancers' => $freelancers,
            'bookings' => $bookings,
            'person_cursor' => $this->personCursor,
        ];
    }

    private function createAdmin(): int
    {
        return (int) $this->upsertFreshUser(self::SEQ_ADMIN, 'admin', 'Admin', $this->locationId('Imus'))->id;
    }

    /**
     * @return array<int, int>
     */
    private function createClients(): array
    {
        $municipalities = ['Bacoor', 'Imus', 'Dasmariñas', 'General Trias', 'Silang', 'Tanza'];
        $clients = [];

        for ($i = 0; $i < self::CLIENT_COUNT; $i++) {
            $locationId = $this->locationId($municipalities[$i % count($municipalities)]);
            $clients[] = (int) $this->upsertFreshUser(self::SEQ_CLIENT_BASE + $i, 'client', 'Customer', $locationId)->id;
        }

        return $clients;
    }

    /**
     * @param  array<int, int>  $clients
     * @param  array<string, int>  $categoryIds
     */
    private function createClientBudgets(array $clients, array $categoryIds, Carbon $now): void
    {
        $categoryNames = $this->categoryNames();
        $rows = [];

        foreach ($clients as $index => $clientId) {
            // Every client plans one budget; the first half plan a second.
            $budgetCount = $index < 15 ? 2 : 1;

            for ($b = 0; $b < $budgetCount; $b++) {
                $categoryName = $categoryNames[($index * 2 + $b) % count($categoryNames)];
                $minimum = 5000 + ($index % 6) * 1500 + $b * 4000;

                $rows[] = [
                    'client_id' => $clientId,
                    'budget_name' => sprintf('%s Budget %d', $categoryName, $b + 1),
                    'description' => sprintf('Planned spending window for an upcoming %s booking.', strtolower($categoryName)),
                    'minimum_budget' => $minimum,
                    'maximum_budget' => $minimum + 9000,
                    'preferred_budget' => $minimum + 4500,
                    'spent_amount' => $b === 0 ? $minimum / 2 : 0,
                    'category_id' => $categoryIds[$categoryName],
                    'budget_type' => $b === 0 ? 'package' : 'service',
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('tbl_client_budget')->insert($rows);
    }

    /**
     * Four tiers per audience. Both plan tables hold a RESTRICT foreign key
     * here, so these rows must exist before any subscription is written.
     *
     * @return array<string, int> plan_code => id
     */
    private function createSubscriptionPlans(Carbon $now): array
    {
        $definitions = [
            ['studio', 'basic', 'monthly', 'Studio Starter', 1290.00, 12.00, 0, 25, 5, 3, 'basic', 1],
            ['studio', 'premium', 'monthly', 'Studio Growth', 2790.00, 9.00, 14, 80, 15, 5, 'priority', 2],
            ['studio', 'enterprise', 'monthly', 'Studio Scale', 5490.00, 6.00, 14, null, null, null, 'dedicated', 3],
            ['studio', 'premium', 'yearly', 'Studio Growth Annual', 27900.00, 8.00, 30, 80, 15, 5, 'priority', 4],
            ['freelancer', 'basic', 'monthly', 'Freelancer Starter', 590.00, 12.00, 0, 15, null, null, 'basic', 1],
            ['freelancer', 'premium', 'monthly', 'Freelancer Growth', 1190.00, 9.00, 14, 45, null, null, 'priority', 2],
            ['freelancer', 'enterprise', 'monthly', 'Freelancer Scale', 2390.00, 6.00, 14, null, null, null, 'dedicated', 3],
            ['freelancer', 'premium', 'yearly', 'Freelancer Growth Annual', 11900.00, 8.00, 30, 45, null, null, 'priority', 4],
        ];

        $planIds = [];

        foreach ($definitions as [$userType, $planType, $cycle, $name, $price, $commission, $trialDays, $maxBooking, $maxPhotographers, $staffLimit, $support, $priority]) {
            $code = strtoupper($userType.'_'.$planType.'_'.$cycle);

            $planIds[$code] = (int) DB::table('tbl_subscription_plans')->insertGetId([
                'user_type' => $userType,
                'plan_type' => $planType,
                'billing_cycle' => $cycle,
                'plan_code' => $code,
                'name' => $name,
                'description' => sprintf('%s plan billed %s, with a %s%% platform commission.', ucfirst($planType), $cycle, $commission),
                'price' => $price,
                'commission_rate' => $commission,
                'trial_days' => $trialDays,
                'max_booking' => $maxBooking,
                'max_studio_photographers' => $maxPhotographers,
                'max_studios' => $userType === 'studio' ? 1 : null,
                'staff_limit' => $staffLimit,
                'priority_level' => $priority,
                'features' => json_encode([
                    'Booking management',
                    'Payout reporting',
                    $support === 'basic' ? 'Email support' : 'Priority support queue',
                ], JSON_THROW_ON_ERROR),
                'support_level' => $support,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $planIds;
    }

    /**
     * @param  array<string, int>  $categoryIds
     * @return array<int, array<string, mixed>>
     */
    private function createFreelancers(array $categoryIds, Carbon $now): array
    {
        $categoryNames = $this->categoryNames();
        $municipalities = ['Imus', 'Bacoor', 'Silang', 'Kawit', 'Tanza', 'Carmona', 'Naic', 'Rosario'];
        $brands = [
            'Northlight Freelance Studio', 'Amber Field Photography', 'Slow Shutter Works',
            'Fieldnote Visuals', 'Coastline Frames', 'Paper Lantern Photography',
            'Backlit Story Co', 'Quiet Hours Photography',
        ];

        $freelancers = [];

        foreach ($municipalities as $index => $municipality) {
            $locationId = $this->locationId($municipality);
            $user = $this->upsertFreshUser(self::SEQ_FREELANCER_BASE + $index, 'freelancer', 'Photographer', $locationId);

            DB::table('tbl_freelancers')->insert([
                'user_id' => $user->id,
                'location_id' => $locationId,
                'brand_name' => $brands[$index],
                'tagline' => 'Independent photography for small, considered shoots.',
                'bio' => sprintf(
                    'A solo photographer working out of %s, taking a limited number of bookings each month so every session gets full attention from planning through final delivery.',
                    $municipality
                ),
                'years_experience' => 3 + $index,
                'street' => sprintf('%d Sitio Road', 12 + $index * 3),
                'barangay' => $this->barangayFor($municipality, $index + 3),
                'service_area' => 'Cavite and nearby provinces',
                'starting_price' => 4200 + $index * 450,
                'deposit_policy' => $index % 2 === 0 ? 'required' : 'not_required',
                'deposit_type' => $index % 2 === 0 ? 'percentage' : null,
                'deposit_amount' => $index % 2 === 0 ? 30.00 : null,
                // Media and outbound links stay unset.
                'brand_logo' => null,
                'portfolio_works' => null,
                'valid_id' => null,
                'facebook_url' => null,
                'instagram_url' => null,
                'website_url' => null,
                'avg_rating' => 0,
                'total_reviews' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('tbl_freelancer_schedules')->insert([
                'user_id' => $user->id,
                'operating_days' => json_encode($this->operatingDaysFor($index), JSON_THROW_ON_ERROR),
                'start_time' => '08:00:00',
                'end_time' => '17:00:00',
                'booking_limit' => 2,
                'advance_booking' => 3,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $categories = [];
            $packages = [];

            for ($j = 0; $j < 2; $j++) {
                $categoryName = $categoryNames[($index * 2 + $j) % count($categoryNames)];
                $categoryId = $categoryIds[$categoryName];
                $categories[] = ['id' => $categoryId, 'name' => $categoryName];

                DB::table('pvt_freelancer_categories')->insert([
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('tbl_freelancer_services')->insert([
                    'user_id' => $user->id,
                    'category_id' => $categoryId,
                    'services_name' => json_encode($this->servicesFor($categoryName), JSON_THROW_ON_ERROR),
                    'starting_from' => 3800 + $j * 700,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach (['Half Day', 'Full Day'] as $tierIndex => $tier) {
                    $price = round((4200 + $index * 450) * ($tierIndex === 0 ? 1.0 : 1.75), 2);
                    $name = sprintf('%s %s Session', $this->packageFamilyFor($categoryName), $tier);

                    $packages[] = [
                        'id' => (int) DB::table('tbl_freelancer_packages')->insertGetId([
                            'user_id' => $user->id,
                            'category_id' => $categoryId,
                            'package_name' => $name,
                            'package_description' => sprintf('%s coverage handled solo, from planning through the final edit.', $tier),
                            'package_inclusions' => json_encode([
                                'Planning call before the session',
                                $tierIndex === 0 ? 'Four hours of coverage' : 'Eight hours of coverage',
                                'Colour-corrected final selects',
                            ], JSON_THROW_ON_ERROR),
                            'duration' => $tierIndex === 0 ? 4 : 8,
                            'maximum_edited_photos' => $tierIndex === 0 ? 90 : 200,
                            'coverage_scope' => 'Cavite and nearby provinces',
                            'package_price' => $price,
                            'allow_multiple_locations' => $tierIndex === 1,
                            'max_locations' => $tierIndex === 1 ? 2 : 1,
                            'allow_time_customization' => $tierIndex === 1,
                            'online_gallery' => false,
                            'cover_images' => null,
                            'status' => 'active',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]),
                        'category_id' => $categoryId,
                        'name' => $name,
                        'price' => $price,
                        'duration' => $tierIndex === 0 ? 4 : 8,
                        'maximum_edited_photos' => $tierIndex === 0 ? 90 : 200,
                    ];
                }
            }

            $freelancers[] = [
                'index' => $index,
                'user_id' => (int) $user->id,
                'brand_name' => $brands[$index],
                'municipality' => $municipality,
                'location_id' => $locationId,
                'categories' => $categories,
                'packages' => $packages,
            ];
        }

        return $freelancers;
    }

    /**
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<string, int>  $planIds
     */
    private function createStudioPlans(array $studios, array $planIds, Carbon $today, Carbon $now): void
    {
        $codes = ['STUDIO_BASIC_MONTHLY', 'STUDIO_PREMIUM_MONTHLY', 'STUDIO_ENTERPRISE_MONTHLY', 'STUDIO_PREMIUM_YEARLY'];
        $prices = ['STUDIO_BASIC_MONTHLY' => 1290.00, 'STUDIO_PREMIUM_MONTHLY' => 2790.00, 'STUDIO_ENTERPRISE_MONTHLY' => 5490.00, 'STUDIO_PREMIUM_YEARLY' => 27900.00];
        $rows = [];

        foreach ($studios as $index => $studio) {
            $code = $codes[$index % count($codes)];
            $yearly = str_ends_with($code, 'YEARLY');
            $start = $today->copy()->subDays(20 + $index);

            $rows[] = [
                'studio_id' => $studio['id'],
                'plan_id' => $planIds[$code],
                'subscription_reference' => sprintf('FS-SUB-STU-%04d', $index + 1),
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($yearly ? 365 : 30)->toDateString(),
                'next_billing_date' => $start->copy()->addDays($yearly ? 365 : 30)->toDateString(),
                'trial_ends_at' => null,
                'amount_paid' => $prices[$code],
                'paid_at' => $start,
                'payment_status' => 'paid',
                'status' => 'active',
                'plan_snapshot' => json_encode(['plan_code' => $code, 'price' => $prices[$code]], JSON_THROW_ON_ERROR),
                'usage_metrics' => json_encode(['bookings' => 6, 'photographers' => self::PHOTOGRAPHERS_PER_STUDIO], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_studio_plans')->insert($rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $freelancers
     * @param  array<string, int>  $planIds
     */
    private function createFreelancerPlans(array $freelancers, array $planIds, Carbon $today, Carbon $now): void
    {
        $codes = ['FREELANCER_BASIC_MONTHLY', 'FREELANCER_PREMIUM_MONTHLY', 'FREELANCER_ENTERPRISE_MONTHLY', 'FREELANCER_PREMIUM_YEARLY'];
        $prices = ['FREELANCER_BASIC_MONTHLY' => 590.00, 'FREELANCER_PREMIUM_MONTHLY' => 1190.00, 'FREELANCER_ENTERPRISE_MONTHLY' => 2390.00, 'FREELANCER_PREMIUM_YEARLY' => 11900.00];
        $rows = [];

        foreach ($freelancers as $index => $freelancer) {
            $code = $codes[$index % count($codes)];
            $yearly = str_ends_with($code, 'YEARLY');
            $start = $today->copy()->subDays(15 + $index);

            $rows[] = [
                'freelancer_id' => $freelancer['user_id'],
                'plan_id' => $planIds[$code],
                'subscription_reference' => sprintf('FS-SUB-FRL-%04d', $index + 1),
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($yearly ? 365 : 30)->toDateString(),
                'next_billing_date' => $start->copy()->addDays($yearly ? 365 : 30)->toDateString(),
                'amount_paid' => $prices[$code],
                'payment_status' => 'paid',
                'status' => 'active',
                'plan_snapshot' => json_encode(['plan_code' => $code, 'price' => $prices[$code]], JSON_THROW_ON_ERROR),
                'usage_metrics' => json_encode(['bookings' => 3], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_freelancer_plans')->insert($rows);
    }

    /**
     * Twelve studio-to-freelancer invitations across mixed statuses.
     *
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<int, array<string, mixed>>  $freelancers
     */
    private function createStudioMembers(array $studios, array $freelancers, Carbon $now): void
    {
        $statuses = ['approved', 'approved', 'pending', 'rejected'];
        $rows = [];

        for ($i = 0; $i < 12; $i++) {
            $studio = $studios[$i % count($studios)];
            $freelancer = $freelancers[$i % count($freelancers)];
            $status = $statuses[$i % count($statuses)];

            $rows[] = [
                'studio_id' => $studio['id'],
                'freelancer_id' => $freelancer['user_id'],
                'invited_by' => $studio['owner_id'],
                'invitation_message' => sprintf('We would like to add you to the %s roster for overflow bookings.', $studio['name']),
                'status' => $status,
                'response_message' => $status === 'pending' ? null : 'Thanks for reaching out.',
                'invited_at' => $now,
                'responded_at' => $status === 'pending' ? null : $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_studio_members')->insert($rows);
    }

    /**
     * Six bookings per studio: three completed, one confirmed, one pending,
     * one cancelled.
     *
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<int, int>  $clients
     * @return array<int, array<string, mixed>>
     */
    private function createStudioBookings(array $studios, array $clients, Carbon $today, Carbon $now): array
    {
        $plan = [
            ['status' => 'completed', 'offset' => -45],
            ['status' => 'completed', 'offset' => -32],
            ['status' => 'completed', 'offset' => -18],
            ['status' => 'confirmed', 'offset' => 9],
            ['status' => 'pending', 'offset' => 21],
            ['status' => 'cancelled', 'offset' => -6],
        ];

        $bookings = [];
        $sequence = 0;
        $assignments = [];

        foreach ($studios as $studio) {
            foreach ($plan as $slot => $slotPlan) {
                $sequence++;
                $package = $studio['packages'][($slot * 3 + $studio['index']) % count($studio['packages'])];
                $clientId = $clients[($studio['index'] * 6 + $slot) % count($clients)];
                $eventDate = $today->copy()->addDays($slotPlan['offset']);
                $reference = sprintf('FS-BKS-%05d', $sequence);
                $completed = $slotPlan['status'] === 'completed';

                $total = (float) $package['price'];
                $downPayment = $completed ? $total : round($total * 0.30, 2);

                $bookingId = (int) DB::table('tbl_bookings')->insertGetId([
                    'booking_reference' => $reference,
                    'client_id' => $clientId,
                    'booking_type' => 'studio',
                    'provider_id' => $studio['id'],
                    'category_id' => $package['category_id'],
                    'event_name' => sprintf('%s session with %s', $package['name'], $studio['name']),
                    'event_date' => $eventDate->toDateString(),
                    'start_time' => '10:00:00',
                    'end_time' => sprintf('%02d:00:00', 10 + $package['duration']),
                    'location_type' => $slot % 2 === 0 ? 'in-studio' : 'on-location',
                    'venue_name' => $slot % 2 === 0 ? $studio['name'] : 'Client-arranged venue',
                    'street' => 'Along the agreed meeting point',
                    'barangay' => $studio['barangay'],
                    'city' => $studio['municipality'],
                    'province' => 'Cavite',
                    'special_requests' => $slot % 3 === 0 ? 'Please keep the schedule tight around the programme.' : null,
                    'total_amount' => $total,
                    'down_payment' => $downPayment,
                    'remaining_balance' => round($total - $downPayment, 2),
                    'deposit_policy' => '30%',
                    'payment_type' => $completed ? 'full_payment' : 'downpayment',
                    'status' => $slotPlan['status'],
                    'payment_status' => match ($slotPlan['status']) {
                        'completed' => 'paid',
                        'confirmed' => 'partially_paid',
                        default => 'unpaid',
                    },
                    'cancellation_reason' => $slotPlan['status'] === 'cancelled' ? 'Client rescheduled to a later season.' : null,
                    'cancelled_by' => $slotPlan['status'] === 'cancelled' ? 'client' : null,
                    'completed_at' => $completed ? $eventDate->copy()->addDay() : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('tbl_booking_packages')->insert([
                    'booking_id' => $bookingId,
                    'package_id' => $package['id'],
                    'package_type' => 'studio',
                    'package_name' => $package['name'],
                    'package_price' => $package['price'],
                    'package_inclusions' => json_encode($package['inclusions'], JSON_THROW_ON_ERROR),
                    'duration' => $package['duration'],
                    'maximum_edited_photos' => $package['maximum_edited_photos'],
                    'coverage_scope' => 'Cavite and nearby provinces',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (! in_array($slotPlan['status'], ['pending', 'cancelled'], true)) {
                    $photographer = $studio['photographers'][$slot % count($studio['photographers'])];

                    $assignments[] = [
                        'booking_id' => $bookingId,
                        'studio_id' => $studio['id'],
                        'photographer_id' => $photographer['id'],
                        'assigned_by' => $studio['owner_id'],
                        'status' => $completed ? 'completed' : 'confirmed',
                        'assignment_notes' => 'Assigned from the studio roster during booking confirmation.',
                        'assigned_at' => $now,
                        'confirmed_at' => $now,
                        'completed_at' => $completed ? $eventDate->copy()->addDay() : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $bookings[] = [
                    'id' => $bookingId,
                    'reference' => $reference,
                    'type' => 'studio',
                    'provider_id' => $studio['id'],
                    'studio_index' => $studio['index'],
                    'client_id' => $clientId,
                    'status' => $slotPlan['status'],
                    'total' => $total,
                    'paid' => $completed ? $total : ($slotPlan['status'] === 'confirmed' ? $downPayment : 0.0),
                    'event_date' => $eventDate->copy(),
                ];
            }
        }

        if ($assignments !== []) {
            DB::table('tbl_booking_assigned_photographers')->insert($assignments);
        }

        return $bookings;
    }

    /**
     * Three bookings per freelancer: two completed, one confirmed.
     *
     * @param  array<int, array<string, mixed>>  $freelancers
     * @param  array<int, int>  $clients
     * @return array<int, array<string, mixed>>
     */
    private function createFreelancerBookings(array $freelancers, array $clients, Carbon $today, Carbon $now): array
    {
        $plan = [
            ['status' => 'completed', 'offset' => -38],
            ['status' => 'completed', 'offset' => -21],
            ['status' => 'confirmed', 'offset' => 12],
        ];

        $bookings = [];
        $sequence = 0;

        foreach ($freelancers as $freelancer) {
            foreach ($plan as $slot => $slotPlan) {
                $sequence++;
                $package = $freelancer['packages'][($slot + $freelancer['index']) % count($freelancer['packages'])];
                $clientId = $clients[(20 + $freelancer['index'] * 3 + $slot) % count($clients)];
                $eventDate = $today->copy()->addDays($slotPlan['offset']);
                $reference = sprintf('FS-BKF-%05d', $sequence);
                $completed = $slotPlan['status'] === 'completed';

                $total = (float) $package['price'];
                $downPayment = $completed ? $total : round($total * 0.30, 2);

                $bookingId = (int) DB::table('tbl_bookings')->insertGetId([
                    'booking_reference' => $reference,
                    'client_id' => $clientId,
                    'booking_type' => 'freelancer',
                    // Polymorphic and unconstrained: for freelancer bookings
                    // provider_id is the freelancer's tbl_users id.
                    'provider_id' => $freelancer['user_id'],
                    'category_id' => $package['category_id'],
                    'event_name' => sprintf('%s with %s', $package['name'], $freelancer['brand_name']),
                    'event_date' => $eventDate->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time' => sprintf('%02d:00:00', 9 + $package['duration']),
                    'location_type' => 'on-location',
                    'venue_name' => 'Client-arranged venue',
                    'street' => 'Along the agreed meeting point',
                    'barangay' => $this->barangayFor($freelancer['municipality'], $slot + 5),
                    'city' => $freelancer['municipality'],
                    'province' => 'Cavite',
                    'special_requests' => null,
                    'total_amount' => $total,
                    'down_payment' => $downPayment,
                    'remaining_balance' => round($total - $downPayment, 2),
                    'deposit_policy' => '30%',
                    'payment_type' => $completed ? 'full_payment' : 'downpayment',
                    'status' => $slotPlan['status'],
                    'payment_status' => $completed ? 'paid' : 'partially_paid',
                    'completed_at' => $completed ? $eventDate->copy()->addDay() : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('tbl_booking_packages')->insert([
                    'booking_id' => $bookingId,
                    'package_id' => $package['id'],
                    'package_type' => 'freelancer',
                    'package_name' => $package['name'],
                    'package_price' => $package['price'],
                    'package_inclusions' => json_encode([
                        'Planning call before the session',
                        'Colour-corrected final selects',
                    ], JSON_THROW_ON_ERROR),
                    'duration' => $package['duration'],
                    'maximum_edited_photos' => $package['maximum_edited_photos'],
                    'coverage_scope' => 'Cavite and nearby provinces',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $bookings[] = [
                    'id' => $bookingId,
                    'reference' => $reference,
                    'type' => 'freelancer',
                    'provider_id' => $freelancer['user_id'],
                    'freelancer_index' => $freelancer['index'],
                    'client_id' => $clientId,
                    'status' => $slotPlan['status'],
                    'total' => $total,
                    'paid' => $completed ? $total : $downPayment,
                    'event_date' => $eventDate->copy(),
                ];
            }
        }

        return $bookings;
    }

    /**
     * One payment and one platform revenue row per booking that has money on
     * it, plus a revenue row for every active subscription.
     *
     * @param  array<int, array<string, mixed>>  $bookings
     */
    private function createPaymentsAndRevenue(array $bookings, Carbon $now): void
    {
        $revenueRows = [];

        foreach ($bookings as $booking) {
            if ($booking['paid'] <= 0) {
                continue;
            }

            $paidAt = $booking['event_date']->copy()->subDays(3);

            $paymentId = (int) DB::table('tbl_payments')->insertGetId([
                'booking_id' => $booking['id'],
                'payment_reference' => 'FS-PAY-'.$booking['reference'],
                'amount' => $booking['paid'],
                'payment_method' => $booking['id'] % 2 === 0 ? 'card' : 'gcash',
                'status' => 'succeeded',
                'payment_details' => json_encode(['channel' => 'seeded', 'booking_reference' => $booking['reference']], JSON_THROW_ON_ERROR),
                'paid_at' => $paidAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $platformFee = round($booking['paid'] * 0.10, 2);

            $revenueRows[] = [
                'transaction_reference' => 'FS-REV-'.$booking['reference'],
                'booking_id' => $booking['id'],
                'payment_id' => $paymentId,
                'subscription_id' => null,
                'total_amount' => $booking['paid'],
                'platform_fee_percentage' => 10.00,
                'platform_fee_amount' => $platformFee,
                'provider_amount' => round($booking['paid'] - $platformFee, 2),
                'provider_type' => $booking['type'],
                'revenue_type' => 'booking',
                'provider_id' => $booking['provider_id'],
                'client_id' => $booking['client_id'],
                'status' => 'completed',
                'breakdown' => json_encode(['gross' => $booking['paid'], 'platform_fee' => $platformFee], JSON_THROW_ON_ERROR),
                'settled_at' => $paidAt,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_system_revenue')->insert($revenueRows);

        $this->createSubscriptionRevenue($now);
    }

    /**
     * Subscription revenue. tbl_system_revenue.subscription_id is a foreign key
     * into tbl_studio_plans only, so freelancer subscriptions leave it null.
     */
    private function createSubscriptionRevenue(Carbon $now): void
    {
        $rows = [];

        $studioPlans = DB::table('tbl_studio_plans')
            ->join('tbl_studios', 'tbl_studios.id', '=', 'tbl_studio_plans.studio_id')
            ->get(['tbl_studio_plans.id', 'tbl_studio_plans.studio_id', 'tbl_studio_plans.amount_paid', 'tbl_studio_plans.subscription_reference', 'tbl_studios.user_id']);

        foreach ($studioPlans as $plan) {
            $fee = round((float) $plan->amount_paid * 0.10, 2);

            $rows[] = [
                'transaction_reference' => 'FS-REV-'.$plan->subscription_reference,
                'booking_id' => null,
                'payment_id' => null,
                'subscription_id' => $plan->id,
                'total_amount' => $plan->amount_paid,
                'platform_fee_percentage' => 10.00,
                'platform_fee_amount' => $fee,
                'provider_amount' => round((float) $plan->amount_paid - $fee, 2),
                'provider_type' => 'studio',
                'revenue_type' => 'subscription',
                'provider_id' => $plan->studio_id,
                'client_id' => $plan->user_id,
                'status' => 'completed',
                'breakdown' => json_encode(['gross' => (float) $plan->amount_paid, 'platform_fee' => $fee], JSON_THROW_ON_ERROR),
                'settled_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $freelancerPlans = DB::table('tbl_freelancer_plans')
            ->get(['id', 'freelancer_id', 'amount_paid', 'subscription_reference']);

        foreach ($freelancerPlans as $plan) {
            $fee = round((float) $plan->amount_paid * 0.10, 2);

            $rows[] = [
                'transaction_reference' => 'FS-REV-'.$plan->subscription_reference,
                'booking_id' => null,
                'payment_id' => null,
                'subscription_id' => null,
                'total_amount' => $plan->amount_paid,
                'platform_fee_percentage' => 10.00,
                'platform_fee_amount' => $fee,
                'provider_amount' => round((float) $plan->amount_paid - $fee, 2),
                'provider_type' => 'freelancer',
                'revenue_type' => 'subscription',
                'provider_id' => $plan->freelancer_id,
                'client_id' => $plan->freelancer_id,
                'status' => 'completed',
                'breakdown' => json_encode(['gross' => (float) $plan->amount_paid, 'platform_fee' => $fee], JSON_THROW_ON_ERROR),
                'settled_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tbl_system_revenue')->insert($rows);
    }

    /**
     * One review per completed booking, then the aggregate columns refreshed
     * from what was actually written.
     *
     * @param  array<int, array<string, mixed>>  $bookings
     */
    private function createReviews(array $bookings, Carbon $now): void
    {
        $texts = [
            'Clear communication from the first call and the final set arrived ahead of schedule.',
            'Calm on the day and the edit matched the mood we asked for.',
            'Well organised, easy to work with, and the selects covered everything we needed.',
            'Good direction throughout the session and a thorough handover afterwards.',
        ];

        $studioRows = [];
        $freelancerRows = [];
        $index = 0;

        foreach ($bookings as $booking) {
            if ($booking['status'] !== 'completed') {
                continue;
            }

            $rating = 4 + ($index % 2);
            $row = [
                'booking_id' => $booking['id'],
                'client_id' => $booking['client_id'],
                'rating' => $rating,
                'title' => $rating === 5 ? 'Would book again' : 'Solid session',
                'review_text' => $texts[$index % count($texts)],
                'review_type' => 'positive',
                'status' => 'published',
                'preset_used' => null,
                'is_recommend' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($booking['type'] === 'studio') {
                $studioRows[] = $row + ['studio_id' => $booking['provider_id']];
            } else {
                $freelancerRows[] = $row + ['freelancer_id' => $booking['provider_id']];
            }

            $index++;
        }

        DB::table('tbl_studio_ratings')->insert($studioRows);
        DB::table('tbl_freelancer_ratings')->insert($freelancerRows);

        $this->refreshRatingAggregates();
    }

    /**
     * Keep tbl_studios / tbl_freelancers aggregates consistent with the review
     * rows rather than hardcoding them.
     */
    private function refreshRatingAggregates(): void
    {
        $studioStats = DB::table('tbl_studio_ratings')
            ->selectRaw('studio_id, AVG(rating) as avg_rating, COUNT(*) as total')
            ->groupBy('studio_id')
            ->get();

        foreach ($studioStats as $stat) {
            DB::table('tbl_studios')->where('id', $stat->studio_id)->update([
                'avg_rating' => round((float) $stat->avg_rating, 2),
                'total_reviews' => $stat->total,
            ]);
        }

        $freelancerStats = DB::table('tbl_freelancer_ratings')
            ->selectRaw('freelancer_id, AVG(rating) as avg_rating, COUNT(*) as total')
            ->groupBy('freelancer_id')
            ->get();

        foreach ($freelancerStats as $stat) {
            DB::table('tbl_freelancers')->where('user_id', $stat->freelancer_id)->update([
                'avg_rating' => round((float) $stat->avg_rating, 2),
                'total_reviews' => $stat->total,
            ]);
        }
    }

    /**
     * Notification payloads carry scalar ids only. The `data` column is the one
     * place a redirect path could hide, so it never holds a url, route, or path.
     *
     * @param  array<int, array<string, mixed>>  $studios
     * @param  array<int, int>  $clients
     * @param  array<int, array<string, mixed>>  $bookings
     */
    private function createNotifications(array $studios, array $clients, array $bookings, Carbon $now): void
    {
        $rows = [];

        foreach ($bookings as $booking) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $booking['client_id'],
                'type' => 'booking_'.$booking['status'],
                'title' => sprintf('Booking %s', $booking['status']),
                'message' => sprintf('Your booking %s is now %s.', $booking['reference'], $booking['status']),
                'data' => json_encode(['booking_id' => $booking['id']], JSON_THROW_ON_ERROR),
                'icon' => 'calendar',
                'color' => $booking['status'] === 'cancelled' ? 'red' : 'green',
                'read_at' => $booking['status'] === 'completed' ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach ($studios as $studio) {
            foreach (['payout_settled', 'subscription_renewed'] as $type) {
                $rows[] = [
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $studio['owner_id'],
                    'type' => $type,
                    'title' => $type === 'payout_settled' ? 'Payout settled' : 'Subscription renewed',
                    'message' => $type === 'payout_settled'
                        ? 'Your latest booking payout has been settled.'
                        : 'Your studio subscription renewed for another billing period.',
                    'data' => json_encode(['studio_id' => $studio['id']], JSON_THROW_ON_ERROR),
                    'icon' => 'bell',
                    'color' => 'blue',
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach ($clients as $index => $clientId) {
            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'user_id' => $clientId,
                'type' => 'budget_reminder',
                'title' => 'Budget check-in',
                'message' => 'Review your saved budget before your next enquiry.',
                'data' => json_encode(['client_id' => $clientId], JSON_THROW_ON_ERROR),
                'icon' => 'bell',
                'color' => 'amber',
                'read_at' => $index % 3 === 0 ? $now : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('tbl_notifications')->insert($chunk);
        }
    }
}
