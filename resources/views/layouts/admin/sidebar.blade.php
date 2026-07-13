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
            <li class="side-nav-title mt-2" data-lang="apps-title">Admin Panel</li>

            {{-- Dashboard --}}
            @php
                $isDashboardActive = Route::is('admin.dashboard');
            @endphp
            
            <li class="side-nav-item {{ $isDashboardActive ? 'active' : '' }}">
                <a href="{{ route('admin.dashboard') }}" class="side-nav-link {{ $isDashboardActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-dashboard"></i></span>
                    <span class="menu-text" data-lang="dashboard">Dashboard</span>
                </a>
            </li>

            {{-- Review Moderation --}}
            @php
                $isReviewModerationActive = Route::is('admin.reviews.index');
            @endphp
            <li class="side-nav-item {{ $isReviewModerationActive ? 'active' : '' }}">
                <a href="{{ route('admin.reviews.index') }}" class="side-nav-link {{ $isReviewModerationActive ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-star"></i></span>
                    <span class="menu-text" data-lang="review-moderation">Review Moderation</span>
                </a>
            </li>

            {{-- Manage Users --}}
            @php
                $manageUsersRoutes  = Route::is('admin.user.index');
            @endphp
            
            <li class="side-nav-item {{ $manageUsersRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageUsers" aria-expanded="{{ $manageUsersRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageUsers" class="side-nav-link {{ $manageUsersRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-users"></i></span>
                    <span class="menu-text" data-lang="manage-users">Users</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageUsersRoutes ? 'show' : '' }}" id="sidebarManageUsers">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.user.index') }}" class="side-nav-link {{ $manageUsersRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="users">View Users</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Studios --}}
            @php
                $manageStudiosRoutes   = Route::is('admin.studio.index');
                $pendingStudiosRoutes  = Route::is('admin.studio.pending');
            @endphp
            
            <li class="side-nav-item {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageStudios" aria-expanded="{{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageStudios" class="side-nav-link {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-device-cctv"></i></span>
                    <span class="menu-text" data-lang="manage-studios">Studios</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageStudiosRoutes || $pendingStudiosRoutes ? 'show' : '' }}" id="sidebarManageStudios">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.studio.index') }}" class="side-nav-link {{ $manageStudiosRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="studios">View Studios</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('admin.studio.pending') }}" class="side-nav-link {{ $pendingStudiosRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="pending-studio-registration">Pending Studios</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Freelancer --}}
            @php
                $viewFreelancerRoutes    = Route::is('admin.freelancer.index');
            @endphp
            
            <li class="side-nav-item {{ $viewFreelancerRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageFreelancer" aria-expanded="{{ $viewFreelancerRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageFreelancer" class="side-nav-link {{ $viewFreelancerRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-user-circle"></i></span>
                    <span class="menu-text" data-lang="manage-freelancer">Freelancer</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $viewFreelancerRoutes ? 'show' : '' }}" id="sidebarManageFreelancer">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.freelancer.index') }}" class="side-nav-link {{ $viewFreelancerRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="freelancer">View Freelancer</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Categories --}}
            @php
                $manageCategoriesRoutes  = Route::is('admin.categories.index');
                $createCategoryRoutes    = Route::is('admin.categories.create');
            @endphp
            
            <li class="side-nav-item {{ $manageCategoriesRoutes || $createCategoryRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageCategories" aria-expanded="{{ $manageCategoriesRoutes || $createCategoryRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageCategories" class="side-nav-link {{ $manageCategoriesRoutes || $createCategoryRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-layout-list"></i></span>
                    <span class="menu-text" data-lang="manage-categories">Categories</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageCategoriesRoutes || $createCategoryRoutes ? 'show' : '' }}" id="sidebarManageCategories">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.categories.index') }}" class="side-nav-link {{ $manageCategoriesRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="categories">View Categories</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('admin.categories.create') }}" class="side-nav-link {{ $createCategoryRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-category">Create Category</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            {{-- Manage Locations --}}
            @php
                $viewLocationRoutes    = Route::is('admin.location.index');
                $createLocationRoutes  = Route::is('admin.location.create');
            @endphp
            
            <li class="side-nav-item {{ $viewLocationRoutes || $createLocationRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageLocations" aria-expanded="{{ $viewLocationRoutes || $createLocationRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageLocations" class="side-nav-link {{ $viewLocationRoutes || $createLocationRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-map-pin"></i></span>
                    <span class="menu-text" data-lang="manage-locations">Locations</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $viewLocationRoutes || $createLocationRoutes ? 'show' : '' }}" id="sidebarManageLocations">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.location.index') }}" class="side-nav-link {{ $viewLocationRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="locations">View Locations</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('admin.location.create') }}" class="side-nav-link {{ $createLocationRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-location">Create Location</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            
            {{-- Manage Subscription --}}
            @php
                $manageSubscriptionRoutes = Route::is('admin.subscription.index');
                $createSubscriptionRoutes = Route::is('admin.subscription.create');
            @endphp
            
            <li class="side-nav-item {{ $manageSubscriptionRoutes || $createSubscriptionRoutes ? 'active' : '' }}">
                <a data-bs-toggle="collapse" href="#sidebarManageSubscription" aria-expanded="{{ $manageSubscriptionRoutes || $createSubscriptionRoutes ? 'true' : 'false' }}" aria-controls="sidebarManageSubscription" class="side-nav-link {{ $manageSubscriptionRoutes || $createSubscriptionRoutes ? 'active' : '' }}">
                    <span class="menu-icon"><i class="ti ti-carambola"></i></span>
                    <span class="menu-text" data-lang="manage-subscription">Subscription</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse {{ $manageSubscriptionRoutes || $createSubscriptionRoutes ? 'show' : '' }}" id="sidebarManageSubscription">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a href="{{ route('admin.subscription.index') }}" class="side-nav-link {{ $manageSubscriptionRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="subscription">View Subscription</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a href="{{ route('admin.subscription.create') }}" class="side-nav-link {{ $createSubscriptionRoutes ? 'active' : '' }}">
                                <span class="menu-text" data-lang="create-subscription">Create Subscription</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
        </ul>
    </div>
</div>
