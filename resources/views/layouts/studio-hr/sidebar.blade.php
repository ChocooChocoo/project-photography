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

            <li class="side-nav-item {{ $manageEmployeeRoutes || $manageEmployeePayrollRoutes || $createPayrollSettingsRoute ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageEmployee" aria-expanded="{{ $manageEmployeeRoutes || $manageEmployeePayrollRoutes || $createPayrollSettingsRoute ? 'true' : 'false' }}" aria-controls="sidebarManageEmployee" class="side-nav-link {{ $manageEmployeeRoutes || $manageEmployeePayrollRoutes || $createPayrollSettingsRoute ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-shield"></i></span>
                    <span class="menu-text" data-lang="manage-employee">Employee</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageEmployeeRoutes || $manageEmployeePayrollRoutes || $createPayrollSettingsRoute ? 'show' : '' }}" id="sidebarManageEmployee">
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
        </ul>
    </div>
</div>
