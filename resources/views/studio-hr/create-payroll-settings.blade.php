@extends('layouts.studio-hr.app')
@section('title', 'Setup Employee Payroll')

{{-- STYLES --}}
@section('styles')
    <style>
        .disabled-option {
            cursor: not-allowed !important;
            opacity: 0.6;
            pointer-events: none;
        }
        
        .btn-check:disabled + .btn-outline-primary {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
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
                            <h4 class="card-title mb-0">Setup Employee Payroll</h4>
                        </div>
                        <div class="card-body">
                            @if(isset($canCreate) && !$canCreate)
                                <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert" id="permissionAlert">
                                    <i class="ti ti-lock me-2"></i>
                                    <strong>Restricted Access:</strong> Your account has view-only permissions. You can browse the form but cannot create new payroll settings.
                                </div>
                            @endif
                            <form class="needs-validation" novalidate id="payrollForm">
                                @csrf

                                {{-- STUDIO SELECTION (Fixed for HR) --}}
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Studio Selection</h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Studio <span class="text-danger">*</span></label>
                                        <select class="form-select" name="studio_id" id="studioSelect" required
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a studio.</div>
                                    </div>
                                </div>

                                {{-- BULK EMPLOYEE SELECTION --}}
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Bulk Employee Selection</h4>
                                        <p class="text-muted small">Select multiple employees to create payroll settings simultaneously.</p>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label">Select Employees <span class="text-danger">*</span></label>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-soft-primary me-2" id="selectAllEmployeesBtn" disabled>
                                                    <i class="ti ti-select-all me-1"></i>Select All
                                                </button>
                                                <button type="button" class="btn btn-sm btn-soft-danger" id="deselectAllEmployeesBtn" disabled>
                                                    <i class="ti ti-deselect me-1"></i>Deselect All
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="card bg-light">
                                            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                                                <div id="employeeLoadingSpinner" class="text-center py-4">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </div>
                                                    <p class="mt-2">Loading employees...</p>
                                                </div>
                                                
                                                <div id="employeeCheckboxList" style="display: none;">
                                                    <div class="alert alert-info" id="noEmployeesMessage" style="display: none;">
                                                        <i class="ti ti-info-circle me-2"></i>
                                                        No eligible employees found for this studio.
                                                    </div>
                                                    <div id="employeeCheckboxes" class="row"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted">Select at least one employee to continue.</small>
                                        <div class="text-danger small mt-1 d-none" id="employeeSelectionError">Please select at least one employee.</div>
                                    </div>
                                </div>

                                {{-- BULK PAYROLL SETTINGS FORM --}}
                                <div id="bulkPayrollForms" style="display: none;">
                                    <div class="alert alert-info mb-3">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <strong>Bulk Mode Active:</strong> Settings below will be applied to <span id="selectedCount">0</span> selected employee(s).
                                    </div>

                                    {{-- PAYROLL BASIS (Now grouped) --}}
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Payroll Basis</h4>
                                            <p class="text-muted small">Note: Payroll basis will be automatically validated based on employee roles.</p>
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <div class="btn-group w-100" role="group" aria-label="Payroll Basis Toggle">
                                                <input type="radio" class="btn-check" name="bulk_payroll_basis" id="bulkBasisAttendance" value="attendance_only">
                                                <label class="btn btn-outline-primary" for="bulkBasisAttendance">
                                                    Attendance Only
                                                </label>
                                                <input type="radio" class="btn-check" name="bulk_payroll_basis" id="bulkBasisBooking" value="booking_and_attendance">
                                                <label class="btn btn-outline-primary" for="bulkBasisBooking">
                                                    Booking + Attendance
                                                </label>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted" id="bulkPayrollBasisHint">Select attendance-only for HR/Finance staff, or booking + attendance for Photographers.</small>
                                            </div>
                                            <div class="text-danger small mt-1 d-none" id="bulkPayrollBasisError">Please select a payroll basis.</div>
                                        </div>
                                    </div>

                                    {{-- BASIC SALARY INFORMATION --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Basic Salary Information</h4>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Monthly Salary</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_monthly_salary"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <small class="text-muted">Fixed monthly salary (if applicable)</small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Daily Rate</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_daily_rate"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <small class="text-muted">Per day rate</small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Hourly Rate</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_hourly_rate"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                            <small class="text-muted">Per hour rate (auto-calculated if empty)</small>
                                        </div>
                                    </div>

                                    {{-- PHOTOGRAPHER-SPECIFIC FIELDS (Hidden by default) --}}
                                    <div id="bulkPhotographerPayrollFields" style="display: none;">
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <h4 class="card-title text-primary mb-3">Photographer Commission Settings</h4>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Per Booking Rate</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">₱</span>
                                                    <input type="number" class="form-control bulk-field" name="bulk_per_booking_rate"
                                                        step="0.01" min="0" placeholder="0.00">
                                                </div>
                                                <small class="text-muted">Fixed amount per booking</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Commission Percentage</label>
                                                <div class="input-group">
                                                    <input type="number" class="form-control bulk-field" name="bulk_booking_commission_percentage"
                                                        step="0.01" min="0" max="100" placeholder="0">
                                                    <span class="input-group-text">%</span>
                                                </div>
                                                <small class="text-muted">Percentage of booking amount</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- DEDUCTIONS --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Deductions</h4>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SSS Deduction</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_sss_deduction"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">PhilHealth Deduction</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_phic_deduction"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Pag-IBIG Deduction</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_hdmf_deduction"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Withholding Tax</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_tax_withholding"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">SSS Loan</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_sss_loan_deduction"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Pag-IBIG Loan</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_hdmf_loan_deduction"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Other Deductions</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_other_deductions"
                                                    step="0.01" min="0" value="0">
                                            </div>
                                        </div>
                                    </div>

                                    {{-- TAX AND VAT SETTINGS --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Tax & VAT Settings</h4>
                                        </div>

                                        {{-- Tax Toggle Row --}}
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input bulk-field" type="checkbox" role="switch"
                                                        id="bulkIsTaxable" name="bulk_is_taxable" value="1" checked>
                                                </div>
                                                <span class="fw-medium">Taxable</span>
                                            </div>
                                        </div>

                                        {{-- Tax Fields Row --}}
                                        <div class="col-md-4 mb-3 bulk-tax-fields">
                                            <label class="form-label">Tax Type</label>
                                            <select class="form-select bulk-field" name="bulk_tax_type" id="bulkTaxType">
                                                <option value="withholding">Withholding Tax</option>
                                                <option value="graduated">Graduated Tax</option>
                                                <option value="exempt">Exempt</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-tax-percentage-field">
                                            <label class="form-label">Tax Percentage</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control bulk-field" name="bulk_tax_percentage"
                                                    step="0.01" min="0" max="100" placeholder="Tax %">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Tax Code</label>
                                            <input type="text" class="form-control bulk-field" name="bulk_tax_code" placeholder="e.g., WITH-2024">
                                        </div>

                                        {{-- VAT Toggle Row --}}
                                        <div class="col-12 mb-2 mt-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input bulk-field" type="checkbox" role="switch"
                                                        id="bulkSubjectToVat" name="bulk_subject_to_vat" value="1">
                                                </div>
                                                <span class="fw-medium">Subject to VAT</span>
                                            </div>
                                        </div>

                                        {{-- VAT Fields Row --}}
                                        <div class="col-md-4 mb-3 bulk-vat-fields" style="display: none;">
                                            <label class="form-label">VAT Percentage</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control bulk-field" name="bulk_vat_percentage"
                                                    value="12" step="0.01" min="0" max="100">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-vat-fields" style="display: none;">
                                            <label class="form-label">VAT Type</label>
                                            <select class="form-select bulk-field" name="bulk_vat_type">
                                                <option value="inclusive">Inclusive</option>
                                                <option value="exclusive">Exclusive</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- ABSENCE AND UNDERTIME --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Absence & Undertime Settings</h4>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Absence Deduction Per Day</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_absence_deduction_per_day"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Undertime Deduction Per Hour</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_undertime_deduction_per_hour"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Late Grace Period (minutes)</label>
                                            <input type="number" class="form-control bulk-field" name="bulk_late_grace_period_minutes"
                                                value="15" min="0" max="120">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Late Deduction Per Minute</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_late_deduction_per_minute"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Absence Deduction Method</label>
                                            <select class="form-select bulk-field" name="bulk_absent_deduction_method" id="bulkAbsentDeductionMethod">
                                                <option value="deduct_daily_rate">Deduct Daily Rate</option>
                                                <option value="deduct_fixed_amount">Deduct Fixed Amount</option>
                                                <option value="deduct_percentage">Deduct Percentage</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-absent-fixed-field" style="display: none;">
                                            <label class="form-label">Fixed Deduction Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control bulk-field" name="bulk_absent_fixed_deduction"
                                                    step="0.01" min="0" placeholder="0.00">
                                            </div>
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-absent-percentage-field" style="display: none;">
                                            <label class="form-label">Percentage Deduction</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control bulk-field" name="bulk_absent_percentage_deduction"
                                                    step="0.01" min="0" max="100" placeholder="0">
                                                <span class="input-group-text">%</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PAYMENT SCHEDULE --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Payment Schedule</h4>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Payment Schedule <span class="text-danger">*</span></label>
                                            <select class="form-select bulk-field" name="bulk_payment_schedule" id="bulkPaymentSchedule" required>
                                                <option value="">Select Schedule</option>
                                                <option value="weekly">Weekly</option>
                                                <option value="bi_weekly">Bi-Weekly</option>
                                                <option value="semi_monthly">Semi-Monthly</option>
                                                <option value="monthly">Monthly</option>
                                            </select>
                                            <div class="invalid-feedback">Please select a payment schedule.</div>
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-payday-fields" id="bulkPayday1Field" style="display: none;">
                                            <label class="form-label">Payday 1 (Day of Month) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control bulk-field" name="bulk_payday_1" min="1" max="31">
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-payday-fields" id="bulkPayday2Field" style="display: none;">
                                            <label class="form-label">Payday 2 (Day of Month) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control bulk-field" name="bulk_payday_2" min="1" max="31">
                                        </div>

                                        <div class="col-md-4 mb-3 bulk-payday-fields" id="bulkPaydayWeeklyField" style="display: none;">
                                            <label class="form-label">Payday (Day of Week) <span class="text-danger">*</span></label>
                                            <select class="form-select bulk-field" name="bulk_payday_weekly">
                                                <option value="">Select Day</option>
                                                <option value="monday">Monday</option>
                                                <option value="tuesday">Tuesday</option>
                                                <option value="wednesday">Wednesday</option>
                                                <option value="thursday">Thursday</option>
                                                <option value="friday">Friday</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- BANKING INFORMATION --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Banking Information</h4>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Bank Name</label>
                                            <input type="text" class="form-control bulk-field" name="bulk_bank_name" placeholder="e.g., BDO, BPI, MetroBank">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Account Number</label>
                                            <input type="text" class="form-control bulk-field" name="bulk_bank_account_number" placeholder="Account number">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Account Name</label>
                                            <input type="text" class="form-control bulk-field" name="bulk_bank_account_name" placeholder="Account holder name">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Payment Method</label>
                                            <select class="form-select bulk-field" name="bulk_payment_method">
                                                <option value="bank_transfer">Bank Transfer</option>
                                                <option value="cash">Cash</option>
                                                <option value="check">Check</option>
                                            </select>
                                        </div>
                                    </div>

                                    {{-- STATUS AND DATES --}}
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Status & Effective Dates</h4>
                                        </div>

                                        {{-- Active Toggle Row --}}
                                        <div class="col-12 mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input bulk-field" type="checkbox" role="switch"
                                                        id="bulkIsActive" name="bulk_is_active" value="1" checked>
                                                </div>
                                                <span class="fw-medium">Active</span>
                                            </div>
                                        </div>

                                        {{-- Date Fields Row --}}
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Effective Date</label>
                                            <input type="date" class="form-control bulk-field" name="bulk_effective_date" value="{{ date('Y-m-d') }}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Expiry Date</label>
                                            <input type="date" class="form-control bulk-field" name="bulk_expiry_date">
                                        </div>

                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control bulk-field" name="bulk_notes" rows="2" placeholder="Additional notes about payroll settings..."></textarea>
                                        </div>
                                    </div>

                                    {{-- HIDDEN FIELD TO STORE SELECTED EMPLOYEE IDS --}}
                                    <input type="hidden" name="selected_employees" id="selectedEmployees" value="">

                                    {{-- SUBMIT BUTTON --}}
                                    <div class="d-flex justify-content-start">
                                        <button type="submit" class="btn btn-primary" id="submitBtn"
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span id="submitText">Create Bulk Payroll Settings</span>
                                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
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
            let allEmployees = [];
            let selectedEmployees = [];

            // ==================== LOAD EMPLOYEES BASED ON STUDIO SELECTION ====================
            $('#studioSelect').on('change', function() {
                const studioId = $(this).val();
                const $employeeLoadingSpinner = $('#employeeLoadingSpinner');
                const $employeeCheckboxList = $('#employeeCheckboxList');
                const $employeeCheckboxes = $('#employeeCheckboxes');
                const $noEmployeesMessage = $('#noEmployeesMessage');
                const $selectAllBtn = $('#selectAllEmployeesBtn');
                const $deselectAllBtn = $('#deselectAllEmployeesBtn');
                const $bulkForms = $('#bulkPayrollForms');
                const $selectedCount = $('#selectedCount');

                if (!studioId) {
                    $employeeCheckboxList.hide();
                    $employeeLoadingSpinner.hide();
                    $selectAllBtn.prop('disabled', true);
                    $deselectAllBtn.prop('disabled', true);
                    $bulkForms.hide();
                    selectedEmployees = [];
                    $selectedCount.text('0');
                    return;
                }

                $employeeLoadingSpinner.show();
                $employeeCheckboxList.hide();
                $employeeCheckboxes.empty();
                $noEmployeesMessage.hide();
                $selectAllBtn.prop('disabled', true);
                $deselectAllBtn.prop('disabled', true);
                $bulkForms.hide();
                selectedEmployees = [];
                $selectedCount.text('0');

                Swal.fire({
                    title: 'Loading employees...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: '{{ route("studio-hr.payroll-settings.employees") }}',
                    method: 'GET',
                    data: { studio_id: studioId, exclude_with_payroll: true },
                    success: function(response) {
                        Swal.close();
                        $employeeLoadingSpinner.hide();

                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                allEmployees = response.data;
                                renderEmployeeCheckboxes(response.data);
                                $employeeCheckboxList.show();
                                $selectAllBtn.prop('disabled', false);
                                $deselectAllBtn.prop('disabled', false);

                                let message = `Found ${response.data.length} eligible employee(s)`;
                                if (response.debug) {
                                    message += ` (${response.debug.available} available out of ${response.debug.total_found} total)`;
                                }

                                Swal.fire({
                                    icon: 'success',
                                    title: 'Employees Loaded',
                                    text: message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                $noEmployeesMessage.show();
                                $employeeCheckboxList.show();

                                let message = 'No eligible employees found for this studio.';
                                if (response.debug) {
                                    if (response.debug.total_found === 0) {
                                        message = 'No employees found in this studio. Make sure employees are properly assigned.';
                                    } else if (response.debug.total_found > 0 && response.debug.available === 0) {
                                        message = `All ${response.debug.total_found} employee(s) in this studio already have payroll settings.`;
                                    }
                                }

                                Swal.fire({
                                    icon: 'info',
                                    title: 'No Employees Available',
                                    text: message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }
                        } else {
                            $noEmployeesMessage.show().text(response.message || 'Error loading employees');
                            $employeeCheckboxList.show();

                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to load employees',
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        $employeeLoadingSpinner.hide();
                        $noEmployeesMessage.show().text('Failed to load employees. Please try again.');
                        $employeeCheckboxList.show();

                        let errorMessage = 'Failed to load employees. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
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
            });

            // ==================== RENDER EMPLOYEE CHECKBOXES ====================
            function renderEmployeeCheckboxes(employees) {
                const $container = $('#employeeCheckboxes');
                $container.empty();

                // Group employees by role
                const grouped = {
                    'studio-photographer': [],
                    'studio-hr': [],
                    'studio-finance': []
                };

                employees.forEach(emp => {
                    if (grouped.hasOwnProperty(emp.role)) {
                        grouped[emp.role].push(emp);
                    }
                });

                // Render Photographers first
                if (grouped['studio-photographer'].length > 0) {
                    $container.append('<div class="col-12 mt-2 mb-1"><h6 class="text-primary"><i class="ti ti-camera me-2"></i>Photographers</h6></div>');
                    grouped['studio-photographer'].forEach(emp => {
                        $container.append(createEmployeeCheckbox(emp));
                    });
                }

                // Render HR
                if (grouped['studio-hr'].length > 0) {
                    $container.append('<div class="col-12 mt-3 mb-1"><h6 class="text-success"><i class="ti ti-users me-2"></i>Human Resource</h6></div>');
                    grouped['studio-hr'].forEach(emp => {
                        $container.append(createEmployeeCheckbox(emp));
                    });
                }

                // Render Finance
                if (grouped['studio-finance'].length > 0) {
                    $container.append('<div class="col-12 mt-3 mb-1"><h6 class="text-info"><i class="ti ti-coin me-2"></i>Finance</h6></div>');
                    grouped['studio-finance'].forEach(emp => {
                        $container.append(createEmployeeCheckbox(emp));
                    });
                }

                // Attach change event to checkboxes
                $('.employee-checkbox').on('change', function() {
                    updateSelectedEmployees();
                });
            }

            function createEmployeeCheckbox(emp) {
                let roleBadge = '';
                if (emp.role === 'studio-photographer') {
                    roleBadge = '<span class="badge badge-soft-primary ms-2">Photographer</span>';
                } else if (emp.role === 'studio-hr') {
                    roleBadge = '<span class="badge badge-soft-success ms-2">HR</span>';
                } else if (emp.role === 'studio-finance') {
                    roleBadge = '<span class="badge badge-soft-info ms-2">Finance</span>';
                }

                return `
                    <div class="col-md-6 mb-2">
                        <div class="form-check">
                            <input class="form-check-input employee-checkbox" type="checkbox"
                                value="${emp.id}"
                                id="emp_${emp.id}"
                                data-role="${emp.role}"
                                data-name="${emp.full_name}">
                            <label class="form-check-label" for="emp_${emp.id}">
                                <strong>${emp.full_name}</strong> ${roleBadge}
                                <br><small class="text-muted">${emp.email}</small>
                            </label>
                        </div>
                    </div>
                `;
            }

            // ==================== UPDATE SELECTED EMPLOYEES ====================
            function updateSelectedEmployees() {
                selectedEmployees = [];
                $('.employee-checkbox:checked').each(function() {
                    selectedEmployees.push({
                        id: $(this).val(),
                        role: $(this).data('role'),
                        name: $(this).data('name')
                    });
                });

                const count = selectedEmployees.length;
                $('#selectedCount').text(count);
                $('#selectedEmployees').val(JSON.stringify(selectedEmployees.map(e => e.id)));

                if (count > 0) {
                    $('#bulkPayrollForms').show();
                    
                    // Validate role compatibility for payroll basis
                    validateSelectedEmployeesRoles();
                } else {
                    $('#bulkPayrollForms').hide();
                    $('#bulkPayrollBasisError').addClass('d-none');
                }

                // Update select/deselect all buttons state
                updateSelectDeselectButtons();
            }

            // ==================== VALIDATE SELECTED EMPLOYEES ROLES ====================
            function validateSelectedEmployeesRoles() {
                const roles = [...new Set(selectedEmployees.map(e => e.role))];
                const $bulkBasisAttendance = $('#bulkBasisAttendance');
                const $bulkBasisBooking = $('#bulkBasisBooking');
                const $attendanceLabel = $('label[for="bulkBasisAttendance"]');
                const $bookingLabel = $('label[for="bulkBasisBooking"]');
                const $hint = $('#bulkPayrollBasisHint');

                // Reset all states
                $bulkBasisAttendance.prop('disabled', false);
                $bulkBasisBooking.prop('disabled', false);
                $attendanceLabel.removeClass('disabled-option').css('opacity', '1').removeAttr('title');
                $bookingLabel.removeClass('disabled-option').css('opacity', '1').removeAttr('title');
                $bulkBasisAttendance.prop('checked', false);
                $bulkBasisBooking.prop('checked', false);

                // Check if mixed roles (photographers + non-photographers)
                const hasPhotographer = roles.includes('studio-photographer');
                const hasNonPhotographer = roles.some(r => r === 'studio-hr' || r === 'studio-finance');

                if (hasPhotographer && hasNonPhotographer) {
                    // Mixed selection - cannot proceed
                    $bulkBasisAttendance.prop('disabled', true);
                    $bulkBasisBooking.prop('disabled', true);
                    $attendanceLabel.addClass('disabled-option').css('opacity', '0.5');
                    $bookingLabel.addClass('disabled-option').css('opacity', '0.5');
                    $hint.html('<span class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Cannot mix Photographers with HR/Finance staff in bulk creation. Please select employees of the same type.</span>');
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mixed Employee Types',
                        text: 'You cannot create payroll settings for Photographers and HR/Finance staff together. Please select employees of the same type.',
                        confirmButtonColor: '#3475db'
                    });
                    
                    $('#bulkPayrollForms').hide();
                    $('.employee-checkbox').prop('checked', false);
                    selectedEmployees = [];
                    $('#selectedCount').text('0');
                    $('#selectedEmployees').val('');
                } 
                else if (hasPhotographer) {
                    // Only photographers
                    $bulkBasisAttendance.prop('disabled', true);
                    $attendanceLabel.addClass('disabled-option').css('opacity', '0.5');
                    $attendanceLabel.attr('title', 'Attendance Only is not available for Photographers');
                    $bulkBasisBooking.prop('checked', true);
                    $hint.html('<span class="text-warning"><i class="ti ti-info-circle me-1"></i>Photographers can only use "Booking + Attendance" payroll basis.</span>');
                    $('#bulkPhotographerPayrollFields').show();
                } 
                else if (hasNonPhotographer) {
                    // Only HR/Finance
                    $bulkBasisBooking.prop('disabled', true);
                    $bookingLabel.addClass('disabled-option').css('opacity', '0.5');
                    $bookingLabel.attr('title', 'Booking + Attendance is only for Photographers');
                    $bulkBasisAttendance.prop('checked', true);
                    $hint.html('<span class="text-warning"><i class="ti ti-info-circle me-1"></i>HR and Finance staff can only use "Attendance Only" payroll basis.</span>');
                    $('#bulkPhotographerPayrollFields').hide();
                }
            }

            // ==================== SELECT/DESELECT ALL ====================
            $('#selectAllEmployeesBtn').on('click', function() {
                $('.employee-checkbox').prop('checked', true);
                updateSelectedEmployees();
            });

            $('#deselectAllEmployeesBtn').on('click', function() {
                $('.employee-checkbox').prop('checked', false);
                updateSelectedEmployees();
            });

            function updateSelectDeselectButtons() {
                const totalCheckboxes = $('.employee-checkbox').length;
                const checkedCheckboxes = $('.employee-checkbox:checked').length;

                if (checkedCheckboxes === totalCheckboxes && totalCheckboxes > 0) {
                    $('#selectAllEmployeesBtn').prop('disabled', true);
                    $('#deselectAllEmployeesBtn').prop('disabled', false);
                } else if (checkedCheckboxes === 0) {
                    $('#selectAllEmployeesBtn').prop('disabled', false);
                    $('#deselectAllEmployeesBtn').prop('disabled', true);
                } else {
                    $('#selectAllEmployeesBtn').prop('disabled', false);
                    $('#deselectAllEmployeesBtn').prop('disabled', false);
                }
            }

            // ==================== BULK PAYROLL BASIS TOGGLE ====================
            $('input[name="bulk_payroll_basis"]').on('change', function() {
                if ($(this).val() === 'booking_and_attendance') {
                    $('#bulkPhotographerPayrollFields').show();
                } else {
                    $('#bulkPhotographerPayrollFields').hide();
                }
            });

            // ==================== BULK TAX TOGGLE ====================
            $('#bulkIsTaxable').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.bulk-tax-fields, .bulk-tax-percentage-field').show();
                } else {
                    $('.bulk-tax-fields, .bulk-tax-percentage-field').hide();
                }
            });

            $('#bulkTaxType').on('change', function() {
                if ($(this).val() === 'withholding') {
                    $('.bulk-tax-percentage-field').show();
                } else {
                    $('.bulk-tax-percentage-field').hide();
                }
            });

            // ==================== BULK VAT TOGGLE ====================
            $('#bulkSubjectToVat').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.bulk-vat-fields').show();
                } else {
                    $('.bulk-vat-fields').hide();
                }
            });

            // ==================== BULK ABSENCE DEDUCTION METHOD ====================
            $('#bulkAbsentDeductionMethod').on('change', function() {
                const method = $(this).val();

                $('.bulk-absent-fixed-field, .bulk-absent-percentage-field').hide();

                if (method === 'deduct_fixed_amount') {
                    $('.bulk-absent-fixed-field').show();
                } else if (method === 'deduct_percentage') {
                    $('.bulk-absent-percentage-field').show();
                }
            });

            // ==================== BULK PAYMENT SCHEDULE ====================
            $('#bulkPaymentSchedule').on('change', function() {
                const schedule = $(this).val();

                $('.bulk-payday-fields').hide();

                if (schedule === 'weekly') {
                    $('#bulkPaydayWeeklyField').show();
                } else if (schedule === 'semi_monthly') {
                    $('#bulkPayday1Field, #bulkPayday2Field').show();
                } else if (schedule === 'monthly') {
                    $('#bulkPayday1Field').show();
                }
            });

            // ==================== BULK FORM SUBMIT HANDLER ====================
            $('#payrollForm').on('submit', function(e) {
                e.preventDefault();

                @if(isset($canCreate) && !$canCreate)
                    Swal.fire({
                        icon: 'error',
                        title: 'Permission Denied',
                        text: 'Your account does not have permission to create payroll settings.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                @endif

                // Validate employee selection
                if (selectedEmployees.length === 0) {
                    $('#employeeSelectionError').removeClass('d-none');
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Employees Selected',
                        text: 'Please select at least one employee.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                } else {
                    $('#employeeSelectionError').addClass('d-none');
                }

                // Validate payroll basis selection
                const selectedBasis = $('input[name="bulk_payroll_basis"]:checked').val();
                if (!selectedBasis) {
                    $('#bulkPayrollBasisError').removeClass('d-none');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Payroll Basis Required',
                        text: 'Please select a payroll basis.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                } else {
                    $('#bulkPayrollBasisError').addClass('d-none');
                }

                // Validate payment schedule
                if (!$('#bulkPaymentSchedule').val()) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Payment Schedule Required',
                        text: 'Please select a payment schedule.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                // Validate form
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }

                // Prepare bulk data
                const employees = selectedEmployees.map(e => ({
                    user_id: e.id,
                    payroll_basis: selectedBasis,
                    monthly_salary: $('input[name="bulk_monthly_salary"]').val() || null,
                    daily_rate: $('input[name="bulk_daily_rate"]').val() || null,
                    hourly_rate: $('input[name="bulk_hourly_rate"]').val() || null,
                    per_booking_rate: $('input[name="bulk_per_booking_rate"]').val() || null,
                    booking_commission_percentage: $('input[name="bulk_booking_commission_percentage"]').val() || null,
                    sss_deduction: $('input[name="bulk_sss_deduction"]').val() || 0,
                    phic_deduction: $('input[name="bulk_phic_deduction"]').val() || 0,
                    hdmf_deduction: $('input[name="bulk_hdmf_deduction"]').val() || 0,
                    tax_withholding: $('input[name="bulk_tax_withholding"]').val() || 0,
                    sss_loan_deduction: $('input[name="bulk_sss_loan_deduction"]').val() || 0,
                    hdmf_loan_deduction: $('input[name="bulk_hdmf_loan_deduction"]').val() || 0,
                    other_deductions: $('input[name="bulk_other_deductions"]').val() || 0,
                    is_taxable: $('#bulkIsTaxable').is(':checked') ? 1 : 0,
                    tax_type: $('select[name="bulk_tax_type"]').val(),
                    tax_percentage: $('input[name="bulk_tax_percentage"]').val() || null,
                    tax_code: $('input[name="bulk_tax_code"]').val() || null,
                    subject_to_vat: $('#bulkSubjectToVat').is(':checked') ? 1 : 0,
                    vat_percentage: $('input[name="bulk_vat_percentage"]').val() || 12,
                    vat_type: $('select[name="bulk_vat_type"]').val() || 'exclusive',
                    absence_deduction_per_day: $('input[name="bulk_absence_deduction_per_day"]').val() || null,
                    undertime_deduction_per_hour: $('input[name="bulk_undertime_deduction_per_hour"]').val() || null,
                    late_grace_period_minutes: $('input[name="bulk_late_grace_period_minutes"]').val() || 15,
                    late_deduction_per_minute: $('input[name="bulk_late_deduction_per_minute"]').val() || null,
                    absent_deduction_method: $('select[name="bulk_absent_deduction_method"]').val(),
                    absent_fixed_deduction: $('input[name="bulk_absent_fixed_deduction"]').val() || null,
                    absent_percentage_deduction: $('input[name="bulk_absent_percentage_deduction"]').val() || null,
                    payment_schedule: $('select[name="bulk_payment_schedule"]').val(),
                    payday_1: $('input[name="bulk_payday_1"]').val() || null,
                    payday_2: $('input[name="bulk_payday_2"]').val() || null,
                    payday_weekly: $('select[name="bulk_payday_weekly"]').val() || null,
                    bank_name: $('input[name="bulk_bank_name"]').val() || null,
                    bank_account_number: $('input[name="bulk_bank_account_number"]').val() || null,
                    bank_account_name: $('input[name="bulk_bank_account_name"]').val() || null,
                    payment_method: $('select[name="bulk_payment_method"]').val() || 'bank_transfer',
                    is_active: $('#bulkIsActive').is(':checked') ? 1 : 0,
                    effective_date: $('input[name="bulk_effective_date"]').val() || null,
                    expiry_date: $('input[name="bulk_expiry_date"]').val() || null,
                    notes: $('textarea[name="bulk_notes"]').val() || null
                }));

                const formData = {
                    studio_id: $('#studioSelect').val(),
                    employees: employees
                };

                const $submitBtn = $('#submitBtn');
                const $submitText = $('#submitText');
                const $spinner = $('#spinner');

                $submitBtn.prop('disabled', true);
                $submitText.text('Creating...');
                $spinner.removeClass('d-none');

                $.ajax({
                    url: '{{ route("studio-hr.payroll-settings.bulk-store") }}',
                    method: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    headers: { 
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(response) {
                        if (response.success) {
                            let message = response.message;
                            if (response.data && response.data.errors && response.data.errors.length > 0) {
                                message += '<br><br><strong>Errors:</strong><br>';
                                response.data.errors.forEach(err => {
                                    message += '• ' + err + '<br>';
                                });
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                html: message,
                                showConfirmButton: true,
                                confirmButtonColor: '#3475db'
                            }).then((result) => {
                                window.location.href = '{{ route("studio-hr.payroll-settings.index") }}';
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
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = '<strong>Validation Errors:</strong><br>';
                            for (let field in errors) {
                                errorMessage += '• ' + errors[field].join('<br>• ') + '<br>';
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitText.text('Create Bulk Payroll Settings');
                        $spinner.addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection