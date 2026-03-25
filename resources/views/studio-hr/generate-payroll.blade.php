@extends('layouts.studio-hr.app')
@section('title', 'Generate Payroll')

{{-- STYLES --}}
@section('styles')
    <style>
        .summary-card {
            border: 1px dashed rgba(52, 117, 219, 0.35);
            background: linear-gradient(135deg, rgba(52, 117, 219, 0.08), rgba(52, 117, 219, 0.02));
        }

        .employee-table-wrap {
            min-height: 260px;
        }

        .table tbody tr.is-selected {
            background-color: rgba(52, 117, 219, 0.06);
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Generate Employee Payroll</h4>
                        </div>
                        <div class="card-body">
                            @if(isset($canGenerate) && !$canGenerate)
                                <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                                    <i class="ti ti-lock me-2"></i>
                                    <strong>Restricted Access:</strong> Your account can view this page but cannot generate payroll records.
                                </div>
                            @endif

                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#generate-payroll" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Generate Payroll
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#generated-payroll" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        View Generated Payrolls
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="generate-payroll">
                                    <form class="needs-validation" novalidate id="generatePayrollForm">
                                        @csrf

                                        <div class="row">
                                            <div class="col-12">
                                                <h5 class="text-primary mb-3">Payroll Filter</h5>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="studioId" class="form-label">Studio <span class="text-danger">*</span></label>
                                                <select class="form-select" id="studioId" name="studio_id" required>
                                                    <option value="">Select Studio</option>
                                                    @foreach($studios as $studio)
                                                        <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                                    @endforeach
                                                </select>
                                                <div class="invalid-feedback">Please select a studio.</div>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="employeeType" class="form-label">Employee Type <span class="text-danger">*</span></label>
                                                <select class="form-select" id="employeeType" name="employee_type" required>
                                                    <option value="">Choose Type</option>
                                                    <option value="regular_employee">Regular Employee</option>
                                                    <option value="studio_photographer">Studio Photographer</option>
                                                </select>
                                                <div class="invalid-feedback">Please choose an employee type.</div>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="periodStart" class="form-label">Period Start <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="periodStart" name="period_start"
                                                    value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                                                <div class="invalid-feedback">Please provide the payroll period start date.</div>
                                            </div>

                                            <div class="col-md-3 mb-3">
                                                <label for="periodEnd" class="form-label">Period End <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="periodEnd" name="period_end"
                                                    value="{{ now()->format('Y-m-d') }}" required>
                                                <div class="invalid-feedback">Please provide the payroll period end date.</div>
                                            </div>
                                        </div>

                                        <div class="row align-items-end mb-4">
                                            <div class="col-md-9 mb-3">
                                                <label for="notes" class="form-label">Notes</label>
                                                <textarea class="form-control" id="notes" name="notes" rows="2"
                                                    placeholder="Optional remarks for this payroll run"></textarea>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <button type="button" class="btn btn-primary w-100" id="loadEmployeesBtn">
                                                    <i class="ti ti-filter-search me-1"></i>Load Employees
                                                </button>
                                            </div>
                                        </div>

                                        <div class="row mb-4">
                                            <div class="col-md-3 mb-3">
                                                <div class="card summary-card shadow-none h-100">
                                                    <div class="card-body">
                                                        <p class="text-muted mb-1">Eligible Employees</p>
                                                        <h3 class="mb-0" id="eligibleEmployeeCount">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="card summary-card shadow-none h-100">
                                                    <div class="card-body">
                                                        <p class="text-muted mb-1">Selected Employees</p>
                                                        <h3 class="mb-0" id="selectedEmployeeCount">0</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="card summary-card shadow-none h-100">
                                                    <div class="card-body">
                                                        <p class="text-muted mb-1">Selected Attendance</p>
                                                        <h3 class="mb-0" id="selectedAttendanceTotal">0.00</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <div class="card summary-card shadow-none h-100">
                                                    <div class="card-body">
                                                        <p class="text-muted mb-1">Selected Booking</p>
                                                        <h3 class="mb-0" id="selectedBookingTotal">0.00</h3>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <h5 class="text-primary mb-1">Employee Selection</h5>
                                                <p class="text-muted mb-0 small">Choose employees using the checkboxes before generating payroll.</p>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-soft-primary btn-sm" id="selectAllBtn" disabled>
                                                    <i class="ti ti-select-all me-1"></i>Select All
                                                </button>
                                                <button type="button" class="btn btn-soft-danger btn-sm" id="clearSelectionBtn" disabled>
                                                    <i class="ti ti-x me-1"></i>Clear Selection
                                                </button>
                                            </div>
                                        </div>

                                        <div class="employee-table-wrap position-relative">
                                            <div id="employeeLoadingState" class="text-center py-5 d-none">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="mt-2 mb-0 text-muted">Loading eligible employees...</p>
                                            </div>

                                            <div id="employeeEmptyState" class="text-center py-5 border rounded">
                                                <i class="ti ti-users fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">Select a studio, employee type, and payroll period, then click <strong>Load Employees</strong>.</p>
                                            </div>

                                            <div class="table-responsive d-none" id="employeeTableWrapper">
                                                <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                        <tr class="text-uppercase fs-xxs">
                                                            <th class="text-center" style="width: 60px;">
                                                                <input type="checkbox" class="form-check-input" id="masterCheckbox">
                                                            </th>
                                                            <th>Employee</th>
                                                            <th>Role</th>
                                                            <th>Payroll Basis</th>
                                                            <th>Attendance Preview</th>
                                                            <th>Booking Preview</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="employeeTableBody"></tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="mt-4 d-flex justify-content-end">
                                            <button type="submit" class="btn btn-success" id="generatePayrollBtn">
                                                <span id="generatePayrollBtnText">Generate Payroll</span>
                                                <span class="spinner-border spinner-border-sm ms-2 d-none" id="generatePayrollSpinner" role="status" aria-hidden="true"></span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="tab-pane show active" id="generated-payroll">
                                    <div class="table-responsive">
                                        <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                <tr class="text-uppercase fs-xxs">
                                                    <th>Reference</th>
                                                    <th>Employee</th>
                                                    <th>Studio</th>
                                                    <th>Period</th>
                                                    <th>Gross</th>
                                                    <th>Deductions</th>
                                                    <th>Net</th>
                                                    <th>Generated</th>
                                                    <th class="text-center" style="width: 1%;">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="generatedPayrollTableBody">
                                                @forelse($generatedPayrolls as $generatedPayroll)
                                                    <tr>
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
                                                        <td>PHP {{ number_format((float) $generatedPayroll->gross_amount, 2) }}</td>
                                                        <td>PHP {{ number_format((float) $generatedPayroll->total_deductions, 2) }}</td>
                                                        <td><strong>PHP {{ number_format((float) $generatedPayroll->net_amount, 2) }}</strong></td>
                                                        <td>
                                                            <div>{{ $generatedPayroll->generated_at?->format('M d, Y h:i A') ?? 'N/A' }}</div>
                                                            <small class="text-muted">{{ $generatedPayroll->generator->full_name ?? 'N/A' }}</small>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-primary btn-sm view-generated-payroll-btn"
                                                                data-id="{{ $generatedPayroll->id }}">
                                                                View
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr id="generatedPayrollEmptyRow">
                                                        <td colspan="9" class="text-center py-4">
                                                            <i class="ti ti-receipt-off fs-1 text-muted"></i>
                                                            <p class="mt-2 mb-0">No generated payroll records found.</p>
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
        </div>
    </div>

    <div class="modal fade" id="viewGeneratedPayrollModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="viewGeneratedPayrollModalLabel">
                        Generated Payroll Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="generatedPayrollModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading generated payroll details...</p>
                    </div>

                    <div id="generatedPayrollModalContent" style="display: none;">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <img src="" id="modalEmployeePhoto" class="rounded-circle"
                                            style="width: 80px; height: 80px; object-fit: cover;" alt="Generated Payroll Employee">
                                    </div>

                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3 h3-md" id="modalEmployeeName">N/A</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-success p-1" id="modalEmployeeTypeBadge">N/A</span>
                                            <span class="badge badge-soft-primary p-1" id="modalPayrollBasisBadge">N/A</span>
                                        </div>

                                        <p class="text-muted mb-0" id="modalEmployeeMeta">
                                            <i class="ti ti-mail me-1"></i> N/A
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">Payroll Summary</h5>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-receipt fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Payroll Reference</label><p class="mb-0 fw-medium" id="modalPayrollReference">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-building-store fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Studio</label><p class="mb-0 fw-medium" id="modalStudioName">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-calendar-event fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Payroll Period</label><p class="mb-0 fw-medium" id="modalPayrollPeriod">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-user-check fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Generated By</label><p class="mb-0 fw-medium" id="modalGeneratedBy">N/A</p></div></div></div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">Computation Details</h5>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-calendar-stats fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Attendance Summary</label><p class="mb-0 fw-medium" id="modalAttendanceSummary">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-camera fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Booking Count</label><p class="mb-0 fw-medium" id="modalBookingCount">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-cash fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Gross Amount</label><p class="mb-0 fw-medium" id="modalGrossAmount">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-cash-banknote fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Net Amount</label><p class="mb-0 fw-medium" id="modalNetAmount">N/A</p></div></div></div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">Deductions and Notes</h5>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-minus fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Deduction Breakdown</label><p class="mb-0 fw-medium" id="modalDeductionBreakdown">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-note fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Notes</label><p class="mb-0 fw-medium" id="modalPayrollNotes">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-report-money fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Attendance Amount</label><p class="mb-0 fw-medium" id="modalAttendanceAmount">N/A</p></div></div></div>
                                    <div class="col-12 col-md-6"><div class="d-flex align-items-start"><div class="flex-shrink-0"><div class="bg-light-primary rounded-circle p-2"><i class="ti ti-coin fs-20 text-primary"></i></div></div><div class="flex-grow-1 ms-3"><label class="text-muted small mb-1">Booking Amount</label><p class="mb-0 fw-medium" id="modalBookingAmount">N/A</p></div></div></div>
                                </div>
                            </div>
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
        $(document).ready(function () {
            let loadedEmployees = [];
            let selectedEmployees = [];

            // ==================== HELPER FUNCTIONS ====================
            function formatCurrency(amount) {
                return Number(amount || 0).toLocaleString('en-PH', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function getAttendancePreviewAmount(employee) {
                const preview = employee.attendance_preview || {};
                const daysPresent = Number(preview.days_present || 0);
                const workedHours = Number(preview.worked_hours || 0);

                if (Number(employee.daily_rate || 0) > 0) {
                    return daysPresent * Number(employee.daily_rate);
                }

                if (Number(employee.hourly_rate || 0) > 0) {
                    return workedHours * Number(employee.hourly_rate);
                }

                if (Number(employee.monthly_salary || 0) > 0 && daysPresent > 0) {
                    const dailyEquivalent = Number(employee.monthly_salary) / 22;
                    return daysPresent * dailyEquivalent;
                }

                return 0;
            }

            function getBookingPreviewAmount(employee) {
                return Number(employee.booking_preview?.booking_amount || 0);
            }

            function updateSummaryCards() {
                let attendanceTotal = 0;
                let bookingTotal = 0;

                selectedEmployees.forEach(function (employee) {
                    attendanceTotal += getAttendancePreviewAmount(employee);
                    bookingTotal += getBookingPreviewAmount(employee);
                });

                $('#eligibleEmployeeCount').text(loadedEmployees.length);
                $('#selectedEmployeeCount').text(selectedEmployees.length);
                $('#selectedAttendanceTotal').text(formatCurrency(attendanceTotal));
                $('#selectedBookingTotal').text(formatCurrency(bookingTotal));
            }

            function updateSelectedEmployees() {
                selectedEmployees = loadedEmployees.filter(function (employee) {
                    return $('#employeeCheckbox_' + employee.id).is(':checked');
                });

                $('#masterCheckbox').prop(
                    'checked',
                    loadedEmployees.length > 0 && selectedEmployees.length === loadedEmployees.length
                );

                $('#employeeTableBody tr').each(function () {
                    const isChecked = $(this).find('.employee-checkbox').is(':checked');
                    $(this).toggleClass('is-selected', isChecked);
                });

                updateSummaryCards();
            }

            function renderEmployeeTable() {
                const $tableWrapper = $('#employeeTableWrapper');
                const $tableBody = $('#employeeTableBody');
                const $emptyState = $('#employeeEmptyState');

                $tableBody.empty();
                $('#masterCheckbox').prop('checked', false);

                if (loadedEmployees.length === 0) {
                    $tableWrapper.addClass('d-none');
                    $emptyState.removeClass('d-none').html(`
                        <div class="text-center py-5 border rounded">
                            <i class="ti ti-filter-off fs-1 text-muted"></i>
                            <p class="mt-2 mb-0">No eligible employees found for the selected filter and payroll period.</p>
                        </div>
                    `);
                    $('#selectAllBtn, #clearSelectionBtn').prop('disabled', true);
                    updateSummaryCards();
                    return;
                }

                loadedEmployees.forEach(function (employee) {
                    const attendance = employee.attendance_preview || {};
                    const booking = employee.booking_preview || {};

                    $tableBody.append(`
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" class="form-check-input employee-checkbox"
                                    id="employeeCheckbox_${employee.id}" value="${employee.id}">
                            </td>
                            <td>
                                <h6 class="mb-1">${employee.full_name}</h6>
                                <small class="text-muted">${employee.email}</small>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">${employee.role_display}</span>
                            </td>
                            <td>
                                <span class="badge badge-soft-primary">${employee.payroll_basis_display}</span>
                            </td>
                            <td>
                                <div><strong>Present:</strong> ${attendance.days_present ?? 0} day(s)</div>
                                <div><strong>Absent:</strong> ${attendance.days_absent ?? 0} day(s)</div>
                                <div><strong>Late:</strong> ${attendance.late_minutes ?? 0} minute(s)</div>
                                <div><strong>Undertime:</strong> ${attendance.undertime_minutes ?? 0} minute(s)</div>
                            </td>
                            <td>
                                <div><strong>Bookings:</strong> ${booking.booking_count ?? 0}</div>
                                <div><strong>Preview:</strong> PHP ${formatCurrency(booking.booking_amount ?? 0)}</div>
                            </td>
                        </tr>
                    `);
                });

                $emptyState.addClass('d-none');
                $tableWrapper.removeClass('d-none');
                $('#selectAllBtn, #clearSelectionBtn').prop('disabled', false);
                updateSummaryCards();
            }

            function showValidationAlert(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    html: message,
                    confirmButtonColor: '#3475db'
                });
            }

            function setLoadingState(isLoading) {
                $('#employeeLoadingState').toggleClass('d-none', !isLoading);

                if (isLoading) {
                    $('#employeeTableWrapper, #employeeEmptyState').addClass('d-none');
                }

                $('#loadEmployeesBtn').prop('disabled', isLoading);
            }

            function resetModalState() {
                $('#generatedPayrollModalLoading').show();
                $('#generatedPayrollModalContent').hide();
            }

            function renderDeductionBreakdown(deductionBreakdown) {
                const entries = Object.entries(deductionBreakdown || {});

                if (entries.length === 0) {
                    return 'No deductions recorded.';
                }

                return entries.map(function ([key, value]) {
                    const label = key.replaceAll('_', ' ').replace(/\b\w/g, function (char) {
                        return char.toUpperCase();
                    });

                    return label + ': PHP ' + value;
                }).join('<br>');
            }

            function populateGeneratedPayrollModal(data) {
                $('#modalEmployeePhoto').attr('src', data.employee_photo);
                $('#modalEmployeeName').text(data.employee_name);
                $('#modalEmployeeTypeBadge').text(data.employee_type_display);
                $('#modalPayrollBasisBadge').text(data.payroll_basis_display);
                $('#modalEmployeeMeta').html('<i class="ti ti-mail me-1"></i> ' + data.employee_email + ' | Role: ' + data.employee_role);
                $('#modalPayrollReference').text(data.payroll_reference);
                $('#modalStudioName').text(data.studio_name);
                $('#modalPayrollPeriod').text(data.period_start + ' - ' + data.period_end);
                $('#modalGeneratedBy').text(data.generated_by + ' | ' + data.generated_at);
                $('#modalAttendanceSummary').html(
                    'Present: ' + data.attendance_days_present +
                    ' day(s)<br>Absent: ' + data.attendance_days_absent +
                    ' day(s)<br>Late: ' + data.attendance_minutes_late +
                    ' minute(s)<br>Undertime: ' + data.attendance_minutes_undertime + ' minute(s)'
                );
                $('#modalBookingCount').text(data.booking_count + ' booking(s)');
                $('#modalGrossAmount').text('PHP ' + data.gross_amount);
                $('#modalNetAmount').text('PHP ' + data.net_amount);
                $('#modalDeductionBreakdown').html(renderDeductionBreakdown(data.deduction_breakdown));
                $('#modalPayrollNotes').text(data.notes);
                $('#modalAttendanceAmount').text('PHP ' + data.attendance_amount);
                $('#modalBookingAmount').text('PHP ' + data.booking_amount);
            }

            function openGeneratedPayrollModal(payrollId) {
                resetModalState();
                const modal = new bootstrap.Modal(document.getElementById('viewGeneratedPayrollModal'));
                modal.show();

                $.ajax({
                    url: '/studio-hr/generate-payroll/' + payrollId,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateGeneratedPayrollModal(response.data);
                            $('#generatedPayrollModalLoading').hide();
                            $('#generatedPayrollModalContent').show();
                        } else {
                            modal.hide();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function (xhr) {
                        modal.hide();

                        let errorMessage = 'Failed to load generated payroll details.';

                        if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            }

            // ==================== LOAD EMPLOYEES ====================
            function loadEmployees() {
                const studioId = $('#studioId').val();
                const employeeType = $('#employeeType').val();
                const periodStart = $('#periodStart').val();
                const periodEnd = $('#periodEnd').val();

                if (!studioId || !employeeType || !periodStart || !periodEnd) {
                    showValidationAlert('Please complete the studio, employee type, and payroll period fields first.');
                    return;
                }

                if (periodEnd < periodStart) {
                    showValidationAlert('The payroll period end date must be on or after the start date.');
                    return;
                }

                loadedEmployees = [];
                selectedEmployees = [];
                updateSummaryCards();
                setLoadingState(true);

                $.ajax({
                    url: '{{ route("studio-hr.generate-payroll.employees") }}',
                    method: 'GET',
                    data: {
                        studio_id: studioId,
                        employee_type: employeeType,
                        period_start: periodStart,
                        period_end: periodEnd
                    },
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            loadedEmployees = response.data || [];
                            renderEmployeeTable();
                        } else {
                            loadedEmployees = [];
                            renderEmployeeTable();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function (xhr) {
                        loadedEmployees = [];
                        renderEmployeeTable();

                        let errorMessage = 'Failed to load employees for payroll generation.';

                        if (xhr.responseJSON?.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function () {
                        setLoadingState(false);
                    }
                });
            }

            // ==================== FORM SUBMIT ====================
            function submitPayrollGeneration() {
                @if(isset($canGenerate) && !$canGenerate)
                    Swal.fire({
                        icon: 'error',
                        title: 'Permission Denied',
                        text: 'Your account does not have permission to generate payroll.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                @endif

                if (selectedEmployees.length === 0) {
                    showValidationAlert('Please select at least one employee before generating payroll.');
                    return;
                }

                const payload = {
                    studio_id: $('#studioId').val(),
                    employee_type: $('#employeeType').val(),
                    period_start: $('#periodStart').val(),
                    period_end: $('#periodEnd').val(),
                    employee_ids: selectedEmployees.map(employee => employee.id),
                    notes: $('#notes').val()
                };

                Swal.fire({
                    icon: 'warning',
                    title: 'Generate Payroll?',
                    text: 'This will create payroll records for the selected employees.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, generate'
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $('#generatePayrollBtn').prop('disabled', true);
                    $('#generatePayrollBtnText').text('Generating...');
                    $('#generatePayrollSpinner').removeClass('d-none');

                    $.ajax({
                        url: '{{ route("studio-hr.generate-payroll.store") }}',
                        method: 'POST',
                        data: JSON.stringify(payload),
                        contentType: 'application/json',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                let message = response.message;

                                if (response.data?.skipped?.length > 0) {
                                    message += '<br><br><strong>Skipped:</strong><br>' + response.data.skipped.join('<br>');
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Payroll Generated',
                                    html: message,
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true,
                                    didClose: function () {
                                        window.location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: response.message,
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        },
                        error: function (xhr) {
                            let errorMessage = 'Failed to generate payroll.';

                            if (xhr.responseJSON?.errors) {
                                errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            } else if (xhr.responseJSON?.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                html: errorMessage,
                                confirmButtonColor: '#3475db'
                            });
                        },
                        complete: function () {
                            $('#generatePayrollBtn').prop('disabled', false);
                            $('#generatePayrollBtnText').text('Generate Payroll');
                            $('#generatePayrollSpinner').addClass('d-none');
                        }
                    });
                });
            }

            // ==================== EVENT HANDLERS ====================
            $('#loadEmployeesBtn').on('click', function () {
                loadEmployees();
            });

            $('#employeeTableBody').on('change', '.employee-checkbox', function () {
                updateSelectedEmployees();
            });

            $('#masterCheckbox').on('change', function () {
                const isChecked = $(this).is(':checked');
                $('.employee-checkbox').prop('checked', isChecked);
                updateSelectedEmployees();
            });

            $('#selectAllBtn').on('click', function () {
                $('.employee-checkbox').prop('checked', true);
                updateSelectedEmployees();
            });

            $('#clearSelectionBtn').on('click', function () {
                $('.employee-checkbox, #masterCheckbox').prop('checked', false);
                updateSelectedEmployees();
            });

            $('#generatePayrollForm').on('submit', function (event) {
                event.preventDefault();

                if (!this.checkValidity()) {
                    event.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                submitPayrollGeneration();
            });

            $('#generatedPayrollTableBody').on('click', '.view-generated-payroll-btn', function () {
                openGeneratedPayrollModal($(this).data('id'));
            });
        });
    </script>
@endsection
