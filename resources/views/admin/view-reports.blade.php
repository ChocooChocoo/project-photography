@extends('layouts.admin.app')
@section('title', 'Booking Reports')
@section('styles') @include('partials.feedback-styles') @endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface py-4">
    <h1 class="h3">Booking reports</h1>
    <form method="get" class="mb-4">
        <label for="report-filter" class="form-label">Report status</label>
        <div class="d-flex flex-wrap gap-2"><select id="report-filter" name="status" class="form-select w-auto"><option value="">All statuses</option>@foreach(['open', 'reviewing', 'resolved', 'dismissed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select><button class="btn btn-primary">Filter reports</button></div>
    </form>
    @forelse($reports as $report)
        <article class="border-top py-3">
            <h2 class="h5"><a href="{{ route('admin.reports.show', $report->id) }}">Report #{{ $report->id }}: {{ $report->reason }}</a></h2>
            <p class="mb-0">{{ $report->reporter?->full_name }}. {{ $report->booking?->booking_reference }}. Target: {{ $report->target_type }} #{{ $report->target_id }}. Status: {{ $report->status }}.</p>
        </article>
    @empty<p>No reports match this filter.</p>@endforelse
    {{ $reports->links() }}
</main></div>
@endsection
