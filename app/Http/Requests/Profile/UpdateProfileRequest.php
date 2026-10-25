<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = auth()->id();
        
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:tbl_users,email,' . $userId,
            'mobile_number' => ['required', 'string', 'max:20', new \App\Rules\PhilippineMobileNumber(auth()->user()?->mobile_number)],
            'current_password' => 'nullable|required_with:password|current_password',
            'password' => 'nullable|confirmed|min:8',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:3072', // 3MB max
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already taken.',
            'mobile_number.required' => 'Mobile number is required.',
            'current_password.required_with' => 'Current password is required when changing password.',
            'current_password.current_password' => 'Current password is incorrect.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'profile_photo.image' => 'Profile photo must be an image file.',
            'profile_photo.mimes' => 'Profile photo must be a file of type: jpeg, png, jpg, gif.',
            'profile_photo.max' => 'Profile photo size cannot exceed 3MB.',
            'cover_photo.image' => 'Cover photo must be an image file.',
            'cover_photo.mimes' => 'Cover photo must be a file of type: jpeg, png, jpg, gif.',
            'cover_photo.max' => 'Cover photo size cannot exceed 5MB.',
        ];
    }
}