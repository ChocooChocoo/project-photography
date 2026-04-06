<?php

namespace App\Http\Requests\Finance;

use App\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate finance payroll approval actions.
 */
class UpdatePayrollApprovalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\UserModel|null $user */
        $user = UserModel::with('roles.permissions')->find(auth()->id());

        if (!$user || !$user->isStudioFinance()) {
            return false;
        }

        $action = strtolower((string) $this->route('action', $this->input('action')));

        if ($action === 'approve') {
            return $this->hasPayrollPermission($user, 'approve');
        }

        if ($action === 'reject') {
            return $this->hasPayrollPermission($user, 'reject');
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:action,reject'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify the payroll approval action.',
            'action.in' => 'The selected payroll approval action is invalid.',
            'rejection_reason.required_if' => 'Please provide a reason when rejecting payroll.',
            'rejection_reason.max' => 'The rejection reason must not exceed 1000 characters.',
        ];
    }

    /**
     * Check if the authenticated finance user has the required payroll permission.
     *
     * @param  \App\Models\UserModel  $user
     * @param  string  $action
     * @return bool
     */
    private function hasPayrollPermission(UserModel $user, string $action): bool
    {
        $permissionMap = [
            'approve' => ['studio-finance.payroll.approve', 'studio-finance.payroll.manage'],
            'reject' => ['studio-finance.payroll.reject', 'studio-finance.payroll.manage'],
        ];

        foreach ($permissionMap[$action] ?? [] as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
