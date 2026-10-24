<?php

namespace App\Http\Requests\StudioOwner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudioMemberInviteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'owner';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'freelancer_id' => [
                'required',
                'exists:tbl_users,id',
                Rule::exists('tbl_users', 'id')->where('role', 'freelancer'),
                // Prevent duplicate invitations
                Rule::unique('tbl_studio_members', 'freelancer_id')->where(function ($query) {
                    return $query->where('studio_id', $this->studio_id)
                                 ->whereIn('status', ['pending', 'approved']);
                })
            ],
            'studio_id' => ['required', \Illuminate\Validation\Rule::exists('tbl_studios', 'id')->where('user_id', auth()->id())],
            'invitation_message' => 'required|string|min:10|max:1000',
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
            'freelancer_id.required' => 'Please select a freelancer to invite.',
            'freelancer_id.exists' => 'The selected freelancer does not exist.',
            'freelancer_id.unique' => 'This freelancer has already been invited or is already a member.',
            'invitation_message.required' => 'Please write an invitation message.',
            'invitation_message.min' => 'Invitation message must be at least 10 characters.',
            'invitation_message.max' => 'Invitation message must not exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'freelancer_id' => 'freelancer',
            'invitation_message' => 'invitation message',
        ];
    }
}