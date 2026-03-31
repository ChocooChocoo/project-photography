@extends('layouts.owner.app')
@section('title', 'HR Leave Requests')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-2">Pending Requests</span>
                            <h3 class="mb-0">{{ $leaveRequestSummary['pending'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-2">Approved Requests</span>
                            <h3 class="mb-0">{{ $leaveRequestSummary['approved'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-2">Rejected Requests</span>
                            <h3 class="mb-0">{{ $leaveRequestSummary['rejected'] }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="card">
                        <div class="card-body">
                            <span class="text-muted small d-block mb-2">Cancelled Requests</span>
                            <h3 class="mb-0">{{ $leaveRequestSummary['cancelled'] }}</h3>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-1">HR Leave Requests</h5>
                            <p class="text-muted mb-0">Review and process leave requests submitted by Human Resource employees from your studios.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>HR Employee</th>
                                        <th>Studio</th>
                                        <th>Leave Type</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($hrLeaveRequests as $leaveRequest)
                                        @php
                                            $statusBadgeClass = match ($leaveRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr id="hrLeaveRequestRow_{{ $leaveRequest->id }}">
                                            <td><span class="fw-semibold">{{ $leaveRequest->request_reference }}</span></td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1">{{ $leaveRequest->user->full_name ?? 'N/A' }}</h6>
                                                    <span class="badge badge-soft-primary">Human Resource</span>
                                                </div>
                                            </td>
                                            <td>{{ $leaveRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $leaveRequest->leave_type_label }}</td>
                                            <td>{{ $leaveRequest->start_date?->format('M d, Y') ?? 'N/A' }} - {{ $leaveRequest->end_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $leaveRequest->status_label }}</span></td>
                                            <td>{{ $leaveRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm view-hr-leave-request-btn" data-id="{{ $leaveRequest->id }}" title="View HR leave request details">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-calendar-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No HR leave requests are available.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="hrLeaveRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">HR Leave Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="hrLeaveRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading HR leave request details...</p>
                    </div>
                    <div id="hrLeaveRequestModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="hrLeaveRequestPhoto" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="HR Employee">
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="hrLeaveRequestName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-primary p-1" id="hrLeaveRequestRole">Human Resource</span>
                                            <span class="badge badge-soft-secondary p-1" id="hrLeaveRequestType">N/A</span>
                                            <span class="badge badge-soft-warning p-1" id="hrLeaveRequestStatus">Pending</span>
                                        </div>
                                        <p class="text-muted mb-0" id="hrLeaveRequestEmail"><i class="ti ti-mail me-1"></i> N/A</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Request Reference</label>
                                    <p class="mb-0 fw-medium" id="hrRequestReference">N/A</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Studio</label>
                                    <p class="mb-0 fw-medium" id="hrRequestStudio">N/A</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Leave Period</label>
                                    <p class="mb-0 fw-medium" id="hrRequestPeriod">N/A</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Total Days</label>
                                    <p class="mb-0 fw-medium" id="hrRequestTotalDays">0 day</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Submitted At</label>
                                    <p class="mb-0 fw-medium" id="hrRequestSubmittedAt">N/A</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <label class="text-muted small mb-1 d-block">Processed By</label>
                                    <p class="mb-0 fw-medium" id="hrRequestProcessedBy">Not processed yet.</p>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <label class="text-muted small mb-1 d-block">Reason for Leave</label>
                            <p class="mb-0" id="hrRequestReason">N/A</p>
                        </div>

                        <div class="alert alert-danger d-none" id="hrRequestRejectionWrapper" role="alert">
                            <strong>Rejection Reason:</strong>
                            <span id="hrRequestRejectionReason">N/A</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-soft-danger" id="hrRejectLeaveRequestBtn" data-id="">Reject</button>
                    <button type="button" class="btn btn-primary" id="hrApproveLeaveRequestBtn" data-id="">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectHrLeaveRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Reject HR Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="rejectHrLeaveRequestForm">
                        @csrf
                        <input type="hidden" id="rejectHrLeaveRequestId">
                        <div class="mb-3">
                            <label class="form-label">Request Reference</label>
                            <input type="text" class="form-control" id="rejectHrLeaveRequestReference" readonly>
                        </div>
                        <div>
                            <label for="hrLeaveRejectionReason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="hrLeaveRejectionReason" name="rejection_reason" rows="5" placeholder="Provide the reason for rejecting this leave request..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRejectHrLeaveRequestBtn">Submit Rejection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const hrLeaveRequestModal = new bootstrap.Modal(document.getElementById('hrLeaveRequestModal'));
            const rejectHrLeaveRequestModal = new bootstrap.Modal(document.getElementById('rejectHrLeaveRequestModal'));
            const hrLeaveRequestBaseUrl = '{{ url('/owner/hr-leave-requests') }}';

            // ==================== HELPER METHODS ====================
            function getStatusBadgeClass(status) {
                if (status === 'approved') return 'badge-soft-success';
                if (status === 'rejected') return 'badge-soft-danger';
                if (status === 'cancelled') return 'badge-soft-secondary';

                return 'badge-soft-warning';
            }

            function showAlert(icon, title, text, shouldReload) {
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text,
                    showConfirmButton: icon !== 'success',
                    confirmButtonColor: '#3475db',
                    timer: icon === 'success' ? 2000 : undefined,
                    timerProgressBar: icon === 'success',
                    didClose: function () {
                        if (shouldReload) {
                            window.location.reload();
                        }
                    }
                });
            }

            function resetRejectValidationErrors() {
                $('#hrLeaveRejectionReason').removeClass('is-invalid');
                $('#hrLeaveRejectionReason').siblings('.invalid-feedback').empty();
            }

            function resetHrLeaveRequestModal() {
                $('#hrLeaveRequestModalLoading').show();
                $('#hrLeaveRequestModalContent').hide();
                $('#hrApproveLeaveRequestBtn, #hrRejectLeaveRequestBtn').attr('data-id', '').prop('disabled', false).removeClass('d-none');
                $('#hrRequestRejectionWrapper').addClass('d-none');
                $('#hrRequestRejectionReason').text('N/A');
            }

            function populateHrLeaveRequestModal(data) {
                $('#hrLeaveRequestPhoto').attr('src', data.hr_photo);
                $('#hrLeaveRequestName').text(data.hr_name);
                $('#hrLeaveRequestRole').text(data.hr_role);
                $('#hrLeaveRequestType').text(data.leave_type_display);
                $('#hrLeaveRequestStatus')
                    .removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary')
                    .addClass(getStatusBadgeClass(data.status))
                    .text(data.status_display);
                $('#hrLeaveRequestEmail').html('<i class="ti ti-mail me-1"></i> ' + data.hr_email);
                $('#hrRequestReference').text(data.request_reference);
                $('#hrRequestStudio').text(data.studio_name);
                $('#hrRequestPeriod').text(data.period_display);
                $('#hrRequestTotalDays').text(data.total_days_display);
                $('#hrRequestSubmittedAt').text(data.submitted_at || 'N/A');
                $('#hrRequestProcessedBy').text(data.processed_by || 'Not processed yet.');
                $('#hrRequestReason').text(data.reason);
                $('#hrApproveLeaveRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_approve);
                $('#hrRejectLeaveRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_reject);

                if (data.rejection_reason) {
                    $('#hrRequestRejectionWrapper').removeClass('d-none');
                    $('#hrRequestRejectionReason').text(data.rejection_reason);
                }
            }

            // ==================== LOAD DATA ====================
            function openHrLeaveRequestModal(leaveRequestId) {
                resetHrLeaveRequestModal();
                hrLeaveRequestModal.show();

                $.ajax({
                    url: hrLeaveRequestBaseUrl + '/' + leaveRequestId,
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateHrLeaveRequestModal(response.data);
                            $('#hrLeaveRequestModalLoading').hide();
                            $('#hrLeaveRequestModalContent').show();
                            return;
                        }

                        hrLeaveRequestModal.hide();
                        showAlert('error', 'Error!', response.message || 'Failed to load HR leave request details.', false);
                    },
                    error: function (xhr) {
                        hrLeaveRequestModal.hide();
                        showAlert('error', 'Error!', xhr.responseJSON?.message || 'Failed to load HR leave request details.', false);
                    }
                });
            }

            // ==================== PROCESS REQUEST ====================
            function processHrLeaveRequest(leaveRequestId, action, rejectionReason) {
                $.ajax({
                    url: hrLeaveRequestBaseUrl + '/' + leaveRequestId + '/' + action,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: action,
                        rejection_reason: rejectionReason || ''
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            const successTitle = action === 'approve' ? 'HR Leave Request Approved' : 'HR Leave Request Rejected';
                            showAlert('success', successTitle, response.message, true);
                            return;
                        }

                        showAlert('error', 'Error!', response.message || 'Failed to process the leave request.', false);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && action === 'reject') {
                            $('#hrLeaveRejectionReason').addClass('is-invalid');
                            $('#hrLeaveRejectionReason').siblings('.invalid-feedback').html(xhr.responseJSON?.errors?.rejection_reason?.[0] || 'Please provide a valid rejection reason.');
                        }

                        showAlert('error', 'Error!', xhr.responseJSON?.message || 'Failed to process the leave request.', false);
                    }
                });
            }

            // ==================== APPROVE HANDLER ====================
            function handleApproveLeaveRequest() {
                const leaveRequestId = $('#hrApproveLeaveRequestBtn').attr('data-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Approve HR Leave Request?',
                    text: 'This HR leave request will be marked as approved.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        processHrLeaveRequest(leaveRequestId, 'approve');
                    }
                });
            }

            // ==================== REJECT HANDLER ====================
            function openRejectHrLeaveRequestModal() {
                resetRejectValidationErrors();
                $('#rejectHrLeaveRequestId').val($('#hrRejectLeaveRequestBtn').attr('data-id'));
                $('#rejectHrLeaveRequestReference').val($('#hrRequestReference').text().trim());
                $('#hrLeaveRejectionReason').val('');
                rejectHrLeaveRequestModal.show();
            }

            function submitRejectHrLeaveRequest() {
                resetRejectValidationErrors();
                processHrLeaveRequest(
                    $('#rejectHrLeaveRequestId').val(),
                    'reject',
                    $('#hrLeaveRejectionReason').val()
                );
            }

            // ==================== EVENT BINDINGS ====================
            $(document).on('click', '.view-hr-leave-request-btn', function () {
                openHrLeaveRequestModal($(this).data('id'));
            });

            $('#hrApproveLeaveRequestBtn').on('click', function () {
                handleApproveLeaveRequest();
            });

            $('#hrRejectLeaveRequestBtn').on('click', function () {
                openRejectHrLeaveRequestModal();
            });

            $('#submitRejectHrLeaveRequestBtn').on('click', function () {
                submitRejectHrLeaveRequest();
            });
        });
    </script>
@endsection
