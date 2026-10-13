@if(!\Illuminate\Support\Facades\Schema::hasTable('tbl_conversations'))
    <p role="status">Messaging is not enabled yet.</p>
@else
    @if($contacts->isNotEmpty())
    <form data-chat-open class="border-top py-3 mb-3">
        <label for="chat-recipient" class="form-label">Start a conversation with</label>
        <select id="chat-recipient" name="recipient_id" class="form-select mb-3" required>
            <option value="">Choose contact</option>
            @foreach($contacts as $contact)<option value="{{ $contact['id'] }}">{{ $contact['name'] }}</option>@endforeach
        </select>
        @if($booking)<input type="hidden" name="booking_id" value="{{ $booking->id }}">@endif
        <p role="status" tabindex="-1"></p>
        <button type="submit" class="btn btn-primary">Open conversation</button>
    </form>
    @endif
    <div class="chat-columns border-top pt-4">
        <section aria-labelledby="chat-inbox-heading">
            <h2 id="chat-inbox-heading" class="h5">Inbox</h2>
            <p data-chat-inbox-status role="status">Loading conversations...</p>
            <nav data-chat-inbox class="chat-inbox" aria-label="Conversations"></nav>
        </section>
        <section aria-labelledby="chat-thread-heading">
            <h2 id="chat-thread-heading" class="h5">{{ $conversation?->otherUser(auth()->id())?->full_name ?? 'Conversation' }}</h2>
            @if($conversation)
                <p data-chat-thread-status role="status" tabindex="-1">Loading messages...</p>
                <div data-chat-thread class="chat-thread" role="log" aria-live="polite" aria-label="Messages"></div>
                <form data-chat-compose class="border-top pt-3 mt-3">
                    <label for="chat-body" class="form-label">Message text</label>
                    <textarea id="chat-body" name="body" class="form-control mb-2" required maxlength="4000" aria-describedby="chat-send-status"></textarea>
                    <p id="chat-send-status" role="status" tabindex="-1"></p>
                    <button type="submit" class="btn btn-primary" disabled>Send message</button>
                </form>
            @else
                <p>Choose an inbox conversation, or open a booking's Message link to contact its participants.</p>
            @endif
        </section>
    </div>
@endif
