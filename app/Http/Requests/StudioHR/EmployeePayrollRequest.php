<?php

namespace App\Http\Requests\StudioHR;

use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::with('roles.permissions')->find(auth()->id());

        if (!$user || $user->role !== 'studio-hr') {
            return false;
        }

        if ($this->isMethod('POST')) {
            return $this->hasPayrollPermission($user, 'create');
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->hasPayrollPermission($user, 'update');
        }

        return $this->hasPayrollPermission($user, 'view');
    }

    public function rules(): array
    {
        $assignedStudioIds = $this->getAssignedStudioIds(auth()->id());

        $rules = [
            'user_id' => [
                'required',
                Rule::exists('tbl_users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer']);
                }),
                Rule::unique('tbl_employee_payroll', 'user_id')
                    ->where(function ($query) use ($assignedStudioIds) {
                        $query->whereIn('studio_id', $assignedStudioIds);
                    })
                    ->ignore($this->route('id')),
            ],
            'studio_id' => [
                'required',
                Rule::exists('tbl_studios', 'id')->where(function ($query) use ($assignedStudioIds) {
                    $query->whereIn('id', $assignedStudioIds);
                }),
            ],
            'payroll_basis' => [
                'required',
                'in:attendance_only,booking_and_attendance',
                function ($attribute, $value, $fail) {
                    $user = UserModel::find($this->user_id);

                    if (!$user) {
                        return $fail('Selected employee not found.');
                    }

                    if ($user->role === 'studio-photographer' && $value !== 'booking_and_attendance') {
                        $fail('Photographers must use "Booking + Attendance" payroll basis.');
                    }

                    if (in_array($user->role, ['studio-hr', 'studio-finance']) && $value !== 'attendance_only') {
                        $fail('HR and Finance staff must use "Attendance Only" payroll basis.');
                    }
                },
            ],
            'daily_rate' => 'nullable|numeric|min:0|max:999999.99',
            'monthly_salary' => 'nullable|numeric|min:0|max:999999.99',
            'hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
            'per_booking_rate' => 'nullable|numeric|min:0|max:999999.99',
            'booking_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'sss_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'phic_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'tax_withholding' => 'nullable|numeric|min:0|max:999999.99',
            'sss_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'hdmf_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
            'other_deductions' => 'nullable|numeric|min:0|max:999999.99',
            'is_taxable' => 'boolean',
            'tax_type' => 'required_if:is_taxable,true|in:withholding,graduated,exempt',
            'tax_percentage' => 'required_if:tax_type,withholding|nullable|numeric|min:0|max:100',
            'tax_code' => 'nullable|string|max:50',
            'subject_to_vat' => 'boolean',
            'vat_percentage' => 'required_if:subject_to_vat,true|numeric|min:0|max:100',
            'vat_type' => 'required_if:subject_to_vat,true|in:inclusive,exclusive',
            'absence_deduction_per_day' => 'nullable|numeric|min:0|max:999999.99',
            'undertime_deduction_per_hour' => 'nullable|numeric|min:0|max:999999.99',
            'late_grace_period_minutes' => 'nullable|integer|min:0|max:120',
            'late_deduction_per_minute' => 'nullable|numeric|min:0|max:999999.99',
            'absent_deduction_method' => 'nullable|in:deduct_daily_rate,deduct_fixed_amount,deduct_percentage',
            'absent_fixed_deduction' => 'required_if:absent_deduction_method,deduct_fixed_amount|nullable|numeric|min:0|max:999999.99',
            'absent_percentage_deduction' => 'required_if:absent_deduction_method,deduct_percentage|nullable|numeric|min:0|max:100',
            'payment_schedule' => 'required|in:weekly,bi_weekly,semi_monthly,monthly',
            'payday_1' => 'required_if:payment_schedule,semi_monthly,monthly|nullable|integer|min:1|max:31',
            'payday_2' => 'required_if:payment_schedule,semi_monthly|nullable|integer|min:1|max:31',
            'payday_weekly' => 'required_if:payment_schedule,weekly|nullable|in:monday,tuesday,wednesday,thursday,friday',
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            'payment_method' => 'nullable|in:bank_transfer,cash,check',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string|max:1000',
        ];

        if ($this->payroll_basis === 'booking_and_attendance') {
            $rules['per_booking_rate'] = 'required_without:booking_commission_percentage|nullable|numeric|min:0|max:999999.99';
            $rules['booking_commission_percentage'] = 'required_without:per_booking_rate|nullable|numeric|min:0|max:100';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Please select an employee.',
            'user_id.exists' => 'The selected employee is invalid.',
            'user_id.unique' => 'This employee already has payroll settings for this studio.',
            'studio_id.required' => 'Please select a studio.',
            'studio_id.exists' => 'The selected studio is invalid or not assigned to your account.',
            'payroll_basis.required' => 'Please select a payroll basis.',
            'payroll_basis.in' => 'Invalid payroll basis selected.',
            'daily_rate.numeric' => 'Daily rate must be a valid number.',
            'monthly_salary.numeric' => 'Monthly salary must be a valid number.',
            'per_booking_rate.required_without' => 'Either per booking rate or commission percentage is required for photographers.',
            'booking_commission_percentage.required_without' => 'Either per booking rate or commission percentage is required for photographers.',
            'tax_type.required_if' => 'Please select a tax type.',
            'tax_percentage.required_if' => 'Tax percentage is required for withholding tax.',
            'tax_percentage.numeric' => 'Tax percentage must be a number.',
            'tax_percentage.max' => 'Tax percentage cannot exceed 100%.',
            'vat_percentage.required_if' => 'VAT percentage is required when subject to VAT.',
            'vat_type.required_if' => 'Please select VAT type.',
            'absent_deduction_method.in' => 'Invalid absence deduction method.',
            'absent_fixed_deduction.required_if' => 'Fixed deduction amount is required for this method.',
            'absent_percentage_deduction.required_if' => 'Percentage deduction is required for this method.',
            'payday_1.required_if' => 'First payday is required.',
            'payday_2.required_if' => 'Second payday is required for semi-monthly schedule.',
            'payday_weekly.required_if' => 'Payday is required for weekly schedule.',
            'expiry_date.after_or_equal' => 'Expiry date must be after or equal to effective date.',
        ];
    }

    protected function prepareForValidation()
    {
        $assignedStudioIds = $this->getAssignedStudioIds(auth()->id());

        if (!$this->filled('studio_id') && count($assignedStudioIds) === 1) {
            $this->merge([
                'studio_id' => $assignedStudioIds[0]
            ]);
        }

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

    private function getAssignedStudioIds($hrId): array
    {
        $user = UserModel::find($hrId);
        $studioIds = $user ? $user->getAssignedStudioIds('studio-hr') : collect();

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::where('user_id', $hrId)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = DB::table('tbl_studio_photographers')
                ->where('photographer_id', $hrId)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $hrId)->pluck('id');
        }

        return $studioIds->unique()->values()->all();
    }

    private function hasPayrollPermission(UserModel $user, string $action): bool
    {
        $permissionMap = [
            'view' => ['studio-hr.payroll.view', 'studio-hr.payroll.manage'],
            'create' => ['studio-hr.payroll.create', 'studio-hr.payroll.manage'],
            'update' => ['studio-hr.payroll.edit', 'studio-hr.payroll.update', 'studio-hr.payroll.manage'],
        ];

        foreach ($permissionMap[$action] ?? [] as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
