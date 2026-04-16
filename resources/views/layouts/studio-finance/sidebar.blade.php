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
            <li class="side-nav-title mt-2" data-lang="apps-title">Finance Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('studio-finance.dashboard');
                $financeUser = auth()->user();
                $canViewDashboard = $financeUser?->hasPermission('studio-finance.dashboard.view') ?? false;
                $canManageLeaveRequests = $financeUser?->hasPermission('studio-finance.leave-requests.manage') ?? false;
                $canManageOvertimeRequests = $financeUser?->hasPermission('studio-finance.overtime-requests.manage') ?? false;
                $canViewAttendance = $financeUser?->hasPermission('studio-finance.attendance.view') ?? false;
                $canViewPayrollApprovals = ($financeUser?->hasPermission('studio-finance.payroll.view') ?? false)
                    || ($financeUser?->hasPermission('studio-finance.payroll.approve') ?? false)
                    || ($financeUser?->hasPermission('studio-finance.payroll.reject') ?? false)
                    || ($financeUser?->hasPermission('studio-finance.payroll.manage') ?? false);
                $canViewProcurement = $financeUser?->hasPermission('studio-finance.procurement.view') ?? false;
                $canReviewProcurement = $financeUser?->hasPermission('studio-finance.procurement.review') ?? false;
                $canOrderProcurement = $financeUser?->hasPermission('studio-finance.procurement.order') ?? false;
                $canPayProcurement = $financeUser?->hasPermission('studio-finance.procurement.payment') ?? false;
            @endphp
            
            @if($canViewDashboard)
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('studio-finance.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>
            @endif

            {{-- Request --}}
            @php
                $requestRoutes = Route::is('studio-finance.leave-requests.*') || Route::is('studio-finance.overtime-requests.*');
            @endphp

            @if($canManageLeaveRequests || $canManageOvertimeRequests)
            <li class="side-nav-item {{ $requestRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarFinanceRequest" aria-expanded="{{ $requestRoutes ? 'true' : 'false' }}" aria-controls="sidebarFinanceRequest" class="side-nav-link {{ $requestRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text" data-lang="request">Request</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $requestRoutes ? 'show' : '' }}" id="sidebarFinanceRequest">
                    <ul class="sub-menu">
                        @if($canManageLeaveRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-finance.leave-requests.create') }}" class="side-nav-link {{ Route::is('studio-finance.leave-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-leave">Request Leave</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-finance.leave-requests.index') }}" class="side-nav-link {{ Route::is('studio-finance.leave-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-leave">View Requested Leave</span>
                            </a>
                        </li>
                        @endif
                        @if($canManageOvertimeRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-finance.overtime-requests.create') }}" class="side-nav-link {{ Route::is('studio-finance.overtime-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-overtime">Request Overtime</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-finance.overtime-requests.index') }}" class="side-nav-link {{ Route::is('studio-finance.overtime-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-overtime">View Requested Overtime</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- Attendance --}}
            @php
                $attendanceRoutes = Route::is('studio-finance.attendance.index');
            @endphp

            @if($canViewAttendance)
            <li class="side-nav-item {{ $attendanceRoutes ? 'active' : '' }}">
                <a href="{{ route('studio-finance.attendance.index') }}" class="side-nav-link {{ $attendanceRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-calendar-time"></i></span>
                    <span class="menu-text" data-lang="attendance">Attendance</span>
                </a>
            </li>
            @endif

            {{-- Payroll Approvals --}}
            @php
                $isPayrollApprovalActive = Route::is('studio-finance.payroll-approvals.*');
            @endphp

            @if($canViewPayrollApprovals)
                <li class="side-nav-item {{ $isPayrollApprovalActive ? 'active' : '' }}">
                    <a href="{{ route('studio-finance.payroll-approvals.index') }}" class="side-nav-link {{ $isPayrollApprovalActive ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-receipt-2"></i></span>
                        <span class="menu-text" data-lang="payroll-approvals">Payroll Approvals</span>
                    </a>
                </li>
            @endif

            {{-- Procurement --}}
            @php
                $isProcurementActive = Route::is('studio-finance.procurement.*');
            @endphp

            @if($canViewProcurement || $canReviewProcurement || $canOrderProcurement || $canPayProcurement)
                <li class="side-nav-item {{ $isProcurementActive ? 'active' : '' }}">
                    <a href="{{ route('studio-finance.procurement.index') }}" class="side-nav-link {{ $isProcurementActive ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-package-import"></i></span>
                        <span class="menu-text" data-lang="procurement">Procurement</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
