@extends('layouts.owner.app')
@section('title', 'Client Feedback')
@section('styles') @include('partials.feedback-styles') @endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface py-4">
    <h1 class="h3">Client feedback</h1>
    <p>Studio, package, photographer and photo feedback from your bookings. Flagged or removed feedback does not count toward ratings.</p>
    @forelse($reviews as $item)
        @php($review = $item['review'])
        <article class="border-top py-3">
            <h2 class="h5">{{ ucfirst($item['type']) }} feedback: {{ $review->booking->booking_reference }}</h2>
            <p>{{ $review->booking->client?->full_name }}. Status: {{ $review->status }}. @if($review->rating) Rating: {{ $review->rating }}/5. @endif</p>
            @if($item['type'] === 'service')<p>Package: {{ $review->booking->packages->firstWhere('package_id', $review->package_id)?->package_name ?? 'Booked package' }}</p>@endif
            @if($item['type'] === 'photographer')<p>Photographer: {{ $review->booking->assignedPhotographers->firstWhere('photographer_id', $review->photographer_id)?->photographer?->full_name ?? 'Assigned photographer' }}</p>@endif
            <p class="mb-0">{{ $review->review_text }}</p>
        </article>
    @empty<p>No client feedback yet.</p>@endforelse
</main></div>
@endsection
