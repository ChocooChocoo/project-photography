<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // Employee and Studio Selection
            'user_id' => [
                'required',
                Rule::exists('tbl_users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer']);
                }),
                Rule::unique('tbl_employee_payroll', 'user_id')
                    ->where('studio_id', $this->studio_id)
                    ->ignore($this->route('id')), // For update operations
            ],
            'studio_id' => [
                'required',
                Rule::exists('tbl_studios', 'id')->where(function ($query) {
                    $query->where('user_id', auth()->id())
                        ->whereIn('status', ['verified', 'active']);
                })
            ],
            
            // Payroll Basis - Conditional validation based on user role
            'payroll_basis' => [
                'required',
                function ($attribute, $value, $fail) {
                    // Get the user role
                    $user = \App\Models\UserModel::find($this->user_id);
                    
                    if (!$user) {
                        return $fail('Selected employee not found.');
                    }
                    
                    // Photographers must use booking_and_attendance
                    if ($user->role === 'studio-photographer' && $value !== 'booking_and_attendance') {
                        $fail('Photographers must use "Booking + Attendance" payroll basis.');
                    }
                    
                    // HR and Finance must use attendance_only
                    if (in_array($user->role, ['studio-hr', 'studio-finance']) && $value !== 'attendance_only') {
                        $fail('HR and Finance staff must use "Attendance Only" payroll basis.');
                    }
                },
            ],
            
            // Salary Information (conditional)
            'daily_rate' => 'nullable|numeric|min:0|max:999999.99',
            'monthly_salary' => 'nullable|numeric|min:0|max:999999.99',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'per_booking_rate' => 'nullable|numeric|min:0|max:999999.99',
            'booking_commission_percentage' => 'nullable|numeric|min:0|max:100',
            
            // Remaining standard deductions
            'sss_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'phic_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'tax_withholding' => 'nullable|numeric|min:0|max:999999.99',
            'sss_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'other_deductions' => 'nullable|numeric|min:0|max:999999.99',
            
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
        // Set default values for checkboxes if not provided
        $booleanFields = [
            'is_taxable',
            'subject_to_vat',
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
            'booking_commission_percentage', 'sss_deduction', 'phic_deduction',
            'hdmf_deduction', 'tax_withholding', 'sss_loan_deduction',
            'hdmf_loan_deduction', 'other_deductions', 'tax_percentage',
            'vat_percentage', 'absence_deduction_per_day', 'undertime_deduction_per_hour',
            'late_deduction_per_minute', 'absent_fixed_deduction',
            'absent_percentage_deduction',
        ];
        
        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}