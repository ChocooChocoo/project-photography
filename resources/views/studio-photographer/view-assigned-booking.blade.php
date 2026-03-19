@extends('layouts.studio-photographer.app')
@section('title', 'View Assigned Bookings')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h5 class="card-title">Your Assigned Bookings</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search bookings...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control">
                                        <option value="">All Status</option>
                                        <option value="assigned">Assigned</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Booking ID</th>
                                        <th data-table-sort>Client Name</th>
                                        <th data-table-sort>Studio</th>
                                        <th data-table-sort>Event</th>
                                        <th data-table-sort>Date & Time</th>
                                        <th data-table-sort>Amount</th>
                                        <th data-table-sort>Assigned By</th>
                                        <th data-table-sort>Assigned Status</th>
                                        <th data-table-sort>Assigned Date</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assignments as $assignment)
                                        <tr data-assignment-id="{{ $assignment->id }}">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-medium">{{ $assignment->booking->booking_reference ?? 'N/A' }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                @if($assignment->booking && $assignment->booking->client)
                                                    {{ $assignment->booking->client->first_name }} {{ $assignment->booking->client->last_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                {{ $assignment->studio->studio_name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                {{ $assignment->booking->event_name ?? 'N/A' }}
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <p class="mb-1">{{ $assignment->booking ? \Carbon\Carbon::parse($assignment->booking->event_date)->format('M d, Y') : 'N/A' }}</p>
                                                        <p class="mb-0 fs-xxs text-muted">
                                                            {{ $assignment->booking ? $assignment->booking->start_time : 'N/A' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-semibold">
                                                    PHP {{ $assignment->booking ? number_format($assignment->booking->total_amount, 2) : '0.00' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($assignment->assigner)
                                                    {{ $assignment->assigner->first_name }} {{ $assignment->assigner->last_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $statusBadge = [
                                                        'assigned' => 'badge-soft-info',
                                                        'confirmed' => 'badge-soft-primary',
                                                        'on_site' => 'badge-soft-warning',
                                                        'in_progress' => 'badge-soft-info',
                                                        'completed' => 'badge-soft-success',
                                                        'cancelled' => 'badge-soft-danger'
                                                    ][$assignment->status] ?? 'badge-soft-secondary';
                                                @endphp
                                                <span class="badge {{ $statusBadge }} fs-8 px-1 w-100 text-uppercase">
                                                    {{ str_replace('_', ' ', $assignment->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($assignment->assigned_at)->format('M d, Y') }}
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm view-assignment-btn" data-assignment-id="{{ $assignment->id }}" data-bs-toggle="modal" data-bs-target="#assignmentModal">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i data-lucide="calendar-x" class="fs-20 mb-2"></i>
                                                    <p class="mb-0">No assigned bookings found</p>
                                                    <small class="text-muted">You haven't been assigned to any bookings yet.</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="assignments"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- MODAL --}}
    <div class="modal fade" id="assignmentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="assignmentModalLabel">
                        Booking Assignment Details - <span id="assignmentReference">Loading...</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="assignmentModalBody">
                        <div class="text-center py-5">
                            <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                            <p class="mt-3">Loading assignment details...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div id="assignmentActions" class="text-end w-100">
                        <!-- Status update buttons will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- STATUS UPDATE MODAL --}}
    <div class="modal fade" id="statusUpdateModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="statusUpdateModalLabel">Update Assignment Status</h5>
                    <button type="button" class="btn-close" id="closeStatusModal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row align-items-center g-0">
                        <div class="col-md-8 mb-3">
                            <div class="d-flex align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-semibold" id="statusBookingInfo">Loading...</h6>
                                    <p class="mb-0 text-muted small" id="statusBookingDetails">Loading booking details...</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <span class="badge badge-soft-warning" id="statusCurrentStatus">Loading...</span>
                        </div>
                    </div>

                    <div id="statusUpdateContent">
                        <div class="text-center py-4">
                            <div class="loading-spinner" style="margin: 0 auto;"></div>
                            <p class="mt-2">Loading status update form...</p>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" id="cancelStatusModal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Update Status</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentAssignmentId = null;
            let currentStatusAction = null;
            
            // Initialize Bootstrap modal instances
            const assignmentModal = new bootstrap.Modal(document.getElementById('assignmentModal'));
            const statusUpdateModal = new bootstrap.Modal(document.getElementById('statusUpdateModal'));

            // View assignment details
            $(document).on('click', '.view-assignment-btn', function() {
                currentAssignmentId = $(this).data('assignment-id');
                loadAssignmentDetails(currentAssignmentId);
            });
            
            // Load assignment details
            function loadAssignmentDetails(assignmentId) {
                $.ajax({
                    url: '{{ route("assignment.details", ":id") }}'.replace(':id', assignmentId),
                    type: 'GET',
                    beforeSend: function() {
                        $('#assignmentModalBody').html(`
                            <div class="text-center py-5">
                                <div class="loading-spinner" style="width: 3rem; height: 3rem; margin: 0 auto;"></div>
                                <p class="mt-3">Loading assignment details...</p>
                            </div>
                        `);
                        $('#assignmentActions').html('');
                    },
                    success: function(response) {
                        if (response.success) {
                            renderAssignmentDetails(response);
                        } else {
                            showError('Error loading assignment details');
                        }
                    },
                    error: function(xhr) {
                        showError('Error loading assignment details. Please try again.');
                    }
                });
            }
            
            // Render assignment details
            function renderAssignmentDetails(data) {
                const assignment = data.assignment;
                const booking = data.booking;
                const studio = data.studio;
                const assigner = data.assigner;
                
                if (!booking) {
                    showError('Booking information not found');
                    return;
                }
                
                console.log('Assignment Data:', {
                    id: assignment.id,
                    status: assignment.status,
                    on_site_at: assignment.on_site_at,
                    client_confirmed_at: assignment.client_confirmed_at,
                    has_on_site: !!assignment.on_site_at,
                    has_client_confirmed: !!assignment.client_confirmed_at
                });
                
                const client = booking.client;
                const category = booking.category;
                const packages = booking.packages || [];
                const payments = booking.payments || [];
                
                // Calculate payments
                const totalPaid = payments.reduce((sum, payment) => sum + parseFloat(payment.amount), 0);
                const remainingBalance = parseFloat(booking.total_amount) - totalPaid;
                
                // Format packages
                let packagesHtml = '';
                if (packages.length > 0) {
                    packages.forEach(pkg => {
                        packagesHtml += `
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">${pkg.package_name}:</span>
                                <span class="fw-medium">PHP ${parseFloat(pkg.package_price).toFixed(2)}</span>
                            </div>
                        `;
                    });
                }
                
                // ========== NEW: Helper function to check if cancel is allowed ==========
                function canCancel(status) {
                    // Cancellation only allowed in 'assigned' or 'confirmed' states
                    return ['assigned', 'confirmed'].includes(status);
                }
                
                // ========== UPDATED: Status action buttons with cancellation restrictions ==========
                let statusActions = '';

                if (assignment.status === 'assigned') {
                    // Photographer can only accept or reject
                    statusActions = `
                        <button class="btn btn-soft-danger me-2" id="cancelAssignmentBtn">
                            <i data-lucide="x" class="me-1"></i> Reject Assignment
                        </button>
                        <button class="btn btn-primary" id="confirmAssignmentBtn">
                            <i data-lucide="check" class="me-1"></i> Accept Assignment
                        </button>
                    `;
                } 
                else if (assignment.status === 'confirmed' || assignment.status === 'on_site') {
                    const hasOnSite = assignment.on_site_at !== null && assignment.on_site_at !== undefined;
                    const hasClientConfirmed = assignment.client_confirmed_at !== null && assignment.client_confirmed_at !== undefined;
                    const cancelAllowed = canCancel(assignment.status);
                    
                    console.log('Status check:', { 
                        current_status: assignment.status,
                        hasOnSite, 
                        hasClientConfirmed,
                        cancelAllowed
                    });
                    
                    if (!hasOnSite) {
                        // Not marked on-site yet - cancel still allowed (status is 'confirmed')
                        statusActions = `
                            ${cancelAllowed ? 
                                `<button class="btn btn-soft-danger me-2" id="cancelAssignmentBtn">
                                    <i data-lucide="x" class="me-1"></i> Cancel Assignment
                                </button>` : ''
                            }
                            <button class="btn btn-warning" id="onSiteAssignmentBtn">
                                <i data-lucide="map-pin" class="me-1"></i> Mark as On-Site
                            </button>
                        `;
                    } else if (hasOnSite && !hasClientConfirmed) {
                        // Marked on-site but waiting for client confirmation - CANCEL NOT ALLOWED
                        statusActions = `
                            <div class="alert alert-warning mb-3">
                                <i data-lucide="clock" class="me-2"></i>
                                You have marked as on-site. Waiting for client to confirm your presence.
                                <br><small class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Cancellation is not allowed at this stage.</small>
                            </div>
                            <!-- Cancel button intentionally omitted -->
                        `;
                    } else if (hasOnSite && hasClientConfirmed) {
                        // Client confirmed - CANCEL NOT ALLOWED (on_site status)
                        statusActions = `
                            <div class="alert alert-success mb-3">
                                <i data-lucide="check-circle" class="me-2"></i>
                                Client has confirmed your on-site presence. You may now begin working.
                            </div>
                            <!-- Cancel button intentionally omitted -->
                            <button class="btn btn-primary" id="startAssignmentBtn">
                                <i data-lucide="play" class="me-1"></i> Mark as In Progress
                            </button>
                        `;
                    }
                }
                else if (assignment.status === 'in_progress') {
                    // In progress - CANCEL NOT ALLOWED
                    statusActions = `
                        <div class="alert alert-info text-start mb-3">
                            <i data-lucide="info" class="me-2"></i>
                            You are currently working on this booking.
                            <br><small class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Cancellation is not allowed once work has started.</small>
                        </div>
                        <!-- Cancel button intentionally omitted -->
                        <button class="btn btn-success" id="completeAssignmentBtn">
                            <i data-lucide="check-circle" class="me-1"></i> Mark as Completed
                        </button>
                    `;
                } 
                else if (assignment.status === 'completed') {
                    statusActions = `
                        <span class="badge badge-soft-success fs-6 px-3 py-2">
                            <i data-lucide="check-circle" class="me-1"></i> Work Completed
                        </span>
                        <small class="text-muted d-block mt-2">Waiting for owner to finalize booking</small>
                    `;
                } 
                else if (assignment.status === 'cancelled') {
                    statusActions = `
                        <span class="badge badge-soft-danger fs-6 px-3 py-2">
                            <i data-lucide="x-circle" class="me-1"></i> Cancelled
                        </span>
                    `;
                }
                
                // Also update the onSiteStatusHtml to show correct status
                let onSiteStatusHtml = '';
                if (assignment.on_site_at) {
                    const onSiteDate = new Date(assignment.on_site_at);
                    const formattedOnSite = onSiteDate.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const hasClientConfirmed = assignment.client_confirmed_at !== null && assignment.client_confirmed_at !== undefined;
                    
                    const clientConfirmed = hasClientConfirmed ? 
                        `<span class="badge badge-soft-success ms-2"><i data-lucide="check-circle" class="me-1"></i>Confirmed by Client</span>` :
                        `<span class="badge badge-soft-warning ms-2"><i data-lucide="clock" class="me-1"></i>Awaiting Client Confirmation</span>`;
                    
                    onSiteStatusHtml = `
                        <div class="col-12">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0">
                                    <div class="bg-light-warning rounded-circle p-2">
                                        <i data-lucide="map-pin" class="fs-20 text-warning"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <label class="text-muted small mb-1">On-Site Status</label>
                                    <p class="mb-0 fw-medium">
                                        Marked on-site at: ${formattedOnSite}
                                        ${clientConfirmed}
                                    </p>
                                    ${assignment.client_confirmation_notes ? `
                                        <small class="text-muted d-block mt-1">Client notes: ${assignment.client_confirmation_notes}</small>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                const modalContent = `
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-6">
                            <div class="card border-0 shadow-none mb-4">
                                <div class="card-body p-0">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h6 class="card-title mb-0 fw-semibold text-uppercase small text-primary">
                                            Assignment Overview
                                        </h6>
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="briefcase" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Studio</label>
                                                    <p class="mb-0 fw-medium">${studio?.studio_name || 'N/A'}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="user-check" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Assigned By</label>
                                                    <p class="mb-0 fw-medium">${assigner?.first_name || 'N/A'} ${assigner?.last_name || ''}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar-clock" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Assigned Date</label>
                                                    <p class="mb-0 fw-medium">${new Date(assignment.assigned_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="clipboard-check" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Assignment Status</label>
                                                    <p class="mb-0 fw-medium ${getStatusTextClass(assignment.status)}">${assignment.status.toUpperCase()}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        ${onSiteStatusHtml}
                                        
                                        ${assignment.assignment_notes ? `
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="message-square" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Assignment Notes</label>
                                                    <p class="mb-0 fw-medium">${assignment.assignment_notes}</p>
                                                </div>
                                            </div>
                                        </div>
                                        ` : ''}
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
                                                    <p class="mb-0 fw-medium">${client?.first_name || 'N/A'} ${client?.last_name || ''}</p>
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
                                                    <p class="mb-0 fw-medium">${client?.mobile_number || 'N/A'}</p>
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
                                                    <p class="mb-0 fw-medium">${client?.email || 'N/A'}</p>
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
                                        Booking Details
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="receipt" class="fs-20 text-primary"></i>
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
                                                        <i data-lucide="calendar-heart" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Event</label>
                                                    <p class="mb-0 fw-medium">${booking.event_name}</p>
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
                                                    <label class="text-muted small mb-1">Event Date</label>
                                                    <p class="mb-0 fw-medium">${new Date(booking.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
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
                                                    <label class="text-muted small mb-1">Schedule</label>
                                                    <p class="mb-0 fw-medium">${booking.start_time} - ${booking.end_time}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="layers" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Category</label>
                                                    <p class="mb-0 fw-medium">${category?.category_name || 'N/A'}</p>
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
                                                    <label class="text-muted small mb-1">Payment Status</label>
                                                    <span class="badge ${getPaymentStatusBadgeClass(booking.payment_status)} px-2 fw-medium">
                                                        ${booking.payment_status.replace('_', ' ').toUpperCase()}
                                                    </span>
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
                
                $('#assignmentReference').text(booking.booking_reference);
                $('#assignmentModalBody').html(modalContent);
                $('#assignmentActions').html(statusActions);
                loadIcons();
                assignmentModal.show();
                
                // ========== UPDATED: Bind status update buttons with new on_site button ==========
                bindStatusUpdateButtons();
            }
            
            // Bind status update buttons
            function bindStatusUpdateButtons() {
                // Accept Assignment
                $(document).off('click', '#confirmAssignmentBtn').on('click', '#confirmAssignmentBtn', function() {
                    Swal.fire({
                        title: 'Accept Assignment',
                        text: 'Are you sure you want to accept this assignment?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3475db',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, accept it',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateAssignmentStatus('confirmed');
                        }
                    });
                });
                
                // ========== NEW: On-Site Assignment ==========
                $(document).off('click', '#onSiteAssignmentBtn').on('click', '#onSiteAssignmentBtn', function() {
                    Swal.fire({
                        title: 'Mark as On-Site',
                        text: 'Have you arrived at the event location?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#f39c12',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, I am on-site',
                        cancelButtonText: 'Not yet'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateAssignmentStatus('on_site');
                        }
                    });
                });
                
                // Start Assignment (In Progress) - UPDATED
                $(document).off('click', '#startAssignmentBtn').on('click', '#startAssignmentBtn', function() {
                    Swal.fire({
                        title: 'Start Working',
                        text: 'Are you ready to begin working on this assignment?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3475db',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, start now',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateAssignmentStatus('in_progress');
                        }
                    });
                });
                
                // Complete Assignment
                $(document).off('click', '#completeAssignmentBtn').on('click', '#completeAssignmentBtn', function() {
                    Swal.fire({
                        title: 'Mark as Completed',
                        text: 'Have you finished all your work for this booking?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3475db',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, mark as completed',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            updateAssignmentStatus('completed');
                        }
                    });
                });
                
                // Cancel Assignment
                $(document).off('click', '#cancelAssignmentBtn').on('click', '#cancelAssignmentBtn', function() {
                    // Double-check if cancellation is allowed before showing modal
                    $.ajax({
                        url: '{{ route("assignment.details", ":id") }}'.replace(':id', currentAssignmentId),
                        type: 'GET',
                        success: function(response) {
                            if (response.success) {
                                const assignment = response.assignment;
                                const canCancel = ['assigned', 'confirmed'].includes(assignment.status);
                                
                                if (!canCancel) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Cancellation Not Allowed',
                                        text: 'You cannot cancel this booking because it has already reached or passed the on-site stage.',
                                        confirmButtonColor: '#3475db'
                                    });
                                    return;
                                }
                                
                                // If cancellation is allowed, proceed with the modal
                                showStatusUpdateModal('cancelled');
                            }
                        },
                        error: function() {
                            // Fallback - try to show modal anyway
                            showStatusUpdateModal('cancelled');
                        }
                    });
                });
            }
            
            // Show status update modal (Only for cancellation)
            function showStatusUpdateModal(status) {
                currentStatusAction = status;
                
                // Only show modal for cancellation
                if (status !== 'cancelled') {
                    return;
                }
                
                // Load assignment details for the modal header
                $.ajax({
                    url: '{{ route("assignment.details", ":id") }}'.replace(':id', currentAssignmentId),
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const assignment = response.assignment;
                            const booking = response.booking;
                            
                            // Update modal header
                            $('#statusBookingInfo').text(`${booking.booking_reference} - ${booking.event_name}`);
                            $('#statusBookingDetails').text(`${new Date(booking.event_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })} • ${booking.start_time} - ${booking.end_time}`);
                            $('#statusCurrentStatus').text(assignment.status.toUpperCase());
                            
                            // Load cancellation content
                            loadStatusUpdateContent(status, assignment);
                        }
                    },
                    error: function(xhr) {
                        $('#statusUpdateContent').html(`
                            <div class="alert alert-danger">
                                <i data-lucide="alert-circle" class="me-2"></i>
                                Error loading assignment details. Please try again.
                            </div>
                        `);
                    }
                });
                
                assignmentModal.hide();
                setTimeout(() => {
                    statusUpdateModal.show();
                }, 300);
            }
            
            // Load status update content (Only for cancellation)
            function loadStatusUpdateContent(status, assignment) {
                let content = '';
                
                // Only show form for cancellation
                if (status === 'cancelled') {
                    content = `
                        <div class="alert alert-danger mb-4">
                            <div class="d-flex align-items-center">
                                <i data-lucide="alert-circle" class="fs-20 text-danger me-2"></i>
                                <div>
                                    <h6 class="mb-1">Cancel Assignment</h6>
                                    <p class="mb-0 text-muted">You are about to cancel this assignment. Please provide a reason for cancellation.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cancellationReason" class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancellationReason" rows="4" placeholder="Please explain why you need to cancel this assignment..." maxlength="500" required></textarea>
                            <small class="text-muted">Maximum 500 characters</small>
                        </div>
                    `;
                }
                
                $('#statusUpdateContent').html(content);
                loadIcons();
            }
            
            // Close status modal button
            $('#closeStatusModal').click(function() {
                statusUpdateModal.hide();
                setTimeout(() => {
                    assignmentModal.show(); // Show booking modal again
                }, 300);
            });
            
            // Cancel status modal button
            $('#cancelStatusModal').click(function() {
                statusUpdateModal.hide();
                setTimeout(() => {
                    assignmentModal.show(); // Show booking modal again
                }, 300);
            });
            
            // Confirm status update
            $('#confirmStatusUpdate').click(function() {
                if (currentStatusAction === 'cancelled') {
                    const reason = $('#cancellationReason').val();
                    if (!reason || reason.trim() === '') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Cancellation Reason Required',
                            text: 'Please provide a reason for cancellation.',
                            confirmButtonColor: '#3475db'
                        });
                        return;
                    }
                    updateAssignmentStatus(currentStatusAction, reason);
                } else {
                    updateAssignmentStatus(currentStatusAction);
                }
            });
            
            // Update assignment status
            function updateAssignmentStatus(status, reason = null) {
                $.ajax({
                    url: '{{ route("assignment.update-status", ":id") }}'.replace(':id', currentAssignmentId),
                    type: 'POST',
                    data: {
                        status: status,
                        cancellation_reason: reason,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#confirmStatusUpdate').prop('disabled', true).html('<span class="loading-spinner"></span> Updating...');
                    },
                    success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Confirmed!',
                                    text: response.message, // This will show: "Photographer on-site presence confirmed successfully. The photographer can now begin working."
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update assignment status. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $('#confirmStatusUpdate').prop('disabled', false).text('Update Status');
                    }
                });
            }
            
            // Helper functions
            function getStatusTextClass(status) {
                const textClasses = {
                    'assigned': 'text-info',
                    'confirmed': 'text-primary',
                    'completed': 'text-success',
                    'cancelled': 'text-danger'
                };
                return textClasses[status] || 'text-secondary';
            }
            
            function getPaymentStatusBadgeClass(status) {
                const badgeClasses = {
                    'pending': 'badge-soft-warning',
                    'partially_paid': 'badge-soft-primary',
                    'paid': 'badge-soft-success',
                    'failed': 'badge-soft-danger',
                    'refunded': 'badge-soft-danger'
                };
                return badgeClasses[status] || 'badge-soft-secondary';
            }
            
            function showError(message) {
                $('#assignmentModalBody').html(`
                    <div class="text-center py-5">
                        <i data-lucide="alert-circle" class="fs-20 text-danger mb-3"></i>
                        <p class="text-danger">${message}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="loadAssignmentDetails(${currentAssignmentId})">
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