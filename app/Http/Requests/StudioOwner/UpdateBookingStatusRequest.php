<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(['confirmed', 'in_progress', 'completed', 'cancelled'])],
            'cancellation_reason' => ['required_if:status,cancelled', 'nullable', 'string', 'min:20', 'max:500'],
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
            'status.required' => 'Please select a booking status.',
            'status.in' => 'Invalid booking status selected.',
            'cancellation_reason.required_if' => 'Please provide a reason for cancellation.',
            'cancellation_reason.min' => 'Please provide a reason of at least 20 characters.',
        ];
    }
}