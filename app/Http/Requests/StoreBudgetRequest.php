<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isClient();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'budget_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'minimum_budget' => 'nullable|numeric|min:0',
            'maximum_budget' => 'nullable|numeric|min:0|gte:minimum_budget',
            'preferred_budget' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:tbl_categories,id',
            'budget_type' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'maximum_budget.gte' => 'Maximum budget must be greater than or equal to minimum budget.',
            'category_id.exists' => 'The selected category does not exist.',
            'status.required' => 'Please select a status.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean up empty strings to null
        $this->merge([
            'budget_name' => $this->budget_name ?: null,
            'description' => $this->description ?: null,
            'minimum_budget' => $this->minimum_budget !== '' ? $this->minimum_budget : null,
            'maximum_budget' => $this->maximum_budget !== '' ? $this->maximum_budget : null,
            'preferred_budget' => $this->preferred_budget !== '' ? $this->preferred_budget : null,
            'category_id' => $this->category_id ?: null,
            'budget_type' => $this->budget_type ?: null,
        ]);
    }
}