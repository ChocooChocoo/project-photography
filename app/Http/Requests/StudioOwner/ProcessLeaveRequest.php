<?php

namespace App\Http\Requests\StudioOwner;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate Studio Owner leave request approval and rejection actions.
 */
class ProcessLeaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\UserModel|null $user */
        $user = UserModel::find(auth()->id());

        return $user !== null && $user->isOwner();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
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
            'action.required' => 'Please specify the leave request action.',
            'action.in' => 'The selected leave request action is invalid.',
            'rejection_reason.required_if' => 'Please provide a reason when rejecting a leave request.',
            'rejection_reason.max' => 'The rejection reason must not exceed 1000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'action' => strtolower((string) $this->route('action', $this->input('action'))),
            'rejection_reason' => is_string($this->rejection_reason) ? trim($this->rejection_reason) : $this->rejection_reason,
        ]);
    }
}
