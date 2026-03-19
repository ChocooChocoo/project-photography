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
            'trigger_keywords' => 'required|array|min:1',
            'trigger_keywords.*' => 'required|string|max:100',
            'response_text' => 'required|string|max:2000',
            'response_type' => 'required|in:text,quick_reply,image',
            'image_url' => 'required_if:response_type,image|nullable|string|max:500',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'sometimes|boolean',
            'quick_replies' => 'nullable|array',
            'quick_replies.*.reply_text' => 'required_with:quick_replies|string|max:100',
            'quick_replies.*.action_value' => 'nullable|string|max:255',
            'quick_replies.*.action_type' => 'required_with:quick_replies|in:trigger_intent,open_url,none',
            'quick_replies.*.position' => 'nullable|integer|min:0',
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
            'intent_name.required' => 'Intent name is required.',
            'trigger_keywords.required' => 'At least one trigger keyword is required.',
            'trigger_keywords.min' => 'At least one trigger keyword is required.',
            'trigger_keywords.*.required' => 'Each keyword must not be empty.',
            'response_text.required' => 'Response text is required.',
            'response_type.required' => 'Please select a response type.',
            'response_type.in' => 'Invalid response type selected.',
            'image_url.required_if' => 'Image URL is required when response type is image.',
            'quick_replies.*.reply_text.required_with' => 'Reply text is required for each quick reply.',
            'quick_replies.*.action_type.required_with' => 'Action type is required for each quick reply.',
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