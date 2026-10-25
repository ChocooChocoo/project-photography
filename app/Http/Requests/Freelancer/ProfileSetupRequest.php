<?php

namespace App\Http\Requests\Freelancer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileSetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'freelancer';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            // Personal Information
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'freelancer_mobile_number' => ['required', 'string', 'max:20', 'unique:tbl_users,mobile_number,' . auth()->id(), new \App\Rules\PhilippineMobileNumber],
            
            // Brand Identity
            'brand_name' => 'required|string|max:255',
            'professional_tagline' => 'nullable|string|max:255',
            'bio' => 'required|string|min:50|max:2000',
            'contract_template' => 'nullable|string|max:5000',
            'years_of_experience' => 'required|integer|min:0|max:50',
            'brand_logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            
            // Location and Coverage
            'municipality' => 'required|exists:tbl_locations,municipality',
            'barangay' => 'required|string',
            'street' => 'required|string|max:255',
            'service_area' => 'required|in:Within my city only,Within Cavite province',
            
            // Services Information
            'category_services' => 'required|array|min:1',
            'category_services.*' => 'exists:tbl_categories,id',
            'starting_price' => 'required|numeric|min:0',
            'deposit_policy' => 'required|in:required,not_required',
            
            // ==== Start: Deposit Policy Enhancement ==== //
            'deposit_type' => 'required_if:deposit_policy,required|in:fixed,percentage|nullable',
            'deposit_amount' => 'required_if:deposit_policy,required|numeric|min:0.01|nullable',
            // ==== End: Deposit Policy Enhancement ==== //
            
            // Availability and Schedule
            'operating_days' => 'required|array|min:1',
            'operating_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_clients_per_day' => 'required|integer|min:1|max:100',
            'advance_booking_days' => 'required|integer|min:1|max:31',
            
            // Personal Portfolio
            'portfolios' => 'required|array|min:1',
            'portfolios.*' => 'image|mimes:jpeg,png,jpg|max:3072',
            'facebook_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            
            // Verification Documents
            'freelancer_id_document' => 'required|file|mimes:pdf,jpeg,png,jpg|max:3072',
        ];

        // Additional validation for deposit amount based on type
        // ==== Start: Deposit Policy Enhancement ==== //
        if ($this->input('deposit_policy') === 'required') {
            $depositType = $this->input('deposit_type');
            $depositAmount = $this->input('deposit_amount');
            
            if ($depositType === 'percentage' && ($depositAmount < 1 || $depositAmount > 100)) {
                $rules['deposit_amount'] = 'required|numeric|min:1|max:100';
            } elseif ($depositType === 'fixed') {
                $rules['deposit_amount'] = 'required|numeric|min:1|max:1000000';
            }
        }
        // ==== End: Deposit Policy Enhancement ==== //

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'bio.min' => 'Please provide a more detailed bio (at least 50 characters).',
            'category_services.required' => 'Please select at least one service category.',
            'portfolios.required' => 'Please upload at least one portfolio sample.',
            'portfolios.*.max' => 'Portfolio images must not exceed 3MB each.',
            'freelancer_id_document.required' => 'Please upload a valid government ID.',
            'end_time.after' => 'End time must be after start time.',
            
            // ==== Start: Deposit Policy Enhancement ==== //
            'deposit_type.required_if' => 'Please select a deposit type (Fixed Amount or Percentage).',
            'deposit_amount.required_if' => 'Please enter a deposit amount.',
            'deposit_amount.min' => 'Deposit amount must be at least :min.',
            'deposit_amount.max' => 'Deposit amount must not exceed :max.',
            // ==== End: Deposit Policy Enhancement ==== //
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert operating days array to proper format
        if ($this->has('operating_days')) {
            $this->merge([
                'operating_days' => is_array($this->operating_days) ? $this->operating_days : []
            ]);
        }

        // Convert category services to array
        if ($this->has('category_services')) {
            $categoryServices = is_array($this->category_services) ? $this->category_services : explode(',', $this->category_services);
            $this->merge([
                'category_services' => array_filter($categoryServices)
            ]);
        }

        // Convert portfolios to array
        if ($this->hasFile('portfolios')) {
            $this->merge([
                'portfolios' => $this->file('portfolios')
            ]);
        }

        // ==== Start: Deposit Policy Enhancement ==== //
        // Ensure deposit fields are null if deposit_policy is not_required
        if ($this->input('deposit_policy') === 'not_required') {
            $this->merge([
                'deposit_type' => null,
                'deposit_amount' => null
            ]);
        }
        // ==== End: Deposit Policy Enhancement ==== //
    }
}