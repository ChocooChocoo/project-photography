@extends('layouts.studio-finance.app')
@section('title', 'Payroll Approvals')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="card-title mb-1">Payroll Approvals</h4>
                                <p class="text-muted mb-0">Review HR-generated payroll and approve or reject based on finance permissions.</p>
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!$canApprovePayroll && !$canRejectPayroll)
                                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                                    <i class="ti ti-lock me-2"></i>
                                    <strong>Restricted Access:</strong> Your account can view payroll approvals but cannot approve or reject records.
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                        <tr class="text-uppercase fs-xxs">
                                            <th>Reference</th>
                                            <th>Employee</th>
                                            <th>Studio</th>
                                            <th>Period</th>
                                            <th>Net Amount</th>
                                            <th>Status</th>
                                            <th>Generated</th>
                                            <th class="text-center" style="width: 1%;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payrollApprovalTableBody">
                                        @forelse($generatedPayrolls as $generatedPayroll)
                                            @php
                                                $statusBadgeClass = match ($generatedPayroll->status) {
                                                    'approved' => 'badge-soft-success',
                                                    'rejected' => 'badge-soft-danger',
                                                    default => 'badge-soft-warning',
                                                };
                                            @endphp
                                            <tr id="payrollApprovalRow_{{ $generatedPayroll->id }}">
                                                <td>
                                                    <span class="fw-semibold">{{ $generatedPayroll->payroll_reference }}</span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-1">{{ $generatedPayroll->employee->full_name ?? 'N/A' }}</h6>
                                                        <span class="badge badge-soft-secondary">{{ ucfirst(str_replace('_', ' ', $generatedPayroll->employee_type)) }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $generatedPayroll->studio->studio_name ?? 'N/A' }}</td>
                                                <td>
                                                    {{ $generatedPayroll->period_start?->format('M d, Y') ?? 'N/A' }}
                                                    -
                                                    {{ $generatedPayroll->period_end?->format('M d, Y') ?? 'N/A' }}
                                                </td>
                                                <td><strong>PHP {{ number_format((float) $generatedPayroll->net_amount, 2) }}</strong></td>
                                                <td>
                                                    <div>
                                                        <span class="badge {{ $statusBadgeClass }} payroll-status-badge">
                                                            {{ ucfirst($generatedPayroll->status) }}
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block mt-1 payroll-reviewer-text">
                                                        {{ $generatedPayroll->reviewer->full_name ?? 'Pending review' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <div>{{ $generatedPayroll->generated_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
                                                    <small class="text-muted">{{ $generatedPayroll->generator->full_name ?? 'N/A' }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                                                        <button type="button" class="btn btn-primary btn-sm view-payroll-approval-btn" data-id="{{ $generatedPayroll->id }}">
                                                            View
                                                        </button>
                                                        @if($canApprovePayroll)
                                                            <button type="button" class="btn btn-success btn-sm approve-payroll-btn" data-id="{{ $generatedPayroll->id }}" data-status="{{ $generatedPayroll->status }}">
                                                                Approve
                                                            </button>
                                                        @endif
                                                        @if($canRejectPayroll)
                                                            <button type="button" class="btn btn-danger btn-sm reject-payroll-btn" data-id="{{ $generatedPayroll->id }}" data-reference="{{ $generatedPayroll->payroll_reference }}" data-status="{{ $generatedPayroll->status }}">
                                                                Reject
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr id="payrollApprovalEmptyRow">
                                                <td colspan="8" class="text-center py-4">
                                                    <i class="ti ti-receipt-off fs-1 text-muted"></i>
                                                    <p class="mt-2 mb-0">No generated payroll records available for finance review.</p>
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
    </div>

    <div class="modal fade" id="viewPayrollApprovalModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Payroll Approval Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="payrollApprovalModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading payroll approval details...</p>
                    </div>

                    <div id="payrollApprovalModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="approvalModalEmployeePhoto" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Payroll Employee">
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3 h3-md" id="approvalModalEmployeeName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-success p-1" id="approvalModalEmployeeTypeBadge">N/A</span>
                                            <span class="badge badge-soft-primary p-1" id="approvalModalPayrollBasisBadge">N/A</span>
                                            <span class="badge badge-soft-warning p-1" id="approvalModalStatusBadge">Pending</span>
                                        </div>
                                        <p class="text-muted mb-0" id="approvalModalEmployeeMeta">
                                            <i class="ti ti-mail me-1"></i> N/A
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <h5 class="card-title text-primary">Payroll Summary</h5>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-receipt fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Payroll Reference</label><p class="mb-0 fw-medium" id="approvalModalPayrollReference">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-building-store fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Studio</label><p class="mb-0 fw-medium" id="approvalModalStudioName">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-calendar-event fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Payroll Period</label><p class="mb-0 fw-medium" id="approvalModalPayrollPeriod">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-user-check fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Generated By</label><p class="mb-0 fw-medium" id="approvalModalGeneratedBy">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-stamp fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Reviewed By</label><p class="mb-0 fw-medium" id="approvalModalReviewedBy">Not reviewed yet.</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-clock-check fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Reviewed At</label><p class="mb-0 fw-medium" id="approvalModalReviewedAt">Not reviewed yet.</p></div></div></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <h5 class="card-title text-primary">Computation Details</h5>
                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light-primary rounded-circle p-2">
                                            <i class="ti ti-calendar-stats fs-20 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="text-muted small mb-2 d-block">Attendance Summary</label>
                                        <div class="card border shadow-none mb-0">
                                            <div class="table-responsive">
                                                <table class="table table-bordered align-middle mb-0">
                                                    <thead class="bg-light bg-opacity-50">
                                                        <tr class="text-uppercase fs-xxs">
                                                            <th>Metric</th>
                                                            <th>Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="approvalModalAttendanceSummary"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-camera fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Booking Count</label><p class="mb-0 fw-medium" id="approvalModalBookingCount">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-cash fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Gross Amount</label><p class="mb-0 fw-medium" id="approvalModalSummaryGrossAmount">N/A</p></div></div></div>
                            <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-cash-banknote fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Net Amount</label><p class="mb-0 fw-medium" id="approvalModalSummaryNetAmount">N/A</p></div></div></div>
                        </div>

                        <div class="row g-2 mb-3">
                            <h5 class="card-title text-primary">Deductions and Notes</h5>
                            <div class="col-12">
                                <div class="card border shadow-none mb-0">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-nowrap align-middle mb-0">
                                            <thead class="bg-light bg-opacity-50">
                                                <tr class="text-uppercase fs-xxs">
                                                    <th>Item Details</th>
                                                    <th class="text-start" style="width: 120px;">Qty</th>
                                                    <th class="text-start" style="width: 170px;">Unit Price</th>
                                                    <th class="text-start" style="width: 170px;">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody id="approvalModalInvoiceLineItems"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4"><div class="border rounded p-3 bg-light-subtle h-100"><label class="text-muted small mb-1 d-block">Attendance Amount</label><h5 class="mb-0" id="approvalModalAttendanceAmount">PHP 0.00</h5></div></div>
                            <div class="col-12 col-md-4"><div class="border rounded p-3 bg-light-subtle h-100"><label class="text-muted small mb-1 d-block">Booking Amount</label><h5 class="mb-0" id="approvalModalBookingAmount">PHP 0.00</h5></div></div>
                            <div class="col-12 col-md-4"><div class="border rounded p-3 bg-light-subtle h-100"><label class="text-muted small mb-1 d-block">Total Deductions</label><h5 class="mb-0 text-danger" id="approvalModalTotalDeductionsSummary">- PHP 0.00</h5></div></div>
                            <div class="col-12"><div class="border rounded p-3"><label class="text-muted small mb-1 d-block">Notes</label><p class="mb-0" id="approvalModalPayrollNotes">No remarks provided.</p></div></div>
                            <div class="col-12"><div class="border rounded p-3"><label class="text-muted small mb-1 d-block">Rejection Reason</label><p class="mb-0" id="approvalModalRejectionReason">No rejection reason provided.</p></div></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
                    <div class="d-flex gap-3">
                        <div>
                            <label class="text-muted small d-block">Gross Amount</label>
                            <strong id="approvalModalFooterGrossAmount">PHP 0.00</strong>
                        </div>
                        <div>
                            <label class="text-muted small d-block">Net Amount</label>
                            <strong id="approvalModalFooterNetAmount">PHP 0.00</strong>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        @if($canRejectPayroll)
                            <button type="button" class="btn btn-danger" id="approvalModalRejectBtn" data-id="">Reject</button>
                        @endif
                        @if($canApprovePayroll)
                            <button type="button" class="btn btn-success" id="approvalModalApproveBtn" data-id="">Approve</button>
                        @endif
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rejectPayrollModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Reject Payroll</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="rejectPayrollForm">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" id="rejectPayrollId" name="payroll_id">
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            You are rejecting payroll <strong id="rejectPayrollReference">N/A</strong>. Please provide a clear reason.
                        </div>
                        <div class="form-group">
                            <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="4" placeholder="Enter the reason for rejecting this payroll..." required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" id="submitRejectPayrollBtn">
                            <span id="submitRejectPayrollText">Submit Rejection</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" id="submitRejectPayrollSpinner" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function () {
            'use strict';

            const payrollApprovalModal = new bootstrap.Modal(document.getElementById('viewPayrollApprovalModal'));
            const rejectPayrollModal = new bootstrap.Modal(document.getElementById('rejectPayrollModal'));

            // ==================== HELPER FUNCTIONS ====================
            function formatCurrency(value) {
                const numericValue = Number(value || 0);
                return numericValue.toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function parseNumericAmount(value) {
                return Number(String(value || 0).replace(/,/g, ''));
            }

            function formatInvoiceLabel(key) {
                return key.replaceAll('_', ' ').replace(/\b\w/g, function (char) {
                    return char.toUpperCase();
                });
            }

            function getStatusBadgeClass(status) {
                if (status === 'approved') {
                    return 'badge-soft-success';
                }

                if (status === 'rejected') {
                    return 'badge-soft-danger';
                }

                return 'badge-soft-warning';
            }

            function renderDeductionBreakdown(deductionBreakdown) {
                return Object.entries(deductionBreakdown || {});
            }

            function renderInvoiceLineItems(data) {
                const lineItems = [
                    {
                        label: 'Attendance Compensation',
                        description: 'Computed from attendance records within the payroll period.',
                        quantity: Number(data.attendance_days_present || 0),
                        unitPrice: parseNumericAmount(data.attendance_amount),
                        total: parseNumericAmount(data.attendance_amount)
                    },
                    {
                        label: 'Booking Compensation',
                        description: 'Computed from completed booking records within the payroll period.',
                        quantity: Number(data.booking_count || 0),
                        unitPrice: parseNumericAmount(data.booking_amount),
                        total: parseNumericAmount(data.booking_amount)
                    }
                ];

                renderDeductionBreakdown(data.deduction_breakdown).forEach(function ([key, value]) {
                    const numericValue = parseNumericAmount(value);

                    if (numericValue <= 0) {
                        return;
                    }

                    lineItems.push({
                        label: formatInvoiceLabel(key),
                        description: 'Payroll deduction applied during computation.',
                        quantity: 1,
                        unitPrice: numericValue,
                        total: numericValue
                    });
                });

                return lineItems.map(function (item, index) {
                    return `
                        <tr>
                            <td>
                                <div class="fw-semibold">${item.label}</div>
                                <div class="text-muted small">${item.description}</div>
                            </td>
                            <td class="text-start">${item.quantity}</td>
                            <td class="text-start">PHP ${formatCurrency(item.unitPrice)}</td>
                            <td class="text-start ${index > 1 ? 'text-danger' : ''}">
                                ${index > 1 ? '- ' : ''}PHP ${formatCurrency(item.total)}
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            function updatePayrollRowStatus(payrollId, data) {
                const row = $(`#payrollApprovalRow_${payrollId}`);

                if (!row.length) {
                    return;
                }

                row.find('.payroll-status-badge')
                    .removeClass('badge-soft-warning badge-soft-success badge-soft-danger')
                    .addClass(getStatusBadgeClass(data.status))
                    .text(data.status_display);

                row.find('.payroll-reviewer-text').text(data.reviewed_by || 'Pending review');
                row.find('.approve-payroll-btn, .reject-payroll-btn').attr('data-status', data.status);

                if (data.status === 'approved') {
                    row.find('.approve-payroll-btn, .reject-payroll-btn').prop('disabled', true);
                }
            }

            function showValidationErrors(formElement, errors) {
                formElement.find('.is-invalid').removeClass('is-invalid');
                formElement.find('.invalid-feedback').empty();

                $.each(errors || {}, function (field, messages) {
                    const input = formElement.find(`[name="${field}"]`);

                    if (input.length) {
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').html(messages[0]);
                    }
                });
            }

            function resetPayrollApprovalModal() {
                $('#payrollApprovalModalLoading').show();
                $('#payrollApprovalModalContent').hide();
                $('#approvalModalApproveBtn, #approvalModalRejectBtn').attr('data-id', '').prop('disabled', false);
            }

            function populatePayrollApprovalModal(data) {
                $('#approvalModalEmployeePhoto').attr('src', data.employee_photo);
                $('#approvalModalEmployeeName').text(data.employee_name);
                $('#approvalModalEmployeeTypeBadge').text(data.employee_type_display);
                $('#approvalModalPayrollBasisBadge').text(data.payroll_basis_display);
                $('#approvalModalStatusBadge')
                    .removeClass('badge-soft-warning badge-soft-success badge-soft-danger')
                    .addClass(getStatusBadgeClass(data.status))
                    .text(data.status_display);
                $('#approvalModalEmployeeMeta').html('<i class="ti ti-mail me-1"></i> ' + data.employee_email + ' | Role: ' + data.employee_role);
                $('#approvalModalPayrollReference').text(data.payroll_reference);
                $('#approvalModalStudioName').text(data.studio_name);
                $('#approvalModalPayrollPeriod').text(data.period_start + ' - ' + data.period_end);
                $('#approvalModalGeneratedBy').text(data.generated_by + ' | ' + data.generated_at);
                $('#approvalModalReviewedBy').text(data.reviewed_by);
                $('#approvalModalReviewedAt').text(data.reviewed_at);
                $('#approvalModalAttendanceSummary').html(`
                    <tr><td class="text-muted">Present</td><td class="text-start fw-medium">${data.attendance_days_present} day(s)</td></tr>
                    <tr><td class="text-muted">Absent</td><td class="text-start fw-medium">${data.attendance_days_absent} day(s)</td></tr>
                    <tr><td class="text-muted">Late</td><td class="text-start fw-medium">${data.attendance_minutes_late} minute(s)</td></tr>
                    <tr><td class="text-muted">Undertime</td><td class="text-start fw-medium">${data.attendance_minutes_undertime} minute(s)</td></tr>
                `);
                $('#approvalModalBookingCount').text(data.booking_count + ' booking(s)');
                $('#approvalModalInvoiceLineItems').html(renderInvoiceLineItems(data));
                $('#approvalModalSummaryGrossAmount').text('PHP ' + data.gross_amount);
                $('#approvalModalSummaryNetAmount').text('PHP ' + data.net_amount);
                $('#approvalModalFooterGrossAmount').text('PHP ' + data.gross_amount);
                $('#approvalModalFooterNetAmount').text('PHP ' + data.net_amount);
                $('#approvalModalTotalDeductionsSummary').text('- PHP ' + data.total_deductions);
                $('#approvalModalPayrollNotes').text(data.notes);
                $('#approvalModalRejectionReason').text(data.rejection_reason);
                $('#approvalModalAttendanceAmount').text('PHP ' + data.attendance_amount);
                $('#approvalModalBookingAmount').text('PHP ' + data.booking_amount);
                $('#approvalModalApproveBtn').attr('data-id', data.id).prop('disabled', !data.can_approve);
                $('#approvalModalRejectBtn').attr('data-id', data.id).prop('disabled', !data.can_reject);
            }

            // ==================== LOAD DETAILS ====================
            function openPayrollApprovalModal(payrollId) {
                resetPayrollApprovalModal();
                payrollApprovalModal.show();

                $.ajax({
                    url: '{{ url('/studio-finance/payroll-approvals') }}/' + payrollId,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            populatePayrollApprovalModal(response.data);
                            $('#payrollApprovalModalLoading').hide();
                            $('#payrollApprovalModalContent').show();
                            return;
                        }

                        payrollApprovalModal.hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    error: function (xhr) {
                        payrollApprovalModal.hide();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to load payroll approval details.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // ==================== APPROVE HANDLER ====================
            function approvePayroll(payrollId) {
                $.ajax({
                    url: '{{ url('/studio-finance/payroll-approvals') }}/' + payrollId + '/approve',
                    method: 'POST',
                    data: {
                        action: 'approve',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            updatePayrollRowStatus(payrollId, response.data);
                            $('#approvalModalStatusBadge')
                                .removeClass('badge-soft-warning badge-soft-danger')
                                .addClass('badge-soft-success')
                                .text(response.data.status_display);
                            $('#approvalModalReviewedBy').text(response.data.reviewed_by);
                            $('#approvalModalReviewedAt').text(response.data.reviewed_at);
                            $('#approvalModalApproveBtn, #approvalModalRejectBtn').prop('disabled', true);

                            Swal.fire({
                                icon: 'success',
                                title: 'Payroll Approved',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });

                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to approve payroll.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            function confirmApprovePayroll(payrollId, currentStatus) {
                if (currentStatus === 'approved') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Approved',
                        text: 'This payroll has already been approved.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Approve Payroll?',
                    text: 'This payroll will be marked as approved.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, approve'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        approvePayroll(payrollId);
                    }
                });
            }

            // ==================== REJECT HANDLER ====================
            function openRejectPayrollModal(payrollId, reference, currentStatus) {
                if (currentStatus === 'approved') {
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Approved',
                        text: 'Approved payroll can no longer be rejected.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                $('#rejectPayrollId').val(payrollId);
                $('#rejectPayrollReference').text(reference);
                $('#rejectionReason').val('').removeClass('is-invalid');
                $('#rejectionReason').siblings('.invalid-feedback').empty();
                rejectPayrollModal.show();
            }

            function submitRejectPayroll() {
                const payrollId = $('#rejectPayrollId').val();
                const rejectionReason = $('#rejectionReason').val();
                const rejectForm = $('#rejectPayrollForm');

                $('#submitRejectPayrollBtn').prop('disabled', true);
                $('#submitRejectPayrollText').text('Submitting...');
                $('#submitRejectPayrollSpinner').removeClass('d-none');

                $.ajax({
                    url: '{{ url('/studio-finance/payroll-approvals') }}/' + payrollId + '/reject',
                    method: 'POST',
                    data: {
                        action: 'reject',
                        rejection_reason: rejectionReason,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            rejectPayrollModal.hide();
                            updatePayrollRowStatus(payrollId, response.data);
                            $('#approvalModalStatusBadge')
                                .removeClass('badge-soft-warning badge-soft-success')
                                .addClass('badge-soft-danger')
                                .text(response.data.status_display);
                            $('#approvalModalReviewedBy').text(response.data.reviewed_by);
                            $('#approvalModalReviewedAt').text(response.data.reviewed_at);
                            $('#approvalModalRejectionReason').text(response.data.rejection_reason);

                            Swal.fire({
                                icon: 'success',
                                title: 'Payroll Rejected',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });

                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors(rejectForm, xhr.responseJSON?.errors || {});
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                text: xhr.responseJSON?.message || 'Please provide a valid rejection reason.',
                                confirmButtonColor: '#3475db'
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to reject payroll.',
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function () {
                        $('#submitRejectPayrollBtn').prop('disabled', false);
                        $('#submitRejectPayrollText').text('Submit Rejection');
                        $('#submitRejectPayrollSpinner').addClass('d-none');
                    }
                });
            }

            // ==================== EVENT HANDLERS ====================
            $('#payrollApprovalTableBody').on('click', '.view-payroll-approval-btn', function () {
                openPayrollApprovalModal($(this).data('id'));
            });

            $('#payrollApprovalTableBody').on('click', '.approve-payroll-btn', function () {
                confirmApprovePayroll($(this).data('id'), $(this).data('status'));
            });

            $('#payrollApprovalTableBody').on('click', '.reject-payroll-btn', function () {
                openRejectPayrollModal($(this).data('id'), $(this).data('reference'), $(this).data('status'));
            });

            $('#approvalModalApproveBtn').on('click', function () {
                const payrollId = $(this).attr('data-id');
                const currentStatus = $('#approvalModalStatusBadge').text().trim().toLowerCase();
                confirmApprovePayroll(payrollId, currentStatus);
            });

            $('#approvalModalRejectBtn').on('click', function () {
                const payrollId = $(this).attr('data-id');
                const payrollReference = $('#approvalModalPayrollReference').text().trim();
                const currentStatus = $('#approvalModalStatusBadge').text().trim().toLowerCase();
                openRejectPayrollModal(payrollId, payrollReference, currentStatus);
            });

            $('#rejectPayrollForm').on('submit', function (event) {
                event.preventDefault();
                submitRejectPayroll();
            });
        });
    </script>
@endsection
