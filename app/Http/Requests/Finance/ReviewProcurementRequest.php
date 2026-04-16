<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class ReviewProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isStudioFinance()
            && $user->hasPermission('studio-finance.procurement.review');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject,return'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:action,reject,return'],
        ];
    }
}
