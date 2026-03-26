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
                        aria-expanded="false" id="notificationDropdown">
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

<script>
    // Wait for jQuery to be loaded
    function waitForJQuery() {
        if (typeof window.jQuery === 'undefined') {
            setTimeout(waitForJQuery, 50);
            return;
        }
        
        // jQuery is now loaded, run your code
        jQuery(document).ready(function($) {
            console.log('Notification script loaded');
            
            // Load unread count on page load
            loadUnreadCount();
            
            // Load notifications when dropdown is clicked
            $('#notificationDropdown').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Dropdown clicked');
                loadNotifications();
            });
            
            // Refresh unread count every 30 seconds
            setInterval(loadUnreadCount, 30000);
            
            // Mark all as read
            $('#markAllReadBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                markAllAsRead();
            });
            
            // View all notifications
            $('#viewAllNotifications').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                Swal.fire({
                    title: 'Coming Soon!',
                    text: 'Notifications page will be available soon.',
                    icon: 'info',
                    confirmButtonColor: '#3475db'
                });
            });
            
            // Function to load unread count
            function loadUnreadCount() {
                console.log('Loading unread count...');
                $.ajax({
                    url: '{{ route("notifications.unread-count") }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Unread count response:', response);
                        if (response.success) {
                            const badge = $('#notificationBadge');
                            if (response.count > 0) {
                                badge.text(response.count).show();
                            } else {
                                badge.hide();
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading unread count:', xhr);
                    }
                });
            }
            
            // Function to load notifications
            function loadNotifications() {
                console.log('Loading notifications...');
                const notificationList = $('#notificationList');
                
                // Show loading state
                notificationList.html(`
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>Loading notifications...</span>
                    </div>
                `);
                
                $.ajax({
                    url: '{{ route("notifications.recent") }}',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Notifications response:', response);
                        if (response.success) {
                            updateNotificationList(response.notifications, response.unread_count);
                        } else {
                            notificationList.html(`
                                <div class="text-center py-4 text-muted">
                                    <i class="ti ti-bell-off fs-3 mb-2 d-block"></i>
                                    <span>Failed to load notifications</span>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error loading notifications:', xhr);
                        notificationList.html(`
                            <div class="text-center py-4 text-muted">
                                <i class="ti ti-bell-off fs-3 mb-2 d-block"></i>
                                <span>Error loading notifications</span>
                            </div>
                        `);
                    }
                });
            }
            
            // Function to update notification list
            function updateNotificationList(notifications, unreadCount) {
                console.log('Updating notification list:', notifications, unreadCount);
                const notificationList = $('#notificationList');
                const notificationCount = $('#notificationCount');
                const badge = $('#notificationBadge');
                
                // Update counts
                notificationCount.text(notifications.length + ' Notification' + (notifications.length !== 1 ? 's' : ''));
                
                if (unreadCount > 0) {
                    badge.text(unreadCount).show();
                } else {
                    badge.hide();
                }
                
                if (!notifications || notifications.length === 0) {
                    notificationList.html(`
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-bell-off fs-3 mb-2 d-block"></i>
                            <span>No notifications yet</span>
                        </div>
                    `);
                    return;
                }
                
                let html = '';
                notifications.forEach(function(notification) {
                    const isUnread = notification.read_at === null;
                    const bgClass = isUnread ? 'bg-light' : '';
                    const iconColor = notification.color || 'primary';
                    const icon = notification.icon || 'bell';
                    
                    html += `
                        <div class="dropdown-item notification-item py-3 text-wrap ${bgClass}" id="notification-${notification.id}" data-id="${notification.id}" style="position: relative; padding-right: 45px !important;">
                            <div class="d-flex align-items-start gap-3">
                                <div class="flex-shrink-0 position-relative">
                                    <div class="avatar-sm rounded-circle bg-soft-${iconColor} d-flex align-items-center justify-content-center">
                                        <i class="ti ti-${icon} text-${iconColor}"></i>
                                    </div>
                                    ${isUnread ? '<span class="position-absolute rounded-pill bg-success notification-badge" style="top: 0; right: 0;"><i class="ti ti-bell align-middle"></i></span>' : ''}
                                </div>
                                <div class="flex-grow-1" style="max-width: calc(100% - 70px);">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <span class="fw-medium text-body d-block small text-truncate">${notification.title}</span>
                                        <span class="fs-xs text-muted flex-shrink-0 ms-2">${notification.time_ago || 'Just now'}</span>
                                    </div>
                                    <p class="small text-muted mb-1 text-wrap" style="word-break: break-word; line-height: 1.3;">${notification.message}</p>
                                </div>
                            </div>
                            <button type="button" class="btn btn-link p-0 mark-read-btn position-absolute" 
                                    data-id="${notification.id}" title="Mark as read"
                                    style="top: 50%; right: 12px; transform: translateY(-50%); color: #6c757d; z-index: 10;">
                                <i class="ti ti-check fs-5"></i>
                            </button>
                        </div>
                    `;
                });
                
                notificationList.html(html);
                
                // Add click handler for mark as read buttons
                $('.mark-read-btn').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const notificationId = $(this).data('id');
                    console.log('Mark as read clicked for notification:', notificationId);
                    markAsRead(notificationId);
                });
                
                // Add click handler for notification items to mark as read
                $('.notification-item').on('click', function(e) {
                    const notificationId = $(this).data('id');
                    if (notificationId && !$(e.target).closest('.mark-read-btn').length) {
                        console.log('Notification item clicked:', notificationId);
                        markAsRead(notificationId);
                    }
                });
            }
            
            // Function to mark a single notification as read
            function markAsRead(notificationId) {
                console.log('Marking notification as read:', notificationId);
                $.ajax({
                    url: '{{ route("notifications.mark-read", ":id") }}'.replace(':id', notificationId),
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Mark as read response:', response);
                        if (response.success) {
                            // Update the UI
                            const notificationItem = $(`#notification-${notificationId}`);
                            notificationItem.removeClass('bg-light');
                            notificationItem.find('.notification-badge').remove();
                            
                            // Update unread count
                            const badge = $('#notificationBadge');
                            if (response.unread_count > 0) {
                                badge.text(response.unread_count).show();
                            } else {
                                badge.hide();
                            }
                            
                            // Update notification count text
                            $('#notificationCount').text(response.unread_count + ' Unread');
                            
                            // Optional: Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Marked as read',
                                showConfirmButton: false,
                                timer: 1000,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error marking as read:', xhr);
                    }
                });
            }
            
            // Function to mark all as read
            function markAllAsRead() {
                console.log('Marking all as read');
                $.ajax({
                    url: '{{ route("notifications.mark-all-read") }}',
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('Mark all as read response:', response);
                        if (response.success) {
                            // Update all notifications in the list
                            $('.notification-item').removeClass('bg-light');
                            $('.notification-item .notification-badge').remove();
                            
                            // Hide badge
                            $('#notificationBadge').hide();
                            
                            // Update notification count
                            $('#notificationCount').text('0 Unread');
                            
                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error marking all as read:', xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to mark all as read',
                            confirmButtonColor: '#DC3545'
                        });
                    }
                });
            }
        });
    }

    waitForJQuery();
</script>
