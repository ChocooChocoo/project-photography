@extends('layouts.freelancer.app')
@section('title', 'Studio Assignments')
@section('styles')
    @include('partials.feedback-styles')
    <style>.studio-assignments :is(input, select, .btn) { min-height: 44px; }</style>
@endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface studio-assignments py-4">
    <h1 class="h3">Studio assignments</h1>
    <p>Temporary access covers only your assigned studio booking. Upload photos before event end; the owner reviews and publishes the gallery.</p>
    @forelse($assignments as $assignment)
        @php
            $booking = $assignment->booking;
            $nextStatuses = match($assignment->status) {
                'assigned' => ['confirmed' => 'Accept assignment', 'cancelled' => 'Decline assignment'],
                'confirmed' => ['on_site' => 'Confirm arrival', 'cancelled' => 'Cancel assignment'],
                'on_site' => ['in_progress' => 'Start work'],
                'in_progress' => ['completed' => 'Complete work'],
                default => [],
            };
        @endphp
        <section class="border-top py-4">
            <h2 class="h5">{{ $booking->booking_reference }}: {{ $booking->event_name }}</h2>
            <p>{{ $assignment->studio->studio_name }}. Status: {{ str_replace('_', ' ', $assignment->status) }}.</p>
            <p><strong>Access ends {{ app(\App\Services\FreelancerBookingAccess::class)->eventEnd($booking)->format('M d, Y H:i') }} Asia/Manila.</strong></p>
            @if($nextStatuses)
            <form action="{{ route('freelancer.studio-assignments.status', $assignment->id) }}" method="post" enctype="multipart/form-data" data-work-form class="mb-4">
                @csrf
                <div role="status" class="mb-2" tabindex="-1"></div>
                <label class="form-label" for="status-{{ $assignment->id }}">Next action</label>
                <select class="form-select mb-3" id="status-{{ $assignment->id }}" name="status" required>
                    <option value="">Choose action</option>
                    @foreach($nextStatuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                @if($assignment->status === 'confirmed')
                <label class="form-label" for="proof-{{ $assignment->id }}">Arrival photo (required to confirm arrival)</label>
                <input class="form-control mb-3" type="file" id="proof-{{ $assignment->id }}" name="on_site_proof_image" accept="image/jpeg,image/png,image/webp">
                @endif
                @if(in_array($assignment->status, ['assigned', 'confirmed']))
                <label class="form-label" for="reason-{{ $assignment->id }}">Reason (required to decline or cancel)</label>
                <textarea class="form-control mb-3" id="reason-{{ $assignment->id }}" name="cancellation_reason" maxlength="500"></textarea>
                @endif
                <button class="btn btn-primary" type="submit">Update assignment</button>
            </form>
            @endif
            @if(in_array($assignment->status, ['on_site', 'in_progress']))
            <form action="{{ route('freelancer.studio-assignments.images', $assignment->id) }}" method="post" enctype="multipart/form-data" data-work-form>
                @csrf
                <h3 class="h6">Gallery upload</h3>
                <div role="status" class="mb-2" tabindex="-1"></div>
                <label class="form-label" for="images-{{ $assignment->id }}">Photos (JPG, PNG or GIF, up to 50 files, 5 MB each)</label>
                <input class="form-control mb-3" type="file" id="images-{{ $assignment->id }}" name="images[]" accept="image/jpeg,image/png,image/gif" multiple required>
                <button class="btn btn-primary" type="submit">Upload gallery photos</button>
            </form>
            @endif
        </section>
    @empty
        <p class="border-top py-4">No active studio assignments. Expired or completed assignments no longer grant studio access. Your own freelancer bookings are unchanged.</p>
    @endforelse
</main></div>
@endsection
@section('scripts')
@include('partials.work-form-script')
@endsection
