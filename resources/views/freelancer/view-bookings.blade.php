@extends('layouts.freelancer.app')
@section('title', 'Client Bookings')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Bookings</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Client Name</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Package</th>
                                        <th data-table-sort>Date & Time</th>
                                        <th data-table-sort>Booking Status</th>
                                        <th data-table-sort>Payment Status</th>
                                        <th data-table-sort>Remaining Balance</th>
                                        <th data-table-sort>Total Amount</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        <tr data-booking-id="{{ $booking->id }}">
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <div class="link-reset">{{ $booking->client->first_name }} {{ $booking->client->last_name }}</div>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">Booking ID:</span>
                                                            <span class="text-muted">{{ $booking->booking_reference }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $booking->category->category_name ?? 'N/A' }}</td>
                                            <td>
                                                @if($booking->packages->count() > 0)
                                                    {{ $booking->packages->first()->package_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td data-sort-value="{{ \Carbon\Carbon::parse($booking->event_date)->format('Y-m-d') }}">
                                                <div class="d-flex">
                                                    <div>
                                                        <p class="mb-1">{{ \Carbon\Carbon::parse($booking->event_date)->format('F d, Y') }}</p>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="text-muted">{{ $booking->start_time }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusBadge = [
                                                        'pending' => 'badge-soft-warning',
                                                        'confirmed' => 'badge-soft-primary',
                                                        'in_progress' => 'badge-soft-warning',
                                                        'completed' => 'badge-soft-success',
                                                        'cancelled' => 'badge-soft-danger'
                                                    ][$booking->status] ?? 'badge-soft-primary';
                                                @endphp
                                                <span class="badge {{ $statusBadge }} fs-8 px-1 w-100 text-uppercase">{{ str_replace('_', ' ', $booking->status) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $paymentBadge = [
                                                        'pending' => 'badge-soft-warning',
                                                        'partially_paid' => 'badge-soft-primary',
                                                        'paid' => 'badge-soft-success',
                                                        'failed' => 'badge-soft-danger',
                                                        'refunded' => 'badge-soft-danger'
                                                    ][$booking->payment_status] ?? 'badge-soft-primary';
                                                @endphp
                                                <span class="badge {{ $paymentBadge }} fs-8 px-1 w-100 text-uppercase">{{ str_replace('_', ' ', $booking->payment_status) }}</span>
                                            </td>
                                            <td>PHP {{ number_format($booking->remaining_balance, 2) }}</td>
                                            <td data-sort-value="{{ $booking->total_amount }}">PHP {{ number_format($booking->total_amount, 2) }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm view-booking-btn" 
                                                            data-booking-id="{{ $booking->id }}" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#bookingModal">
                                                        <i class="ti ti-edit fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i data-lucide="calendar-x" class="fs-20 mb-2"></i>
                                                    <p class="mb-0">No bookings found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="bookings"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- MODAL --}}
    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="bookingModalLabel">
                        Booking Details - <span id="bookingReference">Loading...</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="bookingModalBody">
                        <div class="text-center py-5">
                            <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                            <p class="mt-3">Loading booking details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" id="bookingModalFooter" style="display: none;">
                    <div id="bookingActionButtons">
                        <!-- Action buttons will be dynamically added here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATUS UPDATE MODAL --}}
    <div class="modal fade" id="statusUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="statusUpdateForm">
                        <input type="hidden" id="statusBookingId" name="booking_id">
                        <div class="mb-3">
                            <label for="statusSelect" class="form-label">New Status</label>
                            <select class="form-control" id="statusSelect" name="status" required>
                                <option value="">Select Status</option>
                                <option value="confirmed">Confirm Booking</option>
                                <option value="rejected">Reject Booking</option>
                                <option value="in_progress">Mark as In Progress</option>
                                <option value="completed">Mark as Completed</option>
                                <option value="cancelled">Cancel Booking</option>
                            </select>
                        </div>
                        <div class="mb-3" id="reasonField" style="display: none;">
                            <label for="reasonText" class="form-label">Reason</label>
                            <textarea class="form-control" id="reasonText" name="reason" rows="3" placeholder="Please provide a reason..."></textarea>
                            <small class="text-muted">Required for rejection or cancellation</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitStatusUpdate">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    {{-- PAYMENT UPDATE MODAL --}}
    <div class="modal fade" id="paymentUpdateModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Payment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentUpdateForm">
                        <input type="hidden" id="paymentBookingId" name="booking_id">
                        
                        {{-- CURRENT PAYMENT STATUS DISPLAY --}}
                        <div class="mb-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Current Payment Status:</span>
                                <span id="currentPaymentStatus" class="fw-medium text-primary">Loading...</span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">Remaining Balance:</span>
                                <span id="currentRemainingBalance" class="fw-medium text-danger">PHP 0.00</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="paymentStatusSelect" class="form-label fw-semibold">Update Payment Status</label>
                            <select class="form-control" id="paymentStatusSelect" name="payment_status" required>
                                <option value="">-- Select Status --</option>
                                <option value="paid">Mark as Fully Paid</option>
                                <option value="refunded">Refund Payment</option>
                            </select>
                            <small class="text-muted d-block mt-1">
                                • <strong>Fully Paid:</strong> Client has paid the remaining balance<br>
                                • <strong>Refunded:</strong> Only available for fully paid bookings
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="paymentNotes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="paymentNotes" name="notes" rows="2" placeholder="Add any payment notes..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitPaymentUpdate">Update Payment</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentBookingId = null;
            let currentBookingData = null;
            
            // Initialize Bootstrap modals
            const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            const statusUpdateModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));
            const paymentUpdateModal = new bootstrap.Modal(document.getElementById('paymentUpdateModal'));

            // Payment modal show event - populate current status
            $('#paymentUpdateModal').on('show.bs.modal', function() {
                if (window.currentBookingPaymentStatus && window.currentBookingRemainingBalance !== undefined) {
                    $('#currentPaymentStatus').text(window.currentBookingPaymentStatus.replace('_', ' ').toUpperCase());
                    $('#currentRemainingBalance').text(`PHP ${parseFloat(window.currentBookingRemainingBalance).toFixed(2)}`);
                }
            });

            // Clear payment data when main modal closes
            $('#bookingModal').on('hidden.bs.modal', function() {
                window.currentBookingPaymentStatus = null;
                window.currentBookingRemainingBalance = null;
            });

            // Reset form when modal is hidden
            $('#paymentUpdateModal').on('hidden.bs.modal', function() {
                $('#paymentUpdateForm')[0].reset();
            });

            // View booking details
            $(document).on('click', '.view-booking-btn', function() {
                currentBookingId = $(this).data('booking-id');
                loadBookingDetails(currentBookingId);
                bookingModal.show();
            });

            // Load booking details
            function loadBookingDetails(bookingId) {
                $.ajax({
                    url: '{{ route("freelancer.booking.details", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        $('#bookingModalBody').html(`
                            <div class="text-center py-5">
                                <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                                <p class="mt-3">Loading booking details...</p>
                            </div>
                        `);
                        $('#bookingModalFooter').hide();
                    },
                    success: function(response) {
                        if (response.success) {
                            currentBookingData = response;
                            renderBookingDetails(response);
                            renderActionButtons(response.booking);
                        } else {
                            showError('Error loading booking details');
                        }
                    },
                    error: function(xhr) {
                        showError('Error loading booking details. Please try again.');
                    }
                });
            }

            // Render booking details
            function renderBookingDetails(data) {
                const booking = data.booking;
                const client = data.client;
                const category = data.category;
                const packages = data.packages;
                const payments = data.payments;

                let packagesHtml = '';
                if (packages && packages.length > 0) {
                    packages.forEach(pkg => {
                        packagesHtml += `
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">${pkg.package_name}:</span>
                                <span class="fw-medium">PHP ${parseFloat(pkg.package_price).toFixed(2)}</span>
                            </div>
                        `;
                    });
                }

                const totalPaid = payments ? payments.reduce((sum, payment) => sum + parseFloat(payment.amount), 0) : 0;
                const remainingBalance = parseFloat(booking.total_amount) - totalPaid;

                window.currentBookingPaymentStatus = booking.payment_status;
                window.currentBookingRemainingBalance = remainingBalance;

                const modalContent = `
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-6">
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="card-title mb-0 fw-semibold text-uppercase small text-primary">
                                            Booking Overview
                                        </h6>
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="receipt-text" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Booking ID</label>
                                                    <p class="mb-0 fw-medium">${booking.booking_reference}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Booking Date</label>
                                                    <p class="mb-0 fw-medium">${new Date(booking.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="check-circle" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Booking Status</label>
                                                    <p class="mb-0 fw-medium ${getStatusTextClass(booking.status)}">${booking.status.replace('_', ' ').toUpperCase()}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Client Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="user" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Client Name</label>
                                                    <p class="mb-0 fw-medium">${client.first_name} ${client.last_name}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="phone" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Contact Number</label>
                                                    <p class="mb-0 fw-medium">${client.mobile_number || 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="mail" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Email Address</label>
                                                    <p class="mb-0 fw-medium">${client.email}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Service Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="layers" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Category</label>
                                                    <p class="mb-0 fw-medium">${category ? category.category_name : 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="package" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Package(s)</label>
                                                    ${packagesHtml || '<p class="mb-0 fw-medium">N/A</p>'}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Schedule Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar-heart" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Event Date</label>
                                                    <p class="mb-0 fw-medium">${new Date(booking.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Event Location</label>
                                                    <p class="mb-0 fw-medium">${booking.venue_name || booking.street || 'N/A'}, ${booking.city || 'N/A'}, ${booking.province || 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="clock" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Scheduled Time</label>
                                                    <p class="mb-0 fw-medium">${booking.start_time} - ${booking.end_time}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase small text-primary">
                                        Payment Information
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="credit-card" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Total Amount</label>
                                                    <p class="mb-0 fw-semibold fs-5">PHP ${parseFloat(booking.total_amount).toFixed(2)}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="percent" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Down Payment</label>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-medium">PHP ${parseFloat(booking.down_payment).toFixed(2)}</span>
                                                        <span class="badge ${booking.deposit_policy ? 'badge-soft-success' : 'badge-soft-primary'} px-2 fw-medium">${booking.deposit_policy ? 'Yes' : 'No'}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="wallet" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Amount Paid</label>
                                                    <p class="mb-0 fw-medium text-success">PHP ${totalPaid.toFixed(2)}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Remaining Balance</label>
                                                    <p class="mb-0 fw-medium ${remainingBalance > 0 ? 'text-danger' : 'text-success'}">PHP ${remainingBalance.toFixed(2)}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="credit-card" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Payment Type</label>
                                                    <p class="mb-0 fw-medium">${booking.payment_type ? booking.payment_type.replace('_', ' ').toUpperCase() : 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-3 pt-3 border-top">
                                            <div class="bg-light p-3 rounded">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Total Amount:</span>
                                                    <span class="fw-medium">PHP ${parseFloat(booking.total_amount).toFixed(2)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span class="text-muted">Amount Paid:</span>
                                                    <span class="fw-medium">PHP ${totalPaid.toFixed(2)}</span>
                                                </div>
                                                <div class="d-flex justify-content-between pt-2 border-top">
                                                    <span class="fw-semibold">Remaining:</span>
                                                    <span class="fw-bold ${remainingBalance > 0 ? 'text-danger' : 'text-success'}">PHP ${remainingBalance.toFixed(2)}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#bookingReference').text(booking.booking_reference);
                $('#bookingModalBody').html(modalContent);
                $('#bookingModalFooter').show();
                loadIcons();
            }

            // Render action buttons based on booking status and payment status
            function renderActionButtons(booking) {
                let buttonsHtml = '';
                
                // Show different buttons based on current status
                switch(booking.status) {
                    case 'pending':
                        buttonsHtml = `
                            <button class="btn btn-success" id="confirmBookingBtn">
                                <i data-lucide="check-circle" class="me-1"></i> Confirm Booking
                            </button>
                            <button class="btn btn-danger" id="rejectBookingBtn">
                                <i data-lucide="x-circle" class="me-1"></i> Reject Booking
                            </button>
                        `;
                        break;
                        
                    case 'confirmed':
                        buttonsHtml = `
                            <button class="btn btn-danger" id="cancelBookingBtn">
                                <i data-lucide="x-circle" class="me-1"></i> Cancel Booking
                            </button>
                            <button class="btn btn-soft-primary" id="updatePaymentBtn">
                                <i data-lucide="credit-card" class="me-1"></i> Update Payment
                            </button>
                            <button class="btn btn-primary" id="markInProgressBtn">
                                <i data-lucide="play-circle" class="me-1"></i> Mark as In Progress
                            </button>
                        `;
                        break;
                        
                    case 'in_progress':
                        // Check if payment is fully paid before allowing completion
                        const canComplete = booking.payment_status === 'paid';
                        const completeButtonClass = canComplete ? 'btn btn-success' : 'btn btn-danger';
                        const completeDisabled = canComplete ? '' : 'disabled';
                        const completeTitle = canComplete ? '' : 'title="Cannot complete: Payment not fully paid"';
                        
                        buttonsHtml = `
                            <button class="btn btn-soft-primary" id="updatePaymentBtn">
                                <i data-lucide="credit-card" class="me-1"></i> Update Payment
                            </button>
                            <button class="${completeButtonClass}" id="markCompletedBtn" ${completeDisabled} ${completeTitle}>
                                <i data-lucide="check-circle" class="me-1"></i> Mark as Completed
                            </button>
                        `;
                        
                        // Add payment warning if not fully paid
                        if (!canComplete) {
                            buttonsHtml += `
                                <div class="mt-2 p-2 bg-light-warning rounded">
                                    <small class="text-warning">
                                        <i data-lucide="alert-triangle" class="me-1" style="width: 14px;"></i>
                                        Payment must be fully paid before marking as completed.
                                    </small>
                                </div>
                            `;
                        }
                        break;
                        
                    default:
                        buttonsHtml = `
                            <button class="btn btn-soft-primary" id="updatePaymentBtn">
                                <i data-lucide="credit-card" class="me-1"></i> Update Payment
                            </button>
                        `;
                }
                
                $('#bookingActionButtons').html(buttonsHtml);
                loadIcons();
                bindActionButtons(booking);
            }

            // Bind action button events
            function bindActionButtons(booking) {
                // Confirm booking
                $('#confirmBookingBtn').off('click').on('click', function() {
                    updateBookingStatus('confirmed');
                });
                
                // Reject booking
                $('#rejectBookingBtn').off('click').on('click', function() {
                    $('#statusSelect').val('rejected');
                    $('#statusBookingId').val(currentBookingId);
                    statusUpdateModal.show();
                });
                
                // Mark in progress
                $('#markInProgressBtn').off('click').on('click', function() {
                    updateBookingStatus('in_progress');
                });
                
                // Mark completed
                $('#markCompletedBtn').off('click').on('click', function() {
                    updateBookingStatus('completed');
                });
                
                // Cancel booking
                $('#cancelBookingBtn').off('click').on('click', function() {
                    $('#statusSelect').val('cancelled');
                    $('#statusBookingId').val(currentBookingId);
                    statusUpdateModal.show();
                });
                
                // Update payment
                $('#updatePaymentBtn').off('click').on('click', function() {
                    $('#paymentBookingId').val(currentBookingId);
                    paymentUpdateModal.show();
                });
            }

            // Update booking status (without reason)
            function updateBookingStatus(status) {
                Swal.fire({
                    title: 'Confirm Action',
                    text: `Are you sure you want to ${status.replace('_', ' ')} this booking?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitStatusUpdate(status, '');
                    }
                });
            }

            // Status select change
            $('#statusSelect').change(function() {
                const status = $(this).val();
                if (status === 'rejected' || status === 'cancelled') {
                    $('#reasonField').show();
                } else {
                    $('#reasonField').hide();
                }
            });

            // Payment status select change
            $('#paymentStatusSelect').change(function() {
                const status = $(this).val();
                if (status === 'partially_paid') {
                    $('#amountField').show();
                } else {
                    $('#amountField').hide();
                }
            });

            // Submit status update
            $('#submitStatusUpdate').click(function() {
                const status = $('#statusSelect').val();
                const reason = $('#reasonText').val();
                
                if (!status) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select a status',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                if ((status === 'rejected' || status === 'cancelled') && !reason.trim()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Reason Required',
                        text: 'Please provide a reason for rejection or cancellation',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                submitStatusUpdate(status, reason);
            });

            // Submit payment update
            $('#submitPaymentUpdate').click(function() {
                const paymentStatus = $('#paymentStatusSelect').val();
                const amountPaid = $('#amountPaid').val();
                const notes = $('#paymentNotes').val();
                
                if (!paymentStatus) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Missing Information',
                        text: 'Please select a payment status',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                if (paymentStatus === 'partially_paid' && (!amountPaid || parseFloat(amountPaid) <= 0)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Amount Required',
                        text: 'Please enter the amount paid',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                submitPaymentUpdate(paymentStatus, amountPaid, notes);
            });

            // Submit status update to server
            function submitStatusUpdate(status, reason) {
                $.ajax({
                    url: '{{ route("freelancer.booking.update.status", ":id") }}'.replace(':id', currentBookingId),
                    type: 'PUT',
                    data: {
                        status: status,
                        reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#submitStatusUpdate').prop('disabled', true).html('<span class="loading-spinner"></span> Updating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            statusUpdateModal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                // Refresh booking details
                                loadBookingDetails(currentBookingId);
                                // Refresh table row
                                updateTableRow(response.booking);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update status. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $('#submitStatusUpdate').prop('disabled', false).text('Update Status');
                        $('#statusUpdateForm')[0].reset();
                        $('#reasonField').hide();
                    }
                });
            }

            // Submit payment update to server
            function submitPaymentUpdate(paymentStatus, notes) {
                // Confirm for refund
                if (paymentStatus === 'refunded') {
                    Swal.fire({
                        title: 'Confirm Refund',
                        text: 'Are you sure you want to refund this payment? This action cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, refund payment',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            sendPaymentUpdate(paymentStatus, notes);
                        }
                    });
                } else {
                    // Normal payment update (fully paid)
                    Swal.fire({
                        title: 'Confirm Payment',
                        text: 'Mark this booking as fully paid?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3475db',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, mark as paid',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            sendPaymentUpdate(paymentStatus, notes);
                        }
                    });
                }
            }

            // Submit payment update to server
            function sendPaymentUpdate(paymentStatus, notes) {
                $.ajax({
                    url: '{{ route("freelancer.booking.update.payment.status", ":id") }}'.replace(':id', currentBookingId),
                    type: 'PUT',
                    data: {
                        payment_status: paymentStatus,
                        notes: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#submitPaymentUpdate').prop('disabled', true).html('<span class="loading-spinner"></span> Updating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            paymentUpdateModal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                // ✅ FIX: Refresh booking details to show updated remaining balance
                                loadBookingDetails(currentBookingId);
                                // Refresh table row
                                updateTableRow(response.booking);
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update payment status. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $('#submitPaymentUpdate').prop('disabled', false).text('Update Payment');
                        $('#paymentUpdateForm')[0].reset();
                    }
                });
            }

            // Update table row with new data
            function updateTableRow(booking) {
                const row = $(`tr[data-booking-id="${booking.id}"]`);
                
                // Update status badge
                const statusBadge = {
                    'pending': 'badge-soft-warning',
                    'confirmed': 'badge-soft-success',
                    'in_progress': 'badge-soft-info',
                    'completed': 'badge-soft-primary',
                    'cancelled': 'badge-soft-danger',
                    'rejected': 'badge-soft-danger'
                }[booking.status] || 'badge-soft-primary';
                
                row.find('td:nth-child(5)').html(`
                    <span class="badge ${statusBadge} fs-8 px-1 w-100 text-uppercase">
                        ${booking.status.replace('_', ' ')}
                    </span>
                `);
                
                // Update payment status badge
                const paymentBadge = {
                    'pending': 'badge-soft-warning',
                    'partially_paid': 'badge-soft-primary',
                    'paid': 'badge-soft-success',
                    'failed': 'badge-soft-danger',
                    'refunded': 'badge-soft-danger'
                }[booking.payment_status] || 'badge-soft-primary';
                
                row.find('td:nth-child(6)').html(`
                    <span class="badge ${paymentBadge} fs-8 px-1 w-100 text-uppercase">
                        ${booking.payment_status.replace('_', ' ')}
                    </span>
                `);
                
                // ✅ FIX: Update remaining balance - use the value from the response
                const remainingBalance = parseFloat(booking.remaining_balance || 0).toFixed(2);
                row.find('td:nth-child(7)').text(`PHP ${remainingBalance}`);
            }

            // Helper functions
            function getStatusTextClass(status) {
                const textClasses = {
                    'pending': 'text-warning',
                    'confirmed': 'text-primary',
                    'in_progress': 'text-info',
                    'completed': 'text-success',
                    'cancelled': 'text-danger',
                    'rejected': 'text-danger'
                };
                return textClasses[status] || 'text-primary';
            }

            function showError(message) {
                $('#bookingModalBody').html(`
                    <div class="text-center py-5">
                        <i data-lucide="alert-circle" class="fs-20 text-danger mb-3"></i>
                        <p class="text-danger">${message}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadBookingDetails(${currentBookingId})">
                            <i data-lucide="refresh-cw" class="me-1"></i> Retry
                        </button>
                    </div>
                `);
                loadIcons();
            }

            function loadIcons() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            // Initialize icons on page load
            loadIcons();
        });
    </script>
@endsection