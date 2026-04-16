<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
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
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_email' => ['nullable', 'email', 'max:255'],
            'supplier_contact_number' => ['nullable', 'string', 'max:50'],
            'supplier_address' => ['nullable', 'string', 'max:1000'],
            'delivery_address' => ['required', 'string', 'max:1000'],
            'payment_terms' => ['required', 'string', 'max:150'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'purchase_order_attachments' => ['nullable', 'array'],
            'purchase_order_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procurement_request_item_id' => ['required', 'integer'],
            'items.*.approved_unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
