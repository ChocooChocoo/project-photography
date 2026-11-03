@extends('layouts.owner.app')
@section('title', 'Booking History')

{{-- Content --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Booking History</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                                <button class="btn btn-danger d-none">Delete</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Client Name</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Service</th>
                                        <th data-table-sort>Package</th>
                                        <th data-table-sort>Date & Time</th>
                                        <th data-table-sort>Booking Status</th>
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
                                            <td>
                                                @if($booking->packages->count() > 0)
                                                    {{ $booking->packages->first()->package_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
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
                                                        'confirmed' => 'badge-soft-success',
                                                        'in_progress' => 'badge-soft-info',
                                                        'completed' => 'badge-soft-secondary',
                                                        'cancelled' => 'badge-soft-danger'
                                                    ][$booking->status] ?? 'badge-soft-secondary';
                                                @endphp
                                                <span class="badge {{ $statusBadge }} fs-8 px-1 w-100 text-uppercase">{{ str_replace('_', ' ', $booking->status) }}</span>
                                            </td>
                                            <td>₱{{ number_format($booking->total_amount, 2) }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="#" class="btn btn-sm view-booking-history-btn" data-booking-id="{{ $booking->id }}" data-bs-toggle="modal" data-bs-target="#bookingHistoryModal">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i data-lucide="archive-x" class="fs-20 mb-2"></i>
                                                    <p class="mb-0">No booking history found</p>
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

    {{-- BOOKING HISTORY DETAILS MODAL --}}
    <div class="modal fade" id="bookingHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="bookingHistoryModalLabel">
                        Booking Details - <span id="historyBookingReference">Loading...</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="bookingHistoryModalBody">
                        <div class="text-center py-5">
                            <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                            <p class="mt-3">Loading booking details...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentHistoryBookingId = null;
            const historyModal = new bootstrap.Modal(document.getElementById('bookingHistoryModal'));

            // View booking history details
            $(document).on('click', '.view-booking-history-btn', function() {
                currentHistoryBookingId = $(this).data('booking-id');
                loadHistoryBookingDetails(currentHistoryBookingId);
                historyModal.show();
            });

            // Load booking history details
            function loadHistoryBookingDetails(bookingId) {
                $.ajax({
                    url: '{{ route("owner.booking.details", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        $('#bookingHistoryModalBody').html(`
                            <div class="text-center py-5">
                                <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                                <p class="mt-3">Loading booking details...</p>
                            </div>
                        `);
                    },
                    success: function(response) {
                        if (response.success) {
                            renderHistoryBookingDetails(response);
                        } else {
                            showHistoryError('Error loading booking details');
                        }
                    },
                    error: function(xhr) {
                        showHistoryError('Error loading booking details. Please try again.');
                    }
                });
            }

            // Render history booking details (simplified read-only version)
            function renderHistoryBookingDetails(data) {
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
                                <span class="fw-medium">₱${parseFloat(pkg.package_price).toFixed(2)}${parseFloat(pkg.discount_amount) > 0 ? ` <small class="text-success">(-₱${parseFloat(pkg.discount_amount).toFixed(2)} discount)</small>` : ''}</span>
                            </div>
                        `;
                    });
                }

                const totalPaid = payments ? payments.reduce((sum, payment) => {
                    return payment.status === 'succeeded' ? sum + parseFloat(payment.amount) : sum;
                }, 0) : 0;

                const modalContent = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="fw-semibold text-primary mb-3">Booking Information</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="receipt" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Booking Reference</label>
                                                <p class="mb-0 fw-medium">${booking.booking_reference}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex">
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
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Final Status</label>
                                                <div>
                                                    <span class="badge ${data.status_badge_class} fs-7 px-3 py-2">
                                                        ${booking.status.replace('_', ' ').toUpperCase()}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="fw-semibold text-primary mb-3">Client Information</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex">
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
                                        <div class="d-flex">
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
                                        <div class="d-flex">
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
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <h6 class="fw-semibold text-primary mb-3">Event Details</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex">
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
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="clock" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Event Time</label>
                                                <p class="mb-0 fw-medium">${booking.start_time} - ${booking.end_time}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Event Location</label>
                                                <p class="mb-0 fw-medium">${booking.venue_name || booking.street || 'N/A'}, ${booking.city || 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="fw-semibold text-primary mb-3">Payment Summary</h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Total Amount:</span>
                                        <span class="fw-bold">₱${parseFloat(booking.total_amount).toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Amount Paid:</span>
                                        <span class="fw-medium text-success">₱${totalPaid.toFixed(2)}</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top">
                                        <span class="fw-semibold">Remaining Balance:</span>
                                        <span class="fw-bold ${(parseFloat(booking.total_amount) - totalPaid) > 0 ? 'text-danger' : 'text-success'}">
                                            ₱${(parseFloat(booking.total_amount) - totalPaid).toFixed(2)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6 class="fw-semibold text-primary mb-3">Package Details</h6>
                                ${packagesHtml || '<p class="text-muted">No packages available</p>'}
                            </div>
                        </div>
                    </div>
                `;

                $('#historyBookingReference').text(booking.booking_reference);
                $('#bookingHistoryModalBody').html(modalContent);
                loadIcons();
            }

            function showHistoryError(message) {
                $('#bookingHistoryModalBody').html(`
                    <div class="text-center py-5">
                        <i data-lucide="alert-circle" class="fs-20 text-danger mb-3"></i>
                        <p class="text-danger">${message}</p>
                        <button class="btn btn-sm btn-primary mt-2" data-bs-dismiss="modal">Close</button>
                    </div>
                `);
                loadIcons();
            }

            function loadIcons() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            loadIcons();
        });
    </script>
@endsection