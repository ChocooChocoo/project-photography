<?php

namespace App\Http\Requests\StudioHR;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CheckOutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->isStudioHr()
            && auth()->user()->hasPermission('studio-hr.attendance.view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attendance_id' => 'required|exists:tbl_employee_attendance,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'attendance_id.required' => 'Attendance record not found.',
            'attendance_id.exists' => 'Invalid attendance record.',
            'latitude.required' => 'Your current location is required before checking out.',
            'latitude.between' => 'Invalid latitude value.',
            'longitude.required' => 'Your current location is required before checking out.',
            'longitude.between' => 'Invalid longitude value.',
            'notes.max' => 'Notes must not exceed 500 characters.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'errors' => $validator->errors()
        ], 422));
    }
}
