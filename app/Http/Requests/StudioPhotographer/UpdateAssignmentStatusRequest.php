<?php

namespace App\Http\Requests\StudioPhotographer;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;

class UpdateAssignmentStatusRequest extends FormRequest
{
    public function authorize()
    {
        if ($this->routeIs('freelancer.studio-assignments.status')) {
            $assignment = BookingAssignedPhotographerModel::find($this->route('id'));
            return $assignment && auth()->check() && app(\App\Services\FreelancerBookingAccess::class)->allows(auth()->user(), $assignment, 'update-status');
        }
        return auth()->check()
            && auth()->user()->isStudioPhotographer()
            && auth()->user()->hasPermission('studio-photographer.assignment.update_status');
    }

    public function rules()
    {
        return [
            'status' => 'required|in:confirmed,on_site,in_progress,completed,cancelled',
            'on_site_proof_image' => 'required_if:status,on_site|nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
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
