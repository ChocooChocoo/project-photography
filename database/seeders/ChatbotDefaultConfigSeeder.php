<?php

namespace Database\Seeders;

use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotIntentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChatbotDefaultConfigSeeder extends Seeder
{
    /**
     * The owner IDs to seed when a targeted reseed is requested.
     *
     * @var array<int>|null
     */
    protected ?array $targetOwnerIds = null;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ownerIds = collect($this->targetOwnerIds ?? $this->discoverOwnerIds())
            ->map(fn ($ownerId) => (int) $ownerId)
            ->unique()
            ->values();

        foreach ($ownerIds as $ownerId) {
            $this->seedOwnerDefaults((int) $ownerId);
        }
    }

    /**
     * Limit the seeder run to specific owners.
     *
     * @param  array<int>  $ownerIds
     */
    public function forOwners(array $ownerIds): self
    {
        $this->targetOwnerIds = array_values(array_unique(array_map('intval', $ownerIds)));

        return $this;
    }

    /**
     * Seed the default chatbot configuration for a specific owner.
     */
    public function seedOwnerDefaults(int $ownerId, bool $forceUpdate = false): ChatbotConfigModel
    {
        return DB::transaction(function () use ($ownerId, $forceUpdate) {
            $config = ChatbotConfigModel::firstOrNew(['owner_id' => $ownerId]);
            $defaultConfig = static::defaultConfigData();

            if (! $config->exists) {
                $config->fill($defaultConfig);
                $config->owner_id = $ownerId;
            } elseif ($forceUpdate) {
                $config->fill($defaultConfig);
            } else {
                $config->fill($this->mergeMissingConfigValues($config, $defaultConfig));
            }

            $config->save();

            foreach (static::defaultIntents() as $intentAttributes) {
                $intent = ChatbotIntentModel::firstOrNew([
                    'config_id' => $config->id,
                    'intent_name' => $intentAttributes['intent_name'],
                ]);

                if (! $intent->exists || $forceUpdate) {
                    $intent->fill($intentAttributes);
                } else {
                    $intent->fill($this->mergeMissingIntentValues($intent, $intentAttributes));
                }

                $intent->config_id = $config->id;
                $intent->save();
            }

            return $config->fresh(['intents']);
        });
    }

    /**
     * Seed the current project defaults for owner 96.
     */
    public function seedOwner96Defaults(bool $forceUpdate = false): ChatbotConfigModel
    {
        return $this->seedOwnerDefaults(96, $forceUpdate);
    }

    /**
     * Get the default chatbot configuration payload.
     *
     * @return array<string, mixed>
     */
    public static function defaultConfigData(): array
    {
        return [
            'config_name' => 'Photography Assistant',
            'welcome_message' => 'Hello and welcome. I am the studio photography assistant. I can help with bookings, packages, pricing, services, and availability. What would you like to know?',
            'is_active' => true,
            'bot_name' => 'Studio Photography Assistant',
            'bot_avatar' => null,
            'settings' => static::defaultSettings(),
        ];
    }

    /**
     * Get the default chatbot moderation and behavior settings.
     *
     * @return array<string, mixed>
     */
    public static function defaultSettings(): array
    {
        return [
            'moderation' => [
                'blocked_words' => [
                    'fuck',
                    'fucking',
                    'motherfucker',
                    'shit',
                    'bitch',
                    'gago',
                    'ulol',
                    'putangina',
                    'puta',
                ],
                'blocked_response' => 'Let us keep the conversation respectful. I can still help you with bookings, pricing, services, or availability.',
                'spam_response' => 'I may have received a spammy or unclear message. Please send one short question about booking, pricing, services, or availability.',
                'noise_response' => 'I did not catch a clear question there. Please send a simple message about booking, pricing, services, or availability.',
                'max_repeated_character_count' => 5,
                'max_duplicate_word_count' => 4,
                'minimum_meaningful_length' => 2,
                'noise_phrases' => [
                    'asdf',
                    'qwerty',
                    'test',
                    'testing',
                    'hello hello hello hello',
                    'spam',
                    '???',
                    '...',
                ],
            ],
            // Suggestion chips. Clicking one sends its text to the assistant as
            // an ordinary question, so they read as questions, not commands.
            'quick_reply_defaults' => [
                'How does booking work?',
                'What are your package rates?',
                'What services do you offer?',
                'How do I check availability?',
            ],
        ];
    }

    /**
     * Get the default seeded studio knowledge entries.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultIntents(): array
    {
        return [
            [
                'intent_name' => 'Greeting and Help',
                'response_text' => 'The studio assistant helps with booking steps, package rates, services, and schedule availability. Questions outside photography services are not covered.',
                'priority' => 90,
                'is_active' => true,
            ],
            [
                'intent_name' => 'Booking Information',
                'response_text' => 'To book a session, please tell us the service you want, your preferred date, and any key details for the shoot. We will guide you through the next available schedule and package options.',
                'priority' => 100,
                'is_active' => true,
            ],
            [
                'intent_name' => 'Package Pricing',
                'response_text' => 'Our package rates depend on the service type, coverage hours, and add-ons you need. If you tell us the kind of shoot you have in mind, we can guide you to the best package quickly.',
                'priority' => 95,
                'is_active' => true,
            ],
            [
                'intent_name' => 'Service Information',
                'response_text' => 'We assist clients with different photo and event-related services depending on the studio setup. Tell us the type of session you need, and we will point you to the most suitable option.',
                'priority' => 85,
                'is_active' => true,
            ],
            [
                'intent_name' => 'Availability and Contact',
                'response_text' => 'For schedule availability or direct follow-up, please share your preferred date and session type. Our team can confirm availability and assist you with the next step.',
                'priority' => 80,
                'is_active' => true,
            ],
        ];
    }

    /**
     * Merge missing configuration values without overwriting existing customizations.
     *
     * @param  array<string, mixed>  $defaultConfig
     * @return array<string, mixed>
     */
    private function mergeMissingConfigValues(ChatbotConfigModel $config, array $defaultConfig): array
    {
        $mergedConfig = [];

        foreach ($defaultConfig as $key => $value) {
            if ($key === 'settings') {
                $mergedConfig[$key] = array_replace_recursive($value, (array) $config->settings);

                continue;
            }

            $currentValue = $config->getAttribute($key);
            $mergedConfig[$key] = blank($currentValue) ? $value : $currentValue;
        }

        return $mergedConfig;
    }

    /**
     * Merge missing intent values without overwriting existing customizations.
     *
     * @param  array<string, mixed>  $intentAttributes
     * @return array<string, mixed>
     */
    private function mergeMissingIntentValues(ChatbotIntentModel $intent, array $intentAttributes): array
    {
        return [
            'intent_name' => $intent->intent_name ?: $intentAttributes['intent_name'],
            'response_text' => $intent->response_text ?: $intentAttributes['response_text'],
            'priority' => $intent->priority ?: $intentAttributes['priority'],
            'is_active' => $intent->exists ? $intent->is_active : $intentAttributes['is_active'],
        ];
    }

    /**
     * Discover owners with studios for the default seeding flow.
     *
     * @return array<int>
     */
    private function discoverOwnerIds(): array
    {
        return DB::table('tbl_studios')
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($ownerId) => (int) $ownerId)
            ->toArray();
    }
}
