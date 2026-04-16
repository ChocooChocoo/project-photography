<?php

namespace App\Http\Requests\StudioPhotographer;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isStudioPhotographer()
            && $user->hasPermission('studio-photographer.procurement.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:save_draft,submit'],
            'purpose' => ['nullable', 'string', 'max:1500', 'required_if:action,submit'],
            'required_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:action,submit'],
            'is_urgent' => ['nullable', 'boolean'],
            'inventory_bypass_reason' => ['nullable', 'string', 'max:1000'],
            'request_attachments' => ['nullable', 'array'],
            'request_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:1000'],
            'items.*.category' => ['required', 'in:equipment,consumable'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.preferred_supplier' => ['nullable', 'string', 'max:255'],
        ];
    }
}
