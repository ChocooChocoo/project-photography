<?php

namespace App\Http\Requests\StudioHR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudioOwner\RoleModel;
use App\Models\UserModel;

class EmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = auth()->user();
        
        // Check if user is authenticated and has studio-hr role
        if (!$user || $user->role !== 'studio-hr') {
            return false;
        }
        
        // Get the HR user's role and check create permission
        $hrUser = UserModel::with('roles')->find($user->id);
        $hrRole = $hrUser ? $hrUser->roles->first() : null;
        
        // Only allow if HR has create_employee permission
        return $hrRole && $hrRole->hasPermission('create_employee');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'studio_id' => [
                'required',
                Rule::exists('tbl_studios', 'id')->where(function ($query) {
                    $query->whereIn('status', ['verified', 'active']);
                })
            ],
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('tbl_users', 'email')
            ],
            'mobile_number' => 'required|string|max:20',
            'role_id' => 'required|exists:tbl_roles,id', // Changed from 'role' to 'role_id'
            'status' => 'required|in:active,inactive',
            
            // Schedule fields
            'operating_days' => 'required|array|min:1',
            'operating_days.*' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];

        $rules['profile_photo'] = 'nullable|image|mimes:jpg,jpeg,png|max:2048';

        // Photographer-specific validation
        $role = RoleModel::find($this->role_id);
        if ($role && $role->name === 'studio-photographer') {
            $rules['position'] = 'required|string|max:100';
            $rules['specialization'] = 'required|exists:tbl_categories,id';
            $rules['years_experience'] = 'required|integer|min:0|max:50';
        }

        return $rules;
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
            'studio_id.exists' => 'The selected studio is invalid or not accessible.',
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'email.required' => 'Email address is required.',
            'email.unique' => 'This email is already registered.',
            'mobile_number.required' => 'Contact number is required.',
            'role_id.required' => 'Please select a role.',
            'role_id.exists' => 'The selected role is invalid.',
            'position.required' => 'Position is required for photographers.',
            'specialization.required' => 'Specialization is required for photographers.',
            'specialization.exists' => 'The selected specialization is invalid.',
            'years_experience.required' => 'Years of experience is required.',
            'years_experience.integer' => 'Years of experience must be a number.',
            'years_experience.min' => 'Years of experience cannot be negative.',
            'years_experience.max' => 'Years of experience cannot exceed 50.',
            'operating_days.required' => 'Please select at least one operating day.',
            'operating_days.min' => 'Please select at least one operating day.',
            'start_time.required' => 'Start time is required.',
            'end_time.required' => 'End time is required.',
            'end_time.after' => 'End time must be after start time.',
            'status.required' => 'Status is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // No CRUD permission defaults needed anymore
    }
}