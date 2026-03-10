<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\EmployeePayrollRequest;
use App\Models\StudioOwner\EmployeePayrollModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\RbacModel;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollSettingsController extends Controller
{
    /**
     * Display a listing of payroll settings.
     * RBAC: Requires can_read permission
     */
    public function index(Request $request)
    {
        $hrUser = auth()->user();
        
        // Get HR user's RBAC permissions
        $rbac = RbacModel::where('user_id', $hrUser->id)->first();
        
        // Check if HR has read permission
        if (!$rbac || !$rbac->can_read) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }
            
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'You do not have permission to view payroll settings.');
        }
        
        // Get HR user's assigned studio from RBAC
        $studioId = $rbac->studio_id;
        
        // Get the studio details
        $studio = StudiosModel::find($studioId);
        
        if (!$studio) {
            return redirect()->route('studio-hr.dashboard')
                ->with('error', 'No studio assigned to your account.');
        }
        
        // Build query for payroll settings - limited to HR's assigned studio
        $query = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
            ->where('studio_id', $studioId);
        
        // Apply filters from request
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
        
        // Get studios for filter dropdown (only the HR's assigned studio)
        $studios = collect([$studio]);
        
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
        
        return view('studio-hr.view-payroll-settings', compact('payrollSettings', 'studios', 'payrollSettingsJson'));
    }

    /**
     * RBAC: Requires can_create permission
     */
    public function create()
    {
        $hrUser = auth()->user();
        
        // Get HR user's RBAC permissions
        $rbac = RbacModel::where('user_id', $hrUser->id)->first();
        
        // Check if HR has create permission
        $canCreate = $rbac && $rbac->can_create;
        if (!$canCreate) {
            session()->flash('permission_warning', 'Your account has restricted permissions. You can view the form but cannot submit it.');
        }
        
        // Get HR user's assigned studio from RBAC or try alternative methods
        $studioId = null;
        $studio = null;
        
        if ($rbac && $rbac->studio_id) {
            // Case 1: RBAC exists with studio_id
            $studioId = $rbac->studio_id;
            $studio = StudiosModel::find($studioId);
        } else {
            // Case 2: RBAC is null or has no studio_id - try alternative lookup methods
            \Log::warning('HR user has no RBAC studio assignment', [
                'user_id' => $hrUser->id,
                'email' => $hrUser->email
            ]);
            
            // Method A: Check if user is directly associated with a studio via studio_members
            $studioMember = \DB::table('tbl_studio_members')
                ->where('freelancer_id', $hrUser->id)
                ->where('status', 'approved')
                ->first();
                
            if ($studioMember) {
                $studioId = $studioMember->studio_id;
                $studio = StudiosModel::find($studioId);
                \Log::info('Found studio via studio_members', ['studio_id' => $studioId]);
            }
            
            // Method B: If user is a photographer, check studio_photographers
            if (!$studio && $hrUser->role === 'studio-photographer') {
                $studioPhotographer = \DB::table('tbl_studio_photographers')
                    ->where('photographer_id', $hrUser->id)
                    ->first();
                    
                if ($studioPhotographer) {
                    $studioId = $studioPhotographer->studio_id;
                    $studio = StudiosModel::find($studioId);
                    \Log::info('Found studio via studio_photographers', ['studio_id' => $studioId]);
                }
            }
        }
        
        if (!$studioId || !$studio) {
            // No studio found - redirect with error
            $errorMessage = 'No studio assigned to your account. Please contact your studio owner.';
            
            if ($hrUser->role === 'studio-hr') {
                $errorMessage = 'Your HR account is not properly configured. Please contact your studio owner to set up your RBAC permissions.';
            } elseif ($hrUser->role === 'studio-finance') {
                $errorMessage = 'Your Finance account is not properly configured. Please contact your studio owner to set up your RBAC permissions.';
            } elseif ($hrUser->role === 'studio-photographer') {
                $errorMessage = 'Your Photographer account is not properly configured. Please contact your studio owner to set up your RBAC permissions.';
            }
            
            return redirect()->route('studio-hr.payroll-settings.index')
                ->with('error', $errorMessage);
        }
        
        // Get the studio (only the HR's assigned studio)
        $studios = collect([$studio]);
        
        return view('studio-hr.create-payroll-settings', compact('studios', 'canCreate'));
    }

    /**
     * Store a newly created payroll setting.
     * RBAC: Requires can_create permission
     */
    public function store(EmployeePayrollRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Double-check create permission
            if (!$rbac || !$rbac->can_create) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create payroll settings.'
                ], 403);
            }
            
            // Prepare data
            $data = $request->validated();
            $data['created_by'] = $hrUser->id; // Track who created it
            
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
     * RBAC: Requires can_read permission
     */
    public function getEmployees(Request $request)
    {
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Check if HR has read permission
            if (!$rbac || !$rbac->can_read) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view employees.',
                    'data' => []
                ], 403);
            }
            
            $studioId = $rbac->studio_id;
            
            if (!$studioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studio assigned to your account.',
                    'data' => []
                ], 400);
            }
            
            // Verify the studio exists (no need to check ownership like owner side)
            $studio = StudiosModel::find($studioId);
            
            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'Studio not found',
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
            
            // Get employees who already have payroll settings
            $employeesWithPayroll = EmployeePayrollModel::where('studio_id', $studioId)
                ->pluck('user_id')
                ->toArray();
            
            // Filter out employees who already have payroll
            $filteredEmployees = $employees->filter(function($employee) use ($employeesWithPayroll) {
                return !in_array($employee->id, $employeesWithPayroll);
            });
            
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
                    'studio_name' => null,
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

    /**
     * Display the specified payroll setting.
     * RBAC: Requires can_read permission
     */
    public function show($id)
    {
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Check if HR has read permission
            if (!$rbac || !$rbac->can_read) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }
            
            // Get HR's assigned studio
            $studioId = $rbac->studio_id;
            
            $payroll = EmployeePayrollModel::with(['employee', 'studio', 'creator'])
                ->where('studio_id', $studioId)
                ->findOrFail($id);
            
            // Format response data - REMOVED ALLOWANCES, OVERTIME, LEAVE sections
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
                
                // ALLOWANCES SECTION REMOVED COMPLETELY
                
                // Deductions (only kept fields)
                'sss_deduction' => $payroll->sss_deduction,
                'phic_deduction' => $payroll->phic_deduction,
                'hdmf_deduction' => $payroll->hdmf_deduction,
                'tax_withholding' => $payroll->tax_withholding,
                'sss_loan_deduction' => $payroll->sss_loan_deduction,
                'hdmf_loan_deduction' => $payroll->hdmf_loan_deduction,
                'other_deductions' => $payroll->other_deductions,
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
                
                // OVERTIME SETTINGS REMOVED COMPLETELY
                // LEAVE SETTINGS REMOVED COMPLETELY
                
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
     * RBAC: Requires can_update permission
     */
    public function edit($id)
    {
        $hrUser = auth()->user();

        // Get HR user's RBAC permissions
        $rbac = RbacModel::where('user_id', $hrUser->id)->first();

        // Get HR's assigned studio
        $studioId = $rbac ? $rbac->studio_id : null;

        if (!$studioId) {
            return redirect()->route('studio-hr.payroll-settings.index')
                ->with('error', 'No studio assigned to your account.');
        }

        $payroll = EmployeePayrollModel::with(['employee', 'studio'])
            ->where('studio_id', $studioId)
            ->findOrFail($id);

        // Get studios for dropdown (only the HR's assigned studio)
        $studios = StudiosModel::where('id', $studioId)
            ->whereIn('status', ['verified', 'active'])
            ->get();

        // Resolve can_update — allow access but disable fields if no permission
        $canUpdate = $rbac && $rbac->can_update;

        return view('studio-hr.edit-payroll-settings', compact('payroll', 'studios', 'canUpdate'));
    }

    /**
     * Update the specified payroll setting.
     * RBAC: Requires can_update permission
     */
    public function update(EmployeePayrollRequest $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Double-check update permission
            if (!$rbac || !$rbac->can_update) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update payroll settings.'
                ], 403);
            }
            
            // Get HR's assigned studio
            $studioId = $rbac->studio_id;
            
            $payroll = EmployeePayrollModel::where('studio_id', $studioId) // Ensure it belongs to HR's studio
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
     * RBAC: Requires can_update permission
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);
        
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Check if HR has update permission
            if (!$rbac || !$rbac->can_update) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to update payroll status.'
                ], 403);
            }
            
            // Get HR's assigned studio
            $studioId = $rbac->studio_id;
            
            $payroll = EmployeePayrollModel::where('studio_id', $studioId) // Ensure it belongs to HR's studio
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
     * RBAC: Requires can_delete permission
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Check if HR has delete permission
            if (!$rbac || !$rbac->can_delete) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete payroll settings.'
                ], 403);
            }
            
            // Get HR's assigned studio
            $studioId = $rbac->studio_id;
            
            $payroll = EmployeePayrollModel::where('studio_id', $studioId) // Ensure it belongs to HR's studio
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
     * RBAC: Requires can_read permission
     */
    public function getPayrollSettings(Request $request)
    {
        try {
            $hrUser = auth()->user();
            
            // Get HR user's RBAC permissions
            $rbac = RbacModel::where('user_id', $hrUser->id)->first();
            
            // Check if HR has read permission
            if (!$rbac || !$rbac->can_read) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view payroll settings.'
                ], 403);
            }
            
            // Get HR's assigned studio
            $studioId = $rbac->studio_id;
            
            $query = EmployeePayrollModel::with(['employee', 'studio'])
                ->where('studio_id', $studioId);
            
            // Apply filters
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