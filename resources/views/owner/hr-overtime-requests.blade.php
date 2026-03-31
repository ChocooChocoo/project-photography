@extends('layouts.owner.app')
@section('title', 'HR Overtime Requests')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                @php
                    $totalOvertimeRequests = max(array_sum($overtimeRequestSummary), 1);
                    $overtimeSummaryCards = [
                        ['count' => $overtimeRequestSummary['pending'], 'label' => 'Pending Requests', 'meta' => 'WAITING OWNER', 'color' => 'warning', 'icon' => 'ti ti-clock-hour-4'],
                        ['count' => $overtimeRequestSummary['approved'], 'label' => 'Approved Requests', 'meta' => 'DECISIONS MADE', 'color' => 'success', 'icon' => 'ti ti-checklist'],
                        ['count' => $overtimeRequestSummary['rejected'], 'label' => 'Rejected Requests', 'meta' => 'DECLINED', 'color' => 'danger', 'icon' => 'ti ti-xbox-x'],
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
                            <h5 class="card-title mb-1">HR Overtime Requests</h5>
                            <p class="text-muted mb-0">Review and process overtime requests submitted by Human Resource employees from your studios.</p>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>HR Employee</th>
                                        <th>Studio</th>
                                        <th>Overtime Date</th>
                                        <th>Time Range</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($hrOvertimeRequests as $overtimeRequest)
                                        @php
                                            $statusBadgeClass = match ($overtimeRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr id="hrOvertimeRequestRow_{{ $overtimeRequest->id }}">
                                            <td><span class="fw-semibold">{{ $overtimeRequest->request_reference }}</span></td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1">{{ $overtimeRequest->user->full_name ?? 'N/A' }}</h6>
                                                    <span class="badge badge-soft-primary">Human Resource</span>
                                                </div>
                                            </td>
                                            <td>{{ $overtimeRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->overtime_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->start_time?->format('h:i A') ?? 'N/A' }} - {{ $overtimeRequest->end_time?->format('h:i A') ?? 'N/A' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $overtimeRequest->status_label }}</span></td>
                                            <td>{{ $overtimeRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm view-hr-overtime-request-btn" data-id="{{ $overtimeRequest->id }}" title="View HR overtime request details">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-clock-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No HR overtime requests are available.</p>
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

    <div class="modal fade" id="hrOvertimeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">HR Overtime Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="hrOvertimeRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading HR overtime request details...</p>
                    </div>
                    <div id="hrOvertimeRequestModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="hrOvertimeRequestPhoto" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="HR Employee">
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="hrOvertimeRequestName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-primary p-1" id="hrOvertimeRequestRole">Human Resource</span>
                                            <span class="badge badge-soft-secondary p-1">Overtime Request</span>
                                            <span class="badge badge-soft-warning p-1" id="hrOvertimeRequestStatus">Pending</span>
                                        </div>
                                        <p class="text-muted mb-0" id="hrOvertimeRequestEmail"><i class="ti ti-mail me-1"></i> N/A</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Request Reference</label><p class="mb-0 fw-medium" id="hrOvertimeReference">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Studio</label><p class="mb-0 fw-medium" id="hrOvertimeStudio">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Overtime Date</label><p class="mb-0 fw-medium" id="hrOvertimeDate">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Time Range</label><p class="mb-0 fw-medium" id="hrOvertimeTimeRange">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Total Hours</label><p class="mb-0 fw-medium" id="hrOvertimeTotalHours">0 hour</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Submitted At</label><p class="mb-0 fw-medium" id="hrOvertimeSubmittedAt">N/A</p></div></div>
                            <div class="col-md-12"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Processed By</label><p class="mb-0 fw-medium" id="hrOvertimeProcessedBy">Not processed yet.</p></div></div>
                        </div>

                        <div class="border rounded p-3 mb-3">
                            <label class="text-muted small mb-1 d-block">Reason for Overtime</label>
                            <p class="mb-0" id="hrOvertimeReason">N/A</p>
                        </div>

                        <div class="alert alert-danger d-none" id="hrOvertimeRejectionWrapper" role="alert">
                            <strong>Rejection Reason:</strong>
                            <span id="hrOvertimeRejectionReason">N/A</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-soft-danger" id="hrRejectOvertimeRequestBtn" data-id="">Reject</button>
                    <button type="button" class="btn btn-primary" id="hrApproveOvertimeRequestBtn" data-id="">Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectHrOvertimeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Reject HR Overtime Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="rejectHrOvertimeRequestForm">
                        @csrf
                        <input type="hidden" id="rejectHrOvertimeRequestId">
                        <div class="mb-3">
                            <label class="form-label">Request Reference</label>
                            <input type="text" class="form-control" id="rejectHrOvertimeRequestReference" readonly>
                        </div>
                        <div>
                            <label for="hrOvertimeRejectionReasonInput" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="hrOvertimeRejectionReasonInput" name="rejection_reason" rows="5" placeholder="Provide the reason for rejecting this overtime request..."></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submitRejectHrOvertimeRequestBtn">Submit Rejection</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const hrOvertimeRequestModal = new bootstrap.Modal(document.getElementById('hrOvertimeRequestModal'));
            const rejectHrOvertimeRequestModal = new bootstrap.Modal(document.getElementById('rejectHrOvertimeRequestModal'));
            const hrOvertimeRequestBaseUrl = '{{ url('/owner/hr-overtime-requests') }}';

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
                $('#hrOvertimeRejectionReasonInput').removeClass('is-invalid');
                $('#hrOvertimeRejectionReasonInput').siblings('.invalid-feedback').empty();
            }

            function resetHrOvertimeRequestModal() {
                $('#hrOvertimeRequestModalLoading').show();
                $('#hrOvertimeRequestModalContent').hide();
                $('#hrApproveOvertimeRequestBtn, #hrRejectOvertimeRequestBtn').attr('data-id', '').prop('disabled', false).removeClass('d-none');
                $('#hrOvertimeRejectionWrapper').addClass('d-none');
                $('#hrOvertimeRejectionReason').text('N/A');
            }

            function populateHrOvertimeRequestModal(data) {
                $('#hrOvertimeRequestPhoto').attr('src', data.hr_photo);
                $('#hrOvertimeRequestName').text(data.hr_name);
                $('#hrOvertimeRequestRole').text(data.hr_role);
                $('#hrOvertimeRequestStatus')
                    .removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary')
                    .addClass(getStatusBadgeClass(data.status))
                    .text(data.status_display);
                $('#hrOvertimeRequestEmail').html('<i class="ti ti-mail me-1"></i> ' + data.hr_email);
                $('#hrOvertimeReference').text(data.request_reference);
                $('#hrOvertimeStudio').text(data.studio_name);
                $('#hrOvertimeDate').text(data.overtime_date_display);
                $('#hrOvertimeTimeRange').text(data.time_range_display);
                $('#hrOvertimeTotalHours').text(data.total_hours_display);
                $('#hrOvertimeSubmittedAt').text(data.submitted_at || 'N/A');
                $('#hrOvertimeProcessedBy').text(data.processed_by || 'Not processed yet.');
                $('#hrOvertimeReason').text(data.reason);
                $('#hrApproveOvertimeRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_approve);
                $('#hrRejectOvertimeRequestBtn').attr('data-id', data.id).toggleClass('d-none', !data.can_reject);

                if (data.rejection_reason) {
                    $('#hrOvertimeRejectionWrapper').removeClass('d-none');
                    $('#hrOvertimeRejectionReason').text(data.rejection_reason);
                }
            }

            // ==================== LOAD DATA ====================
            function openHrOvertimeRequestModal(overtimeRequestId) {
                resetHrOvertimeRequestModal();
                hrOvertimeRequestModal.show();

                $.ajax({
                    url: hrOvertimeRequestBaseUrl + '/' + overtimeRequestId,
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateHrOvertimeRequestModal(response.data);
                            $('#hrOvertimeRequestModalLoading').hide();
                            $('#hrOvertimeRequestModalContent').show();
                            return;
                        }

                        hrOvertimeRequestModal.hide();
                        showAlert('error', 'Error!', response.message || 'Failed to load HR overtime request details.', false);
                    },
                    error: function (xhr) {
                        hrOvertimeRequestModal.hide();
                        showAlert('error', 'Error!', xhr.responseJSON?.message || 'Failed to load HR overtime request details.', false);
                    }
                });
            }

            // ==================== PROCESS REQUEST ====================
            function processHrOvertimeRequest(overtimeRequestId, action, rejectionReason) {
                $.ajax({
                    url: hrOvertimeRequestBaseUrl + '/' + overtimeRequestId + '/' + action,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        action: action,
                        rejection_reason: rejectionReason || ''
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            const successTitle = action === 'approve' ? 'HR Overtime Request Approved' : 'HR Overtime Request Rejected';
                            showAlert('success', successTitle, response.message, true);
                            return;
                        }

                        showAlert('error', 'Error!', response.message || 'Failed to process the overtime request.', false);
                    },
                    error: function (xhr) {
                        if (xhr.status === 422 && action === 'reject') {
                            $('#hrOvertimeRejectionReasonInput').addClass('is-invalid');
                            $('#hrOvertimeRejectionReasonInput').siblings('.invalid-feedback').html(xhr.responseJSON?.errors?.rejection_reason?.[0] || 'Please provide a valid rejection reason.');
                        }

                        showAlert('error', 'Error!', xhr.responseJSON?.message || 'Failed to process the overtime request.', false);
                    }
                });
            }

            // ==================== APPROVE HANDLER ====================
            function handleApproveOvertimeRequest() {
                const overtimeRequestId = $('#hrApproveOvertimeRequestBtn').attr('data-id');

                Swal.fire({
                    icon: 'warning',
                    title: 'Approve HR Overtime Request?',
                    text: 'This HR overtime request will be marked as approved.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        processHrOvertimeRequest(overtimeRequestId, 'approve');
                    }
                });
            }

            // ==================== REJECT HANDLER ====================
            function openRejectHrOvertimeRequestModal() {
                resetRejectValidationErrors();
                $('#rejectHrOvertimeRequestId').val($('#hrRejectOvertimeRequestBtn').attr('data-id'));
                $('#rejectHrOvertimeRequestReference').val($('#hrOvertimeReference').text().trim());
                $('#hrOvertimeRejectionReasonInput').val('');
                rejectHrOvertimeRequestModal.show();
            }

            function submitRejectHrOvertimeRequest() {
                resetRejectValidationErrors();
                processHrOvertimeRequest(
                    $('#rejectHrOvertimeRequestId').val(),
                    'reject',
                    $('#hrOvertimeRejectionReasonInput').val()
                );
            }

            // ==================== EVENT BINDINGS ====================
            $(document).on('click', '.view-hr-overtime-request-btn', function () {
                openHrOvertimeRequestModal($(this).data('id'));
            });

            $('#hrApproveOvertimeRequestBtn').on('click', function () {
                handleApproveOvertimeRequest();
            });

            $('#hrRejectOvertimeRequestBtn').on('click', function () {
                openRejectHrOvertimeRequestModal();
            });

            $('#submitRejectHrOvertimeRequestBtn').on('click', function () {
                submitRejectHrOvertimeRequest();
            });
        });
    </script>
@endsection
