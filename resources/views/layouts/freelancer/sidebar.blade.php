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
            <li class="side-nav-title mt-2" data-lang="apps-title">Freelancer Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('owner.dashboard');
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('owner.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- Manage Profile --}}
            @php
                $manageProfileRoutes = Route::is('freelancer.profile.index');
                $setupProfileRoutes  = Route::is('freelancer.profile.setup');
            @endphp

            <li class="side-nav-item {{ $manageProfileRoutes || $setupProfileRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageProfile" aria-expanded="{{ $manageProfileRoutes || $setupProfileRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageProfile" class="side-nav-link {{ $manageProfileRoutes || $setupProfileRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="file-user"></i></span>
                    <span class="menu-text" data-lang="manage-profile">Profile</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageProfileRoutes || $setupProfileRoutes ? 'show' : '' }}" id="sidebarManageProfile">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.profile.index') }}" class="side-nav-link {{ $manageProfileRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-profile">View Profile</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.profile.setup') }}" class="side-nav-link {{ $setupProfileRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="setup-profile">Setup Profile</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Bookings --}}
            @php
                $manageBookingsRoutes = Route::is('freelancer.booking.index');
                $bookingHistoryRoutes = Route::is('freelancer.booking.history');
            @endphp
            
            <li class="side-nav-item {{ $manageBookingsRoutes || $bookingHistoryRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageBookings" aria-expanded="{{ $manageBookingsRoutes || $bookingHistoryRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageBookings" class="side-nav-link {{ $manageBookingsRoutes || $bookingHistoryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="notebook-pen"></i></span>
                    <span class="menu-text" data-lang="manage-bookings">Bookings</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageBookingsRoutes || $bookingHistoryRoutes ? 'show' : '' }}" id="sidebarManageBookings">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.booking.index') }}" class="side-nav-link {{ $manageBookingsRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-bookings">View Bookings</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.booking.history') }}" class="side-nav-link {{ $bookingHistoryRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="booking-history">Booking History</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Online Gallery --}}
            @php
                $manageOnlineGalleryRoutes = Route::get('/view/online-gallery', [\App\Http\Controllers\Freelancer\OnlineGalleryController::class, 'index'])->name('freelancer.online-gallery.index');
            @endphp
            
            <li class="side-nav-item {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                <a href="{{ route('freelancer.online-gallery.index') }}" class="side-nav-link {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="image"></i></span>
                    <span class="menu-text" data-lang="online-gallery">Online Gallery</span>
                </a>
            </li>

            @php
                $portfolioGalleryRoute = Route::is('freelancer.online-gallery.portfolio');
            @endphp
            <li class="side-nav-item {{ $portfolioGalleryRoute ? 'active' : '' }}">
                <a href="{{ route('freelancer.online-gallery.portfolio') }}" class="side-nav-link {{ $portfolioGalleryRoute ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="image-plus"></i></span>
                    <span class="menu-text" data-lang="portfolio-gallery">Portfolio Gallery</span>
                </a>
            </li>

            {{-- Member Invitations --}}
            @php
                $memberInvitationRoutes = Route::is('freelancer.invitation.index');
            @endphp
            
            <li class="side-nav-item {{ $memberInvitationRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarMemberInvitation" aria-expanded="{{ $memberInvitationRoutes ? 'true' : 'false' }}" aria-controls="sidebarMemberInvitation" class="side-nav-link {{ $memberInvitationRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="user-plus"></i></span>
                    <span class="menu-text" data-lang="member-invitation">Member Invitations</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $memberInvitationRoutes ? 'show' : '' }}" id="sidebarMemberInvitation">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.invitation.index') }}" class="side-nav-link {{ $memberInvitationRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-invitation">View Invitations</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Packages --}}
            @php
                $viewPackagesRoutes = Route::is('freelancer.packages.index');
                $createPackagesRoutes = Route::is('freelancer.packages.create');
                $listPackagesRoutes = Route::is('freelancer.packages.list');
            @endphp
            
            <li class="side-nav-item {{ $viewPackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManagePackages" aria-expanded="{{ $viewPackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'true' : 'false' }}" aria-controls="sidebarManagePackages" class="side-nav-link {{ $viewPackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="package-check"></i></span>
                    <span class="menu-text" data-lang="manage-packages">Packages</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $viewPackagesRoutes || $createPackagesRoutes || $listPackagesRoutes ? 'show' : '' }}" id="sidebarManagePackages">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.packages.index') }}" class="side-nav-link {{ $viewPackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="packages">View Packages</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.packages.create') }}" class="side-nav-link {{ $createPackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-package">Create Package</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.packages.list') }}" class="side-nav-link {{ $listPackagesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="list-packages">List Packages</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Services --}}
            @php
                $manageServicesRoutes = Route::is('freelancer.services.index');
                $createServicesRoutes = Route::is('freelancer.services.create');
            @endphp
            
            <li class="side-nav-item {{ $manageServicesRoutes || $createServicesRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageServices" aria-expanded="{{ $manageServicesRoutes || $createServicesRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageServices" class="side-nav-link {{ $manageServicesRoutes || $createServicesRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i data-lucide="list-tree"></i></span>
                    <span class="menu-text" data-lang="manage-category-services">Services</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageServicesRoutes || $createServicesRoutes ? 'show' : '' }}" id="sidebarManageServices">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.services.index') }}" class="side-nav-link {{ $manageServicesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="services">View Services</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('freelancer.services.create') }}" class="side-nav-link {{ $createServicesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-service">Create Service</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
