<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreChatbotIntentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isOwner();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'config_id' => 'required|exists:tbl_chatbot_configs,id',
            'intent_name' => 'required|string|max:255',
            'response_text' => 'required|string|max:2000',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
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
            'config_id.required' => 'Please select a chatbot configuration.',
            'config_id.exists' => 'Selected chatbot configuration does not exist.',
            'intent_name.required' => 'Topic name is required.',
            'response_text.required' => 'Reference answer is required.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'priority' => $this->priority ?? 0,
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
