<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <button class="sidenav-toggle-button btn btn-default btn-icon">
                <i class="ti ti-menu-4 fs-22"></i>
            </button>

            <button class="topnav-toggle-button px-2" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <i class="ti ti-menu-4 fs-22"></i>
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <div class="topbar-item">
                <div class="dropdown">
                    <button class="topbar-link dropdown-toggle drop-arrow-none" data-bs-toggle="dropdown"
                        data-bs-offset="0,24" type="button" data-bs-auto-close="outside" aria-haspopup="false"
                        aria-expanded="false" id="notificationDropdown"
                        data-unread-url="{{ route('notifications.unread-count') }}"
                        data-recent-url="{{ route('notifications.recent') }}"
                        data-mark-all-url="{{ route('notifications.mark-all-read') }}"
                        data-mark-read-url="{{ route('notifications.mark-read', ['id' => '__ID__']) }}">
                        <i data-lucide="bell" class="fs-xxl"></i>
                        <span class="badge text-bg-danger badge-circle topbar-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>

                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg" id="notificationMenu">
                        <div class="px-3 py-2 border-bottom">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="m-0 fs-md fw-semibold">Notifications</h6>
                                </div>
                                <div class="col text-end">
                                    <span class="badge badge-soft-success badge-label py-1" id="notificationCount">0 Notifications</span>
                                </div>
                            </div>
                        </div>

                        <div style="max-height: 300px;" data-simplebar id="notificationList">
                            <!-- Notifications will be loaded here via AJAX -->
                            <div class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span>Loading notifications...</span>
                            </div>
                        </div>
                        
                        <div class="dropdown-divider m-0"></div>
                        <div class="px-3 py-2 text-center">
                            <div class="row g-1">
                                <div class="col-6">
                                    <button type="button" class="btn btn-sm btn-soft-primary w-100" id="markAllReadBtn">
                                        <i class="ti ti-check me-1"></i>Mark all read
                                    </button>
                                </div>
                                <div class="col-6">
                                    <a href="#" class="btn btn-sm btn-soft-secondary w-100" id="viewAllNotifications">
                                        <i class="ti ti-eye me-1"></i>View all
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="topbar-item d-none">
                <button class="topbar-link" id="light-dark-mode" type="button">
                    <i data-lucide="moon" class="fs-xxl mode-light-moon"></i>
                </button>
            </div>

            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-toggle="dropdown"
                        data-bs-offset="0,19" href="#!" aria-haspopup="false" aria-expanded="false">
                        @auth
                            @if(auth()->user()->profile_photo)
                                <img src="{{ asset('storage/' . auth()->user()->profile_photo) }}" width="32"
                                    class="rounded-circle me-lg-2 d-flex" alt="user-image">
                            @else
                                <img src="{{ asset('/assets/uploads/profile_placeholder.jpg') }}" width="32"
                                    class="rounded-circle me-lg-2 d-flex" alt="user-image">
                            @endif
                            <div class="d-lg-flex align-items-center gap-1 d-none">
                                <h5 class="my-0">{{ auth()->user()->full_name ?? auth()->user()->first_name . ' ' . auth()->user()->last_name }}</h5>
                                <i class="ti ti-chevron-down align-middle"></i>
                            </div>
                        @else
                            <img src="{{ asset('/assets/uploads/profile_placeholder.jpg') }}" width="32"
                                class="rounded-circle me-lg-2 d-flex" alt="user-image">
                            <div class="d-lg-flex align-items-center gap-1 d-none">
                                <h5 class="my-0">Guest</h5>
                                <i class="ti ti-chevron-down align-middle"></i>
                            </div>
                        @endauth
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="{{ route('studio-finance.profile') }}" class="dropdown-item">
                            <i class="ti ti-user-circle me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form id="logoutForm" action="{{ route('auth.logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="dropdown-item w-100 text-start bg-transparent border-0 text-danger">
                                <i class="ti ti-logout-2 me-1 fs-17 align-middle"></i>
                                <span class="align-middle">Log Out</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" data-bs-toggle="offcanvas" data-bs-target="#theme-settings-offcanvas" type="button">
                    <i class="ti ti-settings icon-spin fs-24"></i>
                </button>
            </div>
        </div>
    </div>
</header>
