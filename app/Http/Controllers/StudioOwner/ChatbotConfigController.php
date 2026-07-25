<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chatbot\StoreChatbotConfigRequest;
use App\Http\Requests\Chatbot\StoreChatbotIntentRequest;
use App\Models\Chatbot\ChatbotConfigModel;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotIntentModel;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConfig(Request $request)
    {
        try {
            $ownerId = Auth::id();

            $config = ChatbotConfigModel::byOwner($ownerId)
                ->with(['intents' => function ($query) {
                    $query->orderBy('priority', 'desc');
                }])
                ->first();

            if (! $config) {
                // Return default empty structure
                return response()->json([
                    'success' => true,
                    'config' => null,
                    'message' => 'No chatbot configuration found. Please create one.',
                ]);
            }

            return response()->json([
                'success' => true,
                'config' => $config,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching chatbot config: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load chatbot configuration: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save or update chatbot configuration
     *
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
                'config' => $config,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saving chatbot config: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save chatbot configuration: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle chatbot active status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        try {
            $ownerId = Auth::id();

            $config = ChatbotConfigModel::byOwner($ownerId)->first();

            if (! $config) {
                return response()->json([
                    'success' => false,
                    'message' => 'No chatbot configuration found.',
                ], 404);
            }

            $config->update([
                'is_active' => ! $config->is_active,
            ]);

            $status = $config->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Chatbot {$status} successfully.",
                'is_active' => $config->is_active,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling chatbot status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle chatbot status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all intents for the current config
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntents(Request $request)
    {
        try {
            $ownerId = Auth::id();

            $config = ChatbotConfigModel::byOwner($ownerId)->first();

            if (! $config) {
                return response()->json([
                    'success' => true,
                    'intents' => [],
                    'config' => null,
                    'message' => 'No chatbot configuration found. Please create one first.',
                ]);
            }

            $intents = ChatbotIntentModel::where('config_id', $config->id)
                ->orderBy('priority', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'intents' => $intents,
                'config' => [
                    'id' => $config->id,
                    'name' => $config->config_name ?? 'Default Config',
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching intents: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load intents: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new intent
     *
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
                'response_text' => $request->response_text,
                'priority' => $request->priority ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Intent created successfully.',
                'intent' => $intent,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating intent: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create intent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing intent
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIntent(StoreChatbotIntentRequest $request, $id)
    {
        try {
            DB::beginTransaction();

            $intent = $this->ownedIntent($id);

            // Update intent
            $intent->update([
                'intent_name' => $request->intent_name,
                'response_text' => $request->response_text,
                'priority' => $request->priority ?? 0,
                'is_active' => $request->boolean('is_active', true),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Intent updated successfully.',
                'intent' => $intent,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating intent: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update intent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete an intent
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIntent($id)
    {
        try {
            DB::beginTransaction();

            $intent = $this->ownedIntent($id);

            $intent->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Intent deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting intent: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete intent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle intent active status
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleIntentStatus(Request $request, $id)
    {
        try {
            $intent = $this->ownedIntent($id);

            $intent->update([
                'is_active' => ! $intent->is_active,
            ]);

            $status = $intent->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Intent {$status} successfully.",
                'is_active' => $intent->is_active,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error toggling intent status: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle intent status: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation history for the owner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations(Request $request)
    {
        try {
            $ownerId = Auth::id();

            $conversations = ChatbotConversationModel::byOwner($ownerId)
                ->with(['user:id,first_name,last_name,email', 'messages' => function ($query) {
                    $query->latest()->limit(5);
                }])
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching conversations: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversations: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get conversation details with all messages
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversationDetails($id)
    {
        try {
            $ownerId = Auth::id();

            $conversation = ChatbotConversationModel::byOwner($ownerId)
                ->with([
                    'user:id,first_name,last_name,email',
                    'messages' => function ($query) {
                        $query->with('intent')->chronological();
                    },
                ])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching conversation details: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve a knowledge entry that belongs to the authenticated owner.
     *
     * @param  int  $id
     * @return ChatbotIntentModel
     */
    protected function ownedIntent($id)
    {
        $ownerId = Auth::id();

        return ChatbotIntentModel::whereHas('config', function ($query) use ($ownerId) {
            $query->where('owner_id', $ownerId);
        })->findOrFail($id);
    }

    /**
     * Get a single intent by ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIntent($id)
    {
        try {
            $ownerId = Auth::id();

            // Verify the intent belongs to this owner's config
            $intent = ChatbotIntentModel::whereHas('config', function ($query) use ($ownerId) {
                $query->where('owner_id', $ownerId);
            })
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'intent' => $intent,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching intent: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load intent: '.$e->getMessage(),
            ], 500);
        }
    }
}
