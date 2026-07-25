<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreChatbotConfigRequest extends FormRequest
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
            'config_name' => 'nullable|string|max:255',
            'welcome_message' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'bot_name' => 'nullable|string|max:100',
            'bot_avatar' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
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
            'config_name.max' => 'Configuration name must not exceed 255 characters.',
            'welcome_message.max' => 'Welcome message must not exceed 1000 characters.',
            'bot_name.max' => 'Bot name must not exceed 100 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'owner_id' => Auth::id(),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
