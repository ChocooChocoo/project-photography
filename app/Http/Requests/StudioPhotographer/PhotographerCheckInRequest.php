<?php

namespace App\Http\Requests\StudioPhotographer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PhotographerCheckInRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->isStudioPhotographer()
            && auth()->user()->hasPermission('studio-photographer.attendance.view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'studio_id' => 'required|exists:tbl_studios,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'studio_id.required' => 'Please select your assigned studio first.',
            'studio_id.exists' => 'The selected studio is invalid.',
            'latitude.required' => 'Your current location is required before checking in.',
            'latitude.between' => 'Invalid latitude value.',
            'longitude.required' => 'Your current location is required before checking in.',
            'longitude.between' => 'Invalid longitude value.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param Validator $validator
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors(),
        ], 422));
    }
}
