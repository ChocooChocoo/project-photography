<?php

namespace App\Http\Requests\Freelancer;

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
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('tbl_categories', 'id')->where('status', 'active'),
            ],
            'service_name' => 'required|array|min:1',
            'service_name.*' => 'required|string|max:255',
            'starting_from' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid or inactive.',
            'service_name.required' => 'Please enter at least one service name.',
            'service_name.array' => 'Service names must be provided as an array.',
            'service_name.min' => 'Please enter at least one service name.',
            'service_name.*.required' => 'Each service name field is required.',
            'service_name.*.string' => 'Service name must be a string.',
            'service_name.*.max' => 'Service name must not exceed 255 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Filter out empty service names
        if ($this->has('service_name')) {
            $filteredServices = array_filter($this->input('service_name'), function ($value) {
                return !empty(trim($value));
            });
            $this->merge(['service_name' => array_values($filteredServices)]);
        }
    }
}