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
            <li class="side-nav-title mt-2" data-lang="apps-title">Studio Owner Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('owner.dashboard');
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('owner.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- Manage Studios --}}
            @php
                $manageStudiosRoutes   = Route::is('owner.studio.index');
                $pendingStudiosRoutes  = Route::is('owner.studio.create');
            @endphp
            
            <li class="side-nav-item {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageStudios" aria-expanded="{{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageStudios" class="side-nav-link {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-aperture"></i></span>
                    <span class="menu-text" data-lang="manage-studios">Studios</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'show' : '' }}" id="sidebarManageStudios">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.studio.index') }}" class="side-nav-link {{ $manageStudiosRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="studios">View Studios</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.studio.create') }}" class="side-nav-link {{ $pendingStudiosRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-studio">Create Studio</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Bookings --}}
            @php
                $manageBookingsRoutes   = Route::is('owner.booking.index');
                $pendingBookingsRoutes  = Route::is('owner.booking.create');
            @endphp
            
            <li class="side-nav-item {{ $manageBookingsRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageBookings" aria-expanded="{{ $manageBookingsRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageBookings" class="side-nav-link {{ $manageBookingsRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-notebook"></i></span>
                    <span class="menu-text" data-lang="manage-bookings">Bookings</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageBookingsRoutes ? 'show' : '' }}" id="sidebarManageBookings">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.booking.index') }}" class="side-nav-link {{ $manageBookingsRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="bookings">View Bookings</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.booking.history') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="booking-history">Booking History</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Online Gallery --}}
            @php
                $manageOnlineGalleryRoutes = Route::is('owner.online-gallery.index');
            @endphp
            
            <li class="side-nav-item {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                <a href="{{ route('owner.online-gallery.index') }}" class="side-nav-link {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-photo"></i></span>
                    <span class="menu-text" data-lang="online-gallery">Online Gallery</span>
                </a>
            </li>

            {{-- Manage Schedules --}}
            @php
                $manageSchedulesRoutes  = Route::is('owner.studio-schedule.index');
                $setupSchedulesRoutes   = Route::is('owner.setup-studio-schedules');
            @endphp
            
            <li class="side-nav-item {{ $manageSchedulesRoutes || $setupSchedulesRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageSchedules" aria-expanded="{{ $manageSchedulesRoutes || $setupSchedulesRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageSchedules" class="side-nav-link {{ $manageSchedulesRoutes || $setupSchedulesRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-calendar-check"></i></span>
                    <span class="menu-text" data-lang="manage-schedules">Schedules</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageSchedulesRoutes || $setupSchedulesRoutes ? 'show' : '' }}" id="sidebarManageSchedules">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.studio-schedule.index') }}" class="side-nav-link {{ $manageSchedulesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="schedules">View Schedules</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.setup-studio-schedules') }}" class="side-nav-link {{ $setupSchedulesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="setup-schedule">Setup Studio Schedule</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Studio Members --}}
            @php
                $manageMembersRoutes   = Route::is('owner.members.index');
            @endphp
            
            <li class="side-nav-item {{ $manageMembersRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageMembers" aria-expanded="{{ $manageMembersRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageMembers" class="side-nav-link {{ $manageMembersRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-star"></i></span>
                    <span class="menu-text" data-lang="manage-members">Members</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageMembersRoutes ? 'show' : '' }}" id="sidebarManageMembers">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.members.index') }}" class="side-nav-link {{ $manageMembersRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="members">View Members</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.members.invite') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="invite-members">Invite New Members</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.members.apply') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="apply-members">Members Application</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Studio Photographers --}}
            @php
                $manageStudioPhotographersRoutes = Route::is('owner.studio-photographers.index');
            @endphp

            <li class="side-nav-item {{ $manageStudioPhotographersRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageStudioPhotographers" aria-expanded="{{ $manageStudioPhotographersRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageStudioPhotographers" class="side-nav-link {{ $manageStudioPhotographersRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-plus"></i></span>
                    <span class="menu-text" data-lang="manage-studio-photographers">Photographers</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageStudioPhotographersRoutes ? 'show' : '' }}" id="sidebarManageStudioPhotographers">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.studio-photographers.index') }}" class="side-nav-link {{ $manageStudioPhotographersRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="studio-photographers">Studio Photographers</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.studio-photographers.create') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="add-photographer">Add Photographer</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Employee --}}
            @php
                $manageEmployeeRoutes = Route::is('owner.employee.index');
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
                            <a href="{{ route('owner.employee.index') }}" class="side-nav-link {{ $manageEmployeeRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="manage-employee">View Employee</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.employee.create') }}" class="side-nav-link">
                                <span class="menu-text" data-lang="create-employee">Create Employee</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Packages --}}
            @php
                $managePackagesRoutes = Route::is('owner.packages.index');
                $createPackagesRoutes = Route::is('owner.packages.create');
                $listPackagesRoutes   = Route::is('owner.packages.list');
            @endphp

            <li class="side-nav-item {{ $managePackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManagePackages" aria-expanded="{{ $managePackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'true' : 'false' }}" aria-controls="sidebarManagePackages" class="side-nav-link {{ $managePackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-package"></i></span>
                    <span class="menu-text" data-lang="manage-packages">Packages</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $managePackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'show' : '' }}" id="sidebarManagePackages">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.packages.index') }}" class="side-nav-link {{ $managePackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="packages">View Packages</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.packages.create') }}" class="side-nav-link {{ $createPackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-package">Create Package</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.packages.list') }}" class="side-nav-link {{ $listPackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="list-packages">List Packages</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Category Services --}}
            @php
                $manageCategoryServicesRoutes   = Route::is('owner.services.index');
                $createCategoryServicesRoutes  = Route::is('owner.services.create');
            @endphp
            
            <li class="side-nav-item {{ $manageCategoryServicesRoutes || $createCategoryServicesRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageCategoryServices" aria-expanded="{{ $manageCategoryServicesRoutes || $createCategoryServicesRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageCategoryServices" class="side-nav-link {{ $manageCategoryServicesRoutes || $createCategoryServicesRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-list-tree"></i></span>
                    <span class="menu-text" data-lang="manage-category-services">Services</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageCategoryServicesRoutes || $createCategoryServicesRoutes ? 'show' : '' }}" id="sidebarManageCategoryServices">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.services.index') }}" class="side-nav-link {{ $manageCategoryServicesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="category-services">View Services</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.services.create') }}" class="side-nav-link {{ $createCategoryServicesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-category-service">Create Service</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Subscription --}}
            @php
                $manageSubscriptionRoutes = Route::is('owner.subscription.index');
                $statusSubscriptionRoutes = Route::is('owner.subscription.status');
            @endphp
            
            <li class="side-nav-item {{ $manageSubscriptionRoutes || $statusSubscriptionRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageSubscription" aria-expanded="{{ $manageSubscriptionRoutes || $statusSubscriptionRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageSubscription" class="side-nav-link {{ $manageSubscriptionRoutes || $statusSubscriptionRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-carambola"></i></span>
                    <span class="menu-text" data-lang="manage-subscription">Subscription</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageSubscriptionRoutes || $statusSubscriptionRoutes ? 'show' : '' }}" id="sidebarManageSubscription">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('owner.subscription.index') }}" class="side-nav-link {{ $manageSubscriptionRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="subscription-plans">View Subscription</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('owner.subscription.status') }}" class="side-nav-link {{ $statusSubscriptionRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="subscription-status">Subscription Status</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
