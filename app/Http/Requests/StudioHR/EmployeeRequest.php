<?php

namespace App\Http\Requests\StudioHR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\StudioOwner\RbacModel;

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
        
        // Get studios assigned to this HR
        $assignedStudioIds = RbacModel::where('user_id', $user->id)
            ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->pluck('studio_id');
        
        // Check if selected studio is in HR's assigned studios
        if ($this->has('studio_id')) {
            return $assignedStudioIds->contains($this->studio_id);
        }
        
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = auth()->user();
        
        // Get studios assigned to this HR for exists validation
        $assignedStudioIds = RbacModel::where('user_id', $user->id)
            ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->pluck('studio_id');
        
        $rules = [
            'studio_id' => [
                'required',
                Rule::exists('tbl_studios', 'id')->where(function ($query) use ($assignedStudioIds) {
                    $query->whereIn('id', $assignedStudioIds)
                          ->whereIn('status', ['verified', 'active']);
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
            'role' => 'required|in:studio-hr,studio-finance,studio-photographer',
            'status' => 'required|in:active,inactive',
            
            // RBAC permissions
            'can_create' => 'sometimes|boolean',
            'can_read' => 'sometimes|boolean',
            'can_update' => 'sometimes|boolean',
            'can_delete' => 'sometimes|boolean',
            
            // Schedule fields
            'operating_days' => 'required|array|min:1',
            'operating_days.*' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ];

        // Role-specific validation
        if ($this->role === 'studio-hr' || $this->role === 'studio-finance') {
            $rules['role_type'] = 'required|in:Manager,Staff';
        }

        $rules['profile_photo'] = 'nullable|image|mimes:jpg,jpeg,png|max:2048';

        if ($this->role === 'studio-photographer') {
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
            'role.required' => 'Please select a role.',
            'role.in' => 'Invalid role selected.',
            'role_type.required' => 'Please select a role type.',
            'role_type.in' => 'Invalid role type selected.',
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
        // Set default values for RBAC permissions if not provided
        if (!$this->has('can_create')) {
            $this->merge(['can_create' => false]);
        }
        if (!$this->has('can_read')) {
            $this->merge(['can_read' => false]);
        }
        if (!$this->has('can_update')) {
            $this->merge(['can_update' => false]);
        }
        if (!$this->has('can_delete')) {
            $this->merge(['can_delete' => false]);
        }
    }
}