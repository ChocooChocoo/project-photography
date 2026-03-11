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
            <li class="side-nav-title mt-2" data-lang="apps-title">Client Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('client.dashboard');
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('client.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- My Bookings --}}
            @php
                $myBookingsRoutes = Route::is('client.my-bookings.index');
                $bookingHistoryRoutes = Route::is('client.my-bookings.history');
            @endphp
            
            <li class="side-nav-item {{ $myBookingsRoutes || $bookingHistoryRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarMyBookings" aria-expanded="{{ $myBookingsRoutes || $bookingHistoryRoutes ? 'true' : 'false' }}" aria-controls="sidebarMyBookings" class="side-nav-link {{ $myBookingsRoutes || $bookingHistoryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-notebook"></i></span>
                    <span class="menu-text" data-lang="manage-bookings">My Bookings</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $myBookingsRoutes || $bookingHistoryRoutes ? 'show' : '' }}" id="sidebarMyBookings">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('client.my-bookings.index') }}" class="side-nav-link {{ $myBookingsRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="view-bookings">View Bookings</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('client.my-bookings.history') }}" class="side-nav-link {{ $bookingHistoryRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="booking-history">Booking History</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Online Gallery --}}
            @php
                $manageOnlineGalleryRoutes = Route::is('client.online-gallery.index');
            @endphp
            
            <li class="side-nav-item {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                <a href="{{ route('client.online-gallery.index') }}" class="side-nav-link {{ $manageOnlineGalleryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-photo"></i></span>
                    <span class="menu-text" data-lang="online-gallery">Online Gallery</span>
                </a>
            </li>

            {{-- Budget --}}
            @php
                $budgetRoutes = Route::is('client.budget.index');
            @endphp
            
            <li class="side-nav-item {{ $budgetRoutes ? 'active' : '' }}">
                <a href="{{ route('client.budget.index') }}" class="side-nav-link {{ $budgetRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-wallet"></i></span>
                    <span class="menu-text" data-lang="budget">Budget</span>
                </a>
            </li>
        </ul>
    </div>
</div>
