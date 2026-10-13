<?php

namespace App\Http\Controllers;

use App\Models\BookingModel;
use App\Models\ConversationModel;
use App\Models\UserModel;
use App\Services\ChatAccess;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChatController extends Controller
{
    use Notifiable;

    public function index(Request $request, ChatAccess $access)
    {
        $booking = $request->query('booking') ? BookingModel::findOrFail($request->query('booking')) : null;
        $contacts = $access->contacts($request->user(), $booking);
        $conversation = null;

        return view('chat.index', compact('booking', 'contacts', 'conversation'));
    }

    public function show(Request $request, ConversationModel $conversation, ChatAccess $access)
    {
        abort_unless($access->canRead($request->user(), $conversation), 403);
        $booking = $conversation->booking;
        $contacts = collect();

        return view('chat.index', compact('booking', 'contacts', 'conversation'));
    }

    public function inbox(Request $request, ChatAccess $access)
    {
        return response()->json(['conversations' => $this->visible($request, $access)->map(function ($conversation) use ($request, $access) {
            return ['id' => $conversation->id, 'name' => $conversation->otherUser($request->user()->id)?->full_name ?? 'Unavailable user',
                'booking_reference' => $conversation->booking?->booking_reference, 'last_message_at' => $conversation->last_message_at,
                'unread' => $conversation->messages()->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->count(),
                'can_send' => $access->canSend($request->user(), $conversation)];
        })->values()]);
    }

    public function open(Request $request, ChatAccess $access)
    {
        $data = $request->validate(['recipient_id' => 'required|integer|exists:tbl_users,id', 'booking_id' => 'nullable|integer|exists:tbl_bookings,id']);
        $other = UserModel::findOrFail($data['recipient_id']);
        $booking = empty($data['booking_id']) ? null : BookingModel::findOrFail($data['booking_id']);
        abort_unless($access->canTalk($request->user(), $other, $booking), 403);
        $ids = [$request->user()->id, $other->id];
        sort($ids);
        $conversation = ConversationModel::firstOrCreate(['user_one_id' => $ids[0], 'user_two_id' => $ids[1], 'booking_scope' => $booking?->id ?? 0], ['booking_id' => $booking?->id]);

        return response()->json(['conversation' => ['id' => $conversation->id], 'url' => route($request->user()->role.'.messages.show', $conversation->id, false)], $conversation->wasRecentlyCreated ? 201 : 200);
    }

    public function messages(Request $request, ConversationModel $conversation, ChatAccess $access)
    {
        abort_unless($access->canRead($request->user(), $conversation), 403);
        $request->validate(['after' => 'nullable|integer|min:0']);
        $messages = $conversation->messages()->where('id', '>', (int) $request->query('after', 0))->orderBy('id')->limit(100)->get(['id', 'sender_id', 'body', 'read_at', 'created_at']);

        return response()->json(['messages' => $messages, 'has_more' => $messages->count() === 100,
            'can_send' => $access->canSend($request->user(), $conversation),
            'read_through' => (int) $conversation->messages()->where('sender_id', $request->user()->id)->whereNotNull('read_at')->max('id')]);
    }

    public function send(Request $request, ConversationModel $conversation, ChatAccess $access)
    {
        abort_unless($access->canSend($request->user(), $conversation), 403);
        $data = $request->validate(['body' => 'required|string|max:4000']);
        abort_if(trim($data['body']) === '', 422, 'Enter a message.');
        $message = DB::transaction(function () use ($request, $conversation, $access, $data) {
            $conversation = ConversationModel::lockForUpdate()->findOrFail($conversation->id);
            abort_unless($access->canSend($request->user(), $conversation), 403);
            $message = $conversation->messages()->create(['sender_id' => $request->user()->id, 'body' => trim($data['body'])]);
            $conversation->last_message_at = now();
            if (! $conversation->last_notified_at || $conversation->last_notified_at->lte(now()->subMinute())) {
                $other = $conversation->otherUser($request->user()->id);
                $this->notifyNewMessage($message, $other);
                $conversation->last_notified_at = now();
            }
            $conversation->save();

            return $message;
        });

        return response()->json(['message' => $message->only(['id', 'sender_id', 'body', 'created_at', 'read_at'])], 201);
    }

    public function read(Request $request, ConversationModel $conversation, ChatAccess $access)
    {
        abort_unless($access->canRead($request->user(), $conversation), 403);
        $data = $request->validate(['through' => 'required|integer|min:0']);
        $conversation->messages()->where('sender_id', '!=', $request->user()->id)->where('id', '<=', $data['through'])->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function unread(Request $request, ChatAccess $access)
    {
        if (! Schema::hasTable('tbl_messages')) {
            return response()->json(['count' => 0]);
        }
        $ids = $this->visible($request, $access)->pluck('id');

        return response()->json(['count' => \App\Models\MessageModel::whereIn('conversation_id', $ids)->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->count()]);
    }

    private function visible(Request $request, ChatAccess $access)
    {
        if (! Schema::hasTable('tbl_conversations')) {
            return collect();
        }

        return ConversationModel::forUser($request->user()->id)->with('booking', 'userOne', 'userTwo')->orderByDesc('last_message_at')->get()
            ->filter(fn ($conversation) => $access->canRead($request->user(), $conversation));
    }
}
