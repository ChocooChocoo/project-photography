<?php

namespace App\Http\Requests\StudioHR;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

class StoreProcurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = UserModel::find(auth()->id());

        return $user !== null
            && $user->isStudioHr()
            && $user->hasPermission('studio-hr.procurement.manage');
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
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.preferred_supplier' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Each amount fits its column, but quantity x cost can still overflow the
     * decimal(12,2) totals; refuse it here instead of failing in the database.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $total = 0;
            foreach ((array) $this->input('items', []) as $index => $item) {
                $line = (float) ($item['quantity'] ?? 0) * (float) ($item['estimated_unit_cost'] ?? 0);
                if ($line > 9999999999.99) {
                    $validator->errors()->add("items.$index.estimated_unit_cost", 'This item costs too much in total (quantity x unit cost). The most one request can hold is ₱9,999,999,999.99.');
                }
                $total += $line;
            }
            if ($total > 9999999999.99) {
                $validator->errors()->add('items', 'The request total can be at most ₱9,999,999,999.99.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'items.*.quantity' => 'quantity',
            'items.*.estimated_unit_cost' => 'estimated unit cost',
            'items.*.item_name' => 'item name',
            'items.*.unit_of_measure' => 'unit of measure',
            'items.*.category' => 'category',
        ];
    }
}
