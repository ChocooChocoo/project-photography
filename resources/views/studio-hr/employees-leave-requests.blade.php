@extends('layouts.studio-hr.app')
@section('title', 'Employees Leave Requests')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Pending Requests</span><h3 class="mb-0">{{ $leaveRequestSummary['pending'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Approved Requests</span><h3 class="mb-0">{{ $leaveRequestSummary['approved'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Rejected Requests</span><h3 class="mb-0">{{ $leaveRequestSummary['rejected'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Cancelled Requests</span><h3 class="mb-0">{{ $leaveRequestSummary['cancelled'] }}</h3></div></div></div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-1">Employees Leave Requests</h5>
                            <p class="text-muted mb-0">Review leave requests submitted by Finance, Photographer, and other non-HR employees in your assigned studio.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>Employee</th>
                                        <th>Studio</th>
                                        <th>Leave Type</th>
                                        <th>Period</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employeeLeaveRequests as $leaveRequest)
                                        @php
                                            $statusBadgeClass = match ($leaveRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr id="employeeLeaveRequestRow_{{ $leaveRequest->id }}">
                                            <td><span class="fw-semibold">{{ $leaveRequest->request_reference }}</span></td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1">{{ $leaveRequest->user->full_name ?? 'N/A' }}</h6>
                                                    <span class="badge badge-soft-primary">{{ ucfirst(str_replace('-', ' ', $leaveRequest->user->role ?? 'employee')) }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $leaveRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $leaveRequest->leave_type_label }}</td>
                                            <td>{{ $leaveRequest->start_date?->format('M d, Y') ?? 'N/A' }} - {{ $leaveRequest->end_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $leaveRequest->status_label }}</span></td>
                                            <td>{{ $leaveRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm view-employee-leave-request-btn" data-id="{{ $leaveRequest->id }}" title="View leave request details">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-calendar-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No non-HR employee leave requests available.</p>
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

    <div class="modal fade" id="employeeLeaveRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Employee Leave Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="employeeLeaveRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Loading employee leave request details...</p>
                    </div>
                    <div id="employeeLeaveRequestModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="employeeLeaveRequestPhoto" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Employee">
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="employeeLeaveRequestName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-primary p-1" id="employeeLeaveRequestRole">N/A</span>
                                            <span class="badge badge-soft-secondary p-1" id="employeeLeaveRequestType">N/A</span>
                                            <span class="badge badge-soft-warning p-1" id="employeeLeaveRequestStatus">Pending</span>
                                        </div>
                                        <p class="text-muted mb-0" id="employeeLeaveRequestEmail"><i class="ti ti-mail me-1"></i> N/A</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Request Reference</label><p class="mb-0 fw-medium" id="employeeRequestReference">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Studio</label><p class="mb-0 fw-medium" id="employeeRequestStudio">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Leave Period</label><p class="mb-0 fw-medium" id="employeeRequestPeriod">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Total Days</label><p class="mb-0 fw-medium" id="employeeRequestTotalDays">0 day</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Submitted At</label><p class="mb-0 fw-medium" id="employeeRequestSubmittedAt">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Processed By</label><p class="mb-0 fw-medium" id="employeeRequestProcessedBy">Not processed yet.</p></div></div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <label class="text-muted small mb-1 d-block">Reason for Leave</label>
                            <p class="mb-0" id="employeeRequestReason">N/A</p>
                        </div>

                        <div class="alert alert-danger d-none" id="employeeRequestRejectionWrapper" role="alert">
                            <strong>Rejection Reason:</strong>
                            <span id="employeeRequestRejectionReason">N/A</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-soft-danger" id="employeeRejectLeaveRequestBtn" data-id="">Reject</button>
                    <button type="button" class="btn btn-primary" id="employeeApproveLeaveRequestBtn" data-id="">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectEmployeeLeaveRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Reject Leave Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="rejectEmployeeLeaveRequestForm">
                        @csrf
                        <input type="hidden" id="rejectEmployeeLeaveRequestId">
                        <div class="mb-3">
                            <label class="form-label">Request Reference</label>
                            <input type="text" class="form-control" id="rejectEmployeeLeaveRequestReference" readonly>
                        </div>
                        <div>
                            <label for="employeeLeaveRejectionReason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="employeeLeaveRejectionReason" name="rejection_reason" rows="5" placeholder="Provide the reason for rejecting this leave request..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRejectEmployeeLeaveRequestBtn">Submit Rejection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const employeeLeaveRequestModal = new bootstrap.Modal(document.getElementById('employeeLeaveRequestModal'));
            const rejectEmployeeLeaveRequestModal = new bootstrap.Modal(document.getElementById('rejectEmployeeLeaveRequestModal'));
            const employeeLeaveRequestBaseUrl = '{{ url('/studio-hr/employees-leave-requests') }}';

            function getStatusBadgeClass(status) {
                if (status === 'approved') return 'badge-soft-success';
                if (status === 'rejected') return 'badge-soft-danger';
                if (status === 'cancelled') return 'badge-soft-secondary';
                return 'badge-soft-warning';
            }

            function resetRejectValidationErrors() {
                $('#employeeLeaveRejectionReason').removeClass('is-invalid');
                $('#employeeLeaveRejectionReason').siblings('.invalid-feedback').empty();
            }

            function resetEmployeeLeaveRequestModal() {
                $('#employeeLeaveRequestModalLoading').show();
                $('#employeeLeaveRequestModalContent').hide();
                $('#employeeApproveLeaveRequestBtn, #employeeRejectLeaveRequestBtn').attr('data-id', '').prop('disabled', false).removeClass('d-none');
                $('#employeeRequestRejectionWrapper').addClass('d-none');
            }

            function populateEmployeeLeaveRequestModal(data) {
                $('#employeeLeaveRequestPhoto').attr('src', data.employee_photo);
                $('#employeeLeaveRequestName').text(data.employee_name);
                $('#employeeLeaveRequestRole').text(data.employee_role);
                $('#employeeLeaveRequestType').text(data.leave_type_display);
                $('#employeeLeaveRequestStatus').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass(getStatusBadgeClass(data.status)).text(data.status_display);
                $('#employeeLeaveRequestEmail').html('<i class="ti ti-mail me-1"></i> ' + data.employee_email);
                $('#employeeRequestReference').text(data.request_reference);
                $('#employeeRequestStudio').text(data.studio_name);
                $('#employeeRequestPeriod').text(data.period_display);
                $('#employeeRequestTotalDays').text(data.total_days_display);
                $('#employeeRequestSubmittedAt').text(data.submitted_at || 'N/A');
                $('#employeeRequestProcessedBy').text(data.processed_by || 'Not processed yet.');
                $('#employeeRequestReason').text(data.reason);
                $('#employeeApproveLeaveRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_approve);
                $('#employeeRejectLeaveRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_reject);

                if (data.rejection_reason) {
                    $('#employeeRequestRejectionWrapper').removeClass('d-none');
                    $('#employeeRequestRejectionReason').text(data.rejection_reason);
                } else {
                    $('#employeeRequestRejectionWrapper').addClass('d-none');
                    $('#employeeRequestRejectionReason').text('N/A');
                }
            }

            function openEmployeeLeaveRequestModal(leaveRequestId) {
                resetEmployeeLeaveRequestModal();
                employeeLeaveRequestModal.show();

                $.ajax({
                    url: employeeLeaveRequestBaseUrl + '/' + leaveRequestId,
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateEmployeeLeaveRequestModal(response.data);
                            $('#employeeLeaveRequestModalLoading').hide();
                            $('#employeeLeaveRequestModalContent').show();
                            return;
                        }

                        employeeLeaveRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to load employee leave request details.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        employeeLeaveRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to load employee leave request details.', confirmButtonColor: '#3475db' });
                    }
                });
            }

            function processEmployeeLeaveRequest(leaveRequestId, action, rejectionReason) {
                $.ajax({
                    url: employeeLeaveRequestBaseUrl + '/' + leaveRequestId + '/' + action,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: action,
                        rejection_reason: rejectionReason || ''
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: action === 'approve' ? 'Leave Request Approved' : 'Leave Request Rejected',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () { window.location.reload(); }
                            });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to process the leave request.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && action === 'reject') {
                            $('#employeeLeaveRejectionReason').addClass('is-invalid');
                            $('#employeeLeaveRejectionReason').siblings('.invalid-feedback').html(xhr.responseJSON?.errors?.rejection_reason?.[0] || 'Please provide a valid rejection reason.');
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to process the leave request.', confirmButtonColor: '#3475db' });
                    }
                });
            }

            $(document).on('click', '.view-employee-leave-request-btn', function () {
                openEmployeeLeaveRequestModal($(this).data('id'));
            });

            $('#employeeApproveLeaveRequestBtn').on('click', function () {
                const leaveRequestId = $(this).attr('data-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Approve Leave Request?',
                    text: 'This leave request will be marked as approved.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        processEmployeeLeaveRequest(leaveRequestId, 'approve');
                    }
                });
            });

            $('#employeeRejectLeaveRequestBtn').on('click', function () {
                resetRejectValidationErrors();
                $('#rejectEmployeeLeaveRequestId').val($(this).attr('data-id'));
                $('#rejectEmployeeLeaveRequestReference').val($('#employeeRequestReference').text().trim());
                $('#employeeLeaveRejectionReason').val('');
                rejectEmployeeLeaveRequestModal.show();
            });

            $('#submitRejectEmployeeLeaveRequestBtn').on('click', function () {
                resetRejectValidationErrors();
                processEmployeeLeaveRequest(
                    $('#rejectEmployeeLeaveRequestId').val(),
                    'reject',
                    $('#employeeLeaveRejectionReason').val()
                );
            });
        });
    </script>
@endsection
