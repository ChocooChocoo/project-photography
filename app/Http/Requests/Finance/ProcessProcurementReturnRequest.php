<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class ProcessProcurementReturnRequest extends FormRequest
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
            'finance_note' => ['required', 'string', 'max:1000'],
            'return_support_files' => ['nullable', 'array'],
            'return_support_files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
            'return_receipt_files' => ['nullable', 'array'],
            'return_receipt_files.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:5120'],
        ];
    }
}
