<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chatbot\ChatbotMessageRequest;
use App\Models\Chatbot\ChatbotConversationModel;
use App\Services\ChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Cross-portal endpoints for the photography AI assistant.
 *
 * Clients, studio owners, and studio photographers all talk to the assistant
 * through these routes. Exception details never reach the response body -- they
 * are logged and replaced with fixed generic copy so no internal detail leaks.
 */
class ChatbotController extends Controller
{
    /**
     * Generic copy returned for any unexpected failure.
     */
    protected const GENERIC_ERROR = 'The assistant is temporarily unavailable. Please try again shortly.';

    public function __construct(protected ChatbotService $chatbotService) {}

    /**
     * Get display config for the assistant widget.
     */
    public function getConfig(Request $request)
    {
        $ownerId = $request->input('owner_id');

        if (! $ownerId) {
            return $this->badRequest('A studio must be specified.');
        }

        try {
            $config = $this->chatbotService->forOwner((int) $ownerId)->getFrontendConfig();

            return response()->json([
                'success' => true,
                'config' => $config,
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Unable to load assistant config.', $e);
        }
    }

    /**
     * Start a new conversation.
     */
    public function startChat(Request $request)
    {
        $ownerId = $request->input('owner_id');

        if (! $ownerId) {
            return $this->badRequest('A studio must be specified.');
        }

        try {
            $conversation = $this->chatbotService
                ->forOwner((int) $ownerId)
                ->startConversation(Auth::id());

            $config = $this->chatbotService->getActiveConfig();

            return response()->json([
                'success' => true,
                'session_id' => $conversation->session_id,
                'conversation_id' => $conversation->id,
                'welcome_message' => $config->welcome_message,
                'bot_name' => $config->bot_name,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Unable to start the assistant.', $e);
        }
    }

    /**
     * Send a message to the assistant.
     */
    public function sendMessage(ChatbotMessageRequest $request)
    {
        $sessionId = $request->input('session_id');

        try {
            if ($sessionId) {
                if (! $this->ownsSession($sessionId)) {
                    return $this->forbidden();
                }

                $this->chatbotService->continueConversation($sessionId);
            } else {
                $this->chatbotService
                    ->forOwner((int) $request->input('owner_id'))
                    ->startConversation(Auth::id());
            }

            return response()->json($this->chatbotService->processMessage($request->input('message')));
        } catch (\Throwable $e) {
            return $this->failure(self::GENERIC_ERROR, $e);
        }
    }

    /**
     * End the current conversation.
     */
    public function endChat(Request $request)
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return $this->badRequest('No conversation specified.');
        }

        if (! $this->ownsSession($sessionId)) {
            return $this->forbidden();
        }

        try {
            $ended = $this->chatbotService->continueConversation($sessionId)->endConversation();

            return response()->json([
                'success' => $ended,
                'message' => $ended ? 'Chat ended successfully.' : 'Unable to end the chat.',
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Unable to end the chat.', $e);
        }
    }

    /**
     * Get the transcript for a conversation the caller owns.
     */
    public function getHistory(Request $request)
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return $this->badRequest('No conversation specified.');
        }

        if (! $this->ownsSession($sessionId)) {
            return $this->forbidden();
        }

        try {
            $history = $this->chatbotService
                ->continueConversation($sessionId)
                ->getConversationHistory()
                ->map(fn ($message) => [
                    'sender_type' => $message->sender_type,
                    'message' => $message->message,
                    'created_at' => $message->created_at?->toIso8601String(),
                ]);

            return response()->json([
                'success' => true,
                'history' => $history,
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Unable to load the conversation.', $e);
        }
    }

    /**
     * Mark the last assistant message as helpful.
     */
    public function markHelpful(Request $request)
    {
        return $this->recordFeedback($request, true);
    }

    /**
     * Mark the last assistant message as not helpful.
     */
    public function markNotHelpful(Request $request)
    {
        return $this->recordFeedback($request, false);
    }

    /**
     * Shared feedback handler.
     */
    protected function recordFeedback(Request $request, bool $helpful)
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return $this->badRequest('No conversation specified.');
        }

        if (! $this->ownsSession($sessionId)) {
            return $this->forbidden();
        }

        try {
            $service = $this->chatbotService->continueConversation($sessionId);

            $marked = $helpful
                ? $service->markLastBotMessageAsHelpful()
                : $service->markLastBotMessageAsNotHelpful();

            return response()->json([
                'success' => $marked,
                'message' => $marked ? 'Thank you for your feedback!' : 'Unable to record feedback.',
            ]);
        } catch (\Throwable $e) {
            return $this->failure('Unable to record feedback.', $e);
        }
    }

    /**
     * A conversation transcript is only readable by the user who started it.
     */
    protected function ownsSession(string $sessionId): bool
    {
        return ChatbotConversationModel::where('session_id', $sessionId)
            ->where('user_id', Auth::id())
            ->exists();
    }

    protected function badRequest(string $message)
    {
        return response()->json(['success' => false, 'message' => $message], 400);
    }

    protected function forbidden()
    {
        return response()->json([
            'success' => false,
            'message' => 'This conversation is not available.',
        ], 403);
    }

    /**
     * Log the real cause, return fixed copy.
     */
    protected function failure(string $message, \Throwable $e)
    {
        Log::error('Assistant request failed.', [
            'exception' => $e::class,
            'reason' => $e->getMessage(),
        ]);

        return response()->json(['success' => false, 'message' => $message], 500);
    }
}
