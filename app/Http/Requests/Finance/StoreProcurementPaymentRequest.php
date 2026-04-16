<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isStudioFinance()
            && $user->hasPermission('studio-finance.procurement.payment');
    }

    public function rules(): array
    {
        return [
            'invoice_reference' => ['required', 'string', 'max:100'],
            'invoice_amount' => ['required', 'numeric', 'min:0'],
            'invoice_date' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
            'supplier_invoice_files' => ['required', 'array', 'min:1'],
            'supplier_invoice_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'payment_proof_files' => ['required', 'array', 'min:1'],
            'payment_proof_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
