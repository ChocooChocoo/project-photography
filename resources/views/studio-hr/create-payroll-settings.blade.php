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

                                {{-- EMPLOYEE SELECTION --}}
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Employee Selection</h4>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Studio <span class="text-danger">*</span></label>
                                        <select class="form-select" name="studio_id" id="studioSelect" required
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a studio.</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Select Employee <span class="text-danger">*</span></label>
                                        <select class="form-select" name="user_id" id="employeeSelect" required disabled>
                                            <option value="">First select a studio</option>
                                        </select>
                                        <div class="invalid-feedback">Please select an employee.</div>
                                    </div>

                                    <div class="col-md-6 mb-3" id="employeeInfoCard" style="display: none;">
                                        <div class="card bg-light">
                                            <div class="card-body py-2">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <i class="ti ti-user-circle fs-1 text-primary"></i>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-1" id="selectedEmployeeName"></h6>
                                                        <p class="mb-0 small text-muted" id="selectedEmployeeRole"></p>
                                                        <p class="mb-0 small text-muted" id="selectedEmployeeEmail"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- PAYROLL BASIS --}}
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Payroll Basis</h4>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <div class="btn-group w-100" role="group" aria-label="Payroll Basis Toggle">
                                            <input type="radio" class="btn-check" name="payroll_basis" id="basisAttendance" value="attendance_only"
                                                {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }} required>
                                            <label class="btn btn-outline-primary" for="basisAttendance">
                                                Attendance Only
                                            </label>
                                            <input type="radio" class="btn-check" name="payroll_basis" id="basisBooking" value="booking_and_attendance"
                                                {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <label class="btn btn-outline-primary" for="basisBooking">
                                                Booking + Attendance
                                            </label>
                                        </div>
                                        <div class="mt-2">
                                            <small class="text-muted" id="payrollBasisHint">Select attendance-only for HR/Finance staff, or booking + attendance for Photographers.</small>
                                        </div>
                                        <div class="text-danger small mt-1 d-none" id="payrollBasisError">Please select a payroll basis.</div>
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
                                            <input type="number" class="form-control" name="monthly_salary"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                        <small class="text-muted">Fixed monthly salary (if applicable)</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Daily Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="daily_rate"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                        <small class="text-muted">Per day rate</small>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Hourly Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hourly_rate"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                        <small class="text-muted">Per hour rate (auto-calculated if empty)</small>
                                    </div>
                                </div>

                                {{-- PHOTOGRAPHER-SPECIFIC FIELDS (Hidden by default) --}}
                                <div id="photographerPayrollFields" style="display: none;">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">Photographer Commission Settings</h4>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Per Booking Rate</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" name="per_booking_rate"
                                                       step="0.01" min="0" placeholder="0.00"
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <small class="text-muted">Fixed amount per booking</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Commission Percentage</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="booking_commission_percentage"
                                                       step="0.01" min="0" max="100" placeholder="0"
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <small class="text-muted">Percentage of booking amount</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- ALLOWANCES --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Allowances</h4>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Rice Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="rice_allowance"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Clothing Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="clothing_allowance"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Laundry Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="laundry_allowance"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Transportation Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="transportation_allowance"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Meal Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="meal_allowance"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Other Allowances</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="other_allowances"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                {{-- CUSTOM ALLOWANCES --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0">Custom Allowances</label>
                                            @if(!isset($canCreate) || $canCreate)
                                                <button type="button" class="btn btn-sm btn-soft-primary" id="addCustomAllowance">
                                                    <i class="ti ti-plus"></i> Add Allowance
                                                </button>
                                            @endif
                                        </div>
                                        <div id="customAllowancesContainer"></div>
                                        <small class="text-muted">Add custom allowance types if needed</small>
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
                                            <input type="number" class="form-control" name="sss_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">PhilHealth Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="phic_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pag-IBIG Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hdmf_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Withholding Tax</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="tax_withholding"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">SSS Loan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="sss_loan_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pag-IBIG Loan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hdmf_loan_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Cash Advance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="cash_advance_deduction"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Other Deductions</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="other_deductions"
                                                   step="0.01" min="0" value="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>
                                </div>

                                {{-- CUSTOM DEDUCTIONS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold mb-0">Custom Deductions</label>
                                            @if(!isset($canCreate) || $canCreate)
                                                <button type="button" class="btn btn-sm btn-soft-danger" id="addCustomDeduction">
                                                    <i class="ti ti-plus"></i> Add Deduction
                                                </button>
                                            @endif
                                        </div>
                                        <div id="customDeductionsContainer"></div>
                                        <small class="text-muted">Add custom deduction types if needed</small>
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
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="isTaxable" name="is_taxable" value="1" checked
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Taxable</span>
                                        </div>
                                    </div>

                                    {{-- Tax Fields Row --}}
                                    <div class="col-md-4 mb-3 tax-fields">
                                        <label class="form-label">Tax Type</label>
                                        <select class="form-select" name="tax_type"
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <option value="withholding">Withholding Tax</option>
                                            <option value="graduated">Graduated Tax</option>
                                            <option value="exempt">Exempt</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3 tax-percentage-field">
                                        <label class="form-label">Tax Percentage</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="tax_percentage"
                                                   step="0.01" min="0" max="100" placeholder="Tax %"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Tax Code</label>
                                        <input type="text" class="form-control" name="tax_code" placeholder="e.g., WITH-2024"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    {{-- VAT Toggle Row --}}
                                    <div class="col-12 mb-2 mt-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="subjectToVat" name="subject_to_vat" value="1"
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Subject to VAT</span>
                                        </div>
                                    </div>

                                    {{-- VAT Fields Row --}}
                                    <div class="col-md-4 mb-3 vat-fields" style="display: none;">
                                        <label class="form-label">VAT Percentage</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="vat_percentage"
                                                   value="12" step="0.01" min="0" max="100"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3 vat-fields" style="display: none;">
                                        <label class="form-label">VAT Type</label>
                                        <select class="form-select" name="vat_type"
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
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
                                            <input type="number" class="form-control" name="absence_deduction_per_day"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Undertime Deduction Per Hour</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="undertime_deduction_per_hour"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Late Grace Period (minutes)</label>
                                        <input type="number" class="form-control" name="late_grace_period_minutes"
                                               value="15" min="0" max="120"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Late Deduction Per Minute</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="late_deduction_per_minute"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Absence Deduction Method</label>
                                        <select class="form-select" name="absent_deduction_method" id="absentDeductionMethod"
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <option value="deduct_daily_rate">Deduct Daily Rate</option>
                                            <option value="deduct_fixed_amount">Deduct Fixed Amount</option>
                                            <option value="deduct_percentage">Deduct Percentage</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4 mb-3 absent-fixed-field" style="display: none;">
                                        <label class="form-label">Fixed Deduction Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="absent_fixed_deduction"
                                                   step="0.01" min="0" placeholder="0.00"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3 absent-percentage-field" style="display: none;">
                                        <label class="form-label">Percentage Deduction</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="absent_percentage_deduction"
                                                   step="0.01" min="0" max="100" placeholder="0"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- OVERTIME SETTINGS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Overtime Settings</h4>
                                    </div>

                                    {{-- Overtime Toggle Row --}}
                                    <div class="col-12 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="overtimeEnabled" name="overtime_enabled" value="1" checked
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Enable Overtime</span>
                                        </div>
                                    </div>

                                    {{-- Overtime Fields Row --}}
                                    <div class="col-md-3 mb-3 overtime-field">
                                        <label class="form-label">Overtime Rate Multiplier</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="overtime_rate_multiplier"
                                                   value="1.25" step="0.01" min="1" max="5"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 mb-3 overtime-field">
                                        <label class="form-label">Night Differential Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="night_differential_rate"
                                                   value="1.10" step="0.01" min="1" max="5"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>

                                    <div class="col-md-3 mb-3 overtime-field">
                                        <label class="form-label">Night Diff Start</label>
                                        <input type="time" class="form-control" name="night_differential_start" value="22:00"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-3 mb-3 overtime-field">
                                        <label class="form-label">Night Diff End</label>
                                        <input type="time" class="form-control" name="night_differential_end" value="06:00"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    {{-- Holiday Overtime Toggle Row --}}
                                    <div class="col-12 mb-2 mt-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="holidayOvertimeEnabled" name="holiday_overtime_enabled" value="1" checked
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Enable Holiday Overtime</span>
                                        </div>
                                    </div>

                                    {{-- Holiday Overtime Fields Row --}}
                                    <div class="col-md-3 mb-3 holiday-field">
                                        <label class="form-label">Holiday Overtime Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="holiday_overtime_rate"
                                                   value="2.00" step="0.01" min="1" max="5"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- LEAVE SETTINGS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Leave Settings</h4>
                                    </div>

                                    {{-- Paid Holidays Toggle Row --}}
                                    <div class="col-12 mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="paidHolidays" name="paid_holidays" value="1" checked
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Paid Holidays</span>
                                        </div>
                                    </div>

                                    {{-- Holiday Fields Row --}}
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Regular Holidays/Year</label>
                                        <input type="number" class="form-control" name="regular_holidays_per_year" value="12" min="0" max="365"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Special Holidays/Year</label>
                                        <input type="number" class="form-control" name="special_holidays_per_year" value="5" min="0" max="365"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Vacation Leave Days</label>
                                        <input type="number" class="form-control" name="vacation_leave_days_per_year" value="15" min="0" max="365"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Sick Leave Days</label>
                                        <input type="number" class="form-control" name="sick_leave_days_per_year" value="15" min="0" max="365"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Emergency Leave Days</label>
                                        <input type="number" class="form-control" name="emergency_leave_days_per_year" value="3" min="0" max="365"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    {{-- Leave Conversion Toggle Row --}}
                                    <div class="col-12 mb-2 mt-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="leaveConversion" name="leave_conversion_enabled" value="1"
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Leave Conversion</span>
                                        </div>
                                    </div>

                                    {{-- Leave Conversion Fields Row --}}
                                    <div class="col-md-3 mb-3 leave-conversion-field" style="display: none;">
                                        <label class="form-label">Conversion Rate (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="leave_conversion_rate"
                                                   step="0.01" min="0" max="100" placeholder="100"
                                                   {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
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
                                        <select class="form-select" name="payment_schedule" id="paymentSchedule" required
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            <option value="">Select Schedule</option>
                                            <option value="weekly">Weekly</option>
                                            <option value="bi_weekly">Bi-Weekly</option>
                                            <option value="semi_monthly">Semi-Monthly</option>
                                            <option value="monthly">Monthly</option>
                                        </select>
                                        <div class="invalid-feedback">Please select a payment schedule.</div>
                                    </div>

                                    <div class="col-md-4 mb-3 payday-fields" id="payday1Field" style="display: none;">
                                        <label class="form-label">Payday 1 (Day of Month) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="payday_1" min="1" max="31"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3 payday-fields" id="payday2Field" style="display: none;">
                                        <label class="form-label">Payday 2 (Day of Month) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="payday_2" min="1" max="31"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3 payday-fields" id="paydayWeeklyField" style="display: none;">
                                        <label class="form-label">Payday (Day of Week) <span class="text-danger">*</span></label>
                                        <select class="form-select" name="payday_weekly"
                                            {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
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
                                        <input type="text" class="form-control" name="bank_name" placeholder="e.g., BDO, BPI, MetroBank"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Account Number</label>
                                        <input type="text" class="form-control" name="bank_account_number" placeholder="Account number"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Account Name</label>
                                        <input type="text" class="form-control" name="bank_account_name" placeholder="Account holder name"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
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
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                       id="isActive" name="is_active" value="1" checked
                                                       {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                            </div>
                                            <span class="fw-medium">Active</span>
                                        </div>
                                    </div>

                                    {{-- Date Fields Row --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Effective Date</label>
                                        <input type="date" class="form-control" name="effective_date" value="{{ date('Y-m-d') }}"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" name="expiry_date"
                                               {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes about payroll settings..."
                                                  {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}></textarea>
                                    </div>
                                </div>

                                {{-- SUBMIT BUTTON --}}
                                <div class="d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary" id="submitBtn"
                                        {{ isset($canCreate) && !$canCreate ? 'disabled' : '' }}>
                                        <span id="submitText">Create Payroll Settings</span>
                                        <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                    </button>
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
            // ==================== LOAD EMPLOYEES BASED ON STUDIO SELECTION ====================
            $('#studioSelect').on('change', function() {
                const studioId = $(this).val();
                const $employeeSelect = $('#employeeSelect');
                const $employeeInfoCard = $('#employeeInfoCard');

                if (!studioId) {
                    $employeeSelect.prop('disabled', true).html('<option value="">First select a studio</option>');
                    $employeeInfoCard.hide();
                    return;
                }

                $employeeSelect.prop('disabled', true).html('<option value="">Loading employees...</option>');

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
                        $employeeSelect.html('<option value="">Select Employee</option>');

                        if (response.success) {
                            if (response.data && response.data.length > 0) {
                                response.data.forEach(function(emp) {
                                    $employeeSelect.append(
                                        `<option value="${emp.id}"
                                            data-role="${emp.role}"
                                            data-email="${emp.email}"
                                            data-name="${emp.full_name}">
                                            ${emp.full_name} (${emp.role_display})
                                        </option>`
                                    );
                                });
                                $employeeSelect.prop('disabled', false);

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
                                $employeeSelect.html('<option value="">No eligible employees found</option>');
                                $employeeSelect.prop('disabled', true);

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
                            $employeeSelect.html('<option value="">Error loading employees</option>');
                            $employeeSelect.prop('disabled', true);

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
                        $employeeSelect.html('<option value="">Error loading employees</option>');
                        $employeeSelect.prop('disabled', true);

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

            // ==================== SHOW EMPLOYEE INFO AND RESTRICT PAYROLL BASIS WHEN SELECTED ====================
            $('#employeeSelect').on('change', function() {
                const selected = $(this).find(':selected');
                const employeeId = $(this).val();

                if (employeeId) {
                    const name = selected.data('name');
                    const role = selected.data('role');
                    const email = selected.data('email');

                    let roleDisplay = {
                        'studio-hr': 'Human Resource',
                        'studio-finance': 'Finance',
                        'studio-photographer': 'Photographer'
                    }[role] || role;

                    $('#selectedEmployeeName').text(name);
                    $('#selectedEmployeeRole').text('Role: ' + roleDisplay);
                    $('#selectedEmployeeEmail').text('Email: ' + email);
                    $('#employeeInfoCard').show();
                    
                    // ========== RESTRICT PAYROLL BASIS BASED ON EMPLOYEE ROLE ==========
                    restrictPayrollBasisByRole(role);
                    
                } else {
                    $('#employeeInfoCard').hide();
                    // Reset radio buttons when no employee selected
                    resetPayrollBasisOptions();
                }
            });

            // ==================== FUNCTION TO RESTRICT PAYROLL BASIS BY ROLE ====================
            function restrictPayrollBasisByRole(role) {
                const $basisAttendance = $('#basisAttendance');
                const $basisBooking = $('#basisBooking');
                const $basisAttendanceLabel = $('label[for="basisAttendance"]');
                const $basisBookingLabel = $('label[for="basisBooking"]');
                const $hint = $('#payrollBasisHint');
                
                // Reset all states first
                $basisAttendance.prop('disabled', false);
                $basisBooking.prop('disabled', false);
                $basisAttendance.prop('checked', false);
                $basisBooking.prop('checked', false);
                $basisAttendanceLabel.removeClass('disabled-option').css('opacity', '1');
                $basisBookingLabel.removeClass('disabled-option').css('opacity', '1');
                
                // Apply restrictions based on role
                if (role === 'studio-photographer') {
                    // Photographers: Only Booking + Attendance allowed
                    $basisAttendance.prop('disabled', true);
                    $basisAttendanceLabel.addClass('disabled-option').css('opacity', '0.5');
                    $basisBooking.prop('checked', true);
                    
                    // Add title/tooltip
                    $basisAttendanceLabel.attr('title', 'Attendance Only is not available for Photographers');
                    $basisBookingLabel.attr('title', '');
                    
                    // Update hint
                    $hint.html('<span class="text-warning"><i class="ti ti-info-circle me-1"></i>Photographers can only use "Booking + Attendance" payroll basis.</span>');
                    
                } else if (role === 'studio-hr' || role === 'studio-finance') {
                    // HR/Finance: Only Attendance Only allowed
                    $basisBooking.prop('disabled', true);
                    $basisBookingLabel.addClass('disabled-option').css('opacity', '0.5');
                    $basisAttendance.prop('checked', true);
                    
                    // Add title/tooltip
                    $basisBookingLabel.attr('title', 'Booking + Attendance is only for Photographers');
                    $basisAttendanceLabel.attr('title', '');
                    
                    // Update hint
                    $hint.html('<span class="text-warning"><i class="ti ti-info-circle me-1"></i>HR and Finance staff can only use "Attendance Only" payroll basis.</span>');
                }
            }

            // ==================== FUNCTION TO RESET PAYROLL BASIS OPTIONS ====================
            function resetPayrollBasisOptions() {
                const $basisAttendance = $('#basisAttendance');
                const $basisBooking = $('#basisBooking');
                const $basisAttendanceLabel = $('label[for="basisAttendance"]');
                const $basisBookingLabel = $('label[for="basisBooking"]');
                const $hint = $('#payrollBasisHint');
                
                $basisAttendance.prop('disabled', false);
                $basisBooking.prop('disabled', false);
                $basisAttendance.prop('checked', false);
                $basisBooking.prop('checked', false);
                $basisAttendanceLabel.removeClass('disabled-option').css('opacity', '1').removeAttr('title');
                $basisBookingLabel.removeClass('disabled-option').css('opacity', '1').removeAttr('title');
                
                // Reset hint
                $hint.html('Select attendance-only for HR/Finance staff, or booking + attendance for Photographers.');
            }

            // ==================== PAYROLL BASIS TOGGLE (with validation) ====================
            $('input[name="payroll_basis"]').on('change', function() {
                const selectedRole = $('#employeeSelect').find(':selected').data('role');
                
                // Validate that the selected option is allowed for this role
                if (selectedRole === 'studio-photographer' && $(this).val() === 'attendance_only') {
                    // This should never happen due to disabled radio, but just in case
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Selection',
                        text: 'Photographers must use "Booking + Attendance" payroll basis.',
                        confirmButtonColor: '#3475db'
                    });
                    $('#basisBooking').prop('checked', true);
                    return;
                }
                
                if ((selectedRole === 'studio-hr' || selectedRole === 'studio-finance') && 
                    $(this).val() === 'booking_and_attendance') {
                    // This should never happen due to disabled radio, but just in case
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Selection',
                        text: 'HR and Finance staff must use "Attendance Only" payroll basis.',
                        confirmButtonColor: '#3475db'
                    });
                    $('#basisAttendance').prop('checked', true);
                    return;
                }
                
                if ($(this).val() === 'booking_and_attendance') {
                    $('#photographerPayrollFields').show();
                } else {
                    $('#photographerPayrollFields').hide();
                }
            });

            // ==================== CUSTOM ALLOWANCES ====================
            let allowanceIndex = 0;

            $('#addCustomAllowance').on('click', function() {
                const container = $('#customAllowancesContainer');
                const index = allowanceIndex++;

                const html = `
                    <div class="row mb-2 custom-allowance-item">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="custom_allowances[${index}][name]" placeholder="Allowance Name">
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="custom_allowances[${index}][amount]" step="0.01" min="0" placeholder="Amount">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-soft-danger remove-custom-item h-100">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                container.append(html);
            });

            // ==================== CUSTOM DEDUCTIONS ====================
            let deductionIndex = 0;

            $('#addCustomDeduction').on('click', function() {
                const container = $('#customDeductionsContainer');
                const index = deductionIndex++;

                const html = `
                    <div class="row mb-2 custom-deduction-item">
                        <div class="col-md-5">
                            <input type="text" class="form-control" name="custom_deductions[${index}][name]" placeholder="Deduction Name">
                        </div>
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" name="custom_deductions[${index}][amount]" step="0.01" min="0" placeholder="Amount">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-sm btn-soft-danger remove-custom-item h-100">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                `;

                container.append(html);
            });

            // ==================== REMOVE CUSTOM ITEM ====================
            $(document).on('click', '.remove-custom-item', function() {
                $(this).closest('.row').remove();
            });

            // ==================== TAX TOGGLE ====================
            $('#isTaxable').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.tax-fields, .tax-percentage-field').show();
                } else {
                    $('.tax-fields, .tax-percentage-field').hide();
                }
            });

            $('#tax_type').on('change', function() {
                if ($(this).val() === 'withholding') {
                    $('.tax-percentage-field').show();
                } else {
                    $('.tax-percentage-field').hide();
                }
            });

            // ==================== VAT TOGGLE ====================
            $('#subjectToVat').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.vat-fields').show();
                } else {
                    $('.vat-fields').hide();
                }
            });

            // ==================== ABSENCE DEDUCTION METHOD ====================
            $('#absentDeductionMethod').on('change', function() {
                const method = $(this).val();

                $('.absent-fixed-field, .absent-percentage-field').hide();

                if (method === 'deduct_fixed_amount') {
                    $('.absent-fixed-field').show();
                } else if (method === 'deduct_percentage') {
                    $('.absent-percentage-field').show();
                }
            });

            // ==================== LEAVE CONVERSION ====================
            $('#leaveConversion').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.leave-conversion-field').show();
                } else {
                    $('.leave-conversion-field').hide();
                }
            });

            // ==================== OVERTIME FIELDS ====================
            $('#overtimeEnabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.overtime-field').show();
                } else {
                    $('.overtime-field').hide();
                }
            });

            $('#holidayOvertimeEnabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.holiday-field').show();
                } else {
                    $('.holiday-field').hide();
                }
            });

            // ==================== PAYMENT SCHEDULE ====================
            $('#paymentSchedule').on('change', function() {
                const schedule = $(this).val();

                $('.payday-fields').hide();

                if (schedule === 'weekly') {
                    $('#paydayWeeklyField').show();
                } else if (schedule === 'semi_monthly') {
                    $('#payday1Field, #payday2Field').show();
                } else if (schedule === 'monthly') {
                    $('#payday1Field').show();
                }
            });

            // ==================== FORM SUBMIT HANDLER ====================
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

                const $form = $(this);
                const $submitBtn = $('#submitBtn');
                const $submitText = $('#submitText');
                const $spinner = $('#spinner');

                // Validate payroll basis based on employee role
                const selectedRole = $('#employeeSelect').find(':selected').data('role');
                const selectedBasis = $('input[name="payroll_basis"]:checked').val();
                
                if (selectedRole === 'studio-photographer' && selectedBasis !== 'booking_and_attendance') {
                    $('#payrollBasisError').text('Photographers must use "Booking + Attendance" payroll basis.').removeClass('d-none');
                    return;
                }
                
                if ((selectedRole === 'studio-hr' || selectedRole === 'studio-finance') && selectedBasis !== 'attendance_only') {
                    $('#payrollBasisError').text('HR and Finance staff must use "Attendance Only" payroll basis.').removeClass('d-none');
                    return;
                }

                if (!$form[0].checkValidity()) {
                    e.stopPropagation();
                    $form.addClass('was-validated');
                    return;
                }

                if (!$('input[name="payroll_basis"]:checked').val()) {
                    $('#payrollBasisError').text('Please select a payroll basis.').removeClass('d-none');
                    return;
                } else {
                    $('#payrollBasisError').addClass('d-none');
                }

                $submitBtn.prop('disabled', true);
                $submitText.text('Creating...');
                $spinner.removeClass('d-none');

                const formData = new FormData(this);

                $.ajax({
                    url: '{{ route("studio-hr.payroll-settings.store") }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: () => {
                                    window.location.href = '{{ route("studio-hr.payroll-settings.index") }}';
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
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';

                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('<br>');
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
                        $submitText.text('Create Payroll Settings');
                        $spinner.addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection