<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Admin\LocationModel;

class RegisterRequest extends FormRequest
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
            'userType' => 'required|in:client,freelancer,owner',
            'firstName' => 'required|string|max:255',
            'middleName' => 'nullable|string|max:255',
            'lastName' => 'required|string|max:255',
            'suffix' => 'nullable|string|max:20',
            'userEmail' => 'required|email|max:255|unique:tbl_users,email',
            'userMobile' => 'required|string|max:20',
            'userPassword' => 'required|string|min:8',
            'userConfirmPassword' => 'required|same:userPassword',
            'orgRole' => 'nullable|in:Business Owner,HR Manager,Marketing Manager',
            'municipality' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    // Check if municipality exists in tbl_locations for Cavite province
                    $exists = LocationModel::where('province', 'Cavite')
                        ->where('municipality', $value)
                        ->where('status', 'active')
                        ->exists();
                    
                    if (!$exists) {
                        $fail('Invalid municipality selected.');
                    }
                }
            ],
            'agreeTerms' => 'required|accepted'
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'userType.required' => 'Please select your account type.',
            'userType.in' => 'Invalid account type selected.',
            'firstName.required' => 'Please enter your first name.',
            'lastName.required' => 'Please enter your last name.',
            'userEmail.required' => 'Please enter your email address.',
            'userEmail.email' => 'Please enter a valid email address.',
            'userEmail.unique' => 'This email is already registered.',
            'userMobile.required' => 'Please enter your mobile number.',
            'userPassword.required' => 'Please enter a password.',
            'userPassword.min' => 'Password must be at least 8 characters.',
            'userConfirmPassword.required' => 'Please confirm your password.',
            'userConfirmPassword.same' => 'Passwords do not match.',
            'municipality.required' => 'Please select your municipality.', // Added message
            'agreeTerms.required' => 'You must agree to the terms and conditions.',
            'agreeTerms.accepted' => 'You must agree to the terms and conditions.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'userMobile' => preg_replace('/[^0-9+]/', '', $this->userMobile)
        ]);
    }
}