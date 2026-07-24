<?php

namespace Database\Seeders;

use App\Models\Admin\CategoriesModel;
use App\Models\Admin\LocationModel;
use App\Models\BookingModel;
use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotIntentModel;
use App\Models\Chatbot\ChatbotMessageModel;
use App\Models\ClientBudgetModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\FreelancerPlanModel;
use App\Models\FreelancerRatingModel;
use App\Models\NotificationModel;
use App\Models\Procurement\ProcurementDefectReturnModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\StudioRatingModel;
use App\Models\SubscriptionPlanModel;
use App\Models\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills in every table the rest of the seeder chain doesn't touch: the
 * platform admin account, reference locations, subscription plans (and
 * studio/freelancer subscriptions to them), online galleries + ratings for
 * completed bookings, a studio membership invite, a generated payroll run,
 * a procurement defect return, sample notifications, and a chatbot
 * conversation. Everything here links to rows the earlier seeders already
 * created — it does not invent its own studios/freelancers/bookings.
 */
class CoverageGapsAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAdmin();
        $this->seedLocations();
        $this->seedSubscriptionPlansAndSubscriptions();
        $this->seedClientBudget();
        $this->seedGalleriesAndRatings();
        $this->seedStudioMembership();
        $this->seedGeneratedPayroll();
        $this->seedProcurementDefectReturn();
        $this->seedNotifications();
        $this->seedChatbotConversation();
    }

    private function seedAdmin(): void
    {
        UserModel::updateOrCreate(
            ['email' => 'admin@lumora.test'],
            [
                'uuid' => (string) Str::uuid(),
                'role' => 'admin',
                'user_type' => 'Admin',
                'first_name' => 'Platform',
                'middle_name' => null,
                'last_name' => 'Administrator',
                'mobile_number' => '+639170000000',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
            ]
        );

        $this->command?->info('Seeded platform admin: admin@lumora.test');
    }

    private function seedLocations(): void
    {
        $locations = [
            [
                'province' => 'Cavite',
                'municipality' => 'Dasmariñas',
                'barangay' => ['Burol', 'Salawag', 'Zone I', 'Zone II', 'San Agustin'],
                'zip_code' => '4114',
            ],
            [
                'province' => 'Cavite',
                'municipality' => 'Imus',
                'barangay' => ['Anabu I-A', 'Bucandala I', 'Malagasang I-A', 'Tanzang Luma'],
                'zip_code' => '4103',
            ],
            [
                'province' => 'Cavite',
                'municipality' => 'Bacoor',
                'barangay' => ['Molino I', 'Molino II', 'Niog I', 'Zapote I'],
                'zip_code' => '4102',
            ],
            [
                'province' => 'Metro Manila',
                'municipality' => 'Makati',
                'barangay' => ['Bel-Air', 'Poblacion', 'San Lorenzo', 'Urdaneta'],
                'zip_code' => '1200',
            ],
            [
                'province' => 'Metro Manila',
                'municipality' => 'Quezon City',
                'barangay' => ['Diliman', 'Cubao', 'Fairview', 'Novaliches'],
                'zip_code' => '1100',
            ],
        ];

        foreach ($locations as $location) {
            LocationModel::updateOrCreate(
                ['province' => $location['province'], 'municipality' => $location['municipality']],
                [
                    'barangay' => $location['barangay'],
                    'zip_code' => $location['zip_code'],
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Seeded ' . count($locations) . ' reference locations.');
    }

    private function seedSubscriptionPlansAndSubscriptions(): void
    {
        $planDefinitions = [
            [
                'user_type' => 'studio',
                'plan_type' => 'basic',
                'billing_cycle' => 'monthly',
                'name' => 'Studio Basic',
                'description' => 'Essential tools for a growing studio.',
                'price' => 999.00,
                'commission_rate' => 10.00,
                'trial_days' => 0,
                'max_booking' => 20,
                'max_studio_photographers' => 3,
                'max_studios' => 1,
                'staff_limit' => 5,
                'priority_level' => 1,
                'features' => ['Booking management', 'Online gallery', 'Basic reports'],
                'support_level' => 'basic',
            ],
            [
                'user_type' => 'studio',
                'plan_type' => 'premium',
                'billing_cycle' => 'monthly',
                'name' => 'Studio Premium',
                'description' => 'Unlimited bookings and priority placement, with a free trial to test it out.',
                'price' => 2499.00,
                'commission_rate' => 7.00,
                'trial_days' => 14,
                'max_booking' => null,
                'max_studio_photographers' => 10,
                'max_studios' => 3,
                'staff_limit' => 20,
                'priority_level' => 4,
                'features' => ['Unlimited bookings', 'Featured placement', 'Advanced income reports', 'Priority support'],
                'support_level' => 'priority',
            ],
            [
                'user_type' => 'freelancer',
                'plan_type' => 'basic',
                'billing_cycle' => 'monthly',
                'name' => 'Freelancer Starter',
                'description' => 'Get booked, get paid.',
                'price' => 399.00,
                'commission_rate' => 12.00,
                'trial_days' => 7,
                'max_booking' => 15,
                'max_studio_photographers' => null,
                'max_studios' => null,
                'staff_limit' => null,
                'priority_level' => 1,
                'features' => ['Booking management', 'Online gallery'],
                'support_level' => 'basic',
            ],
            [
                'user_type' => 'freelancer',
                'plan_type' => 'premium',
                'billing_cycle' => 'monthly',
                'name' => 'Freelancer Pro',
                'description' => 'Unlimited bookings and top placement for full-time freelancers.',
                'price' => 899.00,
                'commission_rate' => 8.00,
                'trial_days' => 0,
                'max_booking' => null,
                'max_studio_photographers' => null,
                'max_studios' => null,
                'staff_limit' => null,
                'priority_level' => 3,
                'features' => ['Unlimited bookings', 'Featured placement', 'Priority support'],
                'support_level' => 'priority',
            ],
        ];

        $plans = [];
        foreach ($planDefinitions as $definition) {
            $plans[$definition['name']] = SubscriptionPlanModel::updateOrCreate(
                ['name' => $definition['name']],
                $definition + [
                    'plan_code' => SubscriptionPlanModel::generatePlanCode(
                        $definition['user_type'],
                        $definition['plan_type'],
                        $definition['billing_cycle']
                    ),
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Seeded ' . count($plans) . ' subscription plans.');

        // Subscribe one studio to the trial-enabled Premium plan, one to Basic.
        $studios = StudiosModel::whereIn('status', ['verified', 'active'])->orderBy('id')->take(2)->get();
        $studioPremium = $plans['Studio Premium'] ?? null;
        $studioBasic = $plans['Studio Basic'] ?? null;

        if ($studios->count() >= 1 && $studioPremium) {
            $studio = $studios[0];
            StudioPlanModel::updateOrCreate(
                ['studio_id' => $studio->id, 'plan_id' => $studioPremium->id],
                [
                    'subscription_reference' => StudioPlanModel::generateSubscriptionReference(),
                    'start_date' => now()->subDays(3),
                    'end_date' => now()->addDays(27),
                    'next_billing_date' => now()->addDays(27),
                    'amount_paid' => 0,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'paid_at' => now()->subDays(3),
                    'trial_ends_at' => now()->addDays(11),
                    'plan_snapshot' => $studioPremium->toArray(),
                ]
            );
        }

        if ($studios->count() >= 2 && $studioBasic) {
            $studio = $studios[1];
            StudioPlanModel::updateOrCreate(
                ['studio_id' => $studio->id, 'plan_id' => $studioBasic->id],
                [
                    'subscription_reference' => StudioPlanModel::generateSubscriptionReference(),
                    'start_date' => now()->subDays(10),
                    'end_date' => now()->addDays(20),
                    'next_billing_date' => now()->addDays(20),
                    'amount_paid' => $studioBasic->price,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'paid_at' => now()->subDays(10),
                    'plan_snapshot' => $studioBasic->toArray(),
                ]
            );
        }

        // Subscribe one freelancer to the trial-enabled Starter plan.
        $freelancerUserId = UserModel::where('role', 'freelancer')->orderBy('id')->value('id');
        $freelancerStarter = $plans['Freelancer Starter'] ?? null;

        if ($freelancerUserId && $freelancerStarter) {
            FreelancerPlanModel::updateOrCreate(
                ['freelancer_id' => $freelancerUserId, 'plan_id' => $freelancerStarter->id],
                [
                    'subscription_reference' => FreelancerPlanModel::generateSubscriptionReference(),
                    'start_date' => now()->subDays(1),
                    'end_date' => now()->addDays(29),
                    'next_billing_date' => now()->addDays(29),
                    'amount_paid' => 0,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'plan_snapshot' => $freelancerStarter->toArray(),
                ]
            );
        }

        $this->command?->info('Subscribed sample studios/freelancer to plans.');
    }

    private function seedClientBudget(): void
    {
        $client = UserModel::where('role', 'client')->orderBy('id')->first();
        $category = CategoriesModel::where('status', 'active')->orderBy('id')->first();

        if (!$client || !$category) {
            $this->command?->warn('Skipped client budget: no client or category found.');
            return;
        }

        ClientBudgetModel::updateOrCreate(
            ['client_id' => $client->id, 'budget_name' => 'Wedding Shoot Budget'],
            [
                'description' => 'Savings set aside for our wedding photo and video package.',
                'minimum_budget' => 15000,
                'maximum_budget' => 40000,
                'preferred_budget' => 25000,
                'spent_amount' => 5000,
                'category_id' => $category->id,
                'budget_type' => 'event',
                'status' => 'active',
            ]
        );

        $this->command?->info('Seeded client budget for ' . $client->email);
    }

    private function seedGalleriesAndRatings(): void
    {
        $studioBookings = BookingModel::where('booking_type', 'studio')
            ->where('status', 'completed')
            ->with('client')
            ->orderBy('id')
            ->take(3)
            ->get();

        $galleryIndex = 0;
        foreach ($studioBookings as $booking) {
            if (!$booking->client) {
                continue;
            }

            $existingGallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->first();
            if (!$existingGallery) {
                $isPublished = $galleryIndex !== 1; // seed one draft gallery to show the review step
                $existingGallery = StudioOnlineGalleryModel::create([
                    'booking_id' => $booking->id,
                    'studio_id' => $booking->provider_id,
                    'client_id' => $booking->client_id,
                    'gallery_type' => 'booking',
                    'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
                    'gallery_name' => $booking->event_name . ' Gallery',
                    'description' => 'Photos from ' . $booking->event_name . '.',
                    'images' => ['studio-online-galleries/seed/sample-1.jpg', 'studio-online-galleries/seed/sample-2.jpg'],
                    'status' => 'active',
                    'total_photos' => 2,
                    'gallery_status' => $isPublished ? 'published' : 'draft',
                    'published_at' => $isPublished ? now() : null,
                ]);
            }

            if (!StudioRatingModel::where('booking_id', $booking->id)->exists()) {
                $rating = StudioRatingModel::create([
                    'booking_id' => $booking->id,
                    'client_id' => $booking->client_id,
                    'studio_id' => $booking->provider_id,
                    'rating' => 5,
                    'title' => 'Wonderful experience',
                    'review_text' => 'The team was professional and the photos turned out beautifully. Highly recommended!',
                    'review_type' => StudioRatingModel::getReviewTypeFromRating(5),
                    'preset_used' => null,
                    'is_recommend' => true,
                    'status' => 'published',
                ]);
                StudioRatingModel::updateAggregate($booking->provider_id);
            }

            $galleryIndex++;
        }

        $freelancerBookings = BookingModel::where('booking_type', 'freelancer')
            ->where('status', 'completed')
            ->with('client')
            ->orderBy('id')
            ->take(2)
            ->get();

        foreach ($freelancerBookings as $booking) {
            if (!$booking->client) {
                continue;
            }

            if (!FreelanceOnlineGalleryModel::where('booking_id', $booking->id)->exists()) {
                FreelanceOnlineGalleryModel::create([
                    'booking_id' => $booking->id,
                    'freelancer_id' => $booking->provider_id,
                    'client_id' => $booking->client_id,
                    'gallery_type' => 'booking',
                    'gallery_reference' => FreelanceOnlineGalleryModel::generateGalleryReference(),
                    'gallery_name' => $booking->event_name . ' Gallery',
                    'description' => 'Photos from ' . $booking->event_name . '.',
                    'images' => ['freelancer-online-galleries/seed/sample-1.jpg'],
                    'status' => 'active',
                    'total_photos' => 1,
                    'gallery_status' => 'published',
                    'published_at' => now(),
                ]);
            }

            if (!FreelancerRatingModel::where('booking_id', $booking->id)->exists()) {
                FreelancerRatingModel::create([
                    'booking_id' => $booking->id,
                    'client_id' => $booking->client_id,
                    'freelancer_id' => $booking->provider_id,
                    'rating' => 4,
                    'title' => 'Great session',
                    'review_text' => 'Smooth session and lovely results. Would book again.',
                    'review_type' => FreelancerRatingModel::getReviewTypeFromRating(4),
                    'preset_used' => null,
                    'is_recommend' => true,
                    'status' => 'published',
                ]);
                FreelancerRatingModel::updateAggregate($booking->provider_id);
            }
        }

        $this->command?->info('Seeded galleries and ratings for completed bookings.');
    }

    private function seedStudioMembership(): void
    {
        $studio = StudiosModel::whereIn('status', ['verified', 'active'])->orderBy('id')->first();
        $freelancer = UserModel::where('role', 'freelancer')->orderBy('id')->first();

        if (!$studio || !$freelancer) {
            $this->command?->warn('Skipped studio membership: no studio or freelancer found.');
            return;
        }

        StudioMemberModel::updateOrCreate(
            ['studio_id' => $studio->id, 'freelancer_id' => $freelancer->id],
            [
                'invited_by' => $studio->user_id,
                'invitation_message' => 'We would love to have you shoot with our studio on peak-season bookings.',
                'status' => 'approved',
                'response_message' => 'Sounds great, count me in!',
                'invited_at' => now()->subDays(5),
                'responded_at' => now()->subDays(4),
            ]
        );

        $this->command?->info('Seeded a studio membership invite.');
    }

    private function seedGeneratedPayroll(): void
    {
        $payrollSetting = EmployeePayrollModel::where('is_active', true)->orderBy('id')->first();

        if (!$payrollSetting) {
            $this->command?->warn('Skipped generated payroll: no payroll setting found.');
            return;
        }

        $studioId = $payrollSetting->studio_id;
        $hrUser = UserModel::whereIn('role', ['studio-hr-manager', 'studio-hr-staff'])
            ->whereHas('roles', fn ($q) => $q->where('tbl_user_roles.studio_id', $studioId))
            ->first();
        $financeUser = UserModel::whereIn('role', ['studio-finance-manager', 'studio-finance-staff'])
            ->whereHas('roles', fn ($q) => $q->where('tbl_user_roles.studio_id', $studioId))
            ->first();

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        GeneratedPayrollModel::updateOrCreate(
            [
                'user_id' => $payrollSetting->user_id,
                'studio_id' => $studioId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'payroll_reference' => 'PR-' . strtoupper(Str::random(8)),
                'payroll_setting_id' => $payrollSetting->id,
                'generated_by' => $hrUser->id ?? $payrollSetting->created_by,
                'employee_type' => 'staff',
                'payroll_basis' => $payrollSetting->payroll_basis,
                'employee_role' => 'studio-hr-staff',
                'attendance_days_present' => 20,
                'attendance_days_absent' => 1,
                'attendance_minutes_late' => 15,
                'attendance_minutes_undertime' => 0,
                'booking_count' => 0,
                'attendance_amount' => $payrollSetting->calculateDailyRate() * 20,
                'booking_amount' => 0,
                'gross_amount' => $payrollSetting->calculateDailyRate() * 20,
                'total_deductions' => $payrollSetting->total_deductions,
                'net_amount' => ($payrollSetting->calculateDailyRate() * 20) - $payrollSetting->total_deductions,
                'deduction_breakdown' => ['sss' => (float) $payrollSetting->sss_deduction, 'phic' => (float) $payrollSetting->phic_deduction],
                'computation_summary' => ['note' => 'Seeded payroll run for verification.'],
                'notes' => 'Seeded generated payroll for the current period.',
                'status' => $financeUser ? 'approved' : 'pending_review',
                'reviewed_by' => $financeUser->id ?? null,
                'reviewed_at' => $financeUser ? now() : null,
                'generated_at' => now(),
            ]
        );

        $this->command?->info('Seeded a generated payroll run.');
    }

    private function seedProcurementDefectReturn(): void
    {
        $request = ProcurementRequestModel::with('items')->orderBy('id')->first();

        if (!$request || $request->items->isEmpty()) {
            $this->command?->warn('Skipped procurement defect return: no procurement request/items found.');
            return;
        }

        $item = $request->items->first();
        $reporter = UserModel::find($request->requester_id)
            ?? UserModel::where('role', 'studio-hr-staff')->first();
        $processor = UserModel::whereIn('role', ['studio-finance-manager', 'studio-finance-staff'])->first();

        ProcurementDefectReturnModel::updateOrCreate(
            ['procurement_request_item_id' => $item->id],
            [
                'procurement_request_id' => $request->id,
                'reported_by' => $reporter->id ?? $processor->id ?? UserModel::first()->id,
                'processed_by' => $processor->id ?? null,
                'reported_quantity' => 1,
                'reason_code' => 'damaged_in_transit',
                'reason_other' => null,
                'requester_note' => 'Item arrived with a cracked housing, requesting a replacement.',
                'finance_note' => $processor ? 'Replacement approved and dispatched.' : null,
                'status' => $processor
                    ? ProcurementDefectReturnModel::STATUS_REPLACEMENT_DELIVERED
                    : ProcurementDefectReturnModel::STATUS_REPORTED,
                'reported_at' => now()->subDays(4),
                'processed_at' => $processor ? now()->subDays(2) : null,
                'replacement_delivered_at' => $processor ? now()->subDay() : null,
            ]
        );

        $this->command?->info('Seeded a procurement defect return.');
    }

    private function seedNotifications(): void
    {
        $recipients = [
            UserModel::where('role', 'client')->orderBy('id')->first(),
            UserModel::where('role', 'owner')->orderBy('id')->first(),
            UserModel::where('role', 'freelancer')->orderBy('id')->first(),
        ];

        $count = 0;
        foreach (array_filter($recipients) as $recipient) {
            NotificationModel::create([
                'user_id' => $recipient->id,
                'type' => 'welcome',
                'title' => 'Welcome to Lumora Studio',
                'message' => 'Your account is ready. Explore your dashboard to get started.',
                'data' => json_encode(['route' => '/']),
                'icon' => 'sparkles',
                'color' => 'info',
            ]);
            $count++;
        }

        $this->command?->info("Seeded {$count} welcome notifications.");
    }

    private function seedChatbotConversation(): void
    {
        $config = ChatbotConfigModel::where('is_active', true)->orderBy('id')->first();
        $client = UserModel::where('role', 'client')->orderBy('id')->first();

        if (!$config || !$client) {
            $this->command?->warn('Skipped chatbot conversation: no active chatbot config or client found.');
            return;
        }

        $conversation = ChatbotConversationModel::updateOrCreate(
            ['user_id' => $client->id, 'owner_id' => $config->owner_id, 'config_id' => $config->id],
            [
                'session_id' => ChatbotConversationModel::generateSessionId(),
                'status' => 'ended',
                'started_at' => now()->subMinutes(10),
                'ended_at' => now()->subMinutes(5),
                'message_count' => 2,
                'metadata' => ['source' => 'seed'],
            ]
        );

        $intent = ChatbotIntentModel::where('config_id', $config->id)->orderBy('id')->first();

        if (!ChatbotMessageModel::where('conversation_id', $conversation->id)->exists()) {
            ChatbotMessageModel::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'user',
                'message' => 'Hi, what packages do you offer?',
                'intent_id' => null,
                'was_helpful' => null,
                'metadata' => null,
            ]);

            ChatbotMessageModel::create([
                'conversation_id' => $conversation->id,
                'sender_type' => 'bot',
                'message' => $intent->response_text ?? 'We offer a range of photography packages — check our Packages page for details!',
                'intent_id' => $intent->id ?? null,
                'was_helpful' => true,
                'metadata' => null,
            ]);
        }

        $this->command?->info('Seeded a chatbot conversation.');
    }
}
