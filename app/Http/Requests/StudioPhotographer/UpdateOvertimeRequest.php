<?php

namespace App\Http\Requests\StudioPhotographer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a studio photographer overtime request update submission.
 */
class UpdateOvertimeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->isStudioPhotographer()
            && auth()->user()->hasPermission('studio-photographer.overtime-requests.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'overtime_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
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
            'overtime_date.required' => 'Please select the overtime date.',
            'overtime_date.after_or_equal' => 'The overtime date must be today or a future date.',
            'start_time.required' => 'Please select the overtime start time.',
            'start_time.date_format' => 'The overtime start time format is invalid.',
            'end_time.required' => 'Please select the overtime end time.',
            'end_time.date_format' => 'The overtime end time format is invalid.',
            'end_time.after' => 'The overtime end time must be later than the overtime start time.',
            'reason.required' => 'Please provide the reason for your overtime request.',
            'reason.min' => 'The overtime reason must contain at least 10 characters.',
            'reason.max' => 'The overtime reason must not exceed 1000 characters.',
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
