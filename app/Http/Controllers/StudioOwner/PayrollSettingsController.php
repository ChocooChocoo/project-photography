<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\EmployeePayrollRequest;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollSettingsController extends Controller
{
    /**
     * Display a listing of payroll settings.
     */
    public function index(Request $request)
    {
        $ownerId = auth()->id();
        
        // Get studios owned by this owner
        $studios = StudiosModel::where('user_id', $ownerId)
            ->whereIn('status', ['verified', 'active'])
            ->get();
        
        // Get studio IDs for filtering
        $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
        
        // Build query for payroll settings
        $query = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
            ->whereIn('studio_id', $studioIds);
        
        // Apply filters from request
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
        
        // Get paginated results
        $payrollSettings = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Transform for JSON response (for AJAX filtering)
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
        
        return view('owner.view-payroll-settings', compact('payrollSettings', 'studios', 'payrollSettingsJson'));
    }

    /**
     * Show the form for creating a new payroll setting.
     */
    public function create()
    {
        $ownerId = auth()->id();
        
        // Get verified studios owned by the current user
        $studios = StudiosModel::where('user_id', $ownerId)
            ->whereIn('status', ['verified', 'active'])
            ->get();
        
        return view('owner.create-payroll-settings', compact('studios'));
    }

    /**
     * Store a newly created payroll setting.
     */
    public function store(EmployeePayrollRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $ownerId = auth()->id();
            
            // Prepare data
            $data = $request->validated();
            $data['created_by'] = $ownerId;
            
            // Handle JSON fields
            if ($request->has('custom_allowances') && is_array($request->custom_allowances)) {
                $data['custom_allowances'] = array_values($request->custom_allowances);
            }
            
            if ($request->has('custom_deductions') && is_array($request->custom_deductions)) {
                $data['custom_deductions'] = array_values($request->custom_deductions);
            }
            
            // Calculate hourly rate if not provided but daily rate is
            if (empty($data['hourly_rate']) && !empty($data['daily_rate'])) {
                $data['hourly_rate'] = $data['daily_rate'] / 8;
            }
            
            // Create payroll setting
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

    /**
     * Get employees eligible for payroll setup.
     */
    public function getEmployees(Request $request)
    {
        try {
            $ownerId = auth()->id();
            $studioId = $request->studio_id;
            
            if (!$studioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio ID is required',
                    'data' => []
                ], 400);
            }
            
            // Verify the studio belongs to the owner
            $studio = StudiosModel::where('id', $studioId)
                ->where('user_id', $ownerId)
                ->first();
            
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found or does not belong to you',
                    'data' => []
                ], 404);
            }
            
            // Get all employees from tbl_rbac for this studio
            $employees = DB::table('tbl_users')
                ->join('tbl_rbac', 'tbl_users.id', '=', 'tbl_rbac.user_id')
                ->where('tbl_rbac.studio_id', $studioId)
                ->whereIn('tbl_users.role', ['studio-hr', 'studio-finance', 'studio-photographer'])
                ->where('tbl_users.status', 'active')
                ->select(
                    'tbl_users.id',
                    'tbl_users.first_name',
                    'tbl_users.last_name',
                    'tbl_users.email',
                    'tbl_users.role',
                    'tbl_rbac.role_type',
                    'tbl_rbac.studio_id'
                )
                ->get();
            
            \Log::info('Raw employees from database:', [
                'studio_id' => $studioId,
                'count' => $employees->count(),
                'employees' => $employees
            ]);
            
            // Get employees who already have payroll settings
            $employeesWithPayroll = EmployeePayrollModel::where('studio_id', $studioId)
                ->pluck('user_id')
                ->toArray();
            
            \Log::info('Employees with existing payroll:', $employeesWithPayroll);
            
            // Filter out employees who already have payroll
            $filteredEmployees = $employees->filter(function($employee) use ($employeesWithPayroll) {
                return !in_array($employee->id, $employeesWithPayroll);
            });
            
            \Log::info('Filtered employees count:', ['count' => $filteredEmployees->count()]);
            
            // Format for response
            $formattedEmployees = $filteredEmployees->map(function ($employee) {
                // Get role display name
                $roleDisplay = $this->getRoleDisplay($employee->role);
                
                // Add role type if exists
                if ($employee->role_type) {
                    $roleDisplay .= ' - ' . $employee->role_type;
                }
                
                return [
                    'id' => $employee->id,
                    'full_name' => trim($employee->first_name . ' ' . $employee->last_name),
                    'email' => $employee->email,
                    'role' => $employee->role,
                    'role_display' => $roleDisplay,
                    'studio_id' => $employee->studio_id,
                    'studio_name' => null, // Will be populated if needed
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
            \Log::error('Failed to load employees: ' . $e->getMessage(), [
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

    /**
     * Display the specified payroll setting.
     */
    public function show($id)
    {
        try {
            $ownerId = auth()->id();
            
            // Get studio IDs owned by this owner
            $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
            
            $payroll = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
                ->whereIn('studio_id', $studioIds)
                ->findOrFail($id);
            
            // Format response data
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
                
                // Allowances
                'rice_allowance' => $payroll->rice_allowance,
                'clothing_allowance' => $payroll->clothing_allowance,
                'laundry_allowance' => $payroll->laundry_allowance,
                'transportation_allowance' => $payroll->transportation_allowance,
                'meal_allowance' => $payroll->meal_allowance,
                'other_allowances' => $payroll->other_allowances,
                'custom_allowances' => $payroll->custom_allowances,
                'total_allowances' => $payroll->total_allowances,
                
                // Deductions
                'sss_deduction' => $payroll->sss_deduction,
                'phic_deduction' => $payroll->phic_deduction,
                'hdmf_deduction' => $payroll->hdmf_deduction,
                'tax_withholding' => $payroll->tax_withholding,
                'sss_loan_deduction' => $payroll->sss_loan_deduction,
                'hdmf_loan_deduction' => $payroll->hdmf_loan_deduction,
                'cash_advance_deduction' => $payroll->cash_advance_deduction,
                'other_deductions' => $payroll->other_deductions,
                'custom_deductions' => $payroll->custom_deductions,
                'total_deductions' => $payroll->total_deductions,
                
                // Tax Settings
                'is_taxable' => $payroll->is_taxable,
                'tax_type' => $payroll->tax_type,
                'tax_percentage' => $payroll->tax_percentage,
                'tax_code' => $payroll->tax_code,
                
                // VAT Settings
                'subject_to_vat' => $payroll->subject_to_vat,
                'vat_percentage' => $payroll->vat_percentage,
                'vat_type' => $payroll->vat_type,
                
                // Absence and Undertime
                'absence_deduction_per_day' => $payroll->absence_deduction_per_day,
                'undertime_deduction_per_hour' => $payroll->undertime_deduction_per_hour,
                'late_grace_period_minutes' => $payroll->late_grace_period_minutes,
                'late_deduction_per_minute' => $payroll->late_deduction_per_minute,
                'absent_deduction_method' => $payroll->absent_deduction_method,
                'absent_fixed_deduction' => $payroll->absent_fixed_deduction,
                'absent_percentage_deduction' => $payroll->absent_percentage_deduction,
                
                // Overtime Settings
                'overtime_enabled' => $payroll->overtime_enabled,
                'overtime_rate_multiplier' => $payroll->overtime_rate_multiplier,
                'night_differential_rate' => $payroll->night_differential_rate,
                'night_differential_start' => $payroll->night_differential_start ? $payroll->night_differential_start->format('H:i') : null,
                'night_differential_end' => $payroll->night_differential_end ? $payroll->night_differential_end->format('H:i') : null,
                'holiday_overtime_enabled' => $payroll->holiday_overtime_enabled,
                'holiday_overtime_rate' => $payroll->holiday_overtime_rate,
                
                // Leave Settings
                'regular_holidays_per_year' => $payroll->regular_holidays_per_year,
                'special_holidays_per_year' => $payroll->special_holidays_per_year,
                'paid_holidays' => $payroll->paid_holidays,
                'vacation_leave_days_per_year' => $payroll->vacation_leave_days_per_year,
                'sick_leave_days_per_year' => $payroll->sick_leave_days_per_year,
                'emergency_leave_days_per_year' => $payroll->emergency_leave_days_per_year,
                'leave_conversion_enabled' => $payroll->leave_conversion_enabled,
                'leave_conversion_rate' => $payroll->leave_conversion_rate,
                
                // Payment Schedule
                'payment_schedule' => $payroll->payment_schedule,
                'payment_schedule_display' => $payroll->payment_schedule_display,
                'payday_1' => $payroll->payday_1,
                'payday_2' => $payroll->payday_2,
                'payday_weekly' => $payroll->payday_weekly,
                
                // Banking Information
                'bank_name' => $payroll->bank_name,
                'bank_account_number' => $payroll->bank_account_number,
                'bank_account_name' => $payroll->bank_account_name,
                'payment_method' => $payroll->payment_method,
                
                // Status and Dates
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

    /**
     * Show the form for editing the specified payroll setting.
     */
    public function edit($id)
    {
        $ownerId = auth()->id();
        
        // Get studio IDs owned by this owner
        $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
        
        $payroll = EmployeePayrollModel::with(['employee', 'studio'])
            ->whereIn('studio_id', $studioIds)
            ->findOrFail($id);
        
        // Get studios for dropdown
        $studios = StudiosModel::where('user_id', $ownerId)
            ->whereIn('status', ['verified', 'active'])
            ->get();
        
        return view('owner.edit-payroll-settings', compact('payroll', 'studios'));
    }

    /**
     * Update the specified payroll setting.
     */
    public function update(EmployeePayrollRequest $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $ownerId = auth()->id();
            
            // Get studio IDs owned by this owner
            $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
            
            $payroll = EmployeePayrollModel::whereIn('studio_id', $studioIds)
                ->findOrFail($id);
            
            // Prepare data
            $data = $request->validated();
            
            // Handle JSON fields
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
            
            // Calculate hourly rate if not provided but daily rate is
            if (empty($data['hourly_rate']) && !empty($data['daily_rate'])) {
                $data['hourly_rate'] = $data['daily_rate'] / 8;
            }
            
            // Update payroll setting
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

    /**
     * Update payroll status (active/inactive).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);
        
        try {
            $ownerId = auth()->id();
            
            // Get studio IDs owned by this owner
            $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
            
            $payroll = EmployeePayrollModel::whereIn('studio_id', $studioIds)
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

    /**
     * Remove the specified payroll setting.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $ownerId = auth()->id();
            
            // Get studio IDs owned by this owner
            $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
            
            $payroll = EmployeePayrollModel::whereIn('studio_id', $studioIds)
                ->findOrFail($id);
            
            // Soft delete
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

    /**
     * Get payroll settings list for DataTable.
     */
    public function getPayrollSettings(Request $request)
    {
        try {
            $ownerId = auth()->id();
            
            // Get studio IDs owned by this owner
            $studioIds = StudiosModel::where('user_id', $ownerId)->pluck('id');
            
            $query = EmployeePayrollModel::with(['employee', 'studio'])
                ->whereIn('studio_id', $studioIds);
            
            // Apply filters
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
            
            // Transform data for response
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
                    'monthly_salary' => $payroll->monthly_salary ? '₱' . number_format($payroll->monthly_salary, 2) : 'N/A',
                    'daily_rate' => $payroll->daily_rate ? '₱' . number_format($payroll->daily_rate, 2) : 'N/A',
                    'total_allowances' => '₱' . number_format($payroll->total_allowances, 2),
                    'total_deductions' => '₱' . number_format($payroll->total_deductions, 2),
                    'base_monthly_net' => '₱' . number_format($payroll->base_monthly_net, 2),
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

    /**
     * Helper: Get role display name.
     */
    private function getRoleDisplay($role): string
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
        ];
        
        return $roles[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }
}