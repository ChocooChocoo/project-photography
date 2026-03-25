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
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('studio-hr.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- Manage Employee --}}
            @php
                $manageEmployeeRoutes = Route::is('studio-hr.employee.index');
            @endphp

            <li class="side-nav-item {{ $manageEmployeeRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageEmployee" aria-expanded="{{ $manageEmployeeRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageEmployee" class="side-nav-link {{ $manageEmployeeRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-shield"></i></span>
                    <span class="menu-text" data-lang="manage-employee">Employee</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageEmployeeRoutes ? 'show' : '' }}" id="sidebarManageEmployee">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employee.index') }}" class="side-nav-link {{ $manageEmployeeRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="manage-employee">View Employee</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-hr.employee.create') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="create-employee">Create Employee</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Payroll --}}
            @php
                $managePayrollRoutes = Route::is('studio-hr.payroll-settings.index');
                $createPayrollRoute = Route::is('studio-hr.payroll-settings.create');
                $canViewPayroll = auth()->user()->hasPermission('view_payroll') || auth()->user()->hasPermission('manage_payroll');
                $canCreatePayroll = auth()->user()->hasPermission('create_payroll') || auth()->user()->hasPermission('manage_payroll');
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

            {{-- Manage Attendance --}}
            @php
                $manageAttendanceRoutes = Route::is('studio-hr.attendance.index');
            @endphp

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
        </ul>
    </div>
</div>
