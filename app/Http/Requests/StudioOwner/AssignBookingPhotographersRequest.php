<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBookingPhotographersRequest extends FormRequest
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
        return [
            'photographer_ids' => ['required', 'array', 'min:1'],
            'photographer_ids.*' => ['required', 'integer', 'distinct', Rule::exists('tbl_users', 'id')],
            'assignment_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photographer_ids.required' => 'Please select at least one photographer.',
            'photographer_ids.array' => 'Invalid photographer selection.',
            'photographer_ids.min' => 'Please select at least one photographer.',
            'photographer_ids.*.required' => 'Each selected photographer is required.',
            'photographer_ids.*.integer' => 'Invalid photographer selected.',
            'photographer_ids.*.distinct' => 'Duplicate photographers are not allowed.',
            'photographer_ids.*.exists' => 'One or more selected photographers no longer exist.',
            'assignment_notes.max' => 'Assignment notes must not exceed 500 characters.',
        ];
    }
}
