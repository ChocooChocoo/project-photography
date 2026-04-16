<div class="sidenav-menu">
    {{-- Logo --}}
    <a href="index.html" class="logo">
        <span class="logo logo-light">
            <span class="logo-lg"><img src="{{ asset('assets/images/logo.png') }}" alt="logo"></span>
            <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo"></span>
        </span>

        <span class="logo logo-dark">
            <span class="logo-lg"><img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo"></span>
            <span class="logo-sm"><img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo"></span>
        </span>
    </a>

    {{-- Sidebar Hover Menu Toggle Button --}}
    <button class="button-on-hover">
        <i class="ti ti-menu-4 fs-22 align-middle"></i>
    </button>

    {{-- Full Sidebar Menu Close Button --}}
    <button class="button-close-offcanvas">
        <i class="ti ti-x align-middle"></i>
    </button>

    {{-- Sidebar --}}
    <div class="scrollbar" data-simplebar>
        <ul class="side-nav">
            <li class="side-nav-title mt-2" data-lang="apps-title">Human Resource Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('studio-hr.dashboard');
                $hrUser = auth()->user();
                $canViewDashboard = $hrUser?->hasPermission('studio-hr.dashboard.view') ?? false;
                $canManageLeaveRequests = $hrUser?->hasPermission('studio-hr.leave-requests.manage') ?? false;
                $canManageOvertimeRequests = $hrUser?->hasPermission('studio-hr.overtime-requests.manage') ?? false;
                $canViewEmployees = $hrUser?->hasPermission('studio-hr.employees.view') ?? false;
                $canCreateEmployees = $hrUser?->hasPermission('studio-hr.employee.create') ?? false;
                $canViewPayroll = $hrUser?->hasPermission('studio-hr.payroll.view') ?? false;
                $canCreatePayroll = $hrUser?->hasPermission('studio-hr.payroll.create') ?? false;
                $canGeneratePayroll = $hrUser?->hasPermission('studio-hr.generate-payroll.manage') ?? false;
                $canViewAttendance = $hrUser?->hasPermission('studio-hr.attendance.view') ?? false;
                $canManageProcurement = $hrUser?->hasPermission('studio-hr.procurement.manage') ?? false;
            @endphp
            
            @if($canViewDashboard)
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('studio-hr.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>
            @endif

            {{-- Request --}}
            @php
                $requestRoutes = Route::is('studio-hr.leave-requests.*')
                    || Route::is('studio-hr.employees-leave-requests.*')
                    || Route::is('studio-hr.overtime-requests.*')
                    || Route::is('studio-hr.employees-overtime-requests.*')
                    || Route::is('studio-hr.procurement.*');
            @endphp

            @if($canManageLeaveRequests || $canManageOvertimeRequests || $canManageProcurement)
            <li class="side-nav-item {{ $requestRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarHrRequest" aria-expanded="{{ $requestRoutes ? 'true' : 'false' }}" aria-controls="sidebarHrRequest" class="side-nav-link {{ $requestRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text" data-lang="request">Request</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $requestRoutes ? 'show' : '' }}" id="sidebarHrRequest">
                    <ul class="sub-menu">
                        @if($canManageLeaveRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.leave-requests.create') }}" class="side-nav-link {{ Route::is('studio-hr.leave-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-leave">Request Leave</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.leave-requests.index') }}" class="side-nav-link {{ Route::is('studio-hr.leave-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-leave">View Requested Leave</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employees-leave-requests.index') }}" class="side-nav-link {{ Route::is('studio-hr.employees-leave-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="employees-leave-requests">Employees Leave Requests</span>
                            </a>
                        </li>
                        @endif
                        @if($canManageOvertimeRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.overtime-requests.create') }}" class="side-nav-link {{ Route::is('studio-hr.overtime-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-overtime">Request Overtime</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.overtime-requests.index') }}" class="side-nav-link {{ Route::is('studio-hr.overtime-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-overtime">View Requested Overtime</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employees-overtime-requests.index') }}" class="side-nav-link {{ Route::is('studio-hr.employees-overtime-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="employees-overtime-requests">Employees Overtime Requests</span>
                            </a>
                        </li>
                        @endif
                        @if($canManageProcurement)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.procurement.create') }}" class="side-nav-link {{ Route::is('studio-hr.procurement.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-procurement">Request Procurement</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.procurement.index') }}" class="side-nav-link {{ Route::is('studio-hr.procurement.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-procurement">View Procurement</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- Manage Employee --}}
            @php
                $manageEmployeeRoutes = Route::is('studio-hr.employee.index');
            @endphp

            @if($canViewEmployees || $canCreateEmployees)
            <li class="side-nav-item {{ $manageEmployeeRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageEmployee" aria-expanded="{{ $manageEmployeeRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageEmployee" class="side-nav-link {{ $manageEmployeeRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-shield"></i></span>
                    <span class="menu-text" data-lang="manage-employee">Employee</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageEmployeeRoutes ? 'show' : '' }}" id="sidebarManageEmployee">
                    <ul class="sub-menu">
                        @if($canViewEmployees)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employee.index') }}" class="side-nav-link {{ $manageEmployeeRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="manage-employee">View Employee</span>
                            </a>
                        </li>
                        @endif
                        @if($canCreateEmployees)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employee.create') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="create-employee">Create Employee</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- Manage Payroll --}}
            @php
                $managePayrollRoutes = Route::is('studio-hr.payroll-settings.index');
                $createPayrollRoute = Route::is('studio-hr.payroll-settings.create');
            @endphp

            @if($canViewPayroll || $canCreatePayroll)
                <li class="side-nav-item {{ $managePayrollRoutes || $createPayrollRoute ? 'active' : '' }}">
                    <a data-bs-toggle="collapse" href="#sidebarManagePayroll" aria-expanded="{{ $managePayrollRoutes || $createPayrollRoute ? 'true' : 'false' }}" aria-controls="sidebarManagePayroll" class="side-nav-link {{ $managePayrollRoutes || $createPayrollRoute ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-cash-banknote-edit"></i></span>
                        <span class="menu-text" data-lang="manage-payroll">Payroll</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <div class="collapse {{ $managePayrollRoutes || $createPayrollRoute ? 'show' : '' }}" id="sidebarManagePayroll">
                        <ul class="sub-menu">
                            @if($canViewPayroll)
                                <li class="side-nav-item">
                                    <a href="{{ route('studio-hr.payroll-settings.index') }}" class="side-nav-link {{ $managePayrollRoutes ? 'active' : '' }}">
                                        <span class="menu-text" data-lang="manage-payroll">View Payroll</span>
                                    </a>
                                </li>
                            @endif
                            @if($canCreatePayroll)
                                <li class="side-nav-item">
                                    <a href="{{ route('studio-hr.payroll-settings.create') }}" class="side-nav-link {{ $createPayrollRoute ? 'active' : '' }}">
                                        <span class="menu-text" data-lang="create-payroll">Create Payroll</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </li>
            @endif

            {{-- Generate Payroll --}}
            @php
                $generatePayrollRoutes = Route::is('studio-hr.generate-payroll.index');
            @endphp

            @if($canGeneratePayroll)
            <li class="side-nav-item {{ $generatePayrollRoutes ? 'active' : '' }}">
                <a href="{{ route('studio-hr.generate-payroll.index') }}" class="side-nav-link {{ $generatePayrollRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-cash-register"></i></span>
                    <span class="menu-text" data-lang="generate-payroll">Generate Payroll</span>
                </a>
            </li>
            @endif

            {{-- Manage Attendance --}}
            @php
                $manageAttendanceRoutes = Route::is('studio-hr.attendance.index');
            @endphp

            @if($canViewAttendance)
            <li class="side-nav-item {{ $manageAttendanceRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageAttendance" aria-expanded="{{ $manageAttendanceRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageAttendance" class="side-nav-link {{ $manageAttendanceRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-calendar"></i></span>
                    <span class="menu-text" data-lang="manage-attendance">Attendance</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageAttendanceRoutes ? 'show' : '' }}" id="sidebarManageAttendance">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.attendance.index') }}" class="side-nav-link {{ $manageAttendanceRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="manage-attendance">View Attendance</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endif
        </ul>
    </div>
</div>
