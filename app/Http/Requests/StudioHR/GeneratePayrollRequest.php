<?php

namespace App\Http\Requests\StudioHR;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'studio_id' => ['required', 'integer', 'exists:tbl_studios,id'],
            'employee_type' => ['required', 'in:regular_employee,studio_photographer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['required', 'integer', 'exists:tbl_users,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'studio_id.required' => 'Please select a studio before generating payroll.',
            'studio_id.exists' => 'The selected studio is invalid.',
            'employee_type.required' => 'Please choose an employee type filter.',
            'employee_type.in' => 'The selected employee type is invalid.',
            'period_start.required' => 'Please provide the payroll period start date.',
            'period_start.date' => 'The payroll period start date must be a valid date.',
            'period_end.required' => 'Please provide the payroll period end date.',
            'period_end.date' => 'The payroll period end date must be a valid date.',
            'period_end.after_or_equal' => 'The payroll period end date must be on or after the start date.',
            'employee_ids.required' => 'Please select at least one employee to generate payroll.',
            'employee_ids.array' => 'The selected employees are invalid.',
            'employee_ids.min' => 'Please select at least one employee to generate payroll.',
            'employee_ids.*.exists' => 'One or more selected employees are invalid.',
            'notes.max' => 'The notes field must not exceed 1000 characters.',
        ];
    }
}
