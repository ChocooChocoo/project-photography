<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanRequest extends FormRequest
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
        $rules = [
            'user_type' => ['required', 'in:studio,freelancer'],
            'plan_type' => ['required', 'in:basic,premium,enterprise'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_booking' => ['nullable', 'integer', 'min:0'],
            'priority_level' => ['nullable', 'integer', 'min:0', 'max:5'],
            'features' => ['required', 'array', 'min:1'],
            'features.*' => ['required', 'string', 'max:255'],
            'support_level' => ['required', 'in:basic,priority,dedicated'],
            'status' => ['required', 'in:active,inactive'],
        ];

        // Conditional rules based on user type
        if ($this->user_type === 'studio') {
            $rules['max_studio_photographers'] = ['nullable', 'integer', 'min:1'];
            $rules['max_studios'] = ['nullable', 'integer', 'min:1'];
            $rules['staff_limit'] = ['nullable', 'integer', 'min:1'];
        }

        // Conditional rule for plan name (auto-generated but can be overridden)
        if ($this->isMethod('post')) {
            $rules['name'] = ['nullable', 'string', 'max:100'];
            $rules['plan_code'] = ['nullable', 'string', 'unique:tbl_subscription_plans,plan_code'];
        } else {
            // For updates
            $rules['name'] = ['required', 'string', 'max:100'];
            $rules['plan_code'] = ['required', 'string', Rule::unique('tbl_subscription_plans', 'plan_code')->ignore($this->route('id'))];
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
            'user_type.required' => 'Please select a user type.',
            'user_type.in' => 'Invalid user type selected.',
            'plan_type.required' => 'Please select a plan type.',
            'plan_type.in' => 'Invalid plan type selected.',
            'billing_cycle.required' => 'Please select a billing cycle.',
            'billing_cycle.in' => 'Invalid billing cycle selected.',
            'price.required' => 'Please enter a price.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price cannot be negative.',
            'commission_rate.required' => 'Please enter a commission rate.',
            'commission_rate.numeric' => 'Commission rate must be a number.',
            'commission_rate.min' => 'Commission rate cannot be negative.',
            'commission_rate.max' => 'Commission rate cannot exceed 100%.',
            'max_booking.integer' => 'Maximum booking must be a whole number.',
            'max_booking.min' => 'Maximum booking cannot be negative.',
            'max_studio_photographers.integer' => 'Maximum studio photographers must be a whole number.',
            'max_studio_photographers.min' => 'Maximum studio photographers must be at least 1.',
            'features.required' => 'Please add at least one feature.',
            'features.array' => 'Features must be provided as an array.',
            'features.min' => 'Please add at least one feature.',
            'features.*.required' => 'Each feature field is required.',
            'support_level.required' => 'Please select a support level.',
            'support_level.in' => 'Invalid support level selected.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
            'max_studios.integer' => 'Maximum studios must be a whole number.',
            'max_studios.min' => 'Maximum studios must be at least 1.',
            'staff_limit.integer' => 'Staff limit must be a whole number.',
            'staff_limit.min' => 'Staff limit must be at least 1.',
            'priority_level.integer' => 'Priority level must be a whole number.',
            'priority_level.min' => 'Priority level must be between 0 and 5.',
            'priority_level.max' => 'Priority level must be between 0 and 5.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Auto-generate plan code if not provided
        if (!$this->has('plan_code') || empty($this->plan_code)) {
            $this->merge([
                'plan_code' => \App\Models\SubscriptionPlanModel::generatePlanCode(
                    $this->user_type,
                    $this->plan_type,
                    $this->billing_cycle
                )
            ]);
        }

        // Auto-generate plan name if not provided
        if (!$this->has('name') || empty($this->name)) {
            $userTypeLabel = $this->user_type === 'studio' ? 'Studio' : 'Freelancer';
            $planTypeLabel = ucfirst($this->plan_type);
            $cycleLabel = ucfirst($this->billing_cycle);
            
            $this->merge([
                'name' => "{$userTypeLabel} {$planTypeLabel} ({$cycleLabel})"
            ]);
        }
    }
}