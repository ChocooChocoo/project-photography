<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ServiceRequest extends FormRequest
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
            'studio_id' => [
                'required',
                'exists:tbl_studios,id',
                // Ensure studio belongs to authenticated owner and is verified
                function ($attribute, $value, $fail) {
                    $studio = \App\Models\StudioOwner\StudiosModel::where('id', $value)
                        ->where('user_id', auth()->id())
                        ->where('status', 'verified')
                        ->first();
                        
                    if (!$studio) {
                        $fail('The selected studio is invalid or not verified.');
                    }
                }
            ],
            'category_id' => [
                'required',
                'exists:tbl_categories,id',
                // Ensure category is active
                function ($attribute, $value, $fail) {
                    $category = \App\Models\Admin\CategoriesModel::where('id', $value)
                        ->where('status', 'active')
                        ->first();
                        
                    if (!$category) {
                        $fail('The selected category is not active.');
                    }
                }
            ],
            'service_name' => 'required|array|min:1',
            'service_name.*' => 'required|string|max:255',
            'starting_from' => 'nullable|numeric|min:0',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'studio_id.required' => 'Please select a verified studio.',
            'studio_id.exists' => 'The selected studio is invalid.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'service_name.required' => 'Please enter at least one service name.',
            'service_name.array' => 'Service names must be an array.',
            'service_name.min' => 'Please enter at least one service name.',
            'service_name.*.required' => 'Each service name field is required.',
            'service_name.*.string' => 'Each service name must be a string.',
            'service_name.*.max' => 'Each service name must not exceed 255 characters.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$validator->errors()->any()) {
                $serviceNames = $this->input('service_name', []);
                $studioId = $this->input('studio_id');
                
                // Filter out empty values
                $filteredServiceNames = array_filter($serviceNames, function ($value) {
                    return !empty(trim($value));
                });
                
                if (empty($filteredServiceNames)) {
                    $validator->errors()->add('service_name', 'Please enter at least one service name.');
                    return;
                }
                
                // Check for duplicate service names in the current request
                $duplicates = array_diff_assoc($filteredServiceNames, array_unique($filteredServiceNames));
                if (!empty($duplicates)) {
                    $validator->errors()->add('service_name', 'Duplicate service names are not allowed.');
                    return;
                }
                
                // Check if service names already exist in the database
                $serviceId = $this->route('service'); // This works for update
                
                foreach ($filteredServiceNames as $serviceName) {
                    $query = \App\Models\StudioOwner\ServicesModel::where('studio_id', $studioId)
                        ->whereJsonContains('service_name', $serviceName);
                    
                    // For update, exclude the current service
                    if ($serviceId) {
                        $query->where('id', '!=', $serviceId);
                    }
                    
                    if ($query->exists()) {
                        $validator->errors()->add('service_name', "The service name '{$serviceName}' already exists for the selected studio.");
                        break;
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Ensure service_name is an array
        $serviceNames = $this->input('service_name', []);
        
        // Filter out empty values
        $filteredServiceNames = array_filter($serviceNames, function ($value) {
            return !empty(trim($value));
        });
        
        // Reset array keys
        $filteredServiceNames = array_values($filteredServiceNames);
        
        $this->merge([
            'service_name' => $filteredServiceNames,
        ]);
    }
}