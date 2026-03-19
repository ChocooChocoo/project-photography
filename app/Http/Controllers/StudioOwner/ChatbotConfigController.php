<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\StoreChatbotConfigRequest;
use App\Http\Requests\Chatbot\StoreChatbotIntentRequest;
use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotIntentModel;
use App\Models\Chatbot\ChatbotQuickReplyModel;
use App\Models\Chatbot\ChatbotConversationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatbotConfigController extends Controller
{
    /**
     * Display chatbot configuration page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('owner.chatbot-config');
    }

    /**
     * Get chatbot config data for the authenticated owner
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $config = ChatbotConfigModel::byOwner($ownerId)
                ->with(['intents' => function($query) {
                    $query->with('quickReplies')->orderBy('priority', 'desc');
                }])
                ->first();

            if (!$config) {
                // Return default empty structure
                return response()->json([
                    'success' => true,
                    'config' => null,
                    'message' => 'No chatbot configuration found. Please create one.'
                ]);
            }

            return response()->json([
                'success' => true,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching chatbot config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load chatbot configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save or update chatbot configuration
     *
     * @param StoreChatbotConfigRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveConfig(StoreChatbotConfigRequest $request)
    {
        try {
            DB::beginTransaction();

            $ownerId = Auth::id();
            
            // Find existing or create new
            $config = ChatbotConfigModel::byOwner($ownerId)->first();
            
            if ($config) {
                $config->update($request->validated());
                $message = 'Chatbot configuration updated successfully.';
            } else {
                $config = ChatbotConfigModel::create(array_merge(
                    $request->validated(),
                    ['owner_id' => $ownerId]
                ));
                $message = 'Chatbot configuration created successfully.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving chatbot config: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save chatbot configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle chatbot active status
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $config = ChatbotConfigModel::byOwner($ownerId)->first();
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No chatbot configuration found.'
                ], 404);
            }

            $config->update([
                'is_active' => !$config->is_active
            ]);

            $status = $config->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Chatbot {$status} successfully.",
                'is_active' => $config->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling chatbot status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle chatbot status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all intents for the current config
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntents(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $config = ChatbotConfigModel::byOwner($ownerId)->first();
            
            if (!$config) {
                return response()->json([
                    'success' => true,
                    'intents' => [],
                    'config' => null,
                    'message' => 'No chatbot configuration found. Please create one first.'
                ]);
            }

            $intents = ChatbotIntentModel::where('config_id', $config->id)
                ->with('quickReplies')
                ->orderBy('priority', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'intents' => $intents,
                'config' => [
                    'id' => $config->id,
                    'name' => $config->config_name ?? 'Default Config'
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching intents: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load intents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new intent
     *
     * @param StoreChatbotIntentRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeIntent(StoreChatbotIntentRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create intent
            $intent = ChatbotIntentModel::create([
                'config_id' => $request->config_id,
                'intent_name' => $request->intent_name,
                'trigger_keywords' => $request->trigger_keywords,
                'response_text' => $request->response_text,
                'response_type' => $request->response_type,
                'image_url' => $request->image_url,
                'priority' => $request->priority ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Create quick replies if provided
            if ($request->has('quick_replies') && is_array($request->quick_replies)) {
                foreach ($request->quick_replies as $index => $replyData) {
                    ChatbotQuickReplyModel::create([
                        'intent_id' => $intent->id,
                        'reply_text' => $replyData['reply_text'],
                        'action_value' => $replyData['action_value'] ?? null,
                        'action_type' => $replyData['action_type'] ?? 'trigger_intent',
                        'position' => $replyData['position'] ?? $index,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            // Load the relationships for response
            $intent->load('quickReplies');

            return response()->json([
                'success' => true,
                'message' => 'Intent created successfully.',
                'intent' => $intent
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating intent: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create intent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing intent
     *
     * @param StoreChatbotIntentRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIntent(StoreChatbotIntentRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $intent = ChatbotIntentModel::findOrFail($id);
            
            // Update intent
            $intent->update([
                'intent_name' => $request->intent_name,
                'trigger_keywords' => $request->trigger_keywords,
                'response_text' => $request->response_text,
                'response_type' => $request->response_type,
                'image_url' => $request->image_url,
                'priority' => $request->priority ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            // Handle quick replies - delete existing and recreate
            if ($request->has('quick_replies')) {
                // Delete existing quick replies
                $intent->quickReplies()->delete();
                
                // Create new quick replies
                foreach ($request->quick_replies as $index => $replyData) {
                    ChatbotQuickReplyModel::create([
                        'intent_id' => $intent->id,
                        'reply_text' => $replyData['reply_text'],
                        'action_value' => $replyData['action_value'] ?? null,
                        'action_type' => $replyData['action_type'] ?? 'trigger_intent',
                        'position' => $replyData['position'] ?? $index,
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            // Load the relationships for response
            $intent->load('quickReplies');

            return response()->json([
                'success' => true,
                'message' => 'Intent updated successfully.',
                'intent' => $intent
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating intent: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update intent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an intent
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIntent($id)
    {
        try {
            DB::beginTransaction();

            $intent = ChatbotIntentModel::findOrFail($id);
            
            // Delete quick replies first (cascade should handle, but explicit)
            $intent->quickReplies()->delete();
            
            // Delete intent
            $intent->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Intent deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting intent: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete intent: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle intent active status
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleIntentStatus(Request $request, $id)
    {
        try {
            $intent = ChatbotIntentModel::findOrFail($id);
            
            $intent->update([
                'is_active' => !$intent->is_active
            ]);

            $status = $intent->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Intent {$status} successfully.",
                'is_active' => $intent->is_active
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling intent status: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle intent status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation history for the owner
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $conversations = ChatbotConversationModel::byOwner($ownerId)
                ->with(['user:id,first_name,last_name,email', 'messages' => function($query) {
                    $query->latest()->limit(5);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'conversations' => $conversations
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching conversations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation details with all messages
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversationDetails($id)
    {
        try {
            $ownerId = Auth::id();
            
            $conversation = ChatbotConversationModel::byOwner($ownerId)
                ->with([
                    'user:id,first_name,last_name,email',
                    'messages' => function($query) {
                        $query->with('intent')->chronological();
                    }
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'conversation' => $conversation
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching conversation details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation details: ' . $e->getMessage()
            ], 500);
        }
    }
}