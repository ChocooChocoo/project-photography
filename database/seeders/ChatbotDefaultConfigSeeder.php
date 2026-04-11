<?php

namespace Database\Seeders;

use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotIntentModel;
use App\Models\Chatbot\ChatbotQuickReplyModel;
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
     * @param array<int> $ownerIds
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

            if (!$config->exists) {
                $config->fill($defaultConfig);
                $config->owner_id = $ownerId;
            } elseif ($forceUpdate) {
                $config->fill($defaultConfig);
            } else {
                $config->fill($this->mergeMissingConfigValues($config, $defaultConfig));
            }

            $config->save();

            foreach (static::defaultIntents() as $intentDefinition) {
                $intentAttributes = $intentDefinition;
                unset($intentAttributes['quick_replies']);

                $intent = ChatbotIntentModel::firstOrNew([
                    'config_id' => $config->id,
                    'intent_name' => $intentDefinition['intent_name'],
                ]);

                if (!$intent->exists || $forceUpdate) {
                    $intent->fill($intentAttributes);
                } else {
                    $intent->fill($this->mergeMissingIntentValues($intent, $intentAttributes));
                }

                $intent->config_id = $config->id;
                $intent->save();

                foreach ($intentDefinition['quick_replies'] as $replyDefinition) {
                    $quickReply = ChatbotQuickReplyModel::firstOrNew([
                        'intent_id' => $intent->id,
                        'reply_text' => $replyDefinition['reply_text'],
                    ]);

                    if (!$quickReply->exists || $forceUpdate) {
                        $quickReply->fill($replyDefinition);
                    } else {
                        $quickReply->fill($this->mergeMissingQuickReplyValues($quickReply, $replyDefinition));
                    }

                    $quickReply->intent_id = $intent->id;
                    $quickReply->save();
                }
            }

            return $config->fresh(['intents.quickReplies']);
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
            'config_name' => 'Realistic Client Support',
            'welcome_message' => 'Hello and welcome. I am here to help with bookings, packages, services, and availability. What would you like to know today?',
            'fallback_message' => 'I can help with bookings, pricing, services, and availability. Please choose a quick reply or ask about one of those topics.',
            'is_active' => true,
            'bot_name' => 'Studio Support Assistant',
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
            'quick_reply_defaults' => [
                'Booking Process',
                'Package Rates',
                'Available Services',
                'Talk to Our Team',
            ],
        ];
    }

    /**
     * Get the default seeded intents and quick replies.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaultIntents(): array
    {
        return [
            [
                'intent_name' => 'Greeting and Help',
                'trigger_keywords' => [
                    'hello',
                    'hi',
                    'hey',
                    'good morning',
                    'good afternoon',
                    'good evening',
                    'greetings',
                    'help',
                    'can you help me',
                    'i need help',
                    'assist me',
                    'support',
                    'hello there',
                    'hi there',
                    'anyone there',
                    'are you available',
                    'can we talk',
                    'start chat',
                    'start',
                    'menu',
                    'what can you do',
                    'how can you help',
                ],
                'response_text' => 'Hi there. I can guide you through booking, package rates, services, and availability. Choose an option below or send your question.',
                'response_type' => 'quick_reply',
                'image_url' => null,
                'priority' => 90,
                'is_active' => true,
                'quick_replies' => [
                    [
                        'reply_text' => 'Booking Process',
                        'action_value' => 'Booking Information',
                        'action_type' => 'trigger_intent',
                        'position' => 0,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Package Rates',
                        'action_value' => 'Package Pricing',
                        'action_type' => 'trigger_intent',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Available Services',
                        'action_value' => 'Service Information',
                        'action_type' => 'trigger_intent',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'intent_name' => 'Booking Information',
                'trigger_keywords' => [
                    'book',
                    'booking',
                    'booking information',
                    'how to book',
                    'how do i book',
                    'i want to book',
                    'book now',
                    'reserve',
                    'reservation',
                    'set an appointment',
                    'appointment',
                    'schedule',
                    'schedule a shoot',
                    'schedule a session',
                    'how can i reserve',
                    'how can i schedule',
                    'start booking',
                    'booking process',
                    'booking steps',
                    'how does booking work',
                    'can i reserve a slot',
                    'need to book a session',
                ],
                'response_text' => 'To book a session, please tell us the service you want, your preferred date, and any key details for the shoot. We will guide you through the next available schedule and package options.',
                'response_type' => 'quick_reply',
                'image_url' => null,
                'priority' => 100,
                'is_active' => true,
                'quick_replies' => [
                    [
                        'reply_text' => 'View Package Rates',
                        'action_value' => 'Package Pricing',
                        'action_type' => 'trigger_intent',
                        'position' => 0,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Ask About Availability',
                        'action_value' => 'Availability and Contact',
                        'action_type' => 'trigger_intent',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Available Services',
                        'action_value' => 'Service Information',
                        'action_type' => 'trigger_intent',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'intent_name' => 'Package Pricing',
                'trigger_keywords' => [
                    'price',
                    'pricing',
                    'package pricing',
                    'package rates',
                    'rates',
                    'rate',
                    'cost',
                    'package',
                    'packages',
                    'how much',
                    'magkano',
                    'how much is it',
                    'how much are your packages',
                    'what are your packages',
                    'show me your packages',
                    'package list',
                    'price list',
                    'pricing list',
                    'quotation',
                    'quote',
                    'wedding packages',
                    'available packages',
                ],
                'response_text' => 'Our package rates depend on the service type, coverage hours, and add-ons you need. If you tell us the kind of shoot you have in mind, we can guide you to the best package quickly.',
                'response_type' => 'quick_reply',
                'image_url' => null,
                'priority' => 95,
                'is_active' => true,
                'quick_replies' => [
                    [
                        'reply_text' => 'Wedding Packages',
                        'action_value' => null,
                        'action_type' => 'none',
                        'position' => 0,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Portrait Services',
                        'action_value' => 'Service Information',
                        'action_type' => 'trigger_intent',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'How to Book',
                        'action_value' => 'Booking Information',
                        'action_type' => 'trigger_intent',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'intent_name' => 'Service Information',
                'trigger_keywords' => [
                    'service',
                    'services',
                    'service information',
                    'available services',
                    'what services do you offer',
                    'what do you offer',
                    'offer',
                    'offers',
                    'shoot',
                    'photoshoot',
                    'photo shoot',
                    'coverage',
                    'studio service',
                    'session types',
                    'what kind of shoots do you do',
                    'what can i book',
                    'types of services',
                    'studio offerings',
                    'portrait services',
                    'event coverage',
                    'what sessions are available',
                    'what packages do you offer for services',
                ],
                'response_text' => 'We assist clients with different photo and event-related services depending on the studio setup. Tell us the type of session you need, and we will point you to the most suitable option.',
                'response_type' => 'quick_reply',
                'image_url' => null,
                'priority' => 85,
                'is_active' => true,
                'quick_replies' => [
                    [
                        'reply_text' => 'Check Package Rates',
                        'action_value' => 'Package Pricing',
                        'action_type' => 'trigger_intent',
                        'position' => 0,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Ask About Availability',
                        'action_value' => 'Availability and Contact',
                        'action_type' => 'trigger_intent',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Start Booking',
                        'action_value' => 'Booking Information',
                        'action_type' => 'trigger_intent',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'intent_name' => 'Availability and Contact',
                'trigger_keywords' => [
                    'available',
                    'availability',
                    'availability and contact',
                    'are you available',
                    'is this date available',
                    'do you have an open slot',
                    'open',
                    'open slot',
                    'contact',
                    'contact details',
                    'phone',
                    'email',
                    'mobile number',
                    'follow up',
                    'follow-up',
                    'staff',
                    'team',
                    'talk to your team',
                    'how can i contact you',
                    'can i contact the studio',
                    'can someone call me',
                    'schedule availability',
                ],
                'response_text' => 'For schedule availability or direct follow-up, please share your preferred date and session type. Our team can confirm availability and assist you with the next step.',
                'response_type' => 'quick_reply',
                'image_url' => null,
                'priority' => 80,
                'is_active' => true,
                'quick_replies' => [
                    [
                        'reply_text' => 'Start Booking',
                        'action_value' => 'Booking Information',
                        'action_type' => 'trigger_intent',
                        'position' => 0,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Package Rates',
                        'action_value' => 'Package Pricing',
                        'action_type' => 'trigger_intent',
                        'position' => 1,
                        'is_active' => true,
                    ],
                    [
                        'reply_text' => 'Available Services',
                        'action_value' => 'Service Information',
                        'action_type' => 'trigger_intent',
                        'position' => 2,
                        'is_active' => true,
                    ],
                ],
            ],
        ];
    }

    /**
     * Merge missing configuration values without overwriting existing customizations.
     *
     * @param array<string, mixed> $defaultConfig
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
     * @param array<string, mixed> $intentAttributes
     * @return array<string, mixed>
     */
    private function mergeMissingIntentValues(ChatbotIntentModel $intent, array $intentAttributes): array
    {
        return [
            'intent_name' => $intent->intent_name ?: $intentAttributes['intent_name'],
            'trigger_keywords' => !empty($intent->trigger_keywords) ? $intent->trigger_keywords : $intentAttributes['trigger_keywords'],
            'response_text' => $intent->response_text ?: $intentAttributes['response_text'],
            'response_type' => $intent->response_type ?: $intentAttributes['response_type'],
            'image_url' => $intent->image_url ?: $intentAttributes['image_url'],
            'priority' => $intent->priority ?: $intentAttributes['priority'],
            'is_active' => $intent->exists ? $intent->is_active : $intentAttributes['is_active'],
        ];
    }

    /**
     * Merge missing quick reply values without overwriting existing customizations.
     *
     * @param array<string, mixed> $replyDefinition
     * @return array<string, mixed>
     */
    private function mergeMissingQuickReplyValues(ChatbotQuickReplyModel $quickReply, array $replyDefinition): array
    {
        return [
            'reply_text' => $quickReply->reply_text ?: $replyDefinition['reply_text'],
            'action_value' => $quickReply->action_value ?: $replyDefinition['action_value'],
            'action_type' => $quickReply->action_type ?: $replyDefinition['action_type'],
            'position' => $quickReply->position ?: $replyDefinition['position'],
            'is_active' => $quickReply->exists ? $quickReply->is_active : $replyDefinition['is_active'],
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
