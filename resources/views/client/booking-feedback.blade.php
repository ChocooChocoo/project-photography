@extends('layouts.client.app')
@section('title', 'Booking Feedback')
@section('styles')
    @include('partials.feedback-styles')
@endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface py-4">
    <a href="{{ route('client.my-bookings.history') }}" class="d-inline-block mb-3">Back to booking history</a>
    <h1 class="h3">Feedback for {{ $booking->booking_reference }}</h1>
    <p>Your feedback covers this completed booking. Reports are reviewed separately by an administrator.</p>
    @forelse($targets as $target)
        <section class="border-top py-4">
            <h2 class="h5">{{ ucfirst($target['type']) }}: {{ $target['label'] }}</h2>
            @if($target['review'])
                <p>Submitted. Status: {{ $target['review']->status }}. @if($target['review']->rating) Rating: {{ $target['review']->rating }}/5. @endif</p>
                <p class="mb-0">{{ $target['review']->review_text }}</p>
            @else
                <form action="{{ route('client.feedback.store', [$booking->id, $target['type']]) }}" data-feedback-form>
                    <input type="hidden" name="target_id" value="{{ $target['id'] }}">
                    @php($prefix = $target['type'].'-'.$target['id'])
                    <label class="form-label" for="rating-{{ $prefix }}">Rating {{ $target['type'] === 'photo' ? '(optional with a comment)' : '' }}</label>
                    <select name="rating" id="rating-{{ $prefix }}" class="form-select mb-3" aria-describedby="feedback-{{ $prefix }}" @required($target['type'] !== 'photo')>
                        <option value="">Choose rating</option>
                        @foreach([1 => 'Poor', 2 => 'Below expectations', 3 => 'Satisfactory', 4 => 'Good', 5 => 'Excellent'] as $value => $label)<option value="{{ $value }}">{{ $value }}: {{ $label }}</option>@endforeach
                    </select>
                    <label class="form-label" for="review-{{ $prefix }}">Comment (optional with a rating)</label>
                    <textarea name="review_text" id="review-{{ $prefix }}" class="form-control mb-3" maxlength="2000" aria-describedby="feedback-{{ $prefix }}"></textarea>
                    <button type="submit" class="btn btn-primary">Submit feedback</button>
                    <p id="feedback-{{ $prefix }}" class="mt-2 mb-0" role="status"></p>
                </form>
            @endif
        </section>
    @empty
        <p>No package, assigned photographer or approved gallery is available for feedback.</p>
    @endforelse
    <section class="border-top py-4">
        <h2 class="h5">Reports for this booking</h2>
        @forelse($reports as $report)
            <article class="mb-3">
                <h3 class="h6">Report #{{ $report->id }}: {{ ucfirst($report->status) }}</h3>
                <p>{{ $report->reason }}. {{ $report->details }}</p>
                @if($report->resolution_note)<p>Administrator note: {{ $report->resolution_note }}</p>@endif
                @if($report->evidence)<a href="{{ route('client.reports.evidence', $report->id) }}">Download your evidence</a>@endif
            </article>
        @empty<p>No reports submitted.</p>@endforelse
        @if($booking->booking_type === 'studio')
        <details><summary class="py-2">Report a concern</summary>
            <p class="mt-3">Evidence is private to you and administrators. The studio owner receives status notifications.</p>
            <form action="{{ route('client.reports.store', $booking->id) }}" enctype="multipart/form-data" data-feedback-form>
                <label for="report-target" class="form-label">Who does this report concern?</label>
                <select id="report-target" class="form-select mb-3" required>
                    <option value="studio:{{ $booking->provider_id }}">Booked studio</option>
                    @foreach($targets->where('type', 'photographer') as $target)<option value="photographer:{{ $target['id'] }}">{{ $target['label'] }}</option>@endforeach
                </select>
                <input type="hidden" name="target_type" value="studio"><input type="hidden" name="target_id" value="{{ $booking->provider_id }}">
                <label for="report-reason" class="form-label">Reason</label><input id="report-reason" name="reason" class="form-control mb-3" maxlength="255" required aria-describedby="report-feedback">
                <label for="report-details" class="form-label">What happened?</label><textarea id="report-details" name="details" class="form-control mb-3" minlength="10" maxlength="5000" required aria-describedby="report-feedback"></textarea>
                <label for="report-evidence" class="form-label">Evidence (optional)</label><input id="report-evidence" name="evidence" type="file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.webp,.pdf" aria-describedby="evidence-help report-feedback">
                <p id="evidence-help">JPG, PNG, WebP or PDF. Maximum 5 MB.</p>
                <button type="submit" class="btn btn-primary">Submit report</button><p id="report-feedback" class="mt-2 mb-0" role="status"></p>
            </form>
        </details>
        @endif
    </section>
</main></div>
@endsection
@section('scripts')
    <script>document.getElementById('report-target')?.addEventListener('change', function () { const [type, id] = this.value.split(':'); this.form.elements.target_type.value = type; this.form.elements.target_id.value = id; });</script>
    @include('partials.feedback-form-script')
@endsection
