@extends('layouts.studio-hr.app')
@section('title', 'Edit Employee Payroll')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Edit Employee Payroll</h4>
                        </div>
                        <div class="card-body">
                            <form class="needs-validation" novalidate id="payrollForm">
                                @csrf
                                @method('PUT')
                                
                                <input type="hidden" name="payroll_id" id="payrollId" value="{{ $payroll->id }}">
                                
                                {{-- EMPLOYEE INFORMATION (Read-only) --}}
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Employee Information</h4>
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="flex-shrink-0">
                                                                <img src="{{ $payroll->employee->profile_photo_url }}" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/users/user-3.jpg') }}'" alt="{{ $payroll->employee->full_name }}">
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <h5 class="mb-1">{{ $payroll->employee->full_name }}</h5>
                                                                <p class="mb-0 text-muted">{{ $payroll->employee->email }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <table class="table table-sm">
                                                            <tr>
                                                                <td class="fw-medium">Role:</td>
                                                                <td>
                                                                    @php
                                                                        $roleDisplay = [
                                                                            'studio-hr' => 'Human Resource',
                                                                            'studio-finance' => 'Finance',
                                                                            'studio-photographer' => 'Photographer'
                                                                        ][$payroll->employee->role] ?? $payroll->employee->role;
                                                                    @endphp
                                                                    <span class="badge badge-soft-primary">{{ $roleDisplay }}</span>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td class="fw-medium">Studio:</td>
                                                                <td>{{ $payroll->studio->studio_name }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="fw-medium">Payroll Basis:</td>
                                                                <td>
                                                                    <span class="badge {{ $payroll->payroll_basis === 'booking_and_attendance' ? 'badge-soft-info' : 'badge-soft-primary' }}">
                                                                        {{ $payroll->payroll_basis_display }}
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- STUDIO SELECTION (Hidden - can't change studio after creation) --}}
                                <input type="hidden" name="studio_id" value="{{ $payroll->studio_id }}">
                                <input type="hidden" name="user_id" value="{{ $payroll->user_id }}">
                                <input type="hidden" name="payroll_basis" value="{{ $payroll->payroll_basis }}">

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
                                                   value="{{ $payroll->monthly_salary }}" step="0.01" min="0">
                                        </div>
                                        <small class="text-muted">Fixed monthly salary (if applicable)</small>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Daily Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="daily_rate" 
                                                   value="{{ $payroll->daily_rate }}" step="0.01" min="0">
                                        </div>
                                        <small class="text-muted">Per day rate</small>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Hourly Rate</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hourly_rate" 
                                                   value="{{ $payroll->hourly_rate }}" step="0.01" min="0">
                                        </div>
                                        <small class="text-muted">Per hour rate (auto-calculated if empty)</small>
                                    </div>
                                </div>

                                {{-- PHOTOGRAPHER-SPECIFIC FIELDS --}}
                                <div id="photographerPayrollFields" 
                                     style="{{ $payroll->payroll_basis !== 'booking_and_attendance' ? 'display: none;' : '' }}">
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-info mb-3">Photographer Commission Settings</h4>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Per Booking Rate</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₱</span>
                                                <input type="number" class="form-control" name="per_booking_rate" 
                                                       value="{{ $payroll->per_booking_rate }}" step="0.01" min="0">
                                            </div>
                                            <small class="text-muted">Fixed amount per booking</small>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Commission Percentage</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="booking_commission_percentage" 
                                                       value="{{ $payroll->booking_commission_percentage }}" step="0.01" min="0" max="100">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <small class="text-muted">Percentage of booking amount</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- ALLOWANCES --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-success mb-3">Allowances</h4>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Rice Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="rice_allowance" 
                                                   value="{{ $payroll->rice_allowance }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Clothing Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="clothing_allowance" 
                                                   value="{{ $payroll->clothing_allowance }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Laundry Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="laundry_allowance" 
                                                   value="{{ $payroll->laundry_allowance }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Transportation Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="transportation_allowance" 
                                                   value="{{ $payroll->transportation_allowance }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Meal Allowance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="meal_allowance" 
                                                   value="{{ $payroll->meal_allowance }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Other Allowances</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="other_allowances" 
                                                   value="{{ $payroll->other_allowances }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                {{-- CUSTOM ALLOWANCES --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold">Custom Allowances</label>
                                            <button type="button" class="btn btn-sm btn-outline-success" id="addCustomAllowance">
                                                <i class="ti ti-plus"></i> Add Allowance
                                            </button>
                                        </div>
                                        <div id="customAllowancesContainer">
                                            @if($payroll->custom_allowances && count($payroll->custom_allowances) > 0)
                                                @foreach($payroll->custom_allowances as $index => $allowance)
                                                <div class="row mb-2 custom-allowance-item">
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" 
                                                               name="custom_allowances[{{ $index }}][name]" 
                                                               value="{{ $allowance['name'] }}" placeholder="Allowance Name">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="input-group">
                                                            <span class="input-group-text">₱</span>
                                                            <input type="number" class="form-control" 
                                                                   name="custom_allowances[{{ $index }}][amount]" 
                                                                   value="{{ $allowance['amount'] }}" step="0.01" min="0" placeholder="Amount">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-custom-item">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- DEDUCTIONS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-danger mb-3">Deductions</h4>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">SSS Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="sss_deduction" 
                                                   value="{{ $payroll->sss_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">PhilHealth Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="phic_deduction" 
                                                   value="{{ $payroll->phic_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pag-IBIG Deduction</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hdmf_deduction" 
                                                   value="{{ $payroll->hdmf_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Withholding Tax</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="tax_withholding" 
                                                   value="{{ $payroll->tax_withholding }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">SSS Loan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="sss_loan_deduction" 
                                                   value="{{ $payroll->sss_loan_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Pag-IBIG Loan</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="hdmf_loan_deduction" 
                                                   value="{{ $payroll->hdmf_loan_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Cash Advance</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="cash_advance_deduction" 
                                                   value="{{ $payroll->cash_advance_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Other Deductions</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="other_deductions" 
                                                   value="{{ $payroll->other_deductions }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                </div>

                                {{-- CUSTOM DEDUCTIONS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold">Custom Deductions</label>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="addCustomDeduction">
                                                <i class="ti ti-plus"></i> Add Deduction
                                            </button>
                                        </div>
                                        <div id="customDeductionsContainer">
                                            @if($payroll->custom_deductions && count($payroll->custom_deductions) > 0)
                                                @foreach($payroll->custom_deductions as $index => $deduction)
                                                <div class="row mb-2 custom-deduction-item">
                                                    <div class="col-md-5">
                                                        <input type="text" class="form-control" 
                                                               name="custom_deductions[{{ $index }}][name]" 
                                                               value="{{ $deduction['name'] }}" placeholder="Deduction Name">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="input-group">
                                                            <span class="input-group-text">₱</span>
                                                            <input type="number" class="form-control" 
                                                                   name="custom_deductions[{{ $index }}][amount]" 
                                                                   value="{{ $deduction['amount'] }}" step="0.01" min="0" placeholder="Amount">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-custom-item">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- TAX AND VAT SETTINGS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-warning mb-3">Tax & VAT Settings</h4>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="isTaxable" name="is_taxable" value="1" 
                                                   {{ $payroll->is_taxable ? 'checked' : '' }}>
                                            <label class="form-check-label" for="isTaxable">Taxable</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 tax-fields" style="{{ !$payroll->is_taxable ? 'display: none;' : '' }}">
                                        <select class="form-select" name="tax_type" id="taxType">
                                            <option value="withholding" {{ $payroll->tax_type == 'withholding' ? 'selected' : '' }}>Withholding Tax</option>
                                            <option value="graduated" {{ $payroll->tax_type == 'graduated' ? 'selected' : '' }}>Graduated Tax</option>
                                            <option value="exempt" {{ $payroll->tax_type == 'exempt' ? 'selected' : '' }}>Exempt</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 tax-percentage-field" 
                                         style="{{ !$payroll->is_taxable || $payroll->tax_type != 'withholding' ? 'display: none;' : '' }}">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="tax_percentage" 
                                                   value="{{ $payroll->tax_percentage }}" step="0.01" min="0" max="100" placeholder="Tax %">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="subjectToVat" name="subject_to_vat" value="1" 
                                                   {{ $payroll->subject_to_vat ? 'checked' : '' }}>
                                            <label class="form-check-label" for="subjectToVat">Subject to VAT</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 vat-fields" style="{{ !$payroll->subject_to_vat ? 'display: none;' : '' }}">
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="vat_percentage" 
                                                   value="{{ $payroll->vat_percentage }}" step="0.01" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 vat-fields" style="{{ !$payroll->subject_to_vat ? 'display: none;' : '' }}">
                                        <select class="form-select" name="vat_type">
                                            <option value="inclusive" {{ $payroll->vat_type == 'inclusive' ? 'selected' : '' }}>Inclusive</option>
                                            <option value="exclusive" {{ $payroll->vat_type == 'exclusive' ? 'selected' : '' }}>Exclusive</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Tax Code</label>
                                        <input type="text" class="form-control" name="tax_code" value="{{ $payroll->tax_code }}" placeholder="e.g., WITH-2024">
                                    </div>
                                </div>

                                {{-- ABSENCE AND UNDERTIME --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-secondary mb-3">Absence & Undertime Settings</h4>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Absence Deduction Per Day</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="absence_deduction_per_day" 
                                                   value="{{ $payroll->absence_deduction_per_day }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Undertime Deduction Per Hour</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="undertime_deduction_per_hour" 
                                                   value="{{ $payroll->undertime_deduction_per_hour }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Late Grace Period (minutes)</label>
                                        <input type="number" class="form-control" name="late_grace_period_minutes" 
                                               value="{{ $payroll->late_grace_period_minutes }}" min="0" max="120">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Late Deduction Per Minute</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="late_deduction_per_minute" 
                                                   value="{{ $payroll->late_deduction_per_minute }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Absence Deduction Method</label>
                                        <select class="form-select" name="absent_deduction_method" id="absentDeductionMethod">
                                            <option value="deduct_daily_rate" {{ $payroll->absent_deduction_method == 'deduct_daily_rate' ? 'selected' : '' }}>
                                                Deduct Daily Rate
                                            </option>
                                            <option value="deduct_fixed_amount" {{ $payroll->absent_deduction_method == 'deduct_fixed_amount' ? 'selected' : '' }}>
                                                Deduct Fixed Amount
                                            </option>
                                            <option value="deduct_percentage" {{ $payroll->absent_deduction_method == 'deduct_percentage' ? 'selected' : '' }}>
                                                Deduct Percentage
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 absent-fixed-field" 
                                         style="{{ $payroll->absent_deduction_method != 'deduct_fixed_amount' ? 'display: none;' : '' }}">
                                        <label class="form-label">Fixed Deduction Amount</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="absent_fixed_deduction" 
                                                   value="{{ $payroll->absent_fixed_deduction }}" step="0.01" min="0">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 absent-percentage-field" 
                                         style="{{ $payroll->absent_deduction_method != 'deduct_percentage' ? 'display: none;' : '' }}">
                                        <label class="form-label">Percentage Deduction</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="absent_percentage_deduction" 
                                                   value="{{ $payroll->absent_percentage_deduction }}" step="0.01" min="0" max="100">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- OVERTIME SETTINGS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-info mb-3">Overtime Settings</h4>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="overtimeEnabled" name="overtime_enabled" value="1" 
                                                   {{ $payroll->overtime_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="overtimeEnabled">Enable Overtime</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 overtime-field" style="{{ !$payroll->overtime_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Overtime Rate Multiplier</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="overtime_rate_multiplier" 
                                                   value="{{ $payroll->overtime_rate_multiplier }}" step="0.01" min="1" max="5">
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 overtime-field" style="{{ !$payroll->overtime_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Night Differential Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="night_differential_rate" 
                                                   value="{{ $payroll->night_differential_rate }}" step="0.01" min="1" max="5">
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 overtime-field" style="{{ !$payroll->overtime_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Night Diff Start</label>
                                        <input type="time" class="form-control" name="night_differential_start" 
                                               value="{{ $payroll->night_differential_start }}">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 overtime-field" style="{{ !$payroll->overtime_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Night Diff End</label>
                                        <input type="time" class="form-control" name="night_differential_end" 
                                               value="{{ $payroll->night_differential_end }}">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="holidayOvertimeEnabled" name="holiday_overtime_enabled" value="1" 
                                                   {{ $payroll->holiday_overtime_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="holidayOvertimeEnabled">Holiday Overtime</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 holiday-field" style="{{ !$payroll->holiday_overtime_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Holiday Overtime Rate</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="holiday_overtime_rate" 
                                                   value="{{ $payroll->holiday_overtime_rate }}" step="0.01" min="1" max="5">
                                            <span class="input-group-text">x</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- LEAVE SETTINGS --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Leave Settings</h4>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Regular Holidays/Year</label>
                                        <input type="number" class="form-control" name="regular_holidays_per_year" 
                                               value="{{ $payroll->regular_holidays_per_year }}" min="0" max="365">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Special Holidays/Year</label>
                                        <input type="number" class="form-control" name="special_holidays_per_year" 
                                               value="{{ $payroll->special_holidays_per_year }}" min="0" max="365">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="paidHolidays" name="paid_holidays" value="1" 
                                                   {{ $payroll->paid_holidays ? 'checked' : '' }}>
                                            <label class="form-check-label" for="paidHolidays">Paid Holidays</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Vacation Leave Days</label>
                                        <input type="number" class="form-control" name="vacation_leave_days_per_year" 
                                               value="{{ $payroll->vacation_leave_days_per_year }}" min="0" max="365">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Sick Leave Days</label>
                                        <input type="number" class="form-control" name="sick_leave_days_per_year" 
                                               value="{{ $payroll->sick_leave_days_per_year }}" min="0" max="365">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Emergency Leave Days</label>
                                        <input type="number" class="form-control" name="emergency_leave_days_per_year" 
                                               value="{{ $payroll->emergency_leave_days_per_year }}" min="0" max="365">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch mt-4">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="leaveConversion" name="leave_conversion_enabled" value="1" 
                                                   {{ $payroll->leave_conversion_enabled ? 'checked' : '' }}>
                                            <label class="form-check-label" for="leaveConversion">Leave Conversion</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3 leave-conversion-field" 
                                         style="{{ !$payroll->leave_conversion_enabled ? 'display: none;' : '' }}">
                                        <label class="form-label">Conversion Rate (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" name="leave_conversion_rate" 
                                                   value="{{ $payroll->leave_conversion_rate }}" step="0.01" min="0" max="100">
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
                                        <select class="form-select" name="payment_schedule" id="paymentSchedule" required>
                                            <option value="weekly" {{ $payroll->payment_schedule == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                            <option value="bi_weekly" {{ $payroll->payment_schedule == 'bi_weekly' ? 'selected' : '' }}>Bi-Weekly</option>
                                            <option value="semi_monthly" {{ $payroll->payment_schedule == 'semi_monthly' ? 'selected' : '' }}>Semi-Monthly</option>
                                            <option value="monthly" {{ $payroll->payment_schedule == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 payday-fields" 
                                         id="payday1Field" style="{{ !in_array($payroll->payment_schedule, ['semi_monthly', 'monthly']) ? 'display: none;' : '' }}">
                                        <label class="form-label">Payday 1 (Day of Month)</label>
                                        <input type="number" class="form-control" name="payday_1" 
                                               value="{{ $payroll->payday_1 }}" min="1" max="31">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 payday-fields" 
                                         id="payday2Field" style="{{ $payroll->payment_schedule != 'semi_monthly' ? 'display: none;' : '' }}">
                                        <label class="form-label">Payday 2 (Day of Month)</label>
                                        <input type="number" class="form-control" name="payday_2" 
                                               value="{{ $payroll->payday_2 }}" min="1" max="31">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3 payday-fields" 
                                         id="paydayWeeklyField" style="{{ $payroll->payment_schedule != 'weekly' ? 'display: none;' : '' }}">
                                        <label class="form-label">Payday (Day of Week)</label>
                                        <select class="form-select" name="payday_weekly">
                                            <option value="">Select Day</option>
                                            <option value="monday" {{ $payroll->payday_weekly == 'monday' ? 'selected' : '' }}>Monday</option>
                                            <option value="tuesday" {{ $payroll->payday_weekly == 'tuesday' ? 'selected' : '' }}>Tuesday</option>
                                            <option value="wednesday" {{ $payroll->payday_weekly == 'wednesday' ? 'selected' : '' }}>Wednesday</option>
                                            <option value="thursday" {{ $payroll->payday_weekly == 'thursday' ? 'selected' : '' }}>Thursday</option>
                                            <option value="friday" {{ $payroll->payday_weekly == 'friday' ? 'selected' : '' }}>Friday</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- BANKING INFORMATION --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-secondary mb-3">Banking Information</h4>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Bank Name</label>
                                        <input type="text" class="form-control" name="bank_name" value="{{ $payroll->bank_name }}" placeholder="e.g., BDO, BPI, MetroBank">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Account Number</label>
                                        <input type="text" class="form-control" name="bank_account_number" value="{{ $payroll->bank_account_number }}" placeholder="Account number">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Account Name</label>
                                        <input type="text" class="form-control" name="bank_account_name" value="{{ $payroll->bank_account_name }}" placeholder="Account holder name">
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Payment Method</label>
                                        <select class="form-select" name="payment_method">
                                            <option value="bank_transfer" {{ $payroll->payment_method == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                            <option value="cash" {{ $payroll->payment_method == 'cash' ? 'selected' : '' }}>Cash</option>
                                            <option value="check" {{ $payroll->payment_method == 'check' ? 'selected' : '' }}>Check</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- STATUS AND DATES --}}
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <h4 class="card-title text-primary mb-3">Status & Effective Dates</h4>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" 
                                                   id="isActive" name="is_active" value="1" 
                                                   {{ $payroll->is_active ? 'checked' : '' }}>
                                            <label class="form-check-label" for="isActive">Active</label>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Effective Date</label>
                                        <input type="date" class="form-control" name="effective_date" 
                                               value="{{ $payroll->effective_date ? $payroll->effective_date->format('Y-m-d') : '' }}">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" name="expiry_date" 
                                               value="{{ $payroll->expiry_date ? $payroll->expiry_date->format('Y-m-d') : '' }}">
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea class="form-control" name="notes" rows="3" placeholder="Additional notes about payroll settings...">{{ $payroll->notes }}</textarea>
                                    </div>
                                </div>

                                {{-- SUBMIT BUTTON --}}
                                <div class="d-flex justify-content-start">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span id="submitText">Update Payroll Settings</span>
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
            // ==================== CUSTOM ALLOWANCES ====================
            let allowanceIndex = {{ $payroll->custom_allowances ? count($payroll->custom_allowances) : 0 }};
            
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
                            <button type="button" class="btn btn-sm btn-outline-danger remove-custom-item">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                
                container.append(html);
            });

            // ==================== CUSTOM DEDUCTIONS ====================
            let deductionIndex = {{ $payroll->custom_deductions ? count($payroll->custom_deductions) : 0 }};
            
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
                            <button type="button" class="btn btn-sm btn-outline-danger remove-custom-item">
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
                    $('.tax-fields').show();
                    $('#taxType').trigger('change');
                } else {
                    $('.tax-fields, .tax-percentage-field').hide();
                }
            });

            $('#taxType').on('change', function() {
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
                
                const $form = $(this);
                const $submitBtn = $('#submitBtn');
                const $submitText = $('#submitText');
                const $spinner = $('#spinner');
                
                // Validate form
                if (!$form[0].checkValidity()) {
                    e.stopPropagation();
                    $form.addClass('was-validated');
                    return;
                }
                
                // Show loading
                $submitBtn.prop('disabled', true);
                $submitText.text('Updating...');
                $spinner.removeClass('d-none');
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Send AJAX request
                $.ajax({
                    url: '/studio-hr/payroll-settings/' + $('#payrollId').val(),
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
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
                        $submitText.text('Update Payroll Settings');
                        $spinner.addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection