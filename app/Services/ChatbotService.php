<?php

namespace App\Services;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotIntentModel;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotMessageModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        // Get active config for this owner
        $this->activeConfig = ChatbotConfigModel::byOwner($ownerId)
            ->active()
            ->with(['activeIntents' => function($query) {
                $query->with('activeQuickReplies')->byPriority();
            }])
            ->first();

        if (!$this->activeConfig) {
            // Create default config if none exists
            $this->activeConfig = ChatbotConfigModel::create([
                'owner_id' => $ownerId,
                'config_name' => 'Default Configuration',
                'welcome_message' => 'Hello! How can I assist you today?',
                'fallback_message' => 'I apologize, but I don\'t understand. Please contact our support team for assistance.',
                'is_active' => true,
                'bot_name' => 'Support Assistant',
            ]);
        }

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

        // Save user message
        $userMessage = $this->saveUserMessage($message);

        // Find matching intent
        $matchedIntent = $this->findMatchingIntent($message);

        $response = null;
        $quickReplies = [];

        if ($matchedIntent) {
            // Increment match count
            $matchedIntent->incrementMatchCount();

            // Get response based on intent
            $response = $matchedIntent->response_text;
            
            // Get quick replies if any
            if ($matchedIntent->response_type === 'quick_reply') {
                $quickReplies = $matchedIntent->activeQuickReplies->map(function($reply) {
                    return [
                        'text' => $reply->reply_text,
                        'action' => $reply->action_value,
                        'action_type' => $reply->action_type,
                    ];
                })->toArray();
            }

            // Save bot message
            $botMessage = $this->saveBotMessage($response, $matchedIntent->id);

            // Prepare metadata
            $metadata = [
                'intent_id' => $matchedIntent->id,
                'intent_name' => $matchedIntent->intent_name,
            ];

            if (!empty($quickReplies)) {
                $metadata['quick_replies'] = $quickReplies;
            }
        } else {
            // Use fallback message
            $response = $this->activeConfig->fallback_message;
            $botMessage = $this->saveBotMessage($response);
            $metadata = ['is_fallback' => true];
        }

        // Update conversation metadata
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
        if (!$this->activeConfig || !$this->activeConfig->intents) {
            return null;
        }

        $message = strtolower(trim($message));
        $bestMatch = null;
        $highestScore = 0;

        foreach ($this->activeConfig->intents as $intent) {
            $keywords = $intent->trigger_keywords ?? [];
            
            foreach ($keywords as $keyword) {
                $keyword = strtolower(trim($keyword));
                
                // Check for exact match or contains
                if (strpos($message, $keyword) !== false) {
                    // Calculate score based on keyword length and position
                    $score = strlen($keyword) * 10;
                    
                    // Boost score if keyword is at start of message
                    if (strpos($message, $keyword) === 0) {
                        $score *= 2;
                    }
                    
                    // Add priority bonus
                    $score += $intent->priority * 5;
                    
                    if ($score > $highestScore) {
                        $highestScore = $score;
                        $bestMatch = $intent;
                    }
                }
            }
        }

        return $bestMatch;
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