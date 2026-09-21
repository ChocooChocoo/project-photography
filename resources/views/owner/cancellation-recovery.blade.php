@extends('layouts.owner.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Photographer cancellation recovery</h1>
    <p class="text-muted">Booking {{ $recovery->booking->booking_reference }} · deadline {{ $recovery->deadline->format('M d, Y h:i A') }}</p>
    <p>Status: <strong>{{ str_replace('_', ' ', $recovery->status) }}</strong></p>
    @if ($recovery->photographer_reason)
        <p><strong>Photographer reason (owner only):</strong> {{ $recovery->photographer_reason }}</p>
    @endif

    @if ($canSetRefundTarget)
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6">Set the refund amount</h2>
                <p class="text-muted small mb-2">
                    The client paid in full and cancelled. The down payment is not refundable, but you decide
                    how much to return. Enter an amount from 0.00 to {{ number_format($paidTotal, 2) }}.
                </p>

                @if (session('error'))
                    <div class="alert alert-danger py-2">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="alert alert-success py-2">{{ session('success') }}</div>
                @endif

                <form method="post" action="{{ route('owner.booking.recovery.escalate', $recovery->id) }}">
                    @csrf
                    <label for="refund_amount" class="form-label">Refund amount (PHP)</label>
                    <input type="number" step="0.01" min="0" max="{{ $paidTotal }}" id="refund_amount" name="refund_amount"
                        value="{{ old('refund_amount', $recovery->refund_amount) }}" class="form-control mb-2" required>
                    @error('refund_amount')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <button class="btn btn-primary">Save refund target</button>
                </form>
            </div>
        </div>
    @endif

    @if ($recovery->status === 'awaiting_replacement')
        <form method="post" action="{{ route('owner.booking.recovery.replacement', $recovery->id) }}" class="mb-3">
            @csrf
            <label for="photographer_id" class="form-label">Available same-studio replacement</label>
            <select id="photographer_id" name="photographer_id" required class="form-select mb-2">
                <option value="">Choose a photographer</option>
                @foreach ($replacementMembers->merge($replacementCandidates)->unique('photographer_id') as $photographer)
                    <option value="{{ $photographer->photographer_id }}">{{ $photographer->photographer?->full_name ?? 'Photographer' }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary">Propose replacement</button>
        </form>
    @endif

    @if (in_array($recovery->status, ['awaiting_replacement', 'replacement_proposed', 'awaiting_client'], true))
        <form method="post" action="{{ route('owner.booking.recovery.escalate', $recovery->id) }}">
            @csrf
            <label for="reason" class="form-label">Escalation note</label>
            <textarea id="reason" name="reason" class="form-control mb-2" maxlength="2000"></textarea>
            <button class="btn btn-outline-danger">Escalate to refund queue</button>
        </form>
    @endif
</div>
@endsection
