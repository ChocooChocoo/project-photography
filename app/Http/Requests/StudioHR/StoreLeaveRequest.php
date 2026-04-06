<?php

namespace App\Http\Requests\StudioHR;

use App\Models\LeaveRequestModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate an HR leave request submission.
 */
class StoreLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->isStudioHr()
            && auth()->user()->hasPermission('studio-hr.leave-requests.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'leave_type' => ['required', Rule::in(array_keys(LeaveRequestModel::getAvailableLeaveTypes()))],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
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
            'leave_type.required' => 'Please select the leave type for this request.',
            'leave_type.in' => 'The selected leave type is invalid.',
            'start_date.required' => 'Please select the leave start date.',
            'start_date.after_or_equal' => 'The leave start date must be today or a future date.',
            'end_date.required' => 'Please select the leave end date.',
            'end_date.after_or_equal' => 'The leave end date must be the same as or later than the leave start date.',
            'reason.required' => 'Please provide the reason for your leave request.',
            'reason.min' => 'The leave reason must contain at least 10 characters.',
            'reason.max' => 'The leave reason must not exceed 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => is_string($this->reason) ? trim($this->reason) : $this->reason,
        ]);
    }
}
