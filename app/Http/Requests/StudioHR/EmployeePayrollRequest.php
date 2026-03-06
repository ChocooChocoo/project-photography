<?php

namespace App\Http\Requests\StudioHR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudioOwner\RbacModel;

class EmployeePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Enforces RBAC: HR must have appropriate permissions based on action
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        
        // Check if user is authenticated and has HR role
        if (!$user || $user->role !== 'studio-hr') {
            return false;
        }

        // Get HR user's RBAC permissions
        $rbac = RbacModel::where('user_id', $user->id)->first();
        
        if (!$rbac) {
            return false;
        }

        // For store/create operation
        if ($this->isMethod('POST')) {
            return $rbac->can_create;
        }

        // For update operation
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $rbac->can_update;
        }

        // For other methods (like showing form)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get HR user's assigned studio from RBAC
        $rbac = RbacModel::where('user_id', auth()->id())->first();
        $studioId = $rbac ? $rbac->studio_id : null;

        $rules = [
            // Employee and Studio Selection
            'user_id' => [
                'required',
                Rule::exists('tbl_users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer']);
                }),
                Rule::unique('tbl_employee_payroll', 'user_id')
                    ->where('studio_id', $studioId)
                    ->ignore($this->route('id')), // For update operations
            ],
            'studio_id' => [
                'required',
                Rule::exists('tbl_studios', 'id')->where(function ($query) use ($studioId) {
                    // HR can only work with their assigned studio
                    $query->where('id', $studioId);
                })
            ],
            
            // Payroll Basis
            'payroll_basis' => 'required|in:attendance_only,booking_and_attendance',
            
            // Salary Information (conditional)
            'daily_rate' => 'nullable|numeric|min:0|max:999999.99',
            'monthly_salary' => 'nullable|numeric|min:0|max:999999.99',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'per_booking_rate' => 'nullable|numeric|min:0|max:999999.99',
            'booking_commission_percentage' => 'nullable|numeric|min:0|max:100',
            
            // Allowances
            'rice_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'clothing_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'laundry_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'transportation_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'meal_allowance' => 'nullable|numeric|min:0|max:999999.99',
            'other_allowances' => 'nullable|numeric|min:0|max:999999.99',
            'custom_allowances' => 'nullable|array',
            'custom_allowances.*.name' => 'required_with:custom_allowances|string|max:100',
            'custom_allowances.*.amount' => 'required_with:custom_allowances|numeric|min:0|max:999999.99',
            
            // Deductions
            'sss_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'phic_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'tax_withholding' => 'nullable|numeric|min:0|max:999999.99',
            'sss_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'cash_advance_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'other_deductions' => 'nullable|numeric|min:0|max:999999.99',
            'custom_deductions' => 'nullable|array',
            'custom_deductions.*.name' => 'required_with:custom_deductions|string|max:100',
            'custom_deductions.*.amount' => 'required_with:custom_deductions|numeric|min:0|max:999999.99',
            
            // Tax Settings
            'is_taxable' => 'boolean',
            'tax_type' => 'required_if:is_taxable,true|in:withholding,graduated,exempt',
            'tax_percentage' => 'required_if:tax_type,withholding|nullable|numeric|min:0|max:100',
            'tax_code' => 'nullable|string|max:50',
            
            // VAT Settings
            'subject_to_vat' => 'boolean',
            'vat_percentage' => 'required_if:subject_to_vat,true|numeric|min:0|max:100',
            'vat_type' => 'required_if:subject_to_vat,true|in:inclusive,exclusive',
            
            // Absence and Undertime
            'absence_deduction_per_day' => 'nullable|numeric|min:0|max:999999.99',
            'undertime_deduction_per_hour' => 'nullable|numeric|min:0|max:999999.99',
            'late_grace_period_minutes' => 'nullable|integer|min:0|max:120',
            'late_deduction_per_minute' => 'nullable|numeric|min:0|max:999999.99',
            'absent_deduction_method' => 'nullable|in:deduct_daily_rate,deduct_fixed_amount,deduct_percentage',
            'absent_fixed_deduction' => 'required_if:absent_deduction_method,deduct_fixed_amount|nullable|numeric|min:0|max:999999.99',
            'absent_percentage_deduction' => 'required_if:absent_deduction_method,deduct_percentage|nullable|numeric|min:0|max:100',
            
            // Overtime Settings
            'overtime_enabled' => 'boolean',
            'overtime_rate_multiplier' => 'required_if:overtime_enabled,true|numeric|min:1|max:5',
            'night_differential_rate' => 'nullable|numeric|min:1|max:5',
            'night_differential_start' => 'nullable|date_format:H:i',
            'night_differential_end' => 'nullable|date_format:H:i',
            'holiday_overtime_enabled' => 'boolean',
            'holiday_overtime_rate' => 'required_if:holiday_overtime_enabled,true|numeric|min:1|max:5',
            
            // Leave Settings
            'regular_holidays_per_year' => 'nullable|integer|min:0|max:365',
            'special_holidays_per_year' => 'nullable|integer|min:0|max:365',
            'paid_holidays' => 'boolean',
            'vacation_leave_days_per_year' => 'nullable|integer|min:0|max:365',
            'sick_leave_days_per_year' => 'nullable|integer|min:0|max:365',
            'emergency_leave_days_per_year' => 'nullable|integer|min:0|max:365',
            'leave_conversion_enabled' => 'boolean',
            'leave_conversion_rate' => 'required_if:leave_conversion_enabled,true|nullable|numeric|min:0|max:100',
            
            // Payment Schedule
            'payment_schedule' => 'required|in:weekly,bi_weekly,semi_monthly,monthly',
            'payday_1' => 'required_if:payment_schedule,semi_monthly,monthly|nullable|integer|min:1|max:31',
            'payday_2' => 'required_if:payment_schedule,semi_monthly|nullable|integer|min:1|max:31',
            'payday_weekly' => 'required_if:payment_schedule,weekly|nullable|in:monday,tuesday,wednesday,thursday,friday',
            
            // Banking Information
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:bank_transfer,cash,check',
            
            // Status and Dates
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string|max:1000',
        ];

        // Conditional validation based on payroll basis
        if ($this->payroll_basis === 'booking_and_attendance') {
            $rules['per_booking_rate'] = 'required_without:booking_commission_percentage|nullable|numeric|min:0|max:999999.99';
            $rules['booking_commission_percentage'] = 'required_without:per_booking_rate|nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Employee Selection
            'user_id.required' => 'Please select an employee.',
            'user_id.exists' => 'The selected employee is invalid.',
            'user_id.unique' => 'This employee already has payroll settings for this studio.',
            
            // Studio Selection
            'studio_id.required' => 'Please select a studio.',
            'studio_id.exists' => 'The selected studio is invalid or not verified.',
            
            // Payroll Basis
            'payroll_basis.required' => 'Please select a payroll basis.',
            'payroll_basis.in' => 'Invalid payroll basis selected.',
            
            // Salary Validation
            'daily_rate.numeric' => 'Daily rate must be a valid number.',
            'monthly_salary.numeric' => 'Monthly salary must be a valid number.',
            'per_booking_rate.required_without' => 'Either per booking rate or commission percentage is required for photographers.',
            'booking_commission_percentage.required_without' => 'Either per booking rate or commission percentage is required for photographers.',
            
            // Custom Allowances
            'custom_allowances.*.name.required_with' => 'Allowance name is required.',
            'custom_allowances.*.amount.required_with' => 'Allowance amount is required.',
            'custom_allowances.*.amount.numeric' => 'Allowance amount must be a number.',
            
            // Custom Deductions
            'custom_deductions.*.name.required_with' => 'Deduction name is required.',
            'custom_deductions.*.amount.required_with' => 'Deduction amount is required.',
            'custom_deductions.*.amount.numeric' => 'Deduction amount must be a number.',
            
            // Tax Validation
            'tax_type.required_if' => 'Please select a tax type.',
            'tax_percentage.required_if' => 'Tax percentage is required for withholding tax.',
            'tax_percentage.numeric' => 'Tax percentage must be a number.',
            'tax_percentage.max' => 'Tax percentage cannot exceed 100%.',
            
            // VAT Validation
            'vat_percentage.required_if' => 'VAT percentage is required when subject to VAT.',
            'vat_type.required_if' => 'Please select VAT type.',
            
            // Absence Validation
            'absent_deduction_method.in' => 'Invalid absence deduction method.',
            'absent_fixed_deduction.required_if' => 'Fixed deduction amount is required for this method.',
            'absent_percentage_deduction.required_if' => 'Percentage deduction is required for this method.',
            
            // Overtime Validation
            'overtime_rate_multiplier.required_if' => 'Overtime rate multiplier is required.',
            'overtime_rate_multiplier.min' => 'Overtime rate must be at least 1x.',
            'overtime_rate_multiplier.max' => 'Overtime rate cannot exceed 5x.',
            
            // Leave Validation
            'leave_conversion_rate.required_if' => 'Leave conversion rate is required.',
            
            // Payment Schedule
            'payday_1.required_if' => 'First payday is required.',
            'payday_2.required_if' => 'Second payday is required for semi-monthly schedule.',
            'payday_weekly.required_if' => 'Payday is required for weekly schedule.',
            
            // Date Validation
            'expiry_date.after_or_equal' => 'Expiry date must be after or equal to effective date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Get HR user's assigned studio from RBAC
        $rbac = RbacModel::where('user_id', auth()->id())->first();
        
        // Auto-set studio_id from RBAC if not provided or override any provided value
        if ($rbac && $rbac->studio_id) {
            $this->merge([
                'studio_id' => $rbac->studio_id
            ]);
        }
        
        // Set default values for checkboxes if not provided
        $booleanFields = [
            'is_taxable',
            'subject_to_vat',
            'overtime_enabled',
            'holiday_overtime_enabled',
            'paid_holidays',
            'leave_conversion_enabled',
            'is_active',
        ];
        
        foreach ($booleanFields as $field) {
            if (!$this->has($field)) {
                $this->merge([$field => false]);
            }
        }
        
        // Convert empty strings to null for numeric fields
        $numericFields = [
            'daily_rate', 'monthly_salary', 'hourly_rate', 'per_booking_rate',
            'booking_commission_percentage', 'rice_allowance', 'clothing_allowance',
            'laundry_allowance', 'transportation_allowance', 'meal_allowance',
            'other_allowances', 'sss_deduction', 'phic_deduction', 'hdmf_deduction',
            'tax_withholding', 'sss_loan_deduction', 'hdmf_loan_deduction',
            'cash_advance_deduction', 'other_deductions', 'tax_percentage',
            'vat_percentage', 'absence_deduction_per_day', 'undertime_deduction_per_hour',
            'late_deduction_per_minute', 'absent_fixed_deduction',
            'absent_percentage_deduction', 'overtime_rate_multiplier',
            'night_differential_rate', 'holiday_overtime_rate', 'leave_conversion_rate',
        ];
        
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}