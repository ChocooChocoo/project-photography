@extends('layouts.'.auth()->user()->role.'.app')
@section('title', 'Messages')
@section('styles')
    @include('partials.feedback-styles')
    <style>
        .chat-columns { display: grid; grid-template-columns: minmax(200px, 280px) minmax(0, 1fr); gap: 2rem; }
        .chat-inbox { max-height: 60vh; overflow-y: auto; }
        .chat-thread { max-height: 55vh; overflow-y: auto; overflow-wrap: anywhere; }
        .chat-message-body { white-space: pre-wrap; }
        .chat-surface :is(input, select, button, .chat-inbox a) { min-height: 44px; }
        .chat-surface textarea { min-height: 100px; }
        @media (max-width: 767px) { .chat-columns { grid-template-columns: minmax(0, 1fr); gap: 1.5rem; } .chat-inbox { max-height: 220px; } }
    </style>
@endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface chat-surface py-4"
    data-user-chat data-base="{{ route(auth()->user()->role.'.messages.index', [], false) }}"
    data-conversation="{{ $conversation?->id }}" data-user="{{ auth()->id() }}" data-peer="{{ $conversation?->otherUser(auth()->id())?->full_name }}">
    <h1 class="h3">Messages</h1>
    <p>Private, text-only conversations with your booking contacts or studio colleagues. Messages refresh every five seconds while this tab is visible.</p>
    @if($booking)<p>Booking: <strong>{{ $booking->booking_reference }}</strong></p>@endif
    @include('chat.inbox-thread')
</main></div>
@endsection
