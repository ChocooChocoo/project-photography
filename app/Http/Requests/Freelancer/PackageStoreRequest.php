<?php

namespace App\Http\Requests\Freelancer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PackageStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'freelancer';
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Convert package_inclusions to array if it's a JSON string
        if ($this->has('package_inclusions') && is_string($this->package_inclusions)) {
            try {
                $inclusions = json_decode($this->package_inclusions, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->merge([
                        'package_inclusions' => $inclusions
                    ]);
                }
            } catch (\Exception $e) {
                // If JSON decode fails, keep as is
            }
        }

        // Handle allow_time_customization boolean conversion
        if ($this->has('allow_time_customization')) {
            $this->merge([
                'allow_time_customization' => filter_var($this->allow_time_customization, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        // ==== NEW: Handle allow_multiple_locations boolean conversion ====
        if ($this->has('allow_multiple_locations')) {
            $this->merge([
                'allow_multiple_locations' => filter_var($this->allow_multiple_locations, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
        // ==== END: Handle allow_multiple_locations boolean conversion ====
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'category_id' => 'required|exists:tbl_categories,id',
            'package_name' => 'required|string|max:255',
            'package_description' => 'required|string',
            'package_inclusions' => 'required|array|min:1',
            'package_inclusions.*' => 'required|string|max:255',
            'allow_time_customization' => 'required|boolean',
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:24',
                function ($attribute, $value, $fail) {
                    $allowCustomization = $this->input('allow_time_customization');
                    
                    // If time customization is NOT allowed, duration is required
                    if (!$allowCustomization && empty($value)) {
                        $fail('Duration is required when time customization is not allowed.');
                    }
                    
                    // If time customization is allowed, duration should be null
                    if ($allowCustomization && !empty($value)) {
                        $fail('Duration should not be provided when time customization is allowed.');
                    }
                },
            ],
            'maximum_edited_photos' => 'required|integer|min:1|max:1000',
            'coverage_scope' => 'nullable|string|max:255',
            'package_price' => 'required|numeric|min:0',
            'online_gallery' => 'required|boolean',
            'status' => 'required|in:active,inactive',
            'cover_images' => 'nullable|array|max:5',
            'cover_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',

            // ==== NEW VALIDATION RULES START ====
            'allow_multiple_locations' => 'sometimes|boolean',
            'max_locations' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
                function ($attribute, $value, $fail) {
                    $allowMultiple = $this->input('allow_multiple_locations');
                    
                    // Required when allow_multiple_locations is true
                    if ($allowMultiple && empty($value)) {
                        $fail('Maximum number of locations is required when multiple locations is enabled.');
                    }
                    
                    // Should be null/empty when allow_multiple_locations is false
                    if (!$allowMultiple && !empty($value)) {
                        $fail('Maximum locations should not be set when multiple locations is disabled.');
                    }
                },
            ],
            // ==== NEW VALIDATION RULES END ====
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'package_name.required' => 'Package name is required.',
            'package_description.required' => 'Package description is required.',
            'package_inclusions.required' => 'At least one inclusion is required.',
            'package_inclusions.min' => 'At least one inclusion is required.',
            'package_inclusions.*.required' => 'Each inclusion field is required.',
            'allow_time_customization.required' => 'Please select if time customization is allowed.',
            'allow_time_customization.boolean' => 'Invalid selection for time customization.',
            'duration.required' => 'Duration is required.',
            'duration.min' => 'Duration must be at least 1 hour.',
            'duration.max' => 'Duration cannot exceed 24 hours.',
            'maximum_edited_photos.required' => 'Maximum edited photos is required.',
            'maximum_edited_photos.min' => 'Minimum of 1 photo is required.',
            'maximum_edited_photos.max' => 'Maximum of 1000 photos allowed.',
            'package_price.required' => 'Package price is required.',
            'package_price.min' => 'Package price must be at least 0.',
            'online_gallery.required' => 'Please select if online gallery is included.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either active or inactive.',
            
            // ==== NEW CUSTOM MESSAGES START ====
            'allow_multiple_locations.boolean' => 'Invalid selection for multiple locations.',
            'max_locations.integer' => 'Maximum locations must be a valid number.',
            'max_locations.min' => 'Maximum locations must be at least 1.',
            'max_locations.max' => 'Maximum locations cannot exceed 10.',
            // ==== NEW CUSTOM MESSAGES END ====
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors occurred.',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}