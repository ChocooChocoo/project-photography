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
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('studio-finance.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- Payroll Approvals --}}
            @php
                $isPayrollApprovalActive = Route::is('studio-finance.payroll-approvals.*');
                $canViewPayrollApprovals = auth()->user()->hasPermission('view_payroll')
                    || auth()->user()->hasPermission('approve_payroll')
                    || auth()->user()->hasPermission('reject_payroll')
                    || auth()->user()->hasPermission('manage_payroll');
            @endphp

            @if($canViewPayrollApprovals)
                <li class="side-nav-item {{ $isPayrollApprovalActive ? 'active' : '' }}">
                    <a href="{{ route('studio-finance.payroll-approvals.index') }}" class="side-nav-link {{ $isPayrollApprovalActive ? 'active' : '' }}">
                        <span class="menu-icon"><i class="ti ti-receipt-2"></i></span>
                        <span class="menu-text" data-lang="payroll-approvals">Payroll Approvals</span>
                    </a>
                </li>
            @endif
        </ul>
    </div>
</div>
