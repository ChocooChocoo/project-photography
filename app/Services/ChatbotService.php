<?php

namespace App\Services;

use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use Database\Seeders\ChatbotDefaultConfigSeeder;
use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotIntentModel;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotMessageModel;

class ChatbotService
{
    /**
     * @var BotMan
     */
    protected $botman;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var ChatbotConfigModel|null
     */
    protected $activeConfig;

    /**
     * @var ChatbotConversationModel|null
     */
    protected $conversation;

    /**
     * Constructor
     */
    public function __construct()
    {
        DriverManager::loadDriver(\BotMan\Drivers\Web\WebDriver::class);
        
        $this->config = [
            'web' => [
                'matchingData' => [
                    'driver' => 'web',
                ],
            ],
        ];

        $this->botman = BotManFactory::create($this->config);
    }

    /**
     * Initialize chatbot for a specific owner
     *
     * @param int $ownerId
     * @return $this
     */
    public function forOwner(int $ownerId)
    {
        $this->bootstrapOwnerConfig($ownerId);

        $this->activeConfig = ChatbotConfigModel::byOwner($ownerId)
            ->active()
            ->with(['activeIntents' => function($query) {
                $query->with('activeQuickReplies')->byPriority();
            }])
            ->first();

        return $this;
    }

    /**
     * Start a new conversation
     *
     * @param int|null $userId
     * @return ChatbotConversationModel
     */
    public function startConversation($userId = null)
    {
        if (!$this->activeConfig) {
            throw new \Exception('Chatbot not initialized. Call forOwner() first.');
        }

        $this->conversation = ChatbotConversationModel::create([
            'session_id' => ChatbotConversationModel::generateSessionId(),
            'user_id' => $userId,
            'owner_id' => $this->activeConfig->owner_id,
            'config_id' => $this->activeConfig->id,
            'status' => 'active',
            'started_at' => now(),
            'message_count' => 0,
            'metadata' => [
                'bot_name' => $this->activeConfig->bot_name,
                'started_from' => request()->path(),
            ],
        ]);

        // Log the welcome message
        if ($this->activeConfig->welcome_message) {
            $this->saveBotMessage($this->activeConfig->welcome_message);
        }

        return $this->conversation;
    }

    /**
     * Continue an existing conversation
     *
     * @param string $sessionId
     * @return $this
     */
    public function continueConversation(string $sessionId)
    {
        $this->conversation = ChatbotConversationModel::where('session_id', $sessionId)
            ->with(['messages' => function($query) {
                $query->chronological();
            }])
            ->first();

        if (!$this->conversation) {
            throw new \Exception('Conversation not found');
        }

        if (!$this->conversation->isActive()) {
            // Reactivate if ended recently? Or start new?
            $this->conversation->update(['status' => 'active']);
        }

        // Load the config that was used for this conversation
        $this->activeConfig = ChatbotConfigModel::with(['activeIntents' => function($query) {
            $query->with('activeQuickReplies')->byPriority();
        }])->find($this->conversation->config_id);

        return $this;
    }

    /**
     * Process a user message and get response
     *
     * @param string $message
     * @return array
     */
    public function processMessage(string $message)
    {
        if (!$this->conversation) {
            throw new \Exception('No active conversation. Call startConversation() or continueConversation() first.');
        }

        $userMessage = $this->saveUserMessage($message);
        $normalizedMessage = $this->normalizeMessage($message);
        $moderationResult = $this->evaluateMessage($normalizedMessage);

        $response = null;
        $quickReplies = [];
        $metadata = [];

        if ($moderationResult !== null) {
            $response = $moderationResult['message'];
            $quickReplies = $this->defaultQuickReplies();
            $metadata = [
                'moderation' => $moderationResult['metadata'],
                'quick_replies' => $quickReplies,
            ];

            $this->saveBotMessage($response);
            $this->appendMessageMetadata($userMessage, [
                'normalized_message' => $normalizedMessage,
                'moderation' => $moderationResult['metadata'],
            ]);
        } else {
            $this->appendMessageMetadata($userMessage, [
                'normalized_message' => $normalizedMessage,
            ]);

            $matchedIntent = $this->findMatchingIntent($normalizedMessage);

            if ($matchedIntent) {
                $matchedIntent->incrementMatchCount();
                $response = $this->resolveIntentResponse($matchedIntent);
                $dynamicMetadata = $this->buildIntentMetadata($matchedIntent);

                if ($matchedIntent->response_type === 'quick_reply') {
                    $quickReplies = $matchedIntent->activeQuickReplies->map(function($reply) {
                        return [
                            'text' => $reply->reply_text,
                            'action' => $reply->action_value,
                            'action_type' => $reply->action_type,
                        ];
                    })->toArray();
                }

                $this->saveBotMessage($response, $matchedIntent->id);

                $metadata = [
                    'intent_id' => $matchedIntent->id,
                    'intent_name' => $matchedIntent->intent_name,
                ];

                if (!empty($dynamicMetadata)) {
                    $metadata = array_merge($metadata, $dynamicMetadata);
                }

                if (!empty($quickReplies)) {
                    $metadata['quick_replies'] = $quickReplies;
                }
            } else {
                $response = $this->activeConfig->fallback_message;
                $quickReplies = $this->defaultQuickReplies();
                $this->saveBotMessage($response);
                $metadata = [
                    'is_fallback' => true,
                    'quick_replies' => $quickReplies,
                ];
            }
        }

        $this->updateConversationMetadata();

        return [
            'success' => true,
            'message' => $response,
            'quick_replies' => $quickReplies,
            'conversation_id' => $this->conversation->id,
            'session_id' => $this->conversation->session_id,
            'metadata' => $metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Find matching intent based on message
     *
     * @param string $message
     * @return ChatbotIntentModel|null
     */
    protected function findMatchingIntent(string $message)
    {
        $intents = $this->getActiveIntents();

        if (!$this->activeConfig || $intents->isEmpty()) {
            return null;
        }

        $directIntentMatch = $this->findDirectIntentMatch($message, $intents);
        if ($directIntentMatch) {
            return $directIntentMatch;
        }

        $bestMatch = null;
        $highestScore = 0;

        foreach ($intents as $intent) {
            $keywords = $intent->trigger_keywords ?? [];
            
            foreach ($keywords as $keyword) {
                $keyword = strtolower(trim($keyword));

                if ($keyword === '' || !$this->messageMatchesKeyword($message, $keyword)) {
                    continue;
                }

                    $score = strlen($keyword) * 10;

                    if ($this->messageStartsWithKeyword($message, $keyword)) {
                        $score *= 2;
                    }

                    $score += $intent->priority * 5;

                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestMatch = $intent;
                    }
            }
        }

        return $bestMatch;
    }

    /**
     * Resolve direct intent triggers before fuzzy keyword matching.
     */
    protected function findDirectIntentMatch(string $message, $intents): ?ChatbotIntentModel
    {
        $normalizedTarget = strtolower(trim($message));

        if ($normalizedTarget === '') {
            return null;
        }

        foreach ($intents as $intent) {
            if (strtolower(trim($intent->intent_name)) === $normalizedTarget) {
                return $intent;
            }
        }

        return null;
    }

    /**
     * Resolve the final response text for a matched intent.
     */
    protected function resolveIntentResponse(ChatbotIntentModel $matchedIntent): string
    {
        if ($this->isPackagePricingIntent($matchedIntent)) {
            return $this->buildStudioPackagesResponse() ?? $matchedIntent->response_text;
        }

        return $matchedIntent->response_text;
    }

    /**
     * Determine whether the matched intent should show dynamic package data.
     */
    protected function isPackagePricingIntent(ChatbotIntentModel $matchedIntent): bool
    {
        return strtolower($matchedIntent->intent_name) === 'package pricing';
    }

    /**
     * Build extra response metadata for intents that need structured frontend rendering.
     *
     * @return array<string, mixed>
     */
    protected function buildIntentMetadata(ChatbotIntentModel $matchedIntent): array
    {
        if ($this->isPackagePricingIntent($matchedIntent)) {
            return [
                'packages' => $this->getStudioPackagePayload(),
            ];
        }

        return [];
    }

    /**
     * Build a package response from the owner's active studio packages.
     */
    protected function buildStudioPackagesResponse(): ?string
    {
        $packages = $this->getStudioPackages();

        if ($packages === null) {
            return null;
        }

        if ($packages->isEmpty()) {
            return 'No package details are available right now. Please try again later or ask our team for assistance.';
        }

        $lines = [
            'Here are our available studio packages:',
        ];

        foreach ($packages as $index => $package) {
            $lines[] = '';
            $lines[] = ($index + 1) . '. ' . $package->package_name;
            $lines[] = 'Price: PHP ' . number_format((float) $package->package_price, 2);

            if ($package->category?->category_name) {
                $lines[] = 'Category: ' . $package->category->category_name;
            }

            if (!empty($package->package_description)) {
                $lines[] = 'Details: ' . trim($package->package_description);
            }

            $inclusions = array_slice((array) $package->package_inclusions, 0, 3);
            if (!empty($inclusions)) {
                $lines[] = 'Includes: ' . implode(', ', array_map('trim', $inclusions));
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get active studio packages for the current owner.
     */
    protected function getStudioPackages()
    {
        if (!$this->activeConfig) {
            return null;
        }

        $studio = StudiosModel::query()
            ->where('user_id', $this->activeConfig->owner_id)
            ->first();

        if (!$studio) {
            return collect();
        }

        return PackagesModel::query()
            ->where('studio_id', $studio->id)
            ->where('status', 'active')
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('package_name')
            ->get();
    }

    /**
     * Build structured package metadata for client-side rendering.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getStudioPackagePayload(): array
    {
        $packages = $this->getStudioPackages();

        if ($packages === null || $packages->isEmpty()) {
            return [];
        }

        return $packages->map(function ($package) {
            return [
                'name' => $package->package_name,
                'price' => 'PHP ' . number_format((float) $package->package_price, 2),
                'category' => $package->category?->category_name,
                'description' => $package->package_description,
                'inclusions' => array_values(array_slice((array) $package->package_inclusions, 0, 3)),
            ];
        })->toArray();
    }

    /**
     * Determine whether the message matches a keyword as a word or phrase.
     */
    protected function messageMatchesKeyword(string $message, string $keyword): bool
    {
        $pattern = '/(?<![a-z0-9])' . preg_quote($keyword, '/') . '(?![a-z0-9])/u';

        return preg_match($pattern, $message) === 1;
    }

    /**
     * Determine whether the message starts with the matched keyword or phrase.
     */
    protected function messageStartsWithKeyword(string $message, string $keyword): bool
    {
        $pattern = '/^' . preg_quote($keyword, '/') . '(?![a-z0-9])/u';

        return preg_match($pattern, $message) === 1;
    }

    /**
     * Bootstrap the chatbot configuration for an owner when missing.
     */
    protected function bootstrapOwnerConfig(int $ownerId): void
    {
        if (ChatbotConfigModel::byOwner($ownerId)->exists()) {
            return;
        }

        app(ChatbotDefaultConfigSeeder::class)->seedOwnerDefaults($ownerId);
    }

    /**
     * Get the active intents collection for the current configuration.
     *
     * @return \Illuminate\Support\Collection<int, ChatbotIntentModel>
     */
    protected function getActiveIntents()
    {
        if (!$this->activeConfig) {
            return collect();
        }

        if ($this->activeConfig->relationLoaded('activeIntents')) {
            return $this->activeConfig->activeIntents;
        }

        return $this->activeConfig->activeIntents()
            ->with('activeQuickReplies')
            ->byPriority()
            ->get();
    }

    /**
     * Normalize a user message before moderation and intent matching.
     */
    protected function normalizeMessage(string $message): string
    {
        $normalizedMessage = strtolower(trim($message));
        $normalizedMessage = preg_replace('/\s+/', ' ', $normalizedMessage) ?? $normalizedMessage;

        return $normalizedMessage;
    }

    /**
     * Evaluate whether a message should be moderated before intent matching.
     *
     * @return array<string, mixed>|null
     */
    protected function evaluateMessage(string $normalizedMessage): ?array
    {
        $moderationSettings = $this->getModerationSettings();
        $blockedWords = $moderationSettings['blocked_words'] ?? [];

        foreach ($blockedWords as $blockedWord) {
            $pattern = '/\b' . preg_quote(strtolower($blockedWord), '/') . '\b/u';

            if (preg_match($pattern, $normalizedMessage) === 1) {
                return [
                    'message' => $moderationSettings['blocked_response'],
                    'metadata' => [
                        'type' => 'blocked_language',
                        'matched_value' => $blockedWord,
                    ],
                ];
            }
        }

        if ($this->isSpamMessage($normalizedMessage, $moderationSettings)) {
            return [
                'message' => $moderationSettings['spam_response'],
                'metadata' => [
                    'type' => 'spam_or_repetition',
                ],
            ];
        }

        if ($this->isNoiseMessage($normalizedMessage, $moderationSettings)) {
            return [
                'message' => $moderationSettings['noise_response'],
                'metadata' => [
                    'type' => 'noise_or_unnecessary',
                ],
            ];
        }

        return null;
    }

    /**
     * Determine if the message looks spammy.
     */
    protected function isSpamMessage(string $normalizedMessage, array $moderationSettings): bool
    {
        if ($normalizedMessage === '') {
            return true;
        }

        $maxRepeatedCharacterCount = (int) ($moderationSettings['max_repeated_character_count'] ?? 5);
        if (preg_match('/(.)\1{' . $maxRepeatedCharacterCount . ',}/u', $normalizedMessage) === 1) {
            return true;
        }

        $words = preg_split('/\s+/', $normalizedMessage, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCounts = array_count_values($words);
        $maxDuplicateWordCount = (int) ($moderationSettings['max_duplicate_word_count'] ?? 4);

        return !empty($wordCounts) && max($wordCounts) >= $maxDuplicateWordCount;
    }

    /**
     * Determine if the message is too noisy or unclear.
     */
    protected function isNoiseMessage(string $normalizedMessage, array $moderationSettings): bool
    {
        $minimumMeaningfulLength = (int) ($moderationSettings['minimum_meaningful_length'] ?? 2);
        $plainText = preg_replace('/[^a-z0-9\s]/u', '', $normalizedMessage) ?? '';

        if (strlen(trim($plainText)) < $minimumMeaningfulLength) {
            return true;
        }

        $noisePhrases = array_map('strtolower', $moderationSettings['noise_phrases'] ?? []);
        if (in_array($normalizedMessage, $noisePhrases, true)) {
            return true;
        }

        return preg_match('/^[^a-z0-9]+$/u', $normalizedMessage) === 1;
    }

    /**
     * Get moderation settings from the active config with defaults applied.
     *
     * @return array<string, mixed>
     */
    protected function getModerationSettings(): array
    {
        $defaultModerationSettings = ChatbotDefaultConfigSeeder::defaultSettings()['moderation'];
        $activeSettings = (array) ($this->activeConfig->settings ?? []);

        return array_replace_recursive(
            $defaultModerationSettings,
            (array) ($activeSettings['moderation'] ?? [])
        );
    }

    /**
     * Get the default quick replies to show when fallback or moderation is triggered.
     *
     * @return array<int, array<string, string|null>>
     */
    protected function defaultQuickReplies(): array
    {
        $labels = ChatbotDefaultConfigSeeder::defaultSettings()['quick_reply_defaults'];

        return [
            [
                'text' => $labels[0] ?? 'Booking Process',
                'action' => 'Booking Information',
                'action_type' => 'trigger_intent',
            ],
            [
                'text' => $labels[1] ?? 'Package Rates',
                'action' => 'Package Pricing',
                'action_type' => 'trigger_intent',
            ],
            [
                'text' => $labels[2] ?? 'Available Services',
                'action' => 'Service Information',
                'action_type' => 'trigger_intent',
            ],
            [
                'text' => $labels[3] ?? 'Talk to Our Team',
                'action' => 'Availability and Contact',
                'action_type' => 'trigger_intent',
            ],
        ];
    }

    /**
     * Append metadata to a saved chat message.
     *
     * @param array<string, mixed> $metadata
     */
    protected function appendMessageMetadata(ChatbotMessageModel $messageModel, array $metadata): void
    {
        $messageModel->update([
            'metadata' => array_replace_recursive((array) $messageModel->metadata, $metadata),
        ]);
    }

    /**
     * Save user message to database
     *
     * @param string $message
     * @return ChatbotMessageModel
     */
    protected function saveUserMessage(string $message)
    {
        $this->conversation->increment('message_count');

        return ChatbotMessageModel::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'user',
            'message' => $message,
            'metadata' => [
                'user_agent' => request()->userAgent(),
                'ip' => request()->ip(),
            ],
        ]);
    }

    /**
     * Save bot message to database
     *
     * @param string $message
     * @param int|null $intentId
     * @return ChatbotMessageModel
     */
    protected function saveBotMessage(string $message, $intentId = null)
    {
        $this->conversation->increment('message_count');

        return ChatbotMessageModel::create([
            'conversation_id' => $this->conversation->id,
            'sender_type' => 'bot',
            'message' => $message,
            'intent_id' => $intentId,
        ]);
    }

    /**
     * Update conversation metadata
     */
    protected function updateConversationMetadata()
    {
        $metadata = $this->conversation->metadata ?? [];
        $metadata['last_activity'] = now()->toIso8601String();
        $metadata['total_messages'] = $this->conversation->message_count;
        
        $this->conversation->update(['metadata' => $metadata]);
    }

    /**
     * End the current conversation
     *
     * @return bool
     */
    public function endConversation()
    {
        if ($this->conversation) {
            $this->conversation->end();
            return true;
        }
        
        return false;
    }

    /**
     * Get conversation history
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getConversationHistory()
    {
        if (!$this->conversation) {
            return collect();
        }

        return $this->conversation->messages()->chronological()->get();
    }

    /**
     * Mark last bot message as helpful
     *
     * @return bool
     */
    public function markLastBotMessageAsHelpful()
    {
        $lastBotMessage = $this->conversation->messages()
            ->where('sender_type', 'bot')
            ->latest()
            ->first();

        if ($lastBotMessage) {
            $lastBotMessage->markAsHelpful();
            return true;
        }

        return false;
    }

    /**
     * Mark last bot message as not helpful
     *
     * @return bool
     */
    public function markLastBotMessageAsNotHelpful()
    {
        $lastBotMessage = $this->conversation->messages()
            ->where('sender_type', 'bot')
            ->latest()
            ->first();

        if ($lastBotMessage) {
            $lastBotMessage->markAsNotHelpful();
            return true;
        }

        return false;
    }

    /**
     * Get active config
     *
     * @return ChatbotConfigModel|null
     */
    public function getActiveConfig()
    {
        return $this->activeConfig;
    }

    /**
     * Get current conversation
     *
     * @return ChatbotConversationModel|null
     */
    public function getConversation()
    {
        return $this->conversation;
    }

    /**
     * Get config settings for frontend
     *
     * @return array
     */
    public function getFrontendConfig()
    {
        if (!$this->activeConfig) {
            return [
                'bot_name' => 'Support Assistant',
                'welcome_message' => 'Hello! How can I assist you today?',
                'is_active' => false,
            ];
        }

        return [
            'bot_name' => $this->activeConfig->bot_name,
            'bot_avatar' => $this->activeConfig->bot_avatar,
            'welcome_message' => $this->activeConfig->welcome_message,
            'is_active' => $this->activeConfig->is_active,
        ];
    }
}
