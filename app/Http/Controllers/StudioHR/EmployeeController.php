<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioHR\EmployeeRequest;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\Admin\CategoriesModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\RoleModel;
use App\Mail\EmployeeRegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $hrId = auth()->id();
        
        $hrUser = UserModel::findOrFail($hrId);
        
        // Get studios assigned to this HR via tbl_user_roles (new RBAC)
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        // Get studios details for filter dropdown
        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->get();
        
        // Check if HR has view_employees permission
        $canView = $hrUser->hasPermission('view_employees');
        
        // Check if HR has update permission for edit/delete actions
        $canUpdate = $hrUser->hasPermission('edit_employee');
        $canDelete = $hrUser->hasPermission('delete_employee');
        
        // Build query for employees (excluding HR themselves)
        $query = UserModel::with(['roles', 'employeeSchedule' => function($q) use ($assignedStudioIds) {
                $q->whereIn('studio_id', $assignedStudioIds);
            }])
            ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->where('id', '!=', $hrId)
            ->whereExists(function ($q) use ($assignedStudioIds) {
                $q->select(DB::raw(1))
                  ->from('tbl_user_roles')
                  ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
                  ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                  ->whereIn('tbl_user_roles.studio_id', $assignedStudioIds)
                  ->whereIn('tbl_roles.name', ['studio-hr-manager', 'studio-hr-staff', 'studio-finance-manager', 'studio-finance-staff', 'studio-photographer']);
            });
        
        // Apply filters from request
        if ($request->filled('studio_id')) {
            $query->whereHas('employeeSchedule', function ($q) use ($request) {
                $q->where('studio_id', $request->studio_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }
        
        // Get paginated results
        $employees = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Transform employees for view
        foreach ($employees as $employee) {
            $schedule = $employee->employeeSchedule->first();
            
            // Get photographer details if applicable
            if ($employee->role === 'studio-photographer') {
                $employee->photographer_details = StudioPhotographersModel::where('photographer_id', $employee->id)->first();
            }
            
            $employee->schedule_data = $schedule;
            $employee->studio_data = $schedule ? $schedule->studio : null;
            
            // Get the employee's role display name
            $userRole = $employee->roles->first();
            $employee->role_display = $userRole ? $userRole->display_name : $this->getRoleDisplay($employee->role);
        }
        
        $employeesJson = json_encode($employees->map(function ($emp) {
            return [
                'id'            => $emp->id,
                'studio_id'     => $emp->studio_data->id ?? null,
                'studio_name'   => $emp->studio_data->studio_name ?? 'N/A',
                'full_name'     => $emp->full_name,
                'email'         => $emp->email,
                'mobile_number' => $emp->mobile_number,
                'role'          => $emp->role,
                'user_type'     => $emp->user_type,
                'status'        => $emp->status,
            ];
        })->values());

        return view('studio-hr.view-employee', compact('employees', 'studios', 'employeesJson', 'canView', 'canUpdate', 'canDelete'));
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $hrId = auth()->id();
        
        $hrUser = UserModel::findOrFail($hrId);
        $canCreate = $hrUser->hasPermission('create_employee');
        
        // Get studios assigned to this HR via tbl_user_roles
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        // Get verified studios assigned to HR
        $studios = StudiosModel::whereIn('id', $assignedStudioIds)
            ->whereIn('status', ['verified', 'active'])
            ->get();
        
        return view('studio-hr.create-employee', compact('studios', 'canCreate'));
    }

    /**
     * Store a newly created employee.
     */
    public function store(EmployeeRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $hrId = auth()->id();
            
            $hrUser = UserModel::findOrFail($hrId);

            if (!$hrUser->hasPermission('create_employee')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to create employees.'
                ], 403);
            }
            
            $uuid = Str::uuid();
            
            // Get the selected role
            $selectedRole = RoleModel::findOrFail($request->role_id);
            
            // Determine the base role and user_type
            $roleName = $selectedRole->name;
            $roleParts = explode('-', $roleName);
            
            if (count($roleParts) === 3) {
                // studio-hr-manager, studio-hr-staff, studio-finance-manager, studio-finance-staff
                $baseRole = $roleParts[0] . '-' . $roleParts[1];
                $userType = $roleParts[2];
            } else {
                // studio-photographer
                $baseRole = $roleName;
                $userType = 'Photographer';
            }
            
            // Generate password
            $password = $baseRole . $uuid;
            $temporaryPassword = $password;
            
            // Prepare user data
            $userData = [
                'uuid' => $uuid,
                'role' => $baseRole,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'user_type' => ucfirst($userType),
                'email' => $request->email,
                'mobile_number' => $request->mobile_number,
                'password' => Hash::make($password),
                'status' => $request->status,
                'email_verified' => 1,
                'verification_token' => null,
                'token_expiry' => null,
            ];

            // Add profile photo if uploaded
            if ($request->hasFile('profile_photo')) {
                $userData['profile_photo'] = $this->handleProfilePhoto($request);
            }
            
            // Create user
            $user = UserModel::create($userData);
            
            // Get studio info
            $studio = StudiosModel::find($request->studio_id);
            
            // If photographer, create studio photographer record
            if ($selectedRole->name === 'studio-photographer') {
                $this->createPhotographerRecord($request, $hrId, $user->id, $studio);
            }
            
            // Assign role to user (new RBAC system)
            $user->assignRole($selectedRole, (int) $request->studio_id);
            
            // Create employee schedule
            $schedule = EmployeeScheduleModel::create([
                'user_id' => $user->id,
                'studio_id' => $request->studio_id,
                'operating_days' => $request->operating_days,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'is_active' => true,
                'notes' => null,
            ]);
            
            // Prepare email data
            $employeeData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'role' => $baseRole,
                'role_display' => $selectedRole->display_name,
                'role_type' => $userType,
                'studio_name' => $studio->studio_name,
                'status' => $request->status,
                'schedule' => $schedule->formatted_operating_days . ' ' . $schedule->formatted_hours,
            ];
            
            // Add photographer-specific data
            if ($selectedRole->name === 'studio-photographer') {
                $employeeData['position'] = $request->position;
                $employeeData['specialization'] = $request->specialization;
                $employeeData['years_experience'] = $request->years_experience;
            }
            
            // Send registration email
            $this->sendRegistrationEmail($employeeData, $temporaryPassword);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee registered successfully! Login credentials have been emailed to ' . $request->email,
                'data' => [
                    'user_id' => $user->id,
                    'role' => $baseRole,
                    'role_type' => $userType,
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create employee (HR): ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees list for DataTable.
     */
    public function getEmployees(Request $request)
    {
        $hrId = auth()->id();
        
        // Get studios assigned to this HR
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        $query = UserModel::with(['roles', 'employeeSchedule' => function($q) use ($assignedStudioIds) {
                $q->whereIn('studio_id', $assignedStudioIds);
            }])
            ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->where('id', '!=', $hrId)
            ->whereExists(function ($q) use ($assignedStudioIds) {
                $q->select(DB::raw(1))
                  ->from('tbl_user_roles')
                  ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
                  ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                  ->whereIn('tbl_user_roles.studio_id', $assignedStudioIds)
                  ->whereIn('tbl_roles.name', ['studio-hr-manager', 'studio-hr-staff', 'studio-finance-manager', 'studio-finance-staff', 'studio-photographer']);
            });
        
        // Apply filters
        if ($request->filled('studio_id')) {
            $query->whereHas('employeeSchedule', function ($q) use ($request) {
                $q->where('studio_id', $request->studio_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile_number', 'like', "%{$search}%");
            });
        }
        
        $employees = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));
        
        // Transform data
        $employees->getCollection()->transform(function ($employee) {
            $schedule = $employee->employeeSchedule->first();
            $studio = $schedule ? $schedule->studio : null;
            
            $userRole = $employee->roles->first();
            $roleDisplay = $userRole ? $userRole->display_name : $this->getRoleDisplay($employee->role);
            
            // Get photographer details if applicable
            $photographerDetails = null;
            if ($employee->role === 'studio-photographer') {
                $photographerDetails = StudioPhotographersModel::where('photographer_id', $employee->id)->first();
            }
            
            return [
                'id' => $employee->id,
                'uuid' => $employee->uuid,
                'full_name' => $employee->full_name,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'mobile_number' => $employee->mobile_number,
                'profile_photo' => $employee->profile_photo_url,
                'role' => $employee->role,
                'role_display' => $roleDisplay,
                'user_type' => $employee->user_type,
                'status' => $employee->status,
                'studio' => $studio ? [
                    'id' => $studio->id,
                    'name' => $studio->studio_name,
                    'logo' => $studio->studio_logo ? asset('storage/' . $studio->studio_logo) : null,
                ] : null,
                'schedule' => $schedule ? [
                    'days' => $schedule->formatted_operating_days,
                    'hours' => $schedule->formatted_hours,
                    'start_time' => $schedule->start_time->format('H:i'),
                    'end_time' => $schedule->end_time->format('H:i'),
                    'operating_days' => $schedule->operating_days,
                ] : null,
                'photographer_details' => $photographerDetails ? [
                    'position' => $photographerDetails->position,
                    'years_experience' => $photographerDetails->years_of_experience,
                    'specialization' => $photographerDetails->specialization,
                ] : null,
                'created_at' => $employee->created_at->format('M d, Y'),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Get employee details.
     */
    public function show($id)
    {
        $hrId = auth()->id();
        
        // Get studios assigned to this HR
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        $employee = UserModel::with(['roles', 'employeeSchedule' => function($q) use ($assignedStudioIds) {
                $q->whereIn('studio_id', $assignedStudioIds);
            }])
            ->whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->where('id', $id)
            ->whereExists(function ($q) use ($assignedStudioIds) {
                $q->select(DB::raw(1))
                  ->from('tbl_user_roles')
                  ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
                  ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                  ->whereIn('tbl_user_roles.studio_id', $assignedStudioIds)
                  ->whereIn('tbl_roles.name', ['studio-hr-manager', 'studio-hr-staff', 'studio-finance-manager', 'studio-finance-staff', 'studio-photographer']);
            })
            ->firstOrFail();
        
        $schedule = $employee->employeeSchedule->first();
        $studio = $schedule ? $schedule->studio : null;
        
        $userRole = $employee->roles->first();
        $roleDisplay = $userRole ? $userRole->display_name : $this->getRoleDisplay($employee->role);
        
        // Get photographer details if applicable
        $photographerDetails = null;
        if ($employee->role === 'studio-photographer') {
            $photographerDetails = StudioPhotographersModel::with(['specializationService.category'])
                ->where('photographer_id', $employee->id)
                ->first();
        }
        
        // Format schedule data
        $scheduleData = null;
        if ($schedule) {
            $scheduleData = [
                'id' => $schedule->id,
                'operating_days' => $schedule->operating_days,
                'days' => $schedule->formatted_operating_days ?? $this->formatOperatingDays($schedule->operating_days),
                'hours' => $schedule->formatted_hours ?? $this->formatHours($schedule->start_time, $schedule->end_time),
                'start_time' => $schedule->start_time instanceof \Carbon\Carbon ? $schedule->start_time->format('H:i') : date('H:i', strtotime($schedule->start_time)),
                'end_time' => $schedule->end_time instanceof \Carbon\Carbon ? $schedule->end_time->format('H:i') : date('H:i', strtotime($schedule->end_time)),
                'is_active' => $schedule->is_active,
            ];
        }
        
        $response = [
            'id' => $employee->id,
            'uuid' => $employee->uuid,
            'full_name' => $employee->full_name,
            'first_name' => $employee->first_name,
            'middle_name' => $employee->middle_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'mobile_number' => $employee->mobile_number,
            'profile_photo' => $employee->profile_photo ? asset('storage/profile-photos/' . $employee->profile_photo) : null,
            'role' => $employee->role,
            'role_display' => $roleDisplay,
            'user_type' => ucfirst($employee->user_type ?? ''),
            'status' => $employee->status,
            'created_at' => $employee->created_at ? $employee->created_at->format('M d, Y h:i A') : 'N/A',
            'studio' => $studio ? [
                'id' => $studio->id,
                'name' => $studio->studio_name,
                'logo' => $studio->studio_logo ? asset('storage/' . $studio->studio_logo) : null,
            ] : null,
            'schedule' => $scheduleData,
        ];
        
        // Add photographer details
        if ($photographerDetails) {
            $serviceName = 'Not specified';
            $categoryName = 'Not specified';
            
            if ($photographerDetails->specializationService) {
                $service = $photographerDetails->specializationService;
                $serviceName = $this->getServiceName($service);
                $categoryName = $service->category->category_name ?? 'Not specified';
            }
            
            $response['photographer_details'] = [
                'id' => $photographerDetails->id,
                'position' => $photographerDetails->position,
                'years_experience' => $photographerDetails->years_of_experience,
                'specialization_id' => $photographerDetails->specialization,
                'service_name' => $serviceName,
                'category_name' => $categoryName,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $response
        ]);
    }

    /**
     * Helper: Get assigned studio IDs for HR user using new RBAC.
     */
    private function getAssignedStudioIds($hrId)
    {
        // Get the HR user's roles
        $hrUser = UserModel::with('roles')->find($hrId);
        $hrRole = $hrUser ? $hrUser->roles->first() : null;
        
        if (!$hrRole) {
            return collect();
        }
        
        $studioIds = $hrUser->getAssignedStudioIds('studio-hr');

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::where('user_id', $hrId)->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::where('user_id', $hrId)->pluck('id');
        }
        
        return $studioIds;
    }

    /**
     * Update employee status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,suspended',
        ]);
        
        $hrId = auth()->id();
        
        $hrUser = UserModel::findOrFail($hrId);

        if (!$hrUser->hasPermission('edit_employee')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update employee status.'
            ], 403);
        }
        
        // Get studios assigned to this HR
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        $employee = UserModel::whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->where('id', $id)
            ->where('id', '!=', $hrId)
            ->whereExists(function ($q) use ($assignedStudioIds) {
                $q->select(DB::raw(1))
                  ->from('tbl_user_roles')
                  ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
                  ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                  ->whereIn('tbl_user_roles.studio_id', $assignedStudioIds)
                  ->whereIn('tbl_roles.name', ['studio-hr-manager', 'studio-hr-staff', 'studio-finance-manager', 'studio-finance-staff', 'studio-photographer']);
            })
            ->firstOrFail();
        
        $employee->update([
            'status' => $request->status
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Employee status updated successfully.'
        ]);
    }

    /**
     * Update employee schedule.
     */
    public function updateSchedule(Request $request, $id)
    {
        $request->validate([
            'operating_days' => 'required|array|min:1',
            'operating_days.*' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        
        $hrId = auth()->id();
        
        $hrUser = UserModel::findOrFail($hrId);

        if (!$hrUser->hasPermission('manage_schedules')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update employee schedules.'
            ], 403);
        }
        
        // Get studios assigned to this HR
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        $schedule = EmployeeScheduleModel::where('user_id', $id)
            ->whereIn('studio_id', $assignedStudioIds)
            ->firstOrFail();
        
        $schedule->update([
            'operating_days' => $request->operating_days,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Employee schedule updated successfully.'
        ]);
    }

    /**
     * Delete employee.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        
        try {
            $hrId = auth()->id();
            
            $hrUser = UserModel::findOrFail($hrId);

            if (!$hrUser->hasPermission('delete_employee')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete employees.'
                ], 403);
            }
            
            // Prevent self-deletion
            if ($id == $hrId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ], 403);
            }
            
            // Get studios assigned to this HR
            $assignedStudioIds = $this->getAssignedStudioIds($hrId);
            
            $employee = UserModel::whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
                ->where('id', $id)
                ->whereExists(function ($q) use ($assignedStudioIds) {
                    $q->select(DB::raw(1))
                      ->from('tbl_user_roles')
                      ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
                      ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                      ->whereIn('tbl_user_roles.studio_id', $assignedStudioIds)
                      ->whereIn('tbl_roles.name', ['studio-hr-manager', 'studio-hr-staff', 'studio-finance-manager', 'studio-finance-staff', 'studio-photographer']);
                })
                ->firstOrFail();
            
            // Delete user roles association
            DB::table('tbl_user_roles')->where('user_id', $employee->id)->delete();
            
            // Delete employee schedule
            EmployeeScheduleModel::where('user_id', $employee->id)->delete();
            
            // Delete photographer record if exists
            if ($employee->role === 'studio-photographer') {
                StudioPhotographersModel::where('photographer_id', $employee->id)->delete();
            }
            
            // Delete user
            $employee->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete employee (HR): ' . $e->getMessage(), [
                'exception' => $e,
                'employee_id' => $id
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete employee: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for photographer specialization.
     */
    public function getCategories()
    {
        try {
            $categories = CategoriesModel::where('status', 'active')
                ->orderBy('category_name')
                ->get(['id', 'category_name']);
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load categories: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load categories',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get services for a specific category.
     */
    public function getServicesByCategory(Request $request, $studioId, $categoryId)
    {
        $hrId = auth()->id();
        
        // Verify the studio is assigned to HR
        $assignedStudioIds = $this->getAssignedStudioIds($hrId);
        
        if (!$assignedStudioIds->contains($studioId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this studio.'
            ], 403);
        }
        
        $services = ServicesModel::where('studio_id', $studioId)
            ->where('category_id', $categoryId)
            ->get();
        
        $formattedServices = [];
        foreach ($services as $service) {
            $serviceNames = $this->getServiceName($service);
            $formattedServices[] = [
                'id' => $service->id,
                'name' => $serviceNames,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => $formattedServices
        ]);
    }

    /**
     * Helper: Format operating days.
     */
    private function formatOperatingDays($operatingDays)
    {
        if (empty($operatingDays)) {
            return 'Not set';
        }
        
        $days = $operatingDays;
        if (is_string($operatingDays)) {
            $days = json_decode($operatingDays, true) ?: [];
        }
        
        if (empty($days)) {
            return 'Not set';
        }
        
        $dayMap = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];
        
        $formatted = [];
        foreach ($days as $day) {
            $dayLower = strtolower($day);
            if (isset($dayMap[$dayLower])) {
                $formatted[] = $dayMap[$dayLower];
            } else {
                $formatted[] = ucfirst($day);
            }
        }
        
        return implode(', ', $formatted);
    }

    /**
     * Helper: Format hours.
     */
    private function formatHours($startTime, $endTime)
    {
        if (!$startTime || !$endTime) {
            return 'Not set';
        }
        
        try {
            $start = $startTime instanceof \Carbon\Carbon 
                ? $startTime->format('h:i A') 
                : date('h:i A', strtotime($startTime));
                
            $end = $endTime instanceof \Carbon\Carbon 
                ? $endTime->format('h:i A') 
                : date('h:i A', strtotime($endTime));
                
            return $start . ' - ' . $end;
        } catch (\Exception $e) {
            return 'Not set';
        }
    }

    /**
     * Helper: Handle profile photo upload.
     */
    private function handleProfilePhoto($request)
    {
        if ($request->hasFile('profile_photo')) {
            $file = $request->file('profile_photo');
            $filename = 'employee_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile-photos', $filename, 'public');
            return $filename;
        }
        
        return null;
    }

    /**
     * Helper: Create photographer record.
     */
    private function createPhotographerRecord($request, $hrId, $userId, $studio)
    {
        $primaryService = ServicesModel::where('studio_id', $request->studio_id)
            ->where('category_id', $request->specialization)
            ->first();
        
        if (!$primaryService) {
            throw new \Exception('No services found for the selected category.');
        }
        
        return StudioPhotographersModel::create([
            'studio_id' => $request->studio_id,
            'owner_id' => $hrId,
            'photographer_id' => $userId,
            'position' => $request->position,
            'specialization' => $primaryService->id,
            'years_of_experience' => $request->years_experience,
            'status' => $request->status,
        ]);
    }

    /**
     * Helper: Get role display name.
     */
    private function getRoleDisplay($role)
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Photographer',
        ];
        
        return $roles[$role] ?? ucfirst(str_replace('-', ' ', $role));
    }

    /**
     * Helper: Extract service name.
     */
    private function getServiceName($service)
    {
        if (!$service) {
            return 'Not specified';
        }

        if (is_array($service->service_name)) {
            return implode(', ', $service->service_name);
        } elseif (is_string($service->service_name)) {
            try {
                $decoded = json_decode($service->service_name, true);
                if (is_array($decoded)) {
                    return implode(', ', $decoded);
                }
                return $service->service_name;
            } catch (\Exception $e) {
                return $service->service_name;
            }
        }
        
        return 'Not specified';
    }

    /**
     * Helper: Send registration email.
     */
    private function sendRegistrationEmail(array $employeeData, string $temporaryPassword): bool
    {
        try {
            Mail::to($employeeData['email'])->send(
                new EmployeeRegistrationMail($employeeData, $temporaryPassword)
            );
            
            Log::info('Registration email sent to employee (HR): ' . $employeeData['email']);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send registration email (HR): ' . $e->getMessage(), [
                'employee_email' => $employeeData['email']
            ]);
            return false;
        }
    }
}
