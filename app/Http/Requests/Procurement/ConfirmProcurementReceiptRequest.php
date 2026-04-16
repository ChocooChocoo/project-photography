<?php

namespace App\Http\Requests\Procurement;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmProcurementReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && (
                ($user->isStudioHr() && $user->hasPermission('studio-hr.procurement.manage'))
                || ($user->isStudioPhotographer() && $user->hasPermission('studio-photographer.procurement.manage'))
            );
    }

    public function rules(): array
    {
        return [
            'receipt_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.procurement_request_item_id' => ['required', 'integer'],
            'items.*.received_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.condition_notes' => ['nullable', 'string', 'max:1000'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'items.*.warranty_expires_at' => ['nullable', 'date'],
            'items.*.acquisition_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.asset_location' => ['nullable', 'string', 'max:255'],
            'items.*.reorder_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
