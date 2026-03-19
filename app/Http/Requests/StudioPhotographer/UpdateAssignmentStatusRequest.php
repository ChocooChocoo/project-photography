<?php

namespace App\Http\Requests\StudioPhotographer;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;

class UpdateAssignmentStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:confirmed,on_site,in_progress,completed,cancelled',
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string|max:500',
            'client_confirmation_notes' => 'nullable|string|max:500'
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Only validate for cancellation requests
            if ($this->status === 'cancelled' && $this->route('id')) {
                $assignment = BookingAssignedPhotographerModel::find($this->route('id'));
                
                if ($assignment && !$assignment->canCancel()) {
                    $validator->errors()->add(
                        'status', 
                        $assignment->getCancellationRestrictionReason() ?: 'Cancellation is not allowed at this stage.'
                    );
                }
            }
        });
    }

    public function messages()
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status value. Allowed values: confirmed, on_site, in_progress, completed, cancelled',
            'cancellation_reason.required_if' => 'Cancellation reason is required when cancelling assignment',
            'cancellation_reason.max' => 'Cancellation reason cannot exceed 500 characters',
            'client_confirmation_notes.max' => 'Confirmation notes cannot exceed 500 characters'
        ];
    }
}