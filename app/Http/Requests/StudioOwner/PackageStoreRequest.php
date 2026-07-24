<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PackageStoreRequest extends FormRequest
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
        $id = $this->route('package');
        
        return [
            'studio_id' => 'required|exists:tbl_studios,id',
            'category_id' => 'required|exists:tbl_categories,id',
            'package_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tbl_packages', 'package_name')
                    ->ignore($id)
                    ->where('studio_id', $this->studio_id)
            ],
            'package_description' => 'required|string|min:10|max:1000',
            'package_inclusions' => 'required|string|min:1',
            'package_location' => 'required|array|min:1',
            'package_location.*' => 'required|in:In-Studio,On-Location',
            
            // ==== NEW VALIDATION RULES START ====
            'allow_multiple_locations' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) {
                    // Rule: Can only be true if package_location includes "On-Location"
                    $locations = $this->package_location ?? [];
                    if ($value && !in_array('On-Location', $locations)) {
                        $fail('Multiple locations can only be enabled for packages that include On-Location.');
                    }
                },
            ],
            'max_locations' => [
                'nullable',
                'integer',
                'min:1',
                'max:10',
                function ($attribute, $value, $fail) {
                    // Required when allow_multiple_locations is true
                    if ($this->allow_multiple_locations && empty($value)) {
                        $fail('Maximum number of locations is required when multiple locations is enabled.');
                    }
                    
                    // Should be null/empty when allow_multiple_locations is false
                    if (!$this->allow_multiple_locations && !empty($value)) {
                        $fail('Maximum locations should not be set when multiple locations is disabled.');
                    }
                },
            ],
            // ==== NEW VALIDATION RULES END ====
            
            'allow_time_customization' => 'required|boolean',
            'duration' => [
                'nullable',
                'integer',
                'min:1',
                'max:24',
                function ($attribute, $value, $fail) {
                    if (!$this->allow_time_customization && empty($value)) {
                        $fail('Duration is required when time customization is not allowed.');
                    }
                },
            ],
            'maximum_edited_photos' => 'required|integer|min:1|max:1000',
            'coverage_scope' => [
                'nullable',
                'string',
                'max:500',
                function ($attribute, $value, $fail) {
                    $locations = $this->package_location ?? [];
                    if (in_array('On-Location', $locations) && empty($value)) {
                        $fail('Coverage scope is required for on-location packages.');
                    }
                },
            ],
            'package_price' => 'required|numeric|min:0',
            'online_gallery' => 'required|boolean',
            'photographer_count' => 'required|integer|min:0|max:10',
            'status' => 'required|in:active,inactive',
            'cover_images' => 'nullable|array|max:5',
            'cover_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
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
            'studio_id.required' => 'Please select a studio.',
            'studio_id.exists' => 'Selected studio does not exist.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'package_name.required' => 'Package name is required.',
            'package_name.unique' => 'A package with this name already exists for this studio.',
            'package_description.required' => 'Package description is required.',
            'package_description.min' => 'Package description must be at least 10 characters.',
            'package_inclusions.required' => 'At least one inclusion is required.',
            'package_location.required' => 'Please select at least one location type.',
            'package_location.min' => 'Please select at least one location type.',
            'package_location.*.in' => 'Invalid location type selected.',
            
            // ==== NEW CUSTOM MESSAGES START ====
            'allow_multiple_locations.boolean' => 'Invalid value for multiple locations.',
            'max_locations.integer' => 'Maximum locations must be a valid number.',
            'max_locations.min' => 'Maximum locations must be at least 1.',
            'max_locations.max' => 'Maximum locations cannot exceed 10.',
            // ==== NEW CUSTOM MESSAGES END ====
            
            'allow_time_customization.required' => 'Please select if time customization is allowed.',
            'allow_time_customization.boolean' => 'Invalid selection for time customization.',
            'duration.integer' => 'Duration must be a valid number.',
            'duration.min' => 'Duration must be at least 1 hour.',
            'duration.max' => 'Duration cannot exceed 24 hours.',
            'maximum_edited_photos.min' => 'Minimum edited photos must be at least 1.',
            'maximum_edited_photos.max' => 'Maximum edited photos cannot exceed 1000.',
            'package_price.min' => 'Package price cannot be negative.',
            'package_price.required' => 'Package price is required.',
            'online_gallery.required' => 'Please select if online gallery is included.',
            'online_gallery.boolean' => 'Invalid selection for online gallery.',
            'photographer_count.required' => 'Please specify number of photographers.',
            'photographer_count.min' => 'Photographer count cannot be negative.',
            'photographer_count.max' => 'Maximum of 10 photographers allowed.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $input = $this->all();
        
        // Convert string boolean values to actual booleans
        if ($this->has('allow_time_customization')) {
            $input['allow_time_customization'] = filter_var($this->allow_time_customization, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }
        
        // ==== NEW PREPARATION START ====
        // Handle allow_multiple_locations boolean conversion
        if ($this->has('allow_multiple_locations')) {
            $input['allow_multiple_locations'] = filter_var($this->allow_multiple_locations, FILTER_VALIDATE_BOOLEAN);
        }
        
        // If allow_multiple_locations is false, ensure max_locations is null/empty
        if (isset($input['allow_multiple_locations']) && !$input['allow_multiple_locations']) {
            $input['max_locations'] = null;
        }
        // ==== NEW PREPARATION END ====
        
        $this->replace($input);
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        $validated = $this->validated();
        
        // If time customization is allowed, explicitly set duration to null
        if (isset($validated['allow_time_customization']) && $validated['allow_time_customization'] === true) {
            $validated['duration'] = null;
        }
        
        // ==== NEW PASSED VALIDATION START ====
        // Ensure max_locations is properly set based on allow_multiple_locations
        if (isset($validated['allow_multiple_locations']) && $validated['allow_multiple_locations'] === true) {
            // Ensure max_locations is within 1-10
            if (isset($validated['max_locations'])) {
                $validated['max_locations'] = min(max((int)$validated['max_locations'], 1), 10);
            }
        } else {
            // If multiple locations not allowed, set max_locations to null
            $validated['max_locations'] = null;
            $validated['allow_multiple_locations'] = false;
        }
        // ==== NEW PASSED VALIDATION END ====
        
        $this->replace($validated);
    }
}