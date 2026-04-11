@extends('layouts.owner.app')
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
                                            <td>PHP {{ number_format($booking->total_amount, 2) }}</td>
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
                                            <td colspan="10" class="text-center py-4">
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
            </div>
        </div>
    </div>

    {{-- UPDATE STATUS MODAL --}}
    <div class="modal fade" id="updateStatusModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="updateStatusModalLabel">Update Booking Status</h5>
                    <button type="button" class="btn-close" id="closeUpdateStatusModal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div id="statusUpdateAlert" class="alert alert-light border mb-4">
                        <div class="row align-items-center g-0">
                            <div class="col-md-12">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-semibold" id="statusBookingInfo">Loading...</h6>
                                        <p class="mb-0 text-muted small" id="statusBookingDetails">Loading booking details...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="bookingStatus" class="form-label fw-semibold">Booking Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="bookingStatus" name="status">
                            <option value="">Select Status</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="cancellationReasonGroup">
                        <label for="cancellationReason" class="form-label fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cancellationReason" rows="3" placeholder="Please provide the reason for cancellation..."></textarea>
                    </div>

                    <div class="alert alert-warning d-none" id="paymentWarningAlert">
                        <div class="d-flex">
                            <i data-lucide="alert-triangle" class="me-2"></i>
                            <div>
                                <strong>Cannot mark as completed</strong>
                                <p class="mb-0 small" id="paymentWarningMessage">
                                    This booking does not yet meet the completion requirements.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="cancelUpdateStatusModal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ASSIGN MODAL (Nested inside booking modal) --}}
    <div class="modal fade" id="assignPhotographerModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="assignPhotographerModalLabel">Assign Photographer</h5>
                    <button type="button" class="btn-close" id="closeAssignModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div id="assignModalAlert" class="alert alert-light border mb-4">
                        <div class="row align-items-center g-0">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h6 class="mb-1 fw-semibold" id="assignBookingInfo">Loading...</h6>
                                        <p class="mb-0 text-muted small" id="assignBookingDetails">Loading booking details...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end mt-2 mt-md-0">
                                <span class="badge badge-soft-warning" id="assignBookingStatus">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <h5 class="fw-semibold text-primary mb-1">Available Photographers</h5>
                        <small class="text-muted">Select photographer(s) to assign. You can assign single or multiple photographers.</small>
                    </div>
                    
                    <div id="photographerListContainer">
                        <div class="text-center py-4">
                            <div class="loading-spinner" style="margin: 0 auto;"></div>
                            <p class="mt-2">Loading photographers...</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="assignmentNotes" class="form-label">Assignment Notes (Optional)</label>
                        <textarea class="form-control" id="assignmentNotes" rows="3" placeholder="Add any special instructions or notes for the photographers..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="cancelAssignModal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmAssignment">Confirm Assignment</button>
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
            let selectedPhotographers = [];
            let currentBookingData = null;
            let currentAssignmentInfo = null;
            
            // Initialize Bootstrap modal instances
            const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
            const assignModal = new bootstrap.Modal(document.getElementById('assignPhotographerModal'));
            const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));

            function getStatusBadgeClass(status) {
                const badgeClasses = {
                    'assigned': 'badge-soft-info',
                    'confirmed': 'badge-soft-primary',
                    'completed': 'badge-soft-success',
                    'cancelled': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-primary';
            }

            function getStatusTextClass(status) {
                const textClasses = {
                    'pending': 'text-warning',
                    'confirmed': 'text-primary',
                    'in_progress': 'text-info',
                    'completed': 'text-success',
                    'cancelled': 'text-danger'
                };
                return textClasses[status] || 'text-primary';
            }

            function loadIcons() {
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            function showError(message) {
                $('#bookingModalBody').html(`
                    <div class="text-center py-5">
                        <i data-lucide="alert-circle" class="fs-20 text-danger mb-3"></i>
                        <p class="text-danger">${message}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="location.reload()">
                            <i data-lucide="refresh-cw" class="me-1"></i> Retry
                        </button>
                    </div>
                `);
                loadIcons();
            }

            function openUpdateStatusModal(bookingId, data) {
                const booking = data.booking;
                const availableStatuses = data.available_statuses || {};
                const canMarkCompleted = data.can_mark_completed || false;
                const completionBlockers = data.completion_blockers || [];
                const completionBlockReason = data.completion_block_reason || 'This booking does not yet meet the completion requirements.';
                
                // Set booking info
                $('#statusBookingInfo').text(`${booking.booking_reference} - ${data.category ? data.category.category_name : 'N/A'}`);
                $('#statusBookingDetails').text(`${new Date(booking.event_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })} • ${booking.start_time}`);
                
                // Populate status dropdown
                const $statusSelect = $('#bookingStatus');
                $statusSelect.empty().append('<option value="">Select Status</option>');
                
                $.each(availableStatuses, function(value, label) {
                    const isCompleted = value === 'completed';
                    const isDisabled = isCompleted && !canMarkCompleted;
                    const disabledLabel = completionBlockers.length > 0
                        ? ` (${completionBlockers[0]})`
                        : ' (Completion requirements not met)';
                    
                    $statusSelect.append($('<option>', {
                        value: value,
                        text: label + (isDisabled ? disabledLabel : ''),
                        disabled: isDisabled
                    }));
                });
                
                // Reset form
                $('#cancellationReasonGroup').addClass('d-none');
                $('#paymentWarningAlert').addClass('d-none');
                $('#cancellationReason').val('');
                $('#paymentWarningMessage').text(completionBlockers.length > 0 ? completionBlockers.join(' ') : completionBlockReason);
                
                // Handle status change
                $statusSelect.off('change').on('change', function() {
                    const selectedStatus = $(this).val();
                    
                    if (selectedStatus === 'cancelled') {
                        $('#cancellationReasonGroup').removeClass('d-none');
                    } else {
                        $('#cancellationReasonGroup').addClass('d-none');
                    }
                    
                    if (selectedStatus === 'completed' && !canMarkCompleted) {
                        $('#paymentWarningAlert').removeClass('d-none');
                    } else {
                        $('#paymentWarningAlert').addClass('d-none');
                    }
                });
                
                // Hide booking modal and show status modal
                bookingModal.hide();
                setTimeout(() => {
                    updateStatusModal.show();
                }, 300);
            }

            // Update Status button click handler
            $(document).on('click', '.update-status-btn', function() {
                const bookingId = $(this).data('booking-id');
                
                if (currentBookingData) {
                    openUpdateStatusModal(bookingId, currentBookingData);
                } else {
                    $.ajax({
                        url: '{{ route("owner.booking.details", ":id") }}'.replace(':id', bookingId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                currentBookingData = response;
                                openUpdateStatusModal(bookingId, response);
                            }
                        }
                    });
                }
            });

            // Confirm Status Update
            $('#confirmStatusUpdate').click(function() {
                const status = $('#bookingStatus').val();
                
                if (!status) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Status Selected',
                        text: 'Please select a booking status.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                const cancellationReason = $('#cancellationReason').val();
                
                Swal.fire({
                    title: 'Update Booking Status',
                    text: `Are you sure you want to mark this booking as ${status.replace('_', ' ')}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, update status',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("owner.booking.update.status", ":id") }}'.replace(':id', currentBookingId),
                            type: 'PUT',
                            data: {
                                status: status,
                                cancellation_reason: cancellationReason,
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function() {
                                $('#confirmStatusUpdate').prop('disabled', true).html('<span class="loading-spinner"></span> Updating...');
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    }).then(() => {
                                        updateStatusModal.hide();
                                        setTimeout(() => {
                                            bookingModal.show();
                                            loadBookingDetails(currentBookingId);
                                        }, 300);
                                        
                                        // Update table row status
                                        const $row = $(`tr[data-booking-id="${response.booking.id}"]`);
                                        if ($row.length) {
                                            const $statusCell = $row.find('td:eq(4)');
                                            $statusCell.html(`
                                                <span class="badge ${response.booking.status_badge} fs-8 px-1 w-100 text-uppercase">
                                                    ${response.booking.status_display}
                                                </span>
                                            `);
                                        }
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
                                let message = 'Failed to update booking status. Please try again.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message,
                                    confirmButtonColor: '#3475db'
                                });
                            },
                            complete: function() {
                                $('#confirmStatusUpdate').prop('disabled', false).text('Update Status');
                                loadIcons();
                            }
                        });
                    }
                });
            });

            // Close status modal handlers
            $('#closeUpdateStatusModal, #cancelUpdateStatusModal').click(function() {
                updateStatusModal.hide();
                setTimeout(() => {
                    bookingModal.show();
                }, 300);
            });

            $(document).on('click', '.view-booking-btn', function() {
                currentBookingId = $(this).data('booking-id');
                loadBookingDetails(currentBookingId);
                bookingModal.show();
            });

            function loadBookingDetails(bookingId) {
                $.ajax({
                    url: '{{ route("owner.booking.details", ":id") }}'.replace(':id', bookingId),
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
                            currentBookingData = response; // ← FIXED: Store the data
                            renderBookingDetails(response);
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
                const assignedPhotographers = data.assignedPhotographers;
                const availableStatuses = data.available_statuses || {}; // ← FIXED: Define variable

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

                let assignedPhotographersHtml = '';
                if (assignedPhotographers && assignedPhotographers.length > 0) {
                    assignedPhotographers.forEach(assignment => {
                        const photographer = assignment.photographer;
                        const initials = (photographer.first_name.charAt(0) + photographer.last_name.charAt(0)).toUpperCase();
                        const studioPhotographer = assignment.studio_photographer;
                        assignedPhotographersHtml += `
                            <div class="d-flex align-items-center justify-content-between mb-2 p-2 border rounded">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xl me-2">
                                        <span class="photographer-avatar avatar-title bg-info-subtle text-info rounded-circle me-2">${initials}</span>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">${photographer.first_name} ${photographer.last_name}</h6>
                                        <small class="text-muted">${studioPhotographer ? studioPhotographer.position : 'Photographer'}</small>
                                    </div>
                                </div>
                                <span class="badge ${getStatusBadgeClass(assignment.status)}">${assignment.status}</span>
                            </div>
                        `;
                    });
                } else {
                    assignedPhotographersHtml = `
                        <div class="text-center py-3 border rounded">
                            <i data-lucide="users" class="fs-20 text-muted mb-2"></i>
                            <p class="mb-0 text-muted">No photographers assigned yet</p>
                        </div>
                    `;
                }

                const totalPaid = payments ? payments.reduce((sum, payment) => {
                    return payment.status === 'succeeded' ? sum + parseFloat(payment.amount) : sum;
                }, 0) : 0;
                const remainingBalance = parseFloat(booking.total_amount) - totalPaid;

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
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge ${data.status_badge_class} fs-7 me-2">
                                                            ${booking.status.replace('_', ' ').toUpperCase()}
                                                        </span>
                                                        ${data.can_owner_complete ? `
                                                            <button class="btn btn-sm btn-success complete-booking-btn" data-booking-id="${booking.id}">
                                                                <i data-lucide="check-circle" class="me-1"></i>Complete Booking
                                                            </button>
                                                        ` : ''}
                                                    </div>
                                                    ${booking.status === 'in_progress' && !data.can_owner_complete ? `
                                                        <small class="text-muted d-block mt-1">
                                                            <i data-lucide="info" class="me-1" style="width: 12px; height: 12px;"></i>
                                                            ${(data.completion_blockers && data.completion_blockers.length > 0)
                                                                ? data.completion_blockers.join(' ')
                                                                : 'This booking does not yet meet the completion requirements.'}
                                                        </small>
                                                    ` : ''}
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
                                                        <span class="badge ${booking.deposit_policy ? 'badge-soft-primary' : 'badge-soft-success'} px-2 fw-medium">${booking.deposit_policy ? 'Yes' : 'No'}</span>
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

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-none">
                                <div class="card-body p-0">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <h6 class="card-title mb-0 fw-semibold text-uppercase small text-primary">
                                                Assigned Photographers
                                            </h6>
                                            <small class="text-muted">
                                                Package allows: <span class="fw-medium">${data.max_photographers} photographer(s)</span> | 
                                                Currently assigned: <span class="fw-medium">${data.current_assigned_count}</span>
                                            </small>
                                        </div>
                                        ${!['in_progress', 'completed'].includes(booking.status) && data.current_assigned_count < data.max_photographers ? `
                                            <button class="btn btn-primary btn-sm" id="assignPhotographerBtn">
                                                <i data-lucide="user-plus" class="me-1"></i> Assign Photographer
                                            </button>
                                        ` : data.current_assigned_count >= data.max_photographers ? `
                                            <span class="badge badge-soft-info px-3 py-2">
                                                <i data-lucide="check-circle" class="me-1" style="width: 16px;"></i>
                                                Maximum photographers assigned
                                            </span>
                                        ` : ''}
                                    </div>
                                    <div id="assignedPhotographersList">
                                        ${assignedPhotographersHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#bookingReference').text(booking.booking_reference);
                $('#bookingModalBody').html(modalContent);
                loadIcons();
                
                // Rebind the assign button click event
                $('#assignPhotographerBtn').off('click').on('click', function() {
                    loadAvailablePhotographers(currentBookingId);
                    bookingModal.hide(); // Hide the booking modal first
                    setTimeout(() => {
                        assignModal.show(); // Show assign modal after a short delay
                    }, 300);
                });
            }

            // Complete booking (owner final step)
            $(document).on('click', '.complete-booking-btn', function() {
                const bookingId = $(this).data('booking-id');
                
                Swal.fire({
                    title: 'Complete Booking',
                    text: 'Are you sure you want to mark this booking as completed? This action cannot be undone.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, complete booking',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("owner.booking.complete", ":id") }}'.replace(':id', bookingId),
                            type: 'PUT',
                            data: {
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
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    }).then(() => {
                                        bookingModal.hide();
                                        // Reload the page to update the table
                                        location.reload();
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
                                let message = 'Failed to complete booking. Please try again.';
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
            });

            // Assign photographer button click handler
            $(document).on('click', '#assignPhotographerBtn', function() {
                loadAvailablePhotographers(currentBookingId);
                bookingModal.hide(); // Hide the booking modal first
                setTimeout(() => {
                    assignModal.show(); // Show assign modal after a short delay
                }, 300);
            });

            // Close assign modal button
            $('#closeAssignModal').click(function() {
                assignModal.hide();
                setTimeout(() => {
                    bookingModal.show(); // Show booking modal again
                }, 300);
            });

            // Cancel assign modal button
            $('#cancelAssignModal').click(function() {
                assignModal.hide();
                setTimeout(() => {
                    bookingModal.show(); // Show booking modal again
                }, 300);
            });

            // Load available photographers
            function loadAvailablePhotographers(bookingId) {
                $.ajax({
                    url: '{{ route("owner.booking.available.photographers", ":id") }}'.replace(':id', bookingId),
                    type: 'GET',
                    beforeSend: function() {
                        $('#photographerListContainer').html(`
                            <div class="text-center py-4">
                                <div class="loading-spinner" style="margin: 0 auto;"></div>
                                <p class="mt-2">Loading photographers...</p>
                            </div>
                        `);
                        selectedPhotographers = [];
                        currentAssignmentInfo = null;
                    },
                    success: function(response) {
                        if (response.success) {
                            currentAssignmentInfo = response.assignment_info || null;
                            renderAvailablePhotographers(response);
                        } else {
                            $('#photographerListContainer').html(`
                                <div class="alert alert-danger">
                                    <i data-lucide="alert-circle" class="me-2"></i>
                                    ${response.message}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        $('#photographerListContainer').html(`
                            <div class="alert alert-danger">
                                <i data-lucide="alert-circle" class="me-2"></i>
                                Error loading photographers. Please try again.
                            </div>
                        `);
                    }
                });
            }

            // Render available photographers
            function renderAvailablePhotographers(data) {
                const booking = data.booking;
                const photographers = data.photographers;
                const assignmentInfo = data.assignment_info;
                const availablePhotographers = photographers.filter(photographer => photographer.is_available === true);
                const maxSelectable = assignmentInfo.is_initial_assignment
                    ? assignmentInfo.required_photographers
                    : assignmentInfo.remaining_needed;

                // Update booking info
                $('#assignBookingInfo').text(`${booking.reference} - ${booking.category}`);
                $('#assignBookingDetails').text(`${booking.event_date} • ${booking.event_name}`);
                
                // Show requirement info in status badge
                if (assignmentInfo.is_initial_assignment) {
                    $('#assignBookingStatus').text(`Initial Assignment: Need ${assignmentInfo.required_photographers} photographers`);
                } else {
                    $('#assignBookingStatus').text(`Adding: Need ${assignmentInfo.remaining_needed} more of ${assignmentInfo.required_photographers}`);
                }

                // Show requirement info alert
                const requirementInfoHtml = `
                    <div class="alert alert-${assignmentInfo.is_initial_assignment ? 'warning' : 'info'} border-0 bg-light-${assignmentInfo.is_initial_assignment ? 'warning' : 'info'} mb-4">
                        <div class="d-flex align-items-center">
                            <i data-lucide="${assignmentInfo.is_initial_assignment ? 'alert-triangle' : 'info'}" class="me-2" style="width: 20px;"></i>
                            <div>
                                <strong>Package Photographer Requirement</strong><br>
                                <small>
                                    This package <span class="fw-bold text-primary">REQUIRES</span> exactly <span class="fw-bold">${assignmentInfo.required_photographers}</span> photographer(s).<br>
                                    ${assignmentInfo.is_initial_assignment 
                                        ? `You must select <span class="fw-bold">ALL ${assignmentInfo.required_photographers}</span> photographers now.` 
                                        : `Currently assigned: <span class="fw-bold">${assignmentInfo.current_assigned}</span>. 
                                        You need to select <span class="fw-bold">${assignmentInfo.remaining_needed}</span> more photographer(s) to complete the requirement.`}
                                </small>
                            </div>
                        </div>
                    </div>
                `;

                if (photographers.length === 0) {
                    $('#photographerListContainer').html(requirementInfoHtml + `
                        <div class="text-center py-4">
                            <i data-lucide="users" class="fs-20 text-muted mb-2"></i>
                            <p class="mb-2">No photographers found for this studio.</p>
                            <small class="text-muted">Please check back later or add more photographers to your studio.</small>
                        </div>
                    `);
                    $('#confirmAssignment').prop('disabled', true);
                    return;
                }

                let availabilitySummaryHtml = '';
                if (availablePhotographers.length === 0) {
                    availabilitySummaryHtml = `
                        <div class="alert alert-danger mb-3">
                            <i data-lucide="alert-circle" class="me-1"></i>
                            No photographers are currently assignable for this booking. Review the conflict reasons below.
                        </div>
                    `;
                } else if (availablePhotographers.length < maxSelectable) {
                    availabilitySummaryHtml = `
                        <div class="alert alert-warning mb-3">
                            <i data-lucide="alert-triangle" class="me-1"></i>
                            Only ${availablePhotographers.length} photographer(s) are currently available, but this booking still needs ${maxSelectable}.
                        </div>
                    `;
                }

                let photographersHtml = '<div class="photographer-list">';
                photographers.forEach(photographer => {
                    const initials = photographer.name.split(' ').map(n => n.charAt(0)).join('').toUpperCase();
                    const photographerId = photographer.id.toString();
                    const isSelected = selectedPhotographers.includes(photographerId);
                    const hasReachedSelectionLimit = selectedPhotographers.length >= maxSelectable && !isSelected;
                    const isUnavailable = photographer.is_available !== true;
                    const isDisabled = isUnavailable || hasReachedSelectionLimit;
                    const conflictDetails = Array.isArray(photographer.availability_conflicts)
                        ? photographer.availability_conflicts
                        : [];
                    const availabilityDetails = conflictDetails.length > 0
                        ? conflictDetails.map(conflict => `<div class="small text-muted">${conflict.message || photographer.availability_reason}</div>`).join('')
                        : `<div class="small text-muted">${photographer.availability_reason || 'Available for assignment.'}</div>`;
                    
                    photographersHtml += `
                        <div class="card border mb-2 photographer-card ${isSelected ? 'selected border-primary' : ''} ${isUnavailable ? 'bg-light-subtle border-danger-subtle' : ''}" data-photographer-id="${photographer.id}" data-is-available="${photographer.is_available ? '1' : '0'}">
                            <div class="card-body p-0">
                                <div class="row align-items-center">
                                    <div class="col-auto px-4">
                                        <div class="form-check form-check-primary">
                                            <input class="form-check-input photographer-checkbox" 
                                                type="checkbox" 
                                                id="photographer_${photographer.id}" 
                                                value="${photographer.id}"
                                                ${isSelected ? 'checked' : ''}
                                                ${isDisabled ? 'disabled' : ''}>
                                            <label class="form-check-label visually-hidden" for="photographer_${photographer.id}">Assign ${photographer.name}</label>
                                        </div>
                                    </div>
                                    <div class="col py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xl flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle">
                                                    ${initials}
                                                </span>
                                            </div>
                                            <div class="ms-3">
                                                <h4 class="mb-0 fw-semibold">${photographer.name}</h4>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <small class="mb-1">${photographer.position || 'Photographer'}</small>
                                                </div>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <span class="badge badge-soft-success text-success small mb-1">${photographer.status}</span>
                                                    <small class="text-muted ms-2">${photographer.years_experience} years experience</small>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="${getAvailabilityBadgeClass(photographer.availability_status)} small mb-1">${getAvailabilityLabel(photographer.availability_status)}</span>
                                                    ${availabilityDetails}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                photographersHtml += '</div>';

                $('#photographerListContainer').html(requirementInfoHtml + availabilitySummaryHtml + photographersHtml);
                
                // Update confirm button text based on requirement
                updateConfirmButtonState(assignmentInfo);
                loadIcons();
            }

            // Add new helper function
            function updateConfirmButtonState(assignmentInfo) {
                if (!assignmentInfo) {
                    $('#confirmAssignment').prop('disabled', true).text('Confirm Assignment');
                    return;
                }

                const requiredCount = assignmentInfo.is_initial_assignment 
                    ? assignmentInfo.required_photographers 
                    : assignmentInfo.remaining_needed;
                const assignableCount = assignmentInfo.assignable_photographers || 0;

                if (assignableCount < requiredCount) {
                    $('#confirmAssignment').prop('disabled', true).text('Confirm Assignment');
                    $('#selectionWarning').remove();
                    return;
                }
                
                if (selectedPhotographers.length === requiredCount) {
                    $('#confirmAssignment').prop('disabled', false);
                    $('#selectionWarning').remove();
                    
                    // Update button text
                    if (assignmentInfo.is_initial_assignment) {
                        $('#confirmAssignment').text(`Assign All ${requiredCount} Photographers`);
                    } else {
                        $('#confirmAssignment').text(`Add ${requiredCount} Photographer(s)`);
                    }
                } else {
                    $('#confirmAssignment').prop('disabled', true);
                    
                    // Show warning
                    let warningMessage = '';
                    if (assignmentInfo.is_initial_assignment) {
                        warningMessage = `You must select exactly ${requiredCount} photographers for the initial assignment.`;
                    } else {
                        warningMessage = `You need to select exactly ${requiredCount} more photographer(s) to complete the requirement.`;
                    }
                    
                    if ($('#selectionWarning').length === 0) {
                        $('#photographerListContainer').prepend(`
                            <div class="alert alert-warning mb-3" id="selectionWarning">
                                <i data-lucide="alert-triangle" class="me-1"></i>
                                ${warningMessage}
                            </div>
                        `);
                    } else {
                        $('#selectionWarning').html(`
                            <i data-lucide="alert-triangle" class="me-1"></i>
                            ${warningMessage}
                        `);
                    }
                    loadIcons();
                }
            }

            // Photographer checkbox change
            $(document).on('change', '.photographer-checkbox', function() {
                const photographerId = $(this).val();
                const assignmentInfo = currentAssignmentInfo;
                const requiredCount = assignmentInfo
                    ? (assignmentInfo.is_initial_assignment ? assignmentInfo.required_photographers : assignmentInfo.remaining_needed)
                    : 1;
                
                if ($(this).is(':checked')) {
                    if (!selectedPhotographers.includes(photographerId) && selectedPhotographers.length < requiredCount) {
                        selectedPhotographers.push(photographerId);
                    } else if (selectedPhotographers.length >= requiredCount) {
                        // Uncheck if trying to exceed required count
                        $(this).prop('checked', false);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Cannot Select More',
                            text: `You need to select exactly ${requiredCount} photographer(s).`,
                            confirmButtonColor: '#3475db',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        return;
                    }
                } else {
                    const index = selectedPhotographers.indexOf(photographerId);
                    if (index > -1) {
                        selectedPhotographers.splice(index, 1);
                    }
                }
                
                // Update UI
                $('.photographer-card').removeClass('selected border-primary');
                selectedPhotographers.forEach(id => {
                    $(`.photographer-card[data-photographer-id="${id}"]`).addClass('selected border-primary');
                });
                
                // Update confirm button state with current assignment info
                updateConfirmButtonState(assignmentInfo);
            });

            // Confirm assignment
            $('#confirmAssignment').click(function() {
                if (selectedPhotographers.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Photographers Selected',
                        text: 'Please select at least one photographer to assign.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                const assignmentNotes = $('#assignmentNotes').val();

                Swal.fire({
                    title: 'Confirm Assignment',
                    text: `Assign ${selectedPhotographers.length} photographer(s) to this booking?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, assign them',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        assignPhotographers(selectedPhotographers, assignmentNotes);
                    }
                });
            });

            // Assign photographers API call
            function assignPhotographers(photographerIds, notes) {
                $.ajax({
                    url: '{{ route("owner.booking.assign.photographers", ":id") }}'.replace(':id', currentBookingId),
                    type: 'POST',
                    data: {
                        photographer_ids: photographerIds,
                        assignment_notes: notes,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#confirmAssignment').prop('disabled', true).html('<span class="loading-spinner"></span> Assigning...');
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            }).then(() => {
                                assignModal.hide();
                                setTimeout(() => {
                                    bookingModal.show();
                                    loadBookingDetails(currentBookingId);
                                }, 300);
                                // Reset form
                                $('#assignmentNotes').val('');
                                selectedPhotographers = [];
                                currentAssignmentInfo = null;
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
                        const conflictMessage = buildPhotographerConflictMessage(xhr.responseJSON?.photographer_conflicts || []);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: `
                                <div>${xhr.responseJSON?.message || 'Failed to assign photographers. Please try again.'}</div>
                                ${conflictMessage}
                            `,
                            confirmButtonColor: '#3475db'
                        }).then(() => {
                            loadAvailablePhotographers(currentBookingId);
                        });
                    },
                    complete: function() {
                        $('#confirmAssignment').prop('disabled', false).text('Confirm Assignment');
                    }
                });
            }

            // Helper functions
            function getAvailabilityBadgeClass(status) {
                const badgeClasses = {
                    'available': 'badge badge-soft-success text-success',
                    'on_leave': 'badge badge-soft-danger text-danger',
                    'in_progress_conflict': 'badge badge-soft-warning text-warning',
                    'reserved_conflict': 'badge badge-soft-info text-info',
                    'already_assigned': 'badge badge-soft-secondary text-secondary'
                };

                return badgeClasses[status] || 'badge badge-soft-secondary text-secondary';
            }

            function getAvailabilityLabel(status) {
                const labels = {
                    'available': 'Available',
                    'on_leave': 'On Approved Leave',
                    'in_progress_conflict': 'In Progress',
                    'reserved_conflict': 'Reserved',
                    'already_assigned': 'Already Assigned'
                };

                return labels[status] || 'Unavailable';
            }

            function buildPhotographerConflictMessage(conflicts) {
                if (!Array.isArray(conflicts) || conflicts.length === 0) {
                    return '';
                }

                const items = conflicts.map(conflict => {
                    const details = Array.isArray(conflict.availability_conflicts)
                        ? conflict.availability_conflicts.map(detail => `<div class="small text-muted">${detail.message || ''}</div>`).join('')
                        : '';

                    return `
                        <div class="text-start border rounded p-2 mt-2">
                            <div class="fw-semibold">${conflict.availability_reason || 'Unavailable photographer'}</div>
                            ${details}
                        </div>
                    `;
                }).join('');

                return `<div class="mt-3">${items}</div>`;
            }

            function getStatusBadgeClass(status) {
                const badgeClasses = {
                    'assigned': 'badge-soft-info',
                    'confirmed': 'badge-soft-primary',
                    'completed': 'badge-soft-success',
                    'cancelled': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-primary';
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
