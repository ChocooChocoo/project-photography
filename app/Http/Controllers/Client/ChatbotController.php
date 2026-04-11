<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\ChatbotMessageRequest;
use App\Services\ChatbotService;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotMessageModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    /**
     * @var ChatbotService
     */
    protected $chatbotService;

    /**
     * Constructor
     *
     * @param ChatbotService $chatbotService
     */
    public function __construct(ChatbotService $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    /**
     * Get chatbot config for frontend
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig(Request $request)
    {
        try {
            $ownerId = $request->input('owner_id');
            
            if (!$ownerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner ID is required.'
                ], 400);
            }

            $config = $this->chatbotService->forOwner($ownerId)->getFrontendConfig();

            return response()->json([
                'success' => true,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching chatbot config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load chatbot configuration.'
            ], 500);
        }
    }

    /**
     * Start a new chat conversation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startChat(Request $request)
    {
        try {
            $ownerId = $request->input('owner_id');
            $userId = Auth::id(); // May be null if not logged in
            
            if (!$ownerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Owner ID is required.'
                ], 400);
            }

            // Initialize chatbot for this owner and start conversation
            $conversation = $this->chatbotService
                ->forOwner($ownerId)
                ->startConversation($userId);

            // Get the welcome message
            $welcomeMessage = $this->chatbotService->getActiveConfig()->welcome_message;

            return response()->json([
                'success' => true,
                'session_id' => $conversation->session_id,
                'conversation_id' => $conversation->id,
                'welcome_message' => $welcomeMessage,
                'bot_name' => $this->chatbotService->getActiveConfig()->bot_name,
                'timestamp' => now()->toIso8601String()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error starting chat: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to start chat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send a message to the chatbot
     *
     * @param ChatbotMessageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(ChatbotMessageRequest $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $ownerId = $request->input('owner_id');
            $message = $request->input('message');

            // Initialize chatbot
            if ($sessionId) {
                // Continue existing conversation
                $this->chatbotService->continueConversation($sessionId);
            } else {
                // Start new conversation
                $this->chatbotService->forOwner($ownerId)->startConversation(Auth::id());
            }

            // Process the message
            $response = $this->chatbotService->processMessage($message);

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error processing message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * End the current chat conversation
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function endChat(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required.'
                ], 400);
            }

            $ended = $this->chatbotService
                ->continueConversation($sessionId)
                ->endConversation();

            return response()->json([
                'success' => $ended,
                'message' => $ended ? 'Chat ended successfully.' : 'Failed to end chat.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error ending chat: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to end chat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation history for the current session
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHistory(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required.'
                ], 400);
            }

            $history = $this->chatbotService
                ->continueConversation($sessionId)
                ->getConversationHistory();

            return response()->json([
                'success' => true,
                'history' => $history
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching history: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation history.'
            ], 500);
        }
    }

    /**
     * Mark last bot message as helpful
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markHelpful(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required.'
                ], 400);
            }

            $marked = $this->chatbotService
                ->continueConversation($sessionId)
                ->markLastBotMessageAsHelpful();

            return response()->json([
                'success' => $marked,
                'message' => $marked ? 'Thank you for your feedback!' : 'Failed to mark message.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error marking message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record feedback.'
            ], 500);
        }
    }

    /**
     * Mark last bot message as not helpful
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markNotHelpful(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            
            if (!$sessionId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session ID is required.'
                ], 400);
            }

            $marked = $this->chatbotService
                ->continueConversation($sessionId)
                ->markLastBotMessageAsNotHelpful();

            return response()->json([
                'success' => $marked,
                'message' => $marked ? 'Thank you for your feedback! We\'ll improve.' : 'Failed to mark message.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error marking message: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record feedback.'
            ], 500);
        }
    }
}
