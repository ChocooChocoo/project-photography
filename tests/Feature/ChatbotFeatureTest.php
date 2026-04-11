<?php

namespace Tests\Feature;

use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotMessageModel;
use App\Services\ChatbotService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotFeatureTest extends TestCase
{
    /**
     * Prepare the database for chatbot feature tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createChatbotTestSchema();
    }

    /**
     * The chatbot bootstraps seeded defaults for an owner without a chatbot config.
     */
    public function test_chatbot_bootstraps_seeded_defaults_for_owner_without_config(): void
    {
        $ownerId = $this->createOwnerWithStudio('bootstrap-owner@example.com', 'Bootstrap Studio');
        $service = app(ChatbotService::class);

        $config = $service->forOwner($ownerId)->getActiveConfig();

        $this->assertNotNull($config);
        $this->assertSame('Realistic Client Support', $config->config_name);
        $this->assertDatabaseHas('tbl_chatbot_intents', [
            'config_id' => $config->id,
            'intent_name' => 'Booking Information',
        ]);
    }

    /**
     * The chatbot matches seeded inquiry intents for common client questions.
     */
    public function test_chatbot_matches_seeded_inquiry_intents(): void
    {
        $ownerId = $this->createOwnerWithStudio('inquiry-owner@example.com', 'Inquiry Studio');
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Classic Portrait',
            'package_price' => 2500,
        ]);

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $pricingResponse = $service->processMessage('Can you share your package price?');
        $bookingResponse = $service->processMessage('How do I book a session?');
        $serviceResponse = $service->processMessage('What services do you offer?');

        $this->assertSame('Package Pricing', $pricingResponse['metadata']['intent_name']);
        $this->assertSame('Booking Information', $bookingResponse['metadata']['intent_name']);
        $this->assertSame('Service Information', $serviceResponse['metadata']['intent_name']);
        $this->assertNotEmpty($pricingResponse['quick_replies']);
        $this->assertIsArray($pricingResponse['metadata']['packages']);
        $this->assertNotEmpty($pricingResponse['metadata']['packages']);
        $this->assertStringContainsString('Classic Portrait', $pricingResponse['message']);
        $this->assertStringContainsString('PHP 2,500.00', $pricingResponse['message']);
    }

    /**
     * The chatbot resolves exact intent targets immediately for quick reply actions.
     */
    public function test_chatbot_resolves_exact_intent_targets_without_fallback(): void
    {
        $ownerId = $this->createOwnerWithStudio('exact-intent-owner@example.com', 'Exact Intent Studio');
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Signature Package',
            'package_price' => 32000,
        ]);

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $pricingResponse = $service->processMessage('Package Pricing');
        $availabilityResponse = $service->processMessage('Availability and Contact');
        $serviceResponse = $service->processMessage('Service Information');
        $bookingResponse = $service->processMessage('Booking Information');

        $this->assertSame('Package Pricing', $pricingResponse['metadata']['intent_name']);
        $this->assertArrayNotHasKey('is_fallback', $pricingResponse['metadata']);
        $this->assertStringContainsString('Signature Package', $pricingResponse['message']);

        $this->assertSame('Availability and Contact', $availabilityResponse['metadata']['intent_name']);
        $this->assertArrayNotHasKey('is_fallback', $availabilityResponse['metadata']);

        $this->assertSame('Service Information', $serviceResponse['metadata']['intent_name']);
        $this->assertArrayNotHasKey('is_fallback', $serviceResponse['metadata']);

        $this->assertSame('Booking Information', $bookingResponse['metadata']['intent_name']);
        $this->assertArrayNotHasKey('is_fallback', $bookingResponse['metadata']);
    }

    /**
     * The chatbot moderates bad words and spam before intent matching.
     */
    public function test_chatbot_moderates_blocked_and_spammy_messages(): void
    {
        $ownerId = $this->createOwnerWithStudio('moderation-owner@example.com', 'Moderation Studio');
        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $blockedResponse = $service->processMessage('fuck your service');
        $spamResponse = $service->processMessage('helooooooooooooo');

        $this->assertSame('blocked_language', $blockedResponse['metadata']['moderation']['type']);
        $this->assertSame('spam_or_repetition', $spamResponse['metadata']['moderation']['type']);

        $lastTwoUserMessages = ChatbotMessageModel::query()
            ->where('sender_type', 'user')
            ->latest('id')
            ->take(2)
            ->get();

        $this->assertTrue($lastTwoUserMessages->contains(function (ChatbotMessageModel $message) {
            return ($message->metadata['moderation']['type'] ?? null) === 'blocked_language';
        }));

        $this->assertTrue($lastTwoUserMessages->contains(function (ChatbotMessageModel $message) {
            return ($message->metadata['moderation']['type'] ?? null) === 'spam_or_repetition';
        }));
    }

    /**
     * The chatbot returns seeded fallback guidance for unknown valid messages.
     */
    public function test_chatbot_returns_fallback_for_unknown_valid_message(): void
    {
        $ownerId = $this->createOwnerWithStudio('fallback-owner@example.com', 'Fallback Studio');
        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();

        $response = $service->processMessage('I want to discuss something custom and unusual.');
        $config = ChatbotConfigModel::byOwner($ownerId)->firstOrFail();

        $this->assertTrue($response['metadata']['is_fallback']);
        $this->assertSame($config->fallback_message, $response['message']);
        $this->assertNotEmpty($response['quick_replies']);
    }

    /**
     * The chatbot uses only active studio packages in the package pricing response.
     */
    public function test_chatbot_returns_only_active_studio_packages(): void
    {
        $ownerId = $this->createOwnerWithStudio('active-package-owner@example.com', 'Package Studio');
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Wedding Essential',
            'package_price' => 15000,
            'package_description' => 'Ideal for intimate ceremonies.',
            'package_inclusions' => ['4 hours coverage', '150 edited photos', 'Online gallery'],
            'status' => 'active',
        ]);
        $this->createStudioPackage($ownerId, [
            'package_name' => 'Luxury Wedding',
            'package_price' => 45000,
            'status' => 'inactive',
        ]);

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();
        $response = $service->processMessage('What are your packages?');

        $this->assertSame('Package Pricing', $response['metadata']['intent_name']);
        $this->assertCount(1, $response['metadata']['packages']);
        $this->assertStringContainsString('Wedding Essential', $response['message']);
        $this->assertStringContainsString('PHP 15,000.00', $response['message']);
        $this->assertStringContainsString('4 hours coverage', $response['message']);
        $this->assertStringNotContainsString('Luxury Wedding', $response['message']);
    }

    /**
     * The chatbot returns a package-specific fallback when no active packages exist.
     */
    public function test_chatbot_returns_package_specific_fallback_when_no_active_packages_exist(): void
    {
        $ownerId = $this->createOwnerWithStudio('no-package-owner@example.com', 'No Package Studio');

        $service = app(ChatbotService::class)->forOwner($ownerId);
        $service->startConversation();
        $response = $service->processMessage('Can I see your package list?');

        $this->assertSame('Package Pricing', $response['metadata']['intent_name']);
        $this->assertSame([], $response['metadata']['packages']);
        $this->assertStringContainsString('No package details are available right now', $response['message']);
        $this->assertNotEmpty($response['quick_replies']);
    }

    /**
     * Create a valid owner and studio record for chatbot feature tests.
     */
    private function createOwnerWithStudio(string $email, string $studioName): int
    {
        $ownerId = DB::table('tbl_users')->insertGetId([
            'uuid' => (string) str()->uuid(),
            'role' => 'owner',
            'first_name' => 'Feature',
            'middle_name' => null,
            'last_name' => 'Owner',
            'user_type' => 'photographer',
            'email' => $email,
            'mobile_number' => '09171234567',
            'password' => bcrypt('Password@123'),
            'status' => 'active',
            'email_verified' => true,
            'verification_token' => null,
            'token_expiry' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tbl_studios')->insert([
            'user_id' => $ownerId,
            'studio_name' => $studioName,
            'studio_type' => 'photography_studio',
            'year_established' => 2020,
            'studio_description' => 'Feature test studio.',
            'studio_logo' => null,
            'starting_price' => '1800',
            'operating_days' => json_encode(['monday', 'tuesday']),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'max_clients_per_day' => 4,
            'advance_booking_days' => 2,
            'business_permit' => null,
            'owner_id_document' => null,
            'status' => 'active',
            'rejection_note' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $ownerId;
    }

    /**
     * Create a studio package for the owner's studio.
     *
     * @param array<string, mixed> $overrides
     */
    private function createStudioPackage(int $ownerId, array $overrides = []): void
    {
        $studioId = DB::table('tbl_studios')->where('user_id', $ownerId)->value('id');
        $categoryId = DB::table('tbl_categories')->insertGetId([
            'category_name' => 'Portrait',
            'description' => 'Portrait category',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (isset($overrides['package_inclusions']) && is_array($overrides['package_inclusions'])) {
            $overrides['package_inclusions'] = json_encode($overrides['package_inclusions']);
        }

        if (isset($overrides['coverage_scope']) && is_array($overrides['coverage_scope'])) {
            $overrides['coverage_scope'] = json_encode($overrides['coverage_scope']);
        }

        DB::table('tbl_packages')->insert(array_merge([
            'studio_id' => $studioId,
            'category_id' => $categoryId,
            'package_name' => 'Default Package',
            'package_description' => 'A sample package description for testing.',
            'package_inclusions' => json_encode(['Consultation', 'Edited photos', 'Online gallery']),
            'duration' => 2,
            'maximum_edited_photos' => 20,
            'coverage_scope' => json_encode(['Studio']),
            'package_price' => 1999.99,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * Create the minimal schema required for chatbot feature tests.
     */
    private function createChatbotTestSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('tbl_users', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('role');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('user_type');
            $table->string('email')->unique();
            $table->string('mobile_number');
            $table->string('password');
            $table->string('status')->default('active');
            $table->boolean('email_verified')->default(false);
            $table->string('verification_token')->nullable();
            $table->timestamp('token_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('studio_name');
            $table->string('studio_type');
            $table->integer('year_established');
            $table->text('studio_description');
            $table->string('studio_logo')->nullable();
            $table->string('starting_price')->nullable();
            $table->json('operating_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('max_clients_per_day')->default(1);
            $table->integer('advance_booking_days')->default(1);
            $table->string('business_permit')->nullable();
            $table->string('owner_id_document')->nullable();
            $table->string('status')->default('active');
            $table->text('rejection_note')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('config_name')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('bot_name')->default('Support Assistant');
            $table->string('bot_avatar')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('tbl_categories')->cascadeOnDelete();
            $table->string('package_name');
            $table->text('package_description');
            $table->json('package_inclusions')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('maximum_edited_photos')->default(0);
            $table->json('coverage_scope')->nullable();
            $table->decimal('package_price', 10, 2);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->foreignId('owner_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->foreignId('config_id')->nullable()->constrained('tbl_chatbot_configs')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('tbl_chatbot_configs')->cascadeOnDelete();
            $table->string('intent_name');
            $table->json('trigger_keywords');
            $table->text('response_text');
            $table->string('response_type')->default('text');
            $table->string('image_url')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('match_count')->default(0);
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('tbl_chatbot_conversations')->cascadeOnDelete();
            $table->string('sender_type');
            $table->text('message');
            $table->foreignId('intent_id')->nullable()->constrained('tbl_chatbot_intents')->nullOnDelete();
            $table->boolean('was_helpful')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('intent_id')->constrained('tbl_chatbot_intents')->cascadeOnDelete();
            $table->string('reply_text');
            $table->string('action_value')->nullable();
            $table->string('action_type')->default('trigger_intent');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
