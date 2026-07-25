<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal in-memory schema and fixtures for the AI assistant test suites.
 *
 * The full migration set is far larger than these tests need, so this builds
 * only the tables the assistant touches: users, studios, categories, packages,
 * and the chatbot configs / intents / conversations / messages chain.
 */
trait BuildsChatbotSchema
{
    /**
     * Build the schema and reset the caches the assistant depends on.
     */
    protected function bootChatbotDatabase(): void
    {
        $this->createChatbotSchema();

        Cache::flush();
    }

    /**
     * Point the Groq integration at a fake credential with generous budgets, so
     * tests exercise the guard pipeline rather than a rate limit. Individual
     * tests tighten a specific limit when that is what they are asserting.
     */
    protected function configureFakeGroq(): void
    {
        config([
            'services.groq.api_key' => 'gsk_test_key_not_real',
            'services.groq.model' => 'qwen/qwen3.6-27b',
            'services.groq.base_url' => 'https://api.groq.com/openai/v1',
            'services.groq.limits.requests_per_minute' => 1000,
            'services.groq.limits.requests_per_day' => 10000,
            'services.groq.limits.tokens_per_minute' => 1000000,
            'services.groq.limits.tokens_per_day' => 10000000,
            'services.groq.limits.requests_per_user_per_minute' => 1000,
        ]);
    }

    /**
     * Create an owner with an attached studio. Returns the owner's user id.
     */
    protected function createOwnerWithStudio(string $email, string $studioName, ?int $forcedId = null): int
    {
        $ownerData = [
            'uuid' => (string) str()->uuid(),
            'role' => 'owner',
            'first_name' => 'Studio',
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
        ];

        if ($forcedId !== null) {
            $ownerData['id'] = $forcedId;
        }

        $ownerId = DB::table('tbl_users')->insertGetId($ownerData);

        DB::table('tbl_studios')->insert([
            'user_id' => $ownerId,
            'studio_name' => $studioName,
            'studio_type' => 'photography_studio',
            'year_established' => 2020,
            'studio_description' => 'Studio used by the assistant test suite.',
            'studio_logo' => null,
            'starting_price' => '1500',
            'operating_days' => json_encode(['monday', 'tuesday']),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'max_clients_per_day' => 5,
            'advance_booking_days' => 3,
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
     * Create a client user. Returns the client's user id.
     */
    protected function createClient(string $email): int
    {
        return DB::table('tbl_users')->insertGetId([
            'uuid' => (string) str()->uuid(),
            'role' => 'client',
            'first_name' => 'Test',
            'middle_name' => null,
            'last_name' => 'Client',
            'user_type' => 'client',
            'email' => $email,
            'mobile_number' => '09171234568',
            'password' => bcrypt('Password@123'),
            'status' => 'active',
            'email_verified' => true,
            'verification_token' => null,
            'token_expiry' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create a package for the given owner's studio.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function createStudioPackage(int $ownerId, array $attributes = []): int
    {
        $studioId = DB::table('tbl_studios')->where('user_id', $ownerId)->value('id');
        $categoryId = $this->firstOrCreateCategory($attributes['category_name'] ?? 'Wedding Photography');

        return DB::table('tbl_packages')->insertGetId([
            'studio_id' => $studioId,
            'category_id' => $categoryId,
            'package_name' => $attributes['package_name'] ?? 'Test Package',
            'package_description' => $attributes['package_description'] ?? 'A package created for assistant tests.',
            'package_inclusions' => json_encode($attributes['package_inclusions'] ?? ['Coverage', 'Edited photos']),
            'duration' => $attributes['duration'] ?? 4,
            'maximum_edited_photos' => $attributes['maximum_edited_photos'] ?? 100,
            'coverage_scope' => json_encode($attributes['coverage_scope'] ?? ['studio']),
            'package_price' => $attributes['package_price'] ?? 10000,
            'status' => $attributes['status'] ?? 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Look up a category by name, creating it when missing.
     */
    private function firstOrCreateCategory(string $categoryName): int
    {
        $existingId = DB::table('tbl_categories')->where('category_name', $categoryName)->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        return DB::table('tbl_categories')->insertGetId([
            'category_name' => $categoryName,
            'description' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create the minimal schema the assistant needs.
     */
    private function createChatbotSchema(): void
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
            $table->string('category_name')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('tbl_chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('config_name')->nullable();
            $table->text('welcome_message')->nullable();
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
            $table->text('response_text');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
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
    }
}
