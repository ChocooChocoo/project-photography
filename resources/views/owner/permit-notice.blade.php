@extends('layouts.owner.app')
@section('title', 'Permit Verification Required')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Business Permit Verification Required</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-triangle me-2"></i>
                                Your studio requires a valid business permit before you can continue setting up.
                                Please review the status below and upload a new copy of your permit for re-verification.
                            </div>

                            @forelse($studios as $studio)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <h5 class="mb-0">{{ $studio->studio_name }}</h5>
                                        @if($studio->status === 'pending')
                                            <span class="badge badge-soft-warning fs-8">PENDING REVIEW</span>
                                        @else
                                            <span class="badge badge-soft-danger fs-8">PERMIT EXPIRED</span>
                                        @endif
                                    </div>

                                    <p class="mb-1">
                                        <span class="text-muted">Permit Expiry:</span>
                                        @if($studio->permit_expiry_date)
                                            {{ $studio->permit_expiry_date->format('F d, Y') }}
                                            @if($studio->isPermitExpired())
                                                <span class="badge badge-soft-danger fs-8">EXPIRED</span>
                                            @else
                                                <span class="badge badge-soft-success fs-8">VALID</span>
                                            @endif
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </p>

                                    @if($studio->rejection_note)
                                        <p class="mb-2">
                                            <span class="text-muted">Last rejection reason:</span>
                                            <span class="fw-medium">{{ $studio->rejection_note }}</span>
                                        </p>
                                    @endif

                                    <form action="{{ route('owner.studio.permit.resubmit', $studio->id) }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                                        @csrf
                                        <div class="col-md-8">
                                            <label class="form-label">New Business Permit/DTI/SEC Registration</label>
                                            <input type="file" class="form-control" name="business_permit" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">PDF, JPG, or PNG. Max size: 3MB</div>
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="ti ti-upload me-1"></i>Resubmit Permit
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No studios found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
