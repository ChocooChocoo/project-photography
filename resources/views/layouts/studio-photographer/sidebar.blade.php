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
            <li class="side-nav-title mt-2" data-lang="apps-title">Studio Photographer Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('studio-photographer.dashboard');
                $photographerUser = auth()->user();
                $canViewDashboard = $photographerUser?->hasPermission('studio-photographer.dashboard.view') ?? false;
                $canManageLeaveRequests = $photographerUser?->hasPermission('studio-photographer.leave-requests.manage') ?? false;
                $canManageOvertimeRequests = $photographerUser?->hasPermission('studio-photographer.overtime-requests.manage') ?? false;
                $canViewAttendance = $photographerUser?->hasPermission('studio-photographer.attendance.view') ?? false;
                $canViewStudio = $photographerUser?->hasPermission('studio-photographer.studio.view') ?? false;
                $canViewBookings = $photographerUser?->hasPermission('studio-photographer.bookings.view') ?? false;
                $canViewOnlineGallery = $photographerUser?->hasPermission('studio-photographer.online_gallery.view') ?? false;
                $canManageProcurement = $photographerUser?->hasPermission('studio-photographer.procurement.manage') ?? false;
            @endphp
            
            @if($canViewDashboard)
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('studio-photographer.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>
            @endif

            {{-- Request --}}
            @php
                $requestRoutes = Route::is('studio-photographer.leave-requests.*')
                    || Route::is('studio-photographer.overtime-requests.*')
                    || Route::is('studio-photographer.procurement.*');
            @endphp

            @if($canManageLeaveRequests || $canManageOvertimeRequests || $canManageProcurement)
            <li class="side-nav-item {{ $requestRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarPhotographerRequest" aria-expanded="{{ $requestRoutes ? 'true' : 'false' }}" aria-controls="sidebarPhotographerRequest" class="side-nav-link {{ $requestRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-file-text"></i></span>
                    <span class="menu-text" data-lang="request">Request</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $requestRoutes ? 'show' : '' }}" id="sidebarPhotographerRequest">
                    <ul class="sub-menu">
                        @if($canManageLeaveRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.leave-requests.create') }}" class="side-nav-link {{ Route::is('studio-photographer.leave-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-leave">Request Leave</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.leave-requests.index') }}" class="side-nav-link {{ Route::is('studio-photographer.leave-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-leave">View Requested Leave</span>
                            </a>
                        </li>
                        @endif
                        @if($canManageOvertimeRequests)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.overtime-requests.create') }}" class="side-nav-link {{ Route::is('studio-photographer.overtime-requests.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-overtime">Request Overtime</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.overtime-requests.index') }}" class="side-nav-link {{ Route::is('studio-photographer.overtime-requests.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-requested-overtime">View Requested Overtime</span>
                            </a>
                        </li>
                        @endif
                        @if($canManageProcurement)
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.procurement.create') }}" class="side-nav-link {{ Route::is('studio-photographer.procurement.create') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="request-procurement">Request Procurement</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.procurement.index') }}" class="side-nav-link {{ Route::is('studio-photographer.procurement.index') ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-procurement">View Procurement</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </li>
            @endif

            {{-- Attendance --}}
            @php
                $attendanceRoutes = Route::is('studio-photographer.attendance.index');
            @endphp

            @if($canViewAttendance)
            <li class="side-nav-item {{ $attendanceRoutes ? 'active' : '' }}">
                <a href="{{ route('studio-photographer.attendance.index') }}" class="side-nav-link {{ $attendanceRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-calendar-time"></i></span>
                    <span class="menu-text" data-lang="attendance">Attendance</span>
                </a>
            </li>
            @endif

            {{-- Assigned Studio --}}
            @php
                $assignedStudioRoutes   = Route::is('studio-photographer.studio.index');
            @endphp
            
            @if($canViewStudio)
            <li class="side-nav-item {{ $assignedStudioRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageAssignedStudio" aria-expanded="{{ $assignedStudioRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageAssignedStudio" class="side-nav-link {{ $assignedStudioRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-aperture"></i></span>
                    <span class="menu-text" data-lang="assigned-studio">Studio</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $assignedStudioRoutes ? 'show' : '' }}" id="sidebarManageAssignedStudio">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('studio-photographer.studio.index') }}" class="side-nav-link {{ $assignedStudioRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="assigned-studio">Assigned Studios</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endif

            {{-- Assigned Booking --}}
            @php
                $assignedBookingRoutes = Route::is('assigned.bookings');
            @endphp

            @if($canViewBookings)
            <li class="side-nav-item {{ $assignedBookingRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageAssignedBooking" aria-expanded="{{ $assignedBookingRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageAssignedBooking" class="side-nav-link {{ $assignedBookingRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-calendar-check"></i></span>
                    <span class="menu-text" data-lang="assigned-booking">Booking</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $assignedBookingRoutes ? 'show' : '' }}" id="sidebarManageAssignedBooking">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('assigned.bookings') }}" class="side-nav-link {{ $assignedBookingRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="assigned-booking">Assigned Booking</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endif

            {{-- Manage Online Gallery --}}
            @php
                $manageOnlineGalleryRoutes = Route::is('studio-photographer.online-gallery.index');
            @endphp

            @if($canViewOnlineGallery)
            <li class="side-nav-item {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                <a href="{{ route('studio-photographer.online-gallery.index') }}" class="side-nav-link {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-photo"></i></span>
                    <span class="menu-text" data-lang="online-gallery">Online Gallery</span>
                </a>
            </li>
            @endif
        </ul>
    </div>
</div>
