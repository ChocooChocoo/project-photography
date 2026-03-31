@extends('layouts.studio-hr.app')
@section('title', 'Employees Overtime Requests')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                @php
                    $totalOvertimeRequests = max(array_sum($overtimeRequestSummary), 1);
                    $overtimeSummaryCards = [
                        ['count' => $overtimeRequestSummary['pending'], 'label' => 'Pending Requests', 'meta' => 'WAITING HR', 'color' => 'warning', 'icon' => 'ti ti-clock-hour-4'],
                        ['count' => $overtimeRequestSummary['approved'], 'label' => 'Approved Requests', 'meta' => 'PROCESSED', 'color' => 'success', 'icon' => 'ti ti-checklist'],
                        ['count' => $overtimeRequestSummary['rejected'], 'label' => 'Rejected Requests', 'meta' => 'NEEDS REVIEW', 'color' => 'danger', 'icon' => 'ti ti-xbox-x'],
                        ['count' => $overtimeRequestSummary['cancelled'], 'label' => 'Cancelled Requests', 'meta' => 'WITHDRAWN', 'color' => 'secondary', 'icon' => 'ti ti-ban'],
                    ];
                @endphp

                <div class="col-12">
                    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center">
                        @foreach ($overtimeSummaryCards as $overtimeSummaryCard)
                            @php
                                $percentage = $overtimeSummaryCard['count'] > 0
                                    ? round(($overtimeSummaryCard['count'] / $totalOvertimeRequests) * 100)
                                    : 0;
                            @endphp
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="avatar avatar-lg flex-shrink-0">
                                                <span class="avatar-title bg-{{ $overtimeSummaryCard['color'] }}-subtle text-{{ $overtimeSummaryCard['color'] }} rounded fs-24">
                                                    <i class="{{ $overtimeSummaryCard['icon'] }}"></i>
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <h4 class="mb-0">{{ $overtimeSummaryCard['count'] }}</h4>
                                                <p class="mb-0 text-muted">{{ $overtimeSummaryCard['label'] }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted fs-xs fw-semibold">{{ $overtimeSummaryCard['meta'] }}</span>
                                                <span class="text-muted">{{ $percentage }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $overtimeSummaryCard['color'] }}" style="width: {{ $percentage }}%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-1">Employees Overtime Requests</h5>
                            <p class="text-muted mb-0">Review overtime requests submitted by Finance, Photographer, and other non-HR employees in your assigned studio.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>Employee</th>
                                        <th>Studio</th>
                                        <th>Overtime Date</th>
                                        <th>Time Range</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($employeeOvertimeRequests as $overtimeRequest)
                                        @php
                                            $statusBadgeClass = match ($overtimeRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr id="employeeOvertimeRequestRow_{{ $overtimeRequest->id }}">
                                            <td><span class="fw-semibold">{{ $overtimeRequest->request_reference }}</span></td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1">{{ $overtimeRequest->user->full_name ?? 'N/A' }}</h6>
                                                    <span class="badge badge-soft-primary">{{ ucfirst(str_replace('-', ' ', $overtimeRequest->user->role ?? 'employee')) }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $overtimeRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->overtime_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->start_time?->format('h:i A') ?? 'N/A' }} - {{ $overtimeRequest->end_time?->format('h:i A') ?? 'N/A' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $overtimeRequest->status_label }}</span></td>
                                            <td>{{ $overtimeRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm view-employee-overtime-request-btn" data-id="{{ $overtimeRequest->id }}" title="View overtime request details">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-clock-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No non-HR employee overtime requests available.</p>
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

    <div class="modal fade" id="employeeOvertimeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Employee Overtime Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="employeeOvertimeRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Loading employee overtime request details...</p>
                    </div>
                    <div id="employeeOvertimeRequestModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="employeeOvertimeRequestPhoto" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Employee">
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="employeeOvertimeRequestName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-primary p-1" id="employeeOvertimeRequestRole">N/A</span>
                                            <span class="badge badge-soft-secondary p-1">Overtime Request</span>
                                            <span class="badge badge-soft-warning p-1" id="employeeOvertimeRequestStatus">Pending</span>
                                        </div>
                                        <p class="text-muted mb-0" id="employeeOvertimeRequestEmail"><i class="ti ti-mail me-1"></i> N/A</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Request Reference</label><p class="mb-0 fw-medium" id="employeeOvertimeReference">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Studio</label><p class="mb-0 fw-medium" id="employeeOvertimeStudio">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Overtime Date</label><p class="mb-0 fw-medium" id="employeeOvertimeDate">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Time Range</label><p class="mb-0 fw-medium" id="employeeOvertimeTimeRange">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Total Hours</label><p class="mb-0 fw-medium" id="employeeOvertimeTotalHours">0 hour</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Submitted At</label><p class="mb-0 fw-medium" id="employeeOvertimeSubmittedAt">N/A</p></div></div>
                            <div class="col-md-12"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Processed By</label><p class="mb-0 fw-medium" id="employeeOvertimeProcessedBy">Not processed yet.</p></div></div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <label class="text-muted small mb-1 d-block">Reason for Overtime</label>
                            <p class="mb-0" id="employeeOvertimeReason">N/A</p>
                        </div>

                        <div class="alert alert-danger d-none" id="employeeOvertimeRejectionWrapper" role="alert">
                            <strong>Rejection Reason:</strong>
                            <span id="employeeOvertimeRejectionReason">N/A</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-soft-danger" id="employeeRejectOvertimeRequestBtn" data-id="">Reject</button>
                    <button type="button" class="btn btn-primary" id="employeeApproveOvertimeRequestBtn" data-id="">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectEmployeeOvertimeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Reject Overtime Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="rejectEmployeeOvertimeRequestForm">
                        @csrf
                        <input type="hidden" id="rejectEmployeeOvertimeRequestId">
                        <div class="mb-3">
                            <label class="form-label">Request Reference</label>
                            <input type="text" class="form-control" id="rejectEmployeeOvertimeRequestReference" readonly>
                        </div>
                        <div>
                            <label for="employeeOvertimeRejectionReasonInput" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="employeeOvertimeRejectionReasonInput" name="rejection_reason" rows="5" placeholder="Provide the reason for rejecting this overtime request..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRejectEmployeeOvertimeRequestBtn">Submit Rejection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const employeeOvertimeRequestModal = new bootstrap.Modal(document.getElementById('employeeOvertimeRequestModal'));
            const rejectEmployeeOvertimeRequestModal = new bootstrap.Modal(document.getElementById('rejectEmployeeOvertimeRequestModal'));
            const employeeOvertimeRequestBaseUrl = '{{ url('/studio-hr/employees-overtime-requests') }}';

            function getStatusBadgeClass(status) {
                if (status === 'approved') return 'badge-soft-success';
                if (status === 'rejected') return 'badge-soft-danger';
                if (status === 'cancelled') return 'badge-soft-secondary';
                return 'badge-soft-warning';
            }

            function resetRejectValidationErrors() {
                $('#employeeOvertimeRejectionReasonInput').removeClass('is-invalid');
                $('#employeeOvertimeRejectionReasonInput').siblings('.invalid-feedback').empty();
            }

            function resetEmployeeOvertimeRequestModal() {
                $('#employeeOvertimeRequestModalLoading').show();
                $('#employeeOvertimeRequestModalContent').hide();
                $('#employeeApproveOvertimeRequestBtn, #employeeRejectOvertimeRequestBtn').attr('data-id', '').prop('disabled', false).removeClass('d-none');
                $('#employeeOvertimeRejectionWrapper').addClass('d-none');
            }

            function populateEmployeeOvertimeRequestModal(data) {
                $('#employeeOvertimeRequestPhoto').attr('src', data.employee_photo);
                $('#employeeOvertimeRequestName').text(data.employee_name);
                $('#employeeOvertimeRequestRole').text(data.employee_role);
                $('#employeeOvertimeRequestStatus').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass(getStatusBadgeClass(data.status)).text(data.status_display);
                $('#employeeOvertimeRequestEmail').html('<i class="ti ti-mail me-1"></i> ' + data.employee_email);
                $('#employeeOvertimeReference').text(data.request_reference);
                $('#employeeOvertimeStudio').text(data.studio_name);
                $('#employeeOvertimeDate').text(data.overtime_date_display);
                $('#employeeOvertimeTimeRange').text(data.time_range_display);
                $('#employeeOvertimeTotalHours').text(data.total_hours_display);
                $('#employeeOvertimeSubmittedAt').text(data.submitted_at || 'N/A');
                $('#employeeOvertimeProcessedBy').text(data.processed_by || 'Not processed yet.');
                $('#employeeOvertimeReason').text(data.reason);
                $('#employeeApproveOvertimeRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_approve);
                $('#employeeRejectOvertimeRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_reject);

                if (data.rejection_reason) {
                    $('#employeeOvertimeRejectionWrapper').removeClass('d-none');
                    $('#employeeOvertimeRejectionReason').text(data.rejection_reason);
                } else {
                    $('#employeeOvertimeRejectionWrapper').addClass('d-none');
                    $('#employeeOvertimeRejectionReason').text('N/A');
                }
            }

            function openEmployeeOvertimeRequestModal(overtimeRequestId) {
                resetEmployeeOvertimeRequestModal();
                employeeOvertimeRequestModal.show();

                $.ajax({
                    url: employeeOvertimeRequestBaseUrl + '/' + overtimeRequestId,
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateEmployeeOvertimeRequestModal(response.data);
                            $('#employeeOvertimeRequestModalLoading').hide();
                            $('#employeeOvertimeRequestModalContent').show();
                            return;
                        }

                        employeeOvertimeRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to load employee overtime request details.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        employeeOvertimeRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to load employee overtime request details.', confirmButtonColor: '#3475db' });
                    }
                });
            }

            function processEmployeeOvertimeRequest(overtimeRequestId, action, rejectionReason) {
                $.ajax({
                    url: employeeOvertimeRequestBaseUrl + '/' + overtimeRequestId + '/' + action,
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
                                title: action === 'approve' ? 'Overtime Request Approved' : 'Overtime Request Rejected',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () { window.location.reload(); }
                            });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to process the overtime request.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && action === 'reject') {
                            $('#employeeOvertimeRejectionReasonInput').addClass('is-invalid');
                            $('#employeeOvertimeRejectionReasonInput').siblings('.invalid-feedback').html(xhr.responseJSON?.errors?.rejection_reason?.[0] || 'Please provide a valid rejection reason.');
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to process the overtime request.', confirmButtonColor: '#3475db' });
                    }
                });
            }

            $(document).on('click', '.view-employee-overtime-request-btn', function () {
                openEmployeeOvertimeRequestModal($(this).data('id'));
            });

            $('#employeeApproveOvertimeRequestBtn').on('click', function () {
                const overtimeRequestId = $(this).attr('data-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Approve Overtime Request?',
                    text: 'This overtime request will be marked as approved.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        processEmployeeOvertimeRequest(overtimeRequestId, 'approve');
                    }
                });
            });

            $('#employeeRejectOvertimeRequestBtn').on('click', function () {
                resetRejectValidationErrors();
                $('#rejectEmployeeOvertimeRequestId').val($(this).attr('data-id'));
                $('#rejectEmployeeOvertimeRequestReference').val($('#employeeOvertimeReference').text().trim());
                $('#employeeOvertimeRejectionReasonInput').val('');
                rejectEmployeeOvertimeRequestModal.show();
            });

            $('#submitRejectEmployeeOvertimeRequestBtn').on('click', function () {
                resetRejectValidationErrors();
                processEmployeeOvertimeRequest(
                    $('#rejectEmployeeOvertimeRequestId').val(),
                    'reject',
                    $('#employeeOvertimeRejectionReasonInput').val()
                );
            });
        });
    </script>
@endsection
