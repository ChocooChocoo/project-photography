<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Models\Chatbot\ChatbotMessageModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotConversationController extends Controller
{
    /**
     * Display a listing of conversations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $conversations = ChatbotConversationModel::byOwner($ownerId)
                ->with(['user:id,first_name,last_name,email'])
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
     * Display the specified conversation with all messages.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
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

    /**
     * Export conversation transcript.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function export($id)
    {
        try {
            $ownerId = Auth::id();
            
            $conversation = ChatbotConversationModel::byOwner($ownerId)
                ->with(['user', 'messages' => function($query) {
                    $query->with('intent')->chronological();
                }])
                ->findOrFail($id);

            // Format transcript
            $transcript = $this->formatTranscript($conversation);

            return response()->json([
                'success' => true,
                'transcript' => $transcript
            ]);

        } catch (\Exception $e) {
            \Log::error('Error exporting conversation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to export conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format conversation as readable transcript.
     *
     * @param ChatbotConversationModel $conversation
     * @return array
     */
    protected function formatTranscript($conversation)
    {
        $userName = $conversation->user 
            ? $conversation->user->first_name . ' ' . $conversation->user->last_name
            : 'Guest User';
            
        $transcript = [
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'user' => $userName,
            'started_at' => $conversation->started_at ? $conversation->started_at->format('Y-m-d H:i:s') : null,
            'ended_at' => $conversation->ended_at ? $conversation->ended_at->format('Y-m-d H:i:s') : 'Ongoing',
            'total_messages' => $conversation->message_count,
            'messages' => []
        ];

        foreach ($conversation->messages as $message) {
            $transcript['messages'][] = [
                'time' => $message->created_at ? $message->created_at->format('H:i:s') : null,
                'sender' => $message->sender_type === 'user' ? 'User' : 'Bot',
                'message' => $message->message,
                'intent' => $message->intent ? $message->intent->intent_name : null,
                'helpful' => $message->was_helpful
            ];
        }

        return $transcript;
    }

    /**
     * Delete a conversation.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $ownerId = Auth::id();
            
            $conversation = ChatbotConversationModel::byOwner($ownerId)->findOrFail($id);
            
            // Messages will be cascade deleted
            $conversation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting conversation: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete conversations.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDestroy(Request $request)
    {
        try {
            $ownerId = Auth::id();
            $ids = $request->input('ids', []);
            
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No conversations selected.'
                ], 400);
            }

            ChatbotConversationModel::byOwner($ownerId)
                ->whereIn('id', $ids)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => count($ids) . ' conversation(s) deleted successfully.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error bulk deleting conversations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get conversation statistics.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        try {
            $ownerId = Auth::id();
            
            $totalConversations = ChatbotConversationModel::byOwner($ownerId)->count();
            $activeConversations = ChatbotConversationModel::byOwner($ownerId)
                ->where('status', 'active')
                ->count();
            
            $totalMessages = ChatbotMessageModel::whereHas('conversation', function($query) use ($ownerId) {
                $query->byOwner($ownerId);
            })->count();

            $helpfulCount = ChatbotMessageModel::whereHas('conversation', function($query) use ($ownerId) {
                $query->byOwner($ownerId);
            })->where('was_helpful', true)->count();

            $notHelpfulCount = ChatbotMessageModel::whereHas('conversation', function($query) use ($ownerId) {
                $query->byOwner($ownerId);
            })->where('was_helpful', false)->count();

            return response()->json([
                'success' => true,
                'stats' => [
                    'total_conversations' => $totalConversations,
                    'active_conversations' => $activeConversations,
                    'total_messages' => $totalMessages,
                    'helpful_responses' => $helpfulCount,
                    'not_helpful_responses' => $notHelpfulCount,
                    'satisfaction_rate' => $totalMessages > 0 
                        ? round(($helpfulCount / ($helpfulCount + $notHelpfulCount)) * 100, 2)
                        : 0
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching conversation stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}