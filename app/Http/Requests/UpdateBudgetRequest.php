<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBudgetRequest extends FormRequest
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
            'preferred_budget' => 'required|numeric|min:0|max:99999999.99', // tbl_client_budget.preferred_budget is decimal(10,2)
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
            'preferred_budget.required' => 'Please enter your preferred budget.',
            'preferred_budget.max' => 'The budget can be at most ₱99,999,999.99.',
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
            'preferred_budget' => $this->preferred_budget !== '' ? $this->preferred_budget : null,
            'category_id' => $this->category_id ?: null,
            'budget_type' => $this->budget_type ?: null,
        ]);
    }
}