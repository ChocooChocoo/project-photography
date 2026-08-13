@extends('layouts.client.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Photographer replacement</h1>
    <p>A replacement photographer is available for booking {{ $recovery->booking->booking_reference }}.</p>
    <p class="text-muted">Please respond by {{ $recovery->deadline->format('M d, Y h:i A') }}.</p>
    @if ($recovery->status === 'awaiting_client')
        <form method="post" action="{{ route('client.booking.recovery.response', $recovery->id) }}" class="d-inline">
            @csrf
            <input type="hidden" name="accepted" value="1">
            <button class="btn btn-primary">Accept replacement</button>
        </form>
        <form method="post" action="{{ route('client.booking.recovery.response', $recovery->id) }}" class="d-inline">
            @csrf
            <input type="hidden" name="accepted" value="0">
            <button class="btn btn-outline-danger">Decline and request full refund</button>
        </form>
    @else
        <p>Status: <strong>{{ str_replace('_', ' ', $recovery->status) }}</strong></p>
    @endif
</div>
@endsection
