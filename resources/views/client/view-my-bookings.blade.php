@extends('layouts.client.app')
@section('title', 'My Bookings')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="10" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of My Bookings</h4>
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
                                        <th data-table-sort>Booking ID</th>
                                        <th data-table-sort>Provider</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Package</th>
                                        <th data-table-sort>Event Date</th>
                                        <th data-table-sort>Status</th>
                                        <th data-table-sort>Payment</th>
                                        <th data-table-sort>Total Amount</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        <tr data-booking-id="{{ $booking->id }}">
                                            <td>
                                                <span class="fw-medium">{{ $booking->booking_reference }}</span>
                                                <small class="text-muted d-block">{{ ucfirst($booking->booking_type) }}</small>
                                            </td>
                                            <td>
                                                @if($booking->booking_type === 'studio')
                                                    {{ $booking->provider->studio_name ?? ($booking->provider->studio_name ?? 'Studio') }}
                                                @else
                                                    {{ $booking->provider->brand_name ?? ($booking->provider->brand_name ?? 'Freelancer') }}
                                                @endif
                                            </td>
                                            <td>{{ $booking->category->category_name ?? 'N/A' }}</td>
                                            <td>
                                                @if($booking->packages->count() > 0)
                                                    {{ $booking->packages->first()->package_name }}
                                                    <small class="text-muted d-block">₱{{ number_format($booking->packages->first()->package_price, 2) }}</small>
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <p class="mb-1">{{ \Carbon\Carbon::parse($booking->event_date)->format('M d, Y') }}</p>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="text-muted">{{ $booking->start_time }} - {{ $booking->end_time }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusBadge = [
                                                        'pending' => 'badge-soft-warning',
                                                        'confirmed' => 'badge-soft-success',
                                                        'in_progress' => 'badge-soft-info',
                                                        'completed' => 'badge-soft-secondary',
                                                        'cancelled' => 'badge-soft-danger'
                                                    ][$booking->status] ?? 'badge-soft-secondary';
                                                @endphp
                                                <span class="badge {{ $statusBadge }} fs-8 px-2 w-100 text-uppercase">{{ str_replace('_', ' ', $booking->status) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $paymentBadge = [
                                                        'pending' => 'badge-soft-warning',
                                                        'partially_paid' => 'badge-soft-info',
                                                        'paid' => 'badge-soft-success',
                                                        'failed' => 'badge-soft-danger',
                                                        'refunded' => 'badge-soft-secondary',
                                                        'cancelled' => 'badge-soft-danger'
                                                    ][$booking->payment_status] ?? 'badge-soft-secondary';
                                                    
                                                    $totalPaid = $booking->payments->where('status', 'succeeded')->sum('amount');
                                                    $paymentProgress = $booking->total_amount > 0 ? round(($totalPaid / $booking->total_amount) * 100) : 0;
                                                @endphp
                                                <span class="badge {{ $paymentBadge }} fs-8 px-2 w-100 text-uppercase">{{ str_replace('_', ' ', $booking->payment_status) }}</span>
                                                <small class="text-muted d-block mt-1 text-center">
                                                    ₱{{ number_format($totalPaid, 2) }} / ₱{{ number_format($booking->total_amount, 2) }}
                                                </small>
                                                @if($booking->payment_status === 'partially_paid')
                                                <div class="progress mt-1" style="height: 3px;">
                                                    <div class="progress-bar bg-info" role="progressbar" style="width: {{ $paymentProgress }}%;" 
                                                        aria-valuenow="{{ $paymentProgress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="fw-semibold">₱{{ number_format($booking->total_amount, 2) }}</span>
                                                <small class="text-muted d-block">
                                                    @if($booking->booking_type === 'studio')
                                                        @if($booking->payment_type === 'downpayment')
                                                            {{ $booking->downpayment_percentage ?? 30 }}% Downpayment
                                                        @else
                                                            Full Payment
                                                        @endif
                                                    @else
                                                        {{-- Freelancer dynamic display --}}
                                                        @if(isset($booking->payment_display))
                                                            {{ $booking->payment_display }}
                                                        @elseif($booking->payment_type === 'downpayment')
                                                            30% Downpayment
                                                        @else
                                                            Full Payment
                                                        @endif
                                                    @endif
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm view-booking-btn" 
                                                            data-booking-id="{{ $booking->id }}" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#bookingModal"
                                                            title="View Details">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </button>
                                                    
                                                    <!-- Pay Balance Button - Show for confirmed/in_progress bookings that are partially paid -->
                                                    @if(in_array($booking->status, ['confirmed', 'in_progress']) && in_array($booking->payment_status, ['pending', 'partially_paid']))
                                                        @php
                                                            $totalPaid = $booking->payments->where('status', 'succeeded')->sum('amount');
                                                            $hasRemaining = ($booking->total_amount - $totalPaid) > 0;
                                                        @endphp
                                                        @if($hasRemaining)
                                                        <button class="btn btn-sm pay-balance-btn" 
                                                                data-booking-id="{{ $booking->id }}"
                                                                data-booking-reference="{{ $booking->booking_reference }}"
                                                                title="Pay Remaining Balance">
                                                            <i class="ti ti-credit-card fs-lg"></i>
                                                        </button>
                                                        @endif
                                                    @endif
                                                    
                                                    @if($booking->status === 'pending')
                                                    <button class="btn btn-sm cancel-booking-btn" 
                                                            data-booking-id="{{ $booking->id }}"
                                                            data-booking-reference="{{ $booking->booking_reference }}"
                                                            title="Cancel Booking">
                                                        <i class="ti ti-x fs-lg"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i data-lucide="calendar-x" class="fs-20 mb-2"></i>
                                                    <p class="mb-0">No current bookings found</p>
                                                    <small class="mt-1">You don't have any active bookings at the moment.</small>
                                                    <div class="mt-3">
                                                        <a href="{{ route('client.dashboard') }}" class="btn btn-primary btn-sm">
                                                            <i class="ti ti-search me-1"></i> Browse Services
                                                        </a>
                                                    </div>
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

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div>
                                <h5 class="card-title">
                                    Pending Photographer Confirmations
                                </h5>
                            </div>
                            <div>
                                <p class="text-muted small mb-0">Confirm when photographers arrive on-site to begin the session</p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="pendingConfirmationsContainer">
                                <div class="text-center py-3">
                                    <div class="loading-spinner" style="width: 2rem; height: 2rem; margin: 0 auto;"></div>
                                    <p class="mt-2 text-muted">Loading pending confirmations...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- BOOKING DETAILS MODAL --}}
    <div class="modal fade" id="bookingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="bookingModalLabel">
                        Booking Details - <span id="bookingReference">Loading...</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="bookingModalBody">
                    <div class="text-center py-5">
                        <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                        <p class="mt-3">Loading booking details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- BALANCE PAYMENT MODAL --}}
    <div class="modal fade" id="balancePaymentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">
                        <i class="ti ti-credit-card me-2"></i>Pay Remaining Balance
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="balancePaymentModalBody">
                        <div class="text-center py-4">
                            <div class="loading-spinner" style="width: 2.5rem; height: 2.5rem; margin: 0 auto;"></div>
                            <p class="mt-3 text-muted">Loading payment details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="proceedBalancePayment" style="background-color: #3475db; border-color: #3475db;">
                        <i class="ti ti-credit-card me-1"></i> Proceed to Payment
                    </button>
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
            
            // View booking details
            $(document).on('click', '.view-booking-btn', function() {
                currentBookingId = $(this).data('booking-id');
                loadBookingDetails(currentBookingId);
            });

            // Cancel booking
            $(document).on('click', '.cancel-booking-btn', function() {
                const bookingId = $(this).data('booking-id');
                const bookingRef = $(this).data('booking-reference');
                
                Swal.fire({
                    title: 'Cancel Booking',
                    html: `<p>Are you sure you want to cancel booking <strong>${bookingRef}</strong>?</p>
                          <p class="text-danger small">Note: Bookings can only be cancelled at least 24 hours before the event date.</p>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, cancel booking',
                    cancelButtonText: 'No, keep it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        cancelBooking(bookingId);
                    }
                });
            });

            // Pay Balance button click handler
            $(document).on('click', '.pay-balance-btn', function() {
                const bookingId = $(this).data('booking-id');
                const bookingRef = $(this).data('booking-reference');
                
                // Load payment details and show modal
                loadPaymentDetailsForModal(bookingId, bookingRef);
            });

            // Load payment details for modal
            function loadPaymentDetailsForModal(bookingId, bookingRef) {
                $.ajax({
                    url: '{{ route("client.booking.payment.details", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        // Show loading state in modal
                        $('#balancePaymentModalBody').html(`
                            <div class="text-center py-4">
                                <div class="loading-spinner" style="width: 2.5rem; height: 2.5rem; margin: 0 auto;"></div>
                                <p class="mt-3 text-muted">Loading payment details...</p>
                            </div>
                        `);
                        
                        // Disable proceed button
                        $('#proceedBalancePayment').prop('disabled', true);
                        
                        // Show modal with loading
                        $('#balancePaymentModal').modal('show');
                    },
                    success: function(response) {
                        if (response.success) {
                            renderBalancePaymentModal(response, bookingRef);
                        } else {
                            // Hide modal and show error
                            $('#balancePaymentModal').modal('hide');
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#balancePaymentModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load payment details. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // Render balance payment modal content
            function renderBalancePaymentModal(data, bookingRef) {
                const booking = data.booking;
                const remainingBalance = parseFloat(booking.remaining_balance).toFixed(2);
                const bookingId = booking.id;
                
                let modalContent = `
                    <div class="text-start">
                        <div class="alert alert-info mb-3 d-flex align-items-center">
                            <i class="ti ti-info-circle me-2 fs-16"></i>
                            <div>
                                <strong>Booking:</strong> ${bookingRef}<br>
                                <strong>Status:</strong> <span class="badge bg-info-subtle text-info">${booking.booking_status.replace('_', ' ').toUpperCase()}</span>
                            </div>
                        </div>
                        
                        <div class="bg-light p-3 rounded mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Total Amount:</span>
                                <span class="fw-semibold">₱${parseFloat(booking.total_amount).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Amount Paid:</span>
                                <span class="fw-medium text-success">₱${parseFloat(booking.total_paid).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between pt-2 border-top">
                                <span class="fw-semibold">Remaining Balance:</span>
                                <span class="fw-bold text-danger">₱${remainingBalance}</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-medium">Payment Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="modalBalanceAmount" 
                                    value="${remainingBalance}" min="1" max="${remainingBalance}" step="0.01">
                            </div>
                            <small class="text-muted mt-1 d-block">
                                <i class="ti ti-info-circle me-1"></i>You can pay the full remaining balance or a partial amount.
                            </small>
                        </div>
                `;
                
                if (data.has_pending_payment) {
                    modalContent += `
                        <div class="alert alert-warning mt-3">
                            <i class="ti ti-clock me-2"></i>
                            You have a pending payment for this booking. Please complete it or wait for it to expire.
                        </div>
                    `;
                }
                
                modalContent += `</div>`;
                
                $('#balancePaymentModalBody').html(modalContent);
                
                // Store booking data for proceed button
                $('#proceedBalancePayment').data('booking-id', bookingId);
                $('#proceedBalancePayment').data('booking-ref', bookingRef);
                $('#proceedBalancePayment').data('remaining-balance', remainingBalance);
                
                // Enable proceed button
                $('#proceedBalancePayment').prop('disabled', data.has_pending_payment);
                
                // Reinitialize icons
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            // Proceed button click handler
            $('#proceedBalancePayment').on('click', function() {
                const bookingId = $(this).data('booking-id');
                const bookingRef = $(this).data('booking-ref');
                const remainingBalance = $(this).data('remaining-balance');
                const amount = $('#modalBalanceAmount').val();
                
                // Validate amount
                if (!amount || amount <= 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please enter a valid amount',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                if (parseFloat(amount) > parseFloat(remainingBalance)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Amount cannot exceed remaining balance',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                // Close modal and proceed with payment
                $('#balancePaymentModal').modal('hide');
                initializeBalancePayment(bookingId, amount);
            });

            // Load payment details for balance payment
            function loadPaymentDetails(bookingId, bookingRef) {
                $.ajax({
                    url: '{{ route("client.booking.payment.details", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Loading...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            showBalancePaymentModal(response, bookingRef);
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
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load payment details. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // Show balance payment modal
            function showBalancePaymentModal(data, bookingRef) {
                const booking = data.booking;
                const remainingBalance = parseFloat(booking.remaining_balance).toFixed(2);
                const bookingId = booking.id; // Store booking ID
                
                Swal.fire({
                    title: 'Pay Remaining Balance',
                    html: `
                        <div class="text-start">
                            <div class="alert alert-info mb-3">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>Booking:</strong> ${bookingRef}<br>
                                <strong>Status:</strong> ${booking.booking_status.replace('_', ' ').toUpperCase()}
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Amount:</span>
                                    <span class="fw-semibold">₱${parseFloat(booking.total_amount).toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Amount Paid:</span>
                                    <span class="fw-medium text-success">₱${parseFloat(booking.total_paid).toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between pt-2 border-top">
                                    <span class="fw-semibold">Remaining Balance:</span>
                                    <span class="fw-bold text-danger">₱${remainingBalance}</span>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Payment Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="balanceAmount" 
                                        value="${remainingBalance}" min="1" max="${remainingBalance}" step="0.01">
                                </div>
                                <small class="text-muted">You can pay the full remaining balance or a partial amount.</small>
                            </div>
                            
                            ${data.has_pending_payment ? `
                                <div class="alert alert-warning">
                                    <i class="ti ti-clock me-2"></i>
                                    You have a pending payment for this booking. Please complete it or wait for it to expire.
                                </div>
                            ` : ''}
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Proceed to Payment',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    preConfirm: () => {
                        const amount = $('#balanceAmount').val();
                        
                        if (!amount || amount <= 0) {
                            Swal.showValidationMessage('Please enter a valid amount');
                            return false;
                        }
                        
                        if (parseFloat(amount) > parseFloat(remainingBalance)) {
                            Swal.showValidationMessage('Amount cannot exceed remaining balance');
                            return false;
                        }
                        
                        return { 
                            amount: amount,
                            bookingId: bookingId 
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Pass both bookingId and amount
                        initializeBalancePayment(result.value.bookingId, result.value.amount);
                    }
                });
            }

            // Initialize balance payment
            function initializeBalancePayment(bookingId, amount) {
                $.ajax({
                    url: '{{ route("client.booking.balance.payment", ":id") }}'.replace(':id', bookingId),
                    type: 'POST',
                    data: {
                        amount: amount,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            proceedWithBalancePayment(bookingId, response.payment.id, response.booking_reference, response.amount);
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
                        let message = 'Failed to initialize payment. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message,
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // Proceed with balance payment using existing payment method
            function proceedWithBalancePayment(bookingId, paymentId, bookingRef, amount) {
                $.ajax({
                    url: '{{ route("client.payments.initialize") }}',
                    type: 'POST',
                    data: {
                        booking_id: bookingId,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Redirecting to Payment...',
                            text: 'Please wait while we prepare your payment',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success && response.redirect_url) {
                            // Redirect to Stripe checkout
                            window.location.href = response.redirect_url;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Payment Failed',
                                text: response.message || 'Failed to initialize payment',
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to initialize payment. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message,
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // Reset modal when hidden
            $('#balancePaymentModal').on('hidden.bs.modal', function () {
                $('#balancePaymentModalBody').html(`
                    <div class="text-center py-4">
                        <div class="loading-spinner" style="width: 2.5rem; height: 2.5rem; margin: 0 auto;"></div>
                        <p class="mt-3 text-muted">Loading payment details...</p>
                    </div>
                `);
                $('#proceedBalancePayment').prop('disabled', true);
                $('#proceedBalancePayment').removeData('booking-id');
                $('#proceedBalancePayment').removeData('booking-ref');
                $('#proceedBalancePayment').removeData('remaining-balance');
            });

            // Load booking details
            function loadBookingDetails(bookingId) {
                $.ajax({
                    url: '{{ route("client.booking.details", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        $('#bookingModalBody').html(`
                            <div class="text-center py-5">
                                <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                                <p class="mt-3">Loading booking details...</p>
                            </div>
                        `);
                    },
                    success: function(response) {
                        if (response.success) {
                            renderBookingDetails(response);
                        } else {
                            showError('Error loading booking details: ' + response.message);
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
                const provider = data.provider;
                const providerType = data.provider_type;
                const category = data.category;
                const packages = data.packages;
                const payments = data.payments;
                const assignedPhotographers = data.assignedPhotographers;
                const paymentSummary = data.payment_summary;
                const depositInfo = data.deposit_info || {
                    policy: 'required',
                    type: 'percentage',
                    amount: 30,
                    display: '30% Downpayment',
                    is_percentage: true,
                    is_fixed: false,
                    is_no_deposit: false
                };

                // Format date
                const eventDate = new Date(booking.event_date);
                const formattedDate = eventDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                
                // Format time (remove seconds if present)
                const startTime = booking.start_time.substring(0, 5);
                const endTime = booking.end_time.substring(0, 5);
                
                // Get provider logo
                let providerLogo = '{{ asset("assets/images/sellers/7.png") }}';
                if (provider) {
                    if (providerType === 'studio' && provider.studio_logo) {
                        providerLogo = '{{ asset("storage/") }}/' + provider.studio_logo;
                    } else if (providerType === 'freelancer' && provider.brand_logo) {
                        providerLogo = '{{ asset("storage/") }}/' + provider.brand_logo;
                    }
                }
                
                // Get provider name
                let providerName = '';
                if (provider) {
                    if (providerType === 'studio') {
                        providerName = provider.studio_name || 'Studio';
                    } else {
                        providerName = provider.brand_name || 'Freelancer';
                    }
                }
                
                // Get provider contact
                let providerContact = '';
                if (provider) {
                    if (providerType === 'studio') {
                        providerContact = provider.contact_number || '';
                        if (provider.studio_email) {
                            providerContact += providerContact ? ' • ' + provider.studio_email : provider.studio_email;
                        }
                    } else {
                        providerContact = provider.contact_number || '';
                        if (provider.contact_email) {
                            providerContact += providerContact ? ' • ' + provider.contact_email : provider.contact_email;
                        }
                    }
                }
                
                // Get category name
                const categoryName = category ? category.category_name : 'N/A';
                
                // Get location type display
                const locationTypeDisplay = booking.location_type === 'in-studio' ? 'In-Studio' : 'On-Location';
                
                // Build location details for display
                let locationDetails = '';
                if (booking.location_type === 'on-location') {
                    const parts = [];
                    if (booking.venue_name) parts.push(booking.venue_name);
                    if (booking.street) parts.push(booking.street);
                    if (booking.barangay) parts.push('Brgy. ' + booking.barangay);
                    if (booking.city) parts.push(booking.city);
                    if (booking.province) parts.push(booking.province);
                    locationDetails = parts.join(', ');
                }
                
                // Build payment history
                let paymentHistoryHtml = '';
                if (payments && payments.length > 0) {
                    payments.forEach(payment => {
                        const paidDate = payment.paid_at ? new Date(payment.paid_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'short', 
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        }) : '';
                        
                        const paymentMethod = payment.payment_method ? payment.payment_method.replace('_', ' ').toUpperCase() : 'CARD';
                        
                        paymentHistoryHtml += `
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                <div>
                                    <div class="fw-medium">${paymentMethod}</div>
                                    <small class="text-muted">${payment.payment_reference}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">₱${parseFloat(payment.amount).toFixed(2)}</div>
                                    <div>
                                        <span class="badge ${payment.status === 'succeeded' ? 'badge-soft-success' : 'badge-soft-warning'}">
                                            ${payment.status}
                                        </span>
                                    </div>
                                    ${paidDate ? `<small class="text-muted d-block">${paidDate}</small>` : ''}
                                </div>
                            </div>
                        `;
                    });
                }
                
                // Build packages HTML
                let packagesHtml = '';
                if (packages && packages.length > 0) {
                    packages.forEach(pkg => {
                        packagesHtml += `
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">PACKAGE DETAILS</h5>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-package fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Package</label>
                                            <p class="mb-0 fw-medium">${pkg.package_name}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Price</label>
                                            <p class="mb-0 fw-medium">₱${parseFloat(pkg.package_price).toFixed(2)}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-clock fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Duration</label>
                                            <p class="mb-0 fw-medium">${pkg.duration} hours</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-camera fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Photos</label>
                                            <p class="mb-0 fw-medium">${pkg.maximum_edited_photos} edited photos</p>
                                        </div>
                                    </div>
                                </div>

                                ${pkg.coverage_scope ? `
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-map-pin fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Coverage</label>
                                                <p class="mb-0 fw-medium">${pkg.coverage_scope}</p>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                                
                                ${pkg.package_inclusions && Array.isArray(pkg.package_inclusions) && pkg.package_inclusions.length > 0 ? `
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-checklist fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Inclusions</label>
                                                <ul class="mb-0 ps-3">
                                                    ${pkg.package_inclusions.map(inc => `<li class="fw-medium"><i class="ti ti-check text-success me-2 fs-14"></i>${inc}</li>`).join('')}
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    });
                }
                
                // Build assigned photographers HTML
                let assignedPhotographersHtml = '';
                if (assignedPhotographers && assignedPhotographers.length > 0) {
                    assignedPhotographers.forEach(assignment => {
                        const photographer = assignment.photographer;
                        const studioPhotographer = assignment.studio_photographer;
                        const photographerName = photographer ? `${photographer.first_name} ${photographer.last_name}` : 'Unknown';
                        
                        assignedPhotographersHtml += `
                            <div class="col-12">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light-primary rounded-circle p-2">
                                            <i class="ti ti-user-star fs-20 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="text-muted small mb-1">Photographers</label>
                                        <p class="mb-0 fw-medium">
                                            ${photographerName} 
                                            ${studioPhotographer ? ` - ${studioPhotographer.position || 'Photographer'}` : ''}
                                            <span class="badge ${getStatusBadgeClass(assignment.status)} ms-2">${assignment.status}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                } else if (providerType === 'studio') {
                    // Only show for studios since freelancers don't have assigned photographers
                    assignedPhotographersHtml = `
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-light-primary rounded-circle p-2">
                                        <i class="ti ti-user-star fs-20 text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <label class="text-muted small mb-1">Photographers</label>
                                    <p class="mb-0 fw-medium">No photographers assigned yet</p>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                // ========== FIX: Get deposit display based on provider type ==========
                let depositDisplayHtml = '';
                let paymentTypeDisplay = '';
                
                if (providerType === 'freelancer') {
                    if (depositInfo.is_no_deposit) {
                        depositDisplayHtml = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Payment Type:</span>
                                <span class="fw-medium text-success">Full Payment (No Deposit Required)</span>
                            </div>
                        `;
                        paymentTypeDisplay = 'Full Payment';
                    } else if (depositInfo.is_fixed) {
                        depositDisplayHtml = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Fixed Deposit:</span>
                                <span class="fw-medium">₱${parseFloat(depositInfo.amount).toFixed(2)}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Package Price:</span>
                                <span class="fw-medium">₱${parseFloat(paymentSummary.total_amount).toFixed(2)}</span>
                            </div>
                        `;
                        paymentTypeDisplay = 'Fixed Deposit';
                    } else if (depositInfo.is_percentage) {
                        depositDisplayHtml = `
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Down Payment (${depositInfo.amount}%):</span>
                                <span class="fw-medium">- ₱${parseFloat(paymentSummary.down_payment).toFixed(2)}</span>
                            </div>
                        `;
                        paymentTypeDisplay = depositInfo.amount + '% Down Payment';
                    }
                } else {
                    // Studio display
                    depositDisplayHtml = `
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">Down Payment (${depositInfo.amount}%):</span>
                            <span class="fw-medium">- ₱${parseFloat(paymentSummary.down_payment).toFixed(2)}</span>
                        </div>
                    `;
                    paymentTypeDisplay = depositInfo.amount + '% Down Payment';
                }
                
                // For full payment bookings
                if (booking.payment_type === 'full_payment') {
                    depositDisplayHtml = '';
                    paymentTypeDisplay = 'Full Payment';
                }
                // ========== End of deposit display logic ==========
                
                const modalContent = `
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center flex-column flex-md-row">
                                <div class="flex-shrink-0 mb-3 mb-md-0">
                                    <img src="${providerLogo}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="${providerName}">
                                </div>
                                
                                <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                    <h2 class="mb-1 h3 h3-md">${providerName}</h2>
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                        <span class="badge ${getStatusBadgeClassForBooking(booking.status)} p-1">${booking.status.replace('_', ' ').toUpperCase()}</span>
                                    </div>
                                    
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-map-pin me-1"></i> ${locationTypeDisplay} | ${categoryName}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary"><i class="ti ti-info-circle me-2"></i>BOOKING INFORMATION</h5>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-hash fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Booking Reference</label>
                                            <p class="mb-0 fw-medium">${booking.booking_reference}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-calendar fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Event Date & Time</label>
                                            <p class="mb-0 fw-medium">${formattedDate}<br>${startTime} - ${endTime}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-map-pin fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Event Location</label>
                                            <p class="mb-0 fw-medium">${locationTypeDisplay}</p>
                                            ${locationDetails ? `<small class="text-muted">${locationDetails}</small>` : ''}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-category fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Service Category</label>
                                            <p class="mb-0 fw-medium">${categoryName}</p>
                                        </div>
                                    </div>
                                </div>

                                ${booking.special_requests ? `
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-message fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Special Requests</label>
                                                <p class="mb-0 fw-medium">${booking.special_requests}</p>
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary"><i class="ti ti-building-store me-2"></i>SERVICE PROVIDER</h5>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-building-store fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Provider</label>
                                            <p class="mb-0 fw-medium">${providerName}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-phone fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Contact</label>
                                            <p class="mb-0 fw-medium">${providerContact || 'No contact information'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary"><i class="ti ti-credit-card me-2"></i>PAYMENT INFORMATION</h5>
                                
                                <div class="col-12">
                                    <div class="card border bg-light">
                                        <div class="card-body p-3">
                                            {{-- Receipt Header --}}
                                            <div class="text-start mb-3 pb-2 border-bottom">
                                                <h6 class="fw-bold mb-1"><i class="ti ti-receipt me-2"></i>PAYMENT RECEIPT</h6>
                                                <small class="text-muted">Booking #${booking.booking_reference}</small>
                                            </div>
                                            
                                            {{-- Receipt Items --}}
                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted"><i class="ti ti-package me-2"></i>Package Subtotal:</span>
                                                    <span class="fw-medium">₱${parseFloat(paymentSummary.total_amount).toFixed(2)}</span>
                                                </div>
                                                
                                                ${depositDisplayHtml}
                                                
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="text-muted"><i class="ti ti-circle-check me-2 text-success"></i>Amount Paid:</span>
                                                    <span class="fw-medium text-success">₱${parseFloat(paymentSummary.total_paid).toFixed(2)}</span>
                                                </div>
                                                
                                                {{-- Divider --}}
                                                <div class="border-top border-bottom-0 my-3"></div>
                                                
                                                {{-- Totals --}}
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold"><i class="ti ti-calculator me-2"></i>Total Amount:</span>
                                                    <span class="fw-bold">₱${parseFloat(paymentSummary.total_amount).toFixed(2)}</span>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between align-items-center ${paymentSummary.remaining_balance > 0 ? 'text-danger' : 'text-success'} fw-bold">
                                                    <span><i class="ti ti-wallet me-2"></i>Remaining Balance:</span>
                                                    <span>₱${parseFloat(paymentSummary.remaining_balance).toFixed(2)}</span>
                                                </div>
                                            </div>
                                            
                                            {{-- Payment Status Badge --}}
                                            <div class="text-start mt-3 pt-2 border-top">
                                                <span class="badge ${getPaymentStatusBadgeClass(booking.payment_status)} px-3 py-2">
                                                    <i class="ti ti-circle-check me-1"></i>
                                                    ${booking.payment_status.replace('_', ' ').toUpperCase()}
                                                </span>
                                                <small class="d-block text-muted mt-2">
                                                    <i class="ti ti-tag me-1"></i>
                                                    Payment Type: ${paymentTypeDisplay}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                {{-- Payment History --}}
                                ${paymentHistoryHtml ? `
                                    <div class="col-12 mt-3">
                                        <div class="card border">
                                            <div class="card-header bg-transparent py-2">
                                                <h6 class="mb-0 fw-semibold">
                                                    <i class="ti ti-history me-2"></i>Payment History
                                                </h6>
                                            </div>
                                            <div class="card-body p-2">
                                                ${paymentHistoryHtml}
                                            </div>
                                        </div>
                                    </div>
                                ` : ''}
                            </div>

                            ${packagesHtml}

                            ${providerType === 'studio' ? `
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary"><i class="ti ti-users me-2"></i>ASSIGNED PHOTOGRAPHERS</h5>
                                    ${assignedPhotographersHtml}
                                </div>
                            ` : ''}

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary"><i class="ti ti-checklist me-2"></i>BOOKING STATUS</h5>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-checklist fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Booking Status</label>
                                            <p class="mb-0 fw-medium"><span class="badge ${getStatusBadgeClassForBooking(booking.status)}">${booking.status.replace('_', ' ').toUpperCase()}</span></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-credit-card fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Payment Status</label>
                                            <p class="mb-0 fw-medium">
                                                <span class="badge ${getPaymentStatusBadgeClass(booking.payment_status)}">${booking.payment_status.replace('_', ' ').toUpperCase()}</span> | 
                                                <span class="text-muted">${paymentTypeDisplay}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#bookingReference').text(booking.booking_reference);
                $('#bookingModalBody').html(modalContent);
                loadIcons();
            }

            // Helper function for booking status badge
            function getStatusBadgeClassForBooking(status) {
                const badgeClasses = {
                    'pending': 'badge-soft-warning',
                    'confirmed': 'badge-soft-success',
                    'in_progress': 'badge-soft-info',
                    'completed': 'badge-soft-secondary',
                    'cancelled': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-secondary';
            }

            // Helper function for payment status badge
            function getPaymentStatusBadgeClass(status) {
                const badgeClasses = {
                    'pending': 'badge-soft-warning',
                    'partially_paid': 'badge-soft-info',
                    'paid': 'badge-soft-success',
                    'failed': 'badge-soft-danger',
                    'refunded': 'badge-soft-secondary',
                    'cancelled': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-secondary';
            }

            // Cancel booking function
            function cancelBooking(bookingId) {
                $.ajax({
                    url: '{{ route("client.booking.cancel", ":id") }}'.replace(':id', bookingId),
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Cancelling...',
                            text: 'Please wait while we process your cancellation',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Booking Cancelled',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Cancellation Failed',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while cancelling the booking. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // Helper functions
            function getStatusBadgeClass(status) {
                const badgeClasses = {
                    'assigned': 'badge-soft-info',
                    'confirmed': 'badge-soft-success',
                    'completed': 'badge-soft-secondary',
                    'cancelled': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-secondary';
            }

            function getStatusTextClass(status) {
                const textClasses = {
                    'pending': 'text-warning',
                    'confirmed': 'text-success',
                    'in_progress': 'text-info',
                    'completed': 'text-secondary',
                    'cancelled': 'text-danger'
                };
                return textClasses[status] || 'text-secondary';
            }

            function getPaymentStatusTextClass(status) {
                const textClasses = {
                    'pending': 'text-warning',
                    'partially_paid': 'text-info',
                    'paid': 'text-success',
                    'failed': 'text-danger',
                    'refunded': 'text-secondary',
                    'cancelled': 'text-danger'
                };
                return textClasses[status] || 'text-secondary';
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

            function loadPendingConfirmations() {
                $.ajax({
                    url: '{{ route("client.pending-confirmations") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            renderPendingConfirmations(response.pending_confirmations);
                        } else {
                            $('#pendingConfirmationsContainer').html(`
                                <div class="alert alert-warning mb-0">
                                    <i class="ti ti-alert-circle me-2"></i>
                                    Failed to load pending confirmations.
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $('#pendingConfirmationsContainer').html(`
                            <div class="alert alert-danger mb-0">
                                <i class="ti ti-alert-circle me-2"></i>
                                Error loading pending confirmations. Please refresh the page.
                            </div>
                        `);
                    }
                });
            }

            // Render pending confirmations
            function renderPendingConfirmations(confirmations) {
                if (!confirmations || confirmations.length === 0) {
                    $('#pendingConfirmationsContainer').html(`
                        <div class="text-center py-4">
                            <p class="mb-0 text-muted">No pending photographer confirmations</p>
                            <small class="text-muted">All photographers have been confirmed or no photographers are on-site yet.</small>
                        </div>
                    `);
                    return;
                }
                
                let html = '<div class="row">';
                
                confirmations.forEach(function(item) {
                    const photographer = item.photographer;
                    const booking = item.booking;
                    const studio = item.studio;
                    
                    // Format date
                    const eventDate = new Date(booking.event_date);
                    const formattedDate = eventDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    // Get photographer profile photo
                    let photoUrl = '{{ asset("assets/images/users/user-3.jpg") }}';
                    if (photographer && photographer.profile_photo) {
                        photoUrl = '{{ asset("storage/") }}/' + photographer.profile_photo;
                    }
                    
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border h-100">
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <h6 class="mb-1 fw-semibold">${photographer ? photographer.first_name + ' ' + photographer.last_name : 'Photographer'}</h6>
                                            <p class="mb-0 small text-muted">
                                                <i class="ti ti-building-store me-1"></i>${studio ? studio.studio_name : 'Studio'}
                                            </p>
                                        </div>
                                        <div class="col">
                                            <span class="badge badge-soft-warning mb-2">Awaiting Confirmation</span>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-light p-3 rounded mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Booking:</span>
                                            <span class="fw-medium small">${booking.booking_reference}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Event:</span>
                                            <span class="fw-medium small">${booking.event_name}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted small">Date:</span>
                                            <span class="fw-medium small">${formattedDate}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">Time:</span>
                                            <span class="fw-medium small">${booking.start_time} - ${booking.end_time}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-warning py-2 mb-3">
                                        <i class="ti ti-map-pin me-1"></i>
                                        <small>Photographer marked as on-site. Please confirm their presence.</small>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button class="btn btn-primary confirm-photographer-btn" 
                                                data-assignment-id="${item.id}"
                                                data-photographer-name="${photographer ? photographer.first_name + ' ' + photographer.last_name : 'Photographer'}"
                                                data-booking-ref="${booking.booking_reference}">
                                            <i class="ti ti-check-circle me-1"></i> Confirm On-Site Presence
                                        </button>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <textarea class="form-control form-control-sm confirmation-notes-${item.id}" 
                                                placeholder="Optional notes (e.g., photographer arrived on time, etc.)" 
                                                rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                
                $('#pendingConfirmationsContainer').html(html);
                
                // Bind confirmation buttons
                $('.confirm-photographer-btn').off('click').on('click', function() {
                    const assignmentId = $(this).data('assignment-id');
                    const photographerName = $(this).data('photographer-name');
                    const bookingRef = $(this).data('booking-ref');
                    const notes = $(`.confirmation-notes-${assignmentId}`).val();
                    
                    confirmPhotographerPresence(assignmentId, photographerName, bookingRef, notes);
                });
            }

            // Confirm photographer presence
            function confirmPhotographerPresence(assignmentId, photographerName, bookingRef, notes) {
                Swal.fire({
                    title: 'Confirm Photographer On-Site',
                    html: `
                        <p>Please confirm that photographer <strong>${photographerName}</strong> is physically present at your event location for booking <strong>${bookingRef}</strong>.</p>
                        <p class="text-warning small">
                            <i class="ti ti-alert-triangle me-1"></i>
                            This confirmation verifies that the photographer has arrived on-site. Only confirm if they are actually present.
                        </p>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Confirm Presence',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("client.confirm-photographer", ":assignmentId") }}'.replace(':assignmentId', assignmentId),
                            type: 'POST',
                            data: {
                                confirmation_notes: notes,
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Confirming...',
                                    text: 'Please wait',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Confirmed!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    }).then(() => {
                                        loadPendingConfirmations(); // Reload the list
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
                                let message = 'Failed to confirm photographer. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message,
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        });
                    }
                });
            }

            // Call on page load
            loadPendingConfirmations();

            // Load downpayment percentages for all bookings in the table
            function loadDownpaymentPercentages() {
                $('.downpayment-label').each(function() {
                    const $label = $(this);
                    const bookingId = $label.data('booking-id');
                    const bookingType = $label.data('booking-type');
                    const providerId = $label.data('provider-id');
                    
                    if (bookingType === 'studio') {
                        // For studio bookings, we need to fetch the percentage
                        // You can either pre-load this data or make an API call
                        // Option 1: Use data attribute if available (recommended - add to booking object)
                        // Option 2: Make an API call (less efficient)
                        
                        // For now, we'll use the value from the booking object if available
                        // This requires adding downpayment_percentage to the booking object in the controller
                        
                        // Since we don't have it in the current implementation, 
                        // we'll set a default and update when booking details are loaded
                        $label.text('30% Downpayment');
                    } else {
                        $label.text('30% Downpayment');
                    }
                });
            }

            // Call on page load
            loadDownpaymentPercentages();

            // Initialize icons
            loadIcons();
        });
    </script>
@endsection