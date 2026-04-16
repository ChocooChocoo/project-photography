<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isStudioFinance()
            && $user->hasPermission('studio-finance.procurement.order');
    }

    public function rules(): array
    {
        return [
            'delivered_at' => ['required', 'date'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
            'delivery_receipt_files' => ['required', 'array', 'min:1'],
            'delivery_receipt_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
