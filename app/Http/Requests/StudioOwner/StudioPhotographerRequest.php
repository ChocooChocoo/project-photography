<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudioPhotographerRequest extends FormRequest
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
            'studio_id' => 'required|exists:tbl_studios,id',
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tbl_users', 'email')
            ],
            'mobile_number' => ['required', 'string', 'max:20', new \App\Rules\PhilippineMobileNumber],
            'profile_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'position' => 'required|string|max:100',
            'specialization' => 'required|exists:tbl_categories,id', // Changed back to categories
            'years_experience' => 'required|integer|min:0|max:50',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'studio_id.required' => 'Please select a studio.',
            'studio_id.exists' => 'The selected studio is invalid.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'mobile_number.required' => 'Contact number is required.',
            'position.required' => 'Position is required.',
            'specialization.required' => 'Please select a specialization.',
            'specialization.exists' => 'The selected specialization is invalid.',
            'years_experience.required' => 'Years of experience is required.',
            'status.required' => 'Status is required.',
        ];
    }
}