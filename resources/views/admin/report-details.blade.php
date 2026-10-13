@extends('layouts.admin.app')
@section('title', 'Report Details')
@section('styles') @include('partials.feedback-styles') @endsection
@section('content')
<div class="content-page"><main class="container-fluid feedback-surface py-4">
    <a href="{{ route('admin.reports.index') }}" class="d-inline-block mb-3">Back to reports</a>
    <h1 class="h3">Report #{{ $report->id }}: {{ $report->reason }}</h1>
    <p>Reporter: {{ $report->reporter?->full_name }}. Booking: {{ $report->booking?->booking_reference }}.</p>
    <p>Target: {{ $report->target_type }} #{{ $report->target_id }}. Studio: {{ $report->booking?->studio?->studio_name }}.</p>
    <p>Status: {{ $report->status }}.</p><p>{{ $report->details }}</p>
    @if($report->evidence)<p><a href="{{ route('admin.reports.evidence', $report->id) }}">Download evidence</a></p>@endif
    @if(in_array($report->status, ['open', 'reviewing'], true))
    <form action="{{ route('admin.reports.update', $report->id) }}" class="border-top pt-4" data-feedback-form>
        <label for="report-status" class="form-label">New status</label><select name="status" id="report-status" class="form-select mb-3" aria-describedby="status-feedback" required>@foreach(['reviewing', 'resolved', 'dismissed'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select>
        <label for="resolution-note" class="form-label">Resolution note (required to resolve or dismiss)</label><textarea id="resolution-note" name="resolution_note" class="form-control mb-3" maxlength="5000" aria-describedby="status-feedback">{{ $report->resolution_note }}</textarea>
        <button type="submit" class="btn btn-primary">Update report</button><p id="status-feedback" class="mt-2" role="status"></p>
    </form>
    @else<p>Administrator note: {{ $report->resolution_note }}</p>@endif
</main></div>
@endsection
@section('scripts') @include('partials.feedback-form-script') @endsection
