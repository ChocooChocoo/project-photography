@extends('layouts.client.app')
@section('title', 'My Refunds')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="10" class="card">
                        <div class="card-header">
                            <h4 class="card-title">My Refunds</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search by booking ID or category...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Booking ID</th>
                                        <th data-table-sort>Provider</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Event Date</th>
                                        <th data-table-sort>Amount</th>
                                        <th data-table-sort>Refund Status</th>
                                        <th data-table-sort>Reason</th>
                                        <th data-table-sort>Resolved</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recoveries as $recovery)
                                        @php
                                            $booking = $recovery->booking;
                                            $providerName = $booking->booking_type === 'studio'
                                                ? ($booking->studio->studio_name ?? 'Studio')
                                                : ($booking->freelancer->brand_name ?? 'Freelancer');
                                            $paidAmount = $booking->payments->where('status', 'succeeded')->sum('amount');
                                            $refundedAmount = $booking->payments->where('status', 'refunded')->sum(fn ($payment) => $payment->refunded_amount ?? $payment->amount);
                                             $targetAmount = $recovery->refund_amount ?? $paidAmount;
                                            $isRefunded = $recovery->status === 'refunded';
                                            $statusBadge = $isRefunded ? 'badge-soft-success' : 'badge-soft-warning';
                                            $statusText = $isRefunded ? 'Refunded' : 'Refund Pending';
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="fw-medium">{{ $booking->booking_reference }}</span>
                                                <small class="text-muted d-block">{{ ucfirst($booking->booking_type) }}</small>
                                            </td>
                                            <td>{{ $providerName }}</td>
                                            <td>{{ $booking->category->category_name ?? 'N/A' }}</td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($booking->event_date)->format('M d, Y') }}
                                                <small class="text-muted d-block">{{ $booking->start_time }}</small>
                                            </td>
                                            <td>
                                                <span class="fw-semibold">Target: ₱{{ number_format($targetAmount, 2) }}</span>
                                                 <small class="text-muted d-block">Paid: ₱{{ number_format($paidAmount, 2) }}</small>
                                                @if($isRefunded)
                                                    <small class="text-warning d-block">Refunded: ₱{{ number_format($refundedAmount, 2) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusBadge }} fs-8 px-2 w-100 text-uppercase">{{ $statusText }}</span>
                                            </td>
                                            <td style="max-width: 280px;">
                                                <span class="d-block text-truncate" title="{{ $recovery->outcome_reason }}">
                                                    {{ $recovery->outcome_reason ?: 'Not provided' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($recovery->resolved_at)
                                                    {{ $recovery->resolved_at->format('M d, Y') }}
                                                    <small class="text-muted d-block">{{ $recovery->resolved_at->format('h:i A') }}</small>
                                                @else
                                                    <span class="text-muted">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i data-lucide="receipt-off" class="fs-20 mb-2"></i>
                                                    <p class="mb-0">No refunds found</p>
                                                    <small class="mt-1">Refunds for your cancelled paid bookings will appear here.</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="refunds"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
