<?php

namespace App\Http\Requests\StudioOwner;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class ProcessProcurementApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isOwner()
            && $user->hasPermission('owner.procurement.approve');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject,return'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:action,reject,return'],
        ];
    }
}
