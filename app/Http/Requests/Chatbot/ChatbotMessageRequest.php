<?php

namespace App\Http\Requests\Chatbot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChatbotMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
            'session_id' => 'nullable|string|exists:tbl_chatbot_conversations,session_id',
            'owner_id' => 'required_if:session_id,null|exists:tbl_users,id',
            'intent_id' => 'nullable|exists:tbl_chatbot_intents,id',
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
            'message.required' => 'Message cannot be empty.',
            'message.max' => 'Message must not exceed 1000 characters.',
            'session_id.exists' => 'Invalid conversation session.',
            'owner_id.required_if' => 'Please specify which studio you are inquiring about.',
            'owner_id.exists' => 'Selected studio owner does not exist.',
        ];
    }
}