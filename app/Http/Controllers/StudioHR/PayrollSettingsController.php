<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\EmployeePayrollRequest;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollSettingsController extends Controller
{
    public function index(Request $request)
    {
        $hrUser = $this->getAuthenticatedHrUser();

        if (!$this->hasPayrollPermission($hrUser, 'view')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }

            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'You do not have permission to view payroll settings.');
        }

        $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->get();

        if ($studios->isEmpty()) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }

        $query = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
            ->whereIn('studio_id', $assignedStudioIds);

        if ($request->filled('studio_id')) {
            $query->where('studio_id', $request->studio_id);
        }

        if ($request->filled('payroll_basis')) {
            $query->where('payroll_basis', $request->payroll_basis);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'active' ? 1 : 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $payrollSettings = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $canCreate = $this->hasPayrollPermission($hrUser, 'create');
        $canUpdate = $this->hasPayrollPermission($hrUser, 'update');
        $canDelete = $this->hasPayrollPermission($hrUser, 'delete');

        $payrollSettingsJson = json_encode($payrollSettings->map(function ($payroll) {
            return [
                'id' => $payroll->id,
                'user_id' => $payroll->user_id,
                'studio_id' => $payroll->studio_id,
                'studio_name' => $payroll->studio->studio_name ?? 'N/A',
                'employee_name' => $payroll->employee->full_name ?? 'N/A',
                'employee_email' => $payroll->employee->email ?? 'N/A',
                'employee_role' => $payroll->employee->role ?? 'N/A',
                'payroll_basis' => $payroll->payroll_basis,
                'payroll_basis_display' => $payroll->payroll_basis_display,
                'monthly_salary' => $payroll->monthly_salary,
                'daily_rate' => $payroll->daily_rate,
                'total_allowances' => $payroll->total_allowances,
                'total_deductions' => $payroll->total_deductions,
                'base_monthly_net' => $payroll->base_monthly_net,
                'payment_schedule' => $payroll->payment_schedule_display,
                'is_active' => $payroll->is_active,
                'effective_date' => $payroll->effective_date ? $payroll->effective_date->format('M d, Y') : null,
                'created_at' => $payroll->created_at->format('M d, Y'),
            ];
        })->values());

        return view('studio-hr.view-payroll-settings', compact(
            'payrollSettings',
            'studios',
            'payrollSettingsJson',
            'canCreate',
            'canUpdate',
            'canDelete'
        ));
    }

    public function create()
    {
        $hrUser = $this->getAuthenticatedHrUser();
        $canCreate = $this->hasPayrollPermission($hrUser, 'create');

        if (!$canCreate) {
            session()->flash('permission_warning', 'Your account has restricted permissions. You can view the form but cannot submit it.');
        }

        $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->get();

        if ($studios->isEmpty()) {
            return redirect()->route('studio-hr.payroll-settings.index')
                ->with('error', 'No studio assigned to your account. Please contact your studio owner.');
        }

        return view('studio-hr.create-payroll-settings', compact('studios', 'canCreate'));
    }

    public function store(EmployeePayrollRequest $request)
    {
        DB::beginTransaction();

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create payroll settings.'
                ], 403);
            }

            $data = $request->validated();
            $data['created_by'] = $hrUser->id;

            if ($request->has('custom_allowances') && is_array($request->custom_allowances)) {
                $data['custom_allowances'] = array_values($request->custom_allowances);
            }

            if ($request->has('custom_deductions') && is_array($request->custom_deductions)) {
                $data['custom_deductions'] = array_values($request->custom_deductions);
            }

            if (empty($data['hourly_rate']) && !empty($data['daily_rate'])) {
                $data['hourly_rate'] = $data['daily_rate'] / 8;
            }

            $payroll = EmployeePayrollModel::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll settings created successfully!',
                'data' => [
                    'id' => $payroll->id,
                    'employee_name' => $payroll->employee->full_name ?? 'N/A',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create payroll settings: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payroll settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEmployees(Request $request)
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view employees.',
                    'data' => []
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $studioId = $request->integer('studio_id');

            if (!$studioId || !$assignedStudioIds->contains($studioId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to the selected studio.',
                    'data' => []
                ], 403);
            }

            $studio = StudiosModel::find($studioId);

            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found',
                    'data' => []
                ], 404);
            }

            $employees = UserModel::with('roles')
                ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
                ->where('status', 'active')
                ->where(function ($query) use ($studioId) {
                    $query->whereExists(function ($subQuery) use ($studioId) {
                        $subQuery->select(DB::raw(1))
                            ->from('tbl_studio_employee_schedule')
                            ->whereColumn('tbl_studio_employee_schedule.user_id', 'tbl_users.id')
                            ->where('tbl_studio_employee_schedule.studio_id', $studioId);
                    })->orWhereExists(function ($subQuery) use ($studioId) {
                        $subQuery->select(DB::raw(1))
                            ->from('tbl_studio_photographers')
                            ->whereColumn('tbl_studio_photographers.photographer_id', 'tbl_users.id')
                            ->where('tbl_studio_photographers.studio_id', $studioId);
                    });
                })
                ->get();

            $employeesWithPayroll = EmployeePayrollModel::where('studio_id', $studioId)
                ->pluck('user_id')
                ->toArray();

            $filteredEmployees = $employees->filter(function ($employee) use ($employeesWithPayroll) {
                return !in_array($employee->id, $employeesWithPayroll);
            });

            $formattedEmployees = $filteredEmployees->map(function ($employee) use ($studio, $studioId) {
                $roleDisplay = $employee->roles->first()->display_name ?? $this->getRoleDisplay($employee->role);

                return [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'role' => $employee->role,
                    'role_display' => $roleDisplay,
                    'studio_id' => $studioId,
                    'studio_name' => $studio->studio_name,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedEmployees,
                'debug' => [
                    'total_found' => $employees->count(),
                    'with_payroll' => count($employeesWithPayroll),
                    'available' => $formattedEmployees->count()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load employees: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load employees: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $payroll = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
                ->whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);

            $data = [
                'id' => $payroll->id,
                'user_id' => $payroll->user_id,
                'studio_id' => $payroll->studio_id,
                'studio_name' => $payroll->studio->studio_name ?? 'N/A',
                'employee' => [
                    'id' => $payroll->employee->id,
                    'full_name' => $payroll->employee->full_name,
                    'first_name' => $payroll->employee->first_name,
                    'last_name' => $payroll->employee->last_name,
                    'email' => $payroll->employee->email,
                    'role' => $payroll->employee->role,
                    'role_display' => $this->getRoleDisplay($payroll->employee->role),
                    'profile_photo' => $payroll->employee->profile_photo_url,
                ],
                'payroll_basis' => $payroll->payroll_basis,
                'payroll_basis_display' => $payroll->payroll_basis_display,
                'daily_rate' => $payroll->daily_rate,
                'monthly_salary' => $payroll->monthly_salary,
                'hourly_rate' => $payroll->hourly_rate,
                'per_booking_rate' => $payroll->per_booking_rate,
                'booking_commission_percentage' => $payroll->booking_commission_percentage,
                'sss_deduction' => $payroll->sss_deduction,
                'phic_deduction' => $payroll->phic_deduction,
                'hdmf_deduction' => $payroll->hdmf_deduction,
                'tax_withholding' => $payroll->tax_withholding,
                'sss_loan_deduction' => $payroll->sss_loan_deduction,
                'hdmf_loan_deduction' => $payroll->hdmf_loan_deduction,
                'other_deductions' => $payroll->other_deductions,
                'total_deductions' => $payroll->total_deductions,
                'is_taxable' => $payroll->is_taxable,
                'tax_type' => $payroll->tax_type,
                'tax_percentage' => $payroll->tax_percentage,
                'tax_code' => $payroll->tax_code,
                'subject_to_vat' => $payroll->subject_to_vat,
                'vat_percentage' => $payroll->vat_percentage,
                'vat_type' => $payroll->vat_type,
                'absence_deduction_per_day' => $payroll->absence_deduction_per_day,
                'undertime_deduction_per_hour' => $payroll->undertime_deduction_per_hour,
                'late_grace_period_minutes' => $payroll->late_grace_period_minutes,
                'late_deduction_per_minute' => $payroll->late_deduction_per_minute,
                'absent_deduction_method' => $payroll->absent_deduction_method,
                'absent_fixed_deduction' => $payroll->absent_fixed_deduction,
                'absent_percentage_deduction' => $payroll->absent_percentage_deduction,
                'payment_schedule' => $payroll->payment_schedule,
                'payment_schedule_display' => $payroll->payment_schedule_display,
                'payday_1' => $payroll->payday_1,
                'payday_2' => $payroll->payday_2,
                'payday_weekly' => $payroll->payday_weekly,
                'bank_name' => $payroll->bank_name,
                'bank_account_number' => $payroll->bank_account_number,
                'bank_account_name' => $payroll->bank_account_name,
                'payment_method' => $payroll->payment_method,
                'is_active' => $payroll->is_active,
                'effective_date' => $payroll->effective_date ? $payroll->effective_date->format('Y-m-d') : null,
                'expiry_date' => $payroll->expiry_date ? $payroll->expiry_date->format('Y-m-d') : null,
                'notes' => $payroll->notes,
                'base_monthly_net' => $payroll->base_monthly_net,
                'created_at' => $payroll->created_at->format('M d, Y h:i A'),
                'created_by' => $payroll->creator->full_name ?? 'N/A',
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load payroll settings: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payroll settings'
            ], 500);
        }
    }

    public function edit($id)
    {
        $hrUser = $this->getAuthenticatedHrUser();

        if (!$this->hasPayrollPermission($hrUser, 'view')) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'You do not have permission to view payroll settings.');
        }

        $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);

        if ($assignedStudioIds->isEmpty()) {
            return redirect()->route('studio-hr.payroll-settings.index')
                ->with('error', 'No studio assigned to your account.');
        }

        $payroll = EmployeePayrollModel::with(['employee', 'studio'])
            ->whereIn('studio_id', $assignedStudioIds)
            ->findOrFail($id);

        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->get();

        $canUpdate = $this->hasPayrollPermission($hrUser, 'update');

        return view('studio-hr.edit-payroll-settings', compact('payroll', 'studios', 'canUpdate'));
    }

    public function update(EmployeePayrollRequest $request, $id)
    {
        DB::beginTransaction();

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'update')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update payroll settings.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $payroll = EmployeePayrollModel::whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);

            $data = $request->validated();

            if ($request->has('custom_allowances') && is_array($request->custom_allowances)) {
                $data['custom_allowances'] = array_values($request->custom_allowances);
            } else {
                $data['custom_allowances'] = null;
            }

            if ($request->has('custom_deductions') && is_array($request->custom_deductions)) {
                $data['custom_deductions'] = array_values($request->custom_deductions);
            } else {
                $data['custom_deductions'] = null;
            }

            if (empty($data['hourly_rate']) && !empty($data['daily_rate'])) {
                $data['hourly_rate'] = $data['daily_rate'] / 8;
            }

            $payroll->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll settings updated successfully!',
                'data' => [
                    'id' => $payroll->id,
                    'employee_name' => $payroll->employee->full_name ?? 'N/A',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update payroll settings: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payroll settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'update')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update payroll status.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $payroll = EmployeePayrollModel::whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);

            $payroll->update([
                'is_active' => $request->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll status updated successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update payroll status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payroll status'
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'delete')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete payroll settings.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $payroll = EmployeePayrollModel::whereIn('studio_id', $assignedStudioIds)
                ->findOrFail($id);

            $payroll->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payroll settings deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete payroll settings: ' . $e->getMessage(), [
                'exception' => $e,
                'payroll_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payroll settings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPayrollSettings(Request $request)
    {
        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'view')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $query = EmployeePayrollModel::with(['employee', 'studio'])
                ->whereIn('studio_id', $assignedStudioIds);

            if ($request->filled('studio_id')) {
                $query->where('studio_id', $request->studio_id);
            }

            if ($request->filled('payroll_basis')) {
                $query->where('payroll_basis', $request->payroll_basis);
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active === 'active' ? 1 : 0);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('employee', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $payrollSettings = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 10));

            $payrollSettings->getCollection()->transform(function ($payroll) {
                return [
                    'id' => $payroll->id,
                    'user_id' => $payroll->user_id,
                    'studio_id' => $payroll->studio_id,
                    'studio_name' => $payroll->studio->studio_name ?? 'N/A',
                    'employee_name' => $payroll->employee->full_name ?? 'N/A',
                    'employee_email' => $payroll->employee->email ?? 'N/A',
                    'employee_role' => $payroll->employee->role ?? 'N/A',
                    'employee_role_display' => $this->getRoleDisplay($payroll->employee->role ?? ''),
                    'payroll_basis' => $payroll->payroll_basis,
                    'payroll_basis_display' => $payroll->payroll_basis_display,
                    'monthly_salary' => $payroll->monthly_salary ? 'PHP ' . number_format($payroll->monthly_salary, 2) : 'N/A',
                    'daily_rate' => $payroll->daily_rate ? 'PHP ' . number_format($payroll->daily_rate, 2) : 'N/A',
                    'total_allowances' => 'PHP ' . number_format($payroll->total_allowances, 2),
                    'total_deductions' => 'PHP ' . number_format($payroll->total_deductions, 2),
                    'base_monthly_net' => 'PHP ' . number_format($payroll->base_monthly_net, 2),
                    'payment_schedule' => $payroll->payment_schedule_display,
                    'is_active' => $payroll->is_active,
                    'status_badge' => $payroll->is_active ? 'badge-soft-success' : 'badge-soft-secondary',
                    'status_text' => $payroll->is_active ? 'ACTIVE' : 'INACTIVE',
                    'effective_date' => $payroll->effective_date ? $payroll->effective_date->format('M d, Y') : 'N/A',
                    'created_at' => $payroll->created_at->format('M d, Y'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payrollSettings
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load payroll settings data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payroll settings'
            ], 500);
        }
    }

    private function getRoleDisplay($role): string
    {
        return RoleModel::getFriendlyRoleName($role);
    }

    public function bulkStore(Request $request)
    {
        DB::beginTransaction();

        try {
            $hrUser = $this->getAuthenticatedHrUser();

            if (!$this->hasPayrollPermission($hrUser, 'create')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create payroll settings.'
                ], 403);
            }

            $assignedStudioIds = $this->getAssignedStudioIds($hrUser->id);
            $validated = $request->validate([
                'studio_id' => [
                    'required',
                    function ($attribute, $value, $fail) use ($assignedStudioIds) {
                        if (!$assignedStudioIds->contains((int) $value)) {
                            $fail('The selected studio is invalid or not assigned to your account.');
                        }
                    },
                ],
                'employees' => 'required|array|min:1',
                'employees.*.user_id' => 'required|exists:tbl_users,id',
                'employees.*.payroll_basis' => 'required|in:attendance_only,booking_and_attendance',
                'employees.*.monthly_salary' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.daily_rate' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.hourly_rate' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.per_booking_rate' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.booking_commission_percentage' => 'nullable|numeric|min:0|max:100',
                'employees.*.sss_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.phic_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.hdmf_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.tax_withholding' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.sss_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.hdmf_loan_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.other_deductions' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.is_taxable' => 'boolean',
                'employees.*.tax_type' => 'nullable|in:withholding,graduated,exempt',
                'employees.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
                'employees.*.subject_to_vat' => 'boolean',
                'employees.*.vat_percentage' => 'nullable|numeric|min:0|max:100',
                'employees.*.vat_type' => 'nullable|in:inclusive,exclusive',
                'employees.*.absence_deduction_per_day' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.undertime_deduction_per_hour' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.late_grace_period_minutes' => 'nullable|integer|min:0|max:120',
                'employees.*.late_deduction_per_minute' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.absent_deduction_method' => 'nullable|in:deduct_daily_rate,deduct_fixed_amount,deduct_percentage',
                'employees.*.absent_fixed_deduction' => 'nullable|numeric|min:0|max:999999.99',
                'employees.*.absent_percentage_deduction' => 'nullable|numeric|min:0|max:100',
                'employees.*.payment_schedule' => 'required|in:weekly,bi_weekly,semi_monthly,monthly',
                'employees.*.payday_1' => 'nullable|integer|min:1|max:31',
                'employees.*.payday_2' => 'nullable|integer|min:1|max:31',
                'employees.*.payday_weekly' => 'nullable|in:monday,tuesday,wednesday,thursday,friday',
                'employees.*.bank_name' => 'nullable|string|max:100',
                'employees.*.bank_account_number' => 'nullable|string|max:50',
                'employees.*.bank_account_name' => 'nullable|string|max:255',
                'employees.*.payment_method' => 'nullable|in:bank_transfer,cash,check',
                'employees.*.is_active' => 'boolean',
                'employees.*.effective_date' => 'nullable|date',
                'employees.*.expiry_date' => 'nullable|date|after_or_equal:employees.*.effective_date',
                'employees.*.notes' => 'nullable|string|max:1000',
            ]);

            $studioId = $validated['studio_id'];
            $createdPayrolls = [];
            $errors = [];
            $userIds = collect($validated['employees'])->pluck('user_id')->toArray();

            $existingPayrolls = EmployeePayrollModel::where('studio_id', $studioId)
                ->whereIn('user_id', $userIds)
                ->pluck('user_id')
                ->toArray();

            $users = UserModel::whereIn('id', $userIds)
                ->get()
                ->keyBy('id');

            foreach ($validated['employees'] as $employeeData) {
                $userId = $employeeData['user_id'];

                if (in_array($userId, $existingPayrolls)) {
                    $errors[] = "Employee ID {$userId} already has payroll settings.";
                    continue;
                }

                $user = $users->get($userId);
                if (!$user) {
                    $errors[] = "Employee ID {$userId} not found.";
                    continue;
                }

                $payrollBasis = $employeeData['payroll_basis'];
                $role = $user->role;

                if ($role === 'studio-photographer' && $payrollBasis !== 'booking_and_attendance') {
                    $errors[] = "Employee {$user->full_name} (Photographer) must use 'Booking + Attendance' payroll basis.";
                    continue;
                }

                if (in_array($role, ['studio-hr', 'studio-finance']) && $payrollBasis !== 'attendance_only') {
                    $errors[] = "Employee {$user->full_name} (HR/Finance) must use 'Attendance Only' payroll basis.";
                    continue;
                }

                if (
                    $payrollBasis === 'booking_and_attendance' &&
                    empty($employeeData['per_booking_rate']) &&
                    empty($employeeData['booking_commission_percentage'])
                ) {
                    $errors[] = "Employee {$user->full_name} (Photographer) requires either Per Booking Rate or Commission Percentage.";
                    continue;
                }

                $data = [
                    'user_id' => $userId,
                    'studio_id' => $studioId,
                    'created_by' => $hrUser->id,
                    'payroll_basis' => $payrollBasis,
                    'daily_rate' => $employeeData['daily_rate'] ?? null,
                    'monthly_salary' => $employeeData['monthly_salary'] ?? null,
                    'hourly_rate' => $employeeData['hourly_rate'] ?? null,
                    'per_booking_rate' => $employeeData['per_booking_rate'] ?? null,
                    'booking_commission_percentage' => $employeeData['booking_commission_percentage'] ?? null,
                    'sss_deduction' => $employeeData['sss_deduction'] ?? 0,
                    'phic_deduction' => $employeeData['phic_deduction'] ?? 0,
                    'hdmf_deduction' => $employeeData['hdmf_deduction'] ?? 0,
                    'tax_withholding' => $employeeData['tax_withholding'] ?? 0,
                    'sss_loan_deduction' => $employeeData['sss_loan_deduction'] ?? 0,
                    'hdmf_loan_deduction' => $employeeData['hdmf_loan_deduction'] ?? 0,
                    'other_deductions' => $employeeData['other_deductions'] ?? 0,
                    'is_taxable' => $employeeData['is_taxable'] ?? true,
                    'tax_type' => $employeeData['tax_type'] ?? 'withholding',
                    'tax_percentage' => $employeeData['tax_percentage'] ?? null,
                    'tax_code' => $employeeData['tax_code'] ?? null,
                    'subject_to_vat' => $employeeData['subject_to_vat'] ?? false,
                    'vat_percentage' => $employeeData['vat_percentage'] ?? 12,
                    'vat_type' => $employeeData['vat_type'] ?? 'exclusive',
                    'absence_deduction_per_day' => $employeeData['absence_deduction_per_day'] ?? null,
                    'undertime_deduction_per_hour' => $employeeData['undertime_deduction_per_hour'] ?? null,
                    'late_grace_period_minutes' => $employeeData['late_grace_period_minutes'] ?? 15,
                    'late_deduction_per_minute' => $employeeData['late_deduction_per_minute'] ?? null,
                    'absent_deduction_method' => $employeeData['absent_deduction_method'] ?? 'deduct_daily_rate',
                    'absent_fixed_deduction' => $employeeData['absent_fixed_deduction'] ?? null,
                    'absent_percentage_deduction' => $employeeData['absent_percentage_deduction'] ?? null,
                    'paid_holidays' => true,
                    'payment_schedule' => $employeeData['payment_schedule'],
                    'payday_1' => $employeeData['payday_1'] ?? null,
                    'payday_2' => $employeeData['payday_2'] ?? null,
                    'payday_weekly' => $employeeData['payday_weekly'] ?? null,
                    'bank_name' => $employeeData['bank_name'] ?? null,
                    'bank_account_number' => $employeeData['bank_account_number'] ?? null,
                    'bank_account_name' => $employeeData['bank_account_name'] ?? null,
                    'payment_method' => $employeeData['payment_method'] ?? 'bank_transfer',
                    'is_active' => $employeeData['is_active'] ?? true,
                    'effective_date' => $employeeData['effective_date'] ?? now()->toDateString(),
                    'expiry_date' => $employeeData['expiry_date'] ?? null,
                    'notes' => $employeeData['notes'] ?? null,
                ];

                if (empty($data['hourly_rate']) && !empty($data['daily_rate'])) {
                    $data['hourly_rate'] = $data['daily_rate'] / 8;
                }

                $createdPayrolls[] = EmployeePayrollModel::create($data);
            }

            DB::commit();

            $successCount = count($createdPayrolls);
            $errorCount = count($errors);

            $message = "Successfully created {$successCount} payroll setting(s).";
            if ($errorCount > 0) {
                $message .= " Failed to create {$errorCount} setting(s).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created_count' => $successCount,
                    'created_payrolls' => collect($createdPayrolls)->map(function ($payroll) {
                        return [
                            'id' => $payroll->id,
                            'employee_name' => $payroll->employee->full_name ?? 'N/A',
                        ];
                    }),
                    'errors' => $errors,
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to bulk create payroll settings: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payroll settings: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getAuthenticatedHrUser(): UserModel
    {
        return UserModel::with('roles.permissions')->findOrFail(auth()->id());
    }

    private function getAssignedStudioIds($hrId)
    {
        $studioIds = EmployeeScheduleModel::where('user_id', $hrId)
            ->pluck('studio_id');

        if ($studioIds->isEmpty()) {
            $studioIds = DB::table('tbl_studio_photographers')
                ->where('photographer_id', $hrId)
                ->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $hrId)->pluck('id');
        }

        return $studioIds->unique()->values();
    }

    private function hasPayrollPermission(UserModel $user, string $action): bool
    {
        $permissionMap = [
            'view' => ['studio-hr.payroll.view', 'studio-hr.payroll.manage'],
            'create' => ['studio-hr.payroll.create', 'studio-hr.payroll.manage'],
            'update' => ['studio-hr.payroll.edit', 'studio-hr.payroll.update', 'studio-hr.payroll.manage'],
            'delete' => ['studio-hr.payroll.delete', 'studio-hr.payroll.manage'],
        ];

        foreach ($permissionMap[$action] ?? [] as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
