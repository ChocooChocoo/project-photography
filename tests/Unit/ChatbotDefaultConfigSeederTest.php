<?php

namespace Tests\Unit;

use Database\Seeders\ChatbotDefaultConfigSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotDefaultConfigSeederTest extends TestCase
{
    /**
     * Prepare the database for chatbot seeder tests.
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
     * The chatbot seeder creates one default config with seeded knowledge entries per owner.
     */
    public function test_chatbot_seeder_creates_default_configuration_for_all_owners(): void
    {
        $firstOwnerId = $this->createOwnerWithStudio('owner-one@example.com', 'Studio One');
        $secondOwnerId = $this->createOwnerWithStudio('owner-two@example.com', 'Studio Two');

        $this->seed(ChatbotDefaultConfigSeeder::class);

        $this->assertDatabaseHas('tbl_chatbot_configs', [
            'owner_id' => $firstOwnerId,
            'config_name' => 'Photography Assistant',
            'bot_name' => 'Studio Photography Assistant',
        ]);

        $this->assertDatabaseHas('tbl_chatbot_configs', [
            'owner_id' => $secondOwnerId,
            'config_name' => 'Photography Assistant',
        ]);

        $this->assertSame(2, DB::table('tbl_chatbot_configs')->count());
        $this->assertSame(10, DB::table('tbl_chatbot_intents')->count());
    }

    /**
     * The chatbot seeder can target owner 96 and every knowledge entry carries usable facts.
     */
    public function test_chatbot_seeder_can_reseed_owner_96_with_knowledge_entries(): void
    {
        $ownerId = $this->createOwnerWithStudio('owner-ninety-six@example.com', 'Studio Ninety Six', 96);

        $seeder = app(ChatbotDefaultConfigSeeder::class);
        $seeder->seedOwner96Defaults();

        $this->assertDatabaseHas('tbl_chatbot_configs', [
            'owner_id' => $ownerId,
            'config_name' => 'Photography Assistant',
        ]);

        $entries = DB::table('tbl_chatbot_intents')
            ->join('tbl_chatbot_configs', 'tbl_chatbot_configs.id', '=', 'tbl_chatbot_intents.config_id')
            ->where('tbl_chatbot_configs.owner_id', $ownerId)
            ->get(['tbl_chatbot_intents.intent_name', 'tbl_chatbot_intents.response_text']);

        $this->assertCount(5, $entries);
        $this->assertTrue($entries->every(fn ($entry) => filled($entry->intent_name) && filled($entry->response_text)));
    }

    /**
     * The chatbot seeder remains idempotent when run multiple times.
     */
    public function test_chatbot_seeder_is_idempotent(): void
    {
        $ownerId = $this->createOwnerWithStudio('owner-repeat@example.com', 'Studio Repeat');

        $this->seed(ChatbotDefaultConfigSeeder::class);
        $configCountAfterFirstRun = DB::table('tbl_chatbot_configs')->count();
        $intentCountAfterFirstRun = DB::table('tbl_chatbot_intents')->count();

        $this->seed(ChatbotDefaultConfigSeeder::class);

        $this->assertSame($configCountAfterFirstRun, DB::table('tbl_chatbot_configs')->count());
        $this->assertSame($intentCountAfterFirstRun, DB::table('tbl_chatbot_intents')->count());
        $this->assertDatabaseHas('tbl_chatbot_configs', [
            'owner_id' => $ownerId,
            'config_name' => 'Photography Assistant',
        ]);
    }

    /**
     * Create a valid owner and studio record for chatbot seeding.
     */
    private function createOwnerWithStudio(string $email, string $studioName, ?int $ownerId = null): int
    {
        $ownerData = [
            'uuid' => (string) str()->uuid(),
            'role' => 'owner',
            'first_name' => 'Seed',
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

        if ($ownerId !== null) {
            $ownerData['id'] = $ownerId;
        }

        $ownerId = DB::table('tbl_users')->insertGetId($ownerData);

        DB::table('tbl_studios')->insert([
            'user_id' => $ownerId,
            'studio_name' => $studioName,
            'studio_type' => 'photography_studio',
            'year_established' => 2020,
            'studio_description' => 'Seeded studio for chatbot tests.',
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
     * Create the minimal schema required for chatbot seeder tests.
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

        Schema::create('tbl_chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->constrained('tbl_chatbot_configs')->cascadeOnDelete();
            $table->string('intent_name');
            $table->text('response_text');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}
