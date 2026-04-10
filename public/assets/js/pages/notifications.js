(function () {
    function updateBadge($badge, count) {
        if (count > 0) {
            $badge.text(count).show();
            return;
        }

        $badge.hide();
    }

    function renderNotificationState($list, message, icon) {
        $list.html(
            '<div class="text-center py-4 text-muted">' +
                '<i class="ti ti-' + icon + ' fs-3 mb-2 d-block"></i>' +
                '<span>' + message + '</span>' +
            '</div>'
        );
    }

    document.addEventListener("DOMContentLoaded", function () {
        if (typeof window.jQuery === "undefined") {
            return;
        }

        var $ = window.jQuery;
        var $dropdown = $("#notificationDropdown");

        if (!$dropdown.length) {
            return;
        }

        var unreadUrl = $dropdown.data("unread-url");
        var recentUrl = $dropdown.data("recent-url");
        var markAllUrl = $dropdown.data("mark-all-url");
        var markReadUrl = $dropdown.data("mark-read-url");
        var csrfToken = $('meta[name="csrf-token"]').attr("content");
        var $badge = $("#notificationBadge");
        var $notificationCount = $("#notificationCount");
        var $notificationList = $("#notificationList");
        var notificationsLoaded = false;
        var isLoadingNotifications = false;

        function renderLoadingState() {
            $notificationList.html(
                '<div class="text-center py-4 text-muted">' +
                    '<div class="spinner-border spinner-border-sm text-primary me-2" role="status">' +
                        '<span class="visually-hidden">Loading...</span>' +
                    '</div>' +
                    '<span>Loading notifications...</span>' +
                '</div>'
            );
        }

        function updateNotificationList(notifications, unreadCount) {
            $notificationCount.text(
                notifications.length + " Notification" + (notifications.length !== 1 ? "s" : "")
            );

            updateBadge($badge, unreadCount);

            if (!notifications.length) {
                renderNotificationState($notificationList, "No notifications yet", "bell-off");
                return;
            }

            var html = notifications.map(function (notification) {
                var isUnread = notification.read_at === null;
                var bgClass = isUnread ? "bg-light" : "";
                var iconColor = notification.color || "primary";
                var icon = notification.icon || "bell";
                var unreadMarker = isUnread
                    ? '<span class="position-absolute rounded-pill bg-success notification-badge" style="top: 0; right: 0;"><i class="ti ti-bell align-middle"></i></span>'
                    : "";

                return (
                    '<div class="dropdown-item notification-item py-3 text-wrap ' + bgClass + '" id="notification-' + notification.id + '" data-id="' + notification.id + '" style="position: relative; padding-right: 45px !important;">' +
                        '<div class="d-flex align-items-start gap-3">' +
                            '<div class="flex-shrink-0 position-relative">' +
                                '<div class="avatar-sm rounded-circle bg-soft-' + iconColor + ' d-flex align-items-center justify-content-center">' +
                                    '<i class="ti ti-' + icon + ' text-' + iconColor + '"></i>' +
                                '</div>' +
                                unreadMarker +
                            '</div>' +
                            '<div class="flex-grow-1" style="max-width: calc(100% - 70px);">' +
                                '<div class="d-flex justify-content-between align-items-start">' +
                                    '<span class="fw-medium text-body d-block small text-truncate">' + notification.title + '</span>' +
                                    '<span class="fs-xs text-muted flex-shrink-0 ms-2">' + (notification.time_ago || "Just now") + '</span>' +
                                '</div>' +
                                '<p class="small text-muted mb-1 text-wrap" style="word-break: break-word; line-height: 1.3;">' + notification.message + "</p>" +
                            "</div>" +
                        "</div>" +
                        '<button type="button" class="btn btn-link p-0 mark-read-btn position-absolute" data-id="' + notification.id + '" title="Mark as read" style="top: 50%; right: 12px; transform: translateY(-50%); color: #6c757d; z-index: 10;">' +
                            '<i class="ti ti-check fs-5"></i>' +
                        "</button>" +
                    "</div>"
                );
            }).join("");

            $notificationList.html(html);
        }

        function loadUnreadCount() {
            if (!unreadUrl) {
                return;
            }

            $.ajax({
                url: unreadUrl,
                type: "GET",
                headers: {
                    "X-CSRF-TOKEN": csrfToken
                }
            }).done(function (response) {
                if (response.success) {
                    updateBadge($badge, response.count);
                }
            });
        }

        function loadNotifications(forceReload) {
            if (!recentUrl || isLoadingNotifications) {
                return;
            }

            if (notificationsLoaded && !forceReload) {
                return;
            }

            isLoadingNotifications = true;
            renderLoadingState();

            $.ajax({
                url: recentUrl,
                type: "GET",
                headers: {
                    "X-CSRF-TOKEN": csrfToken
                }
            }).done(function (response) {
                if (response.success) {
                    updateNotificationList(response.notifications || [], response.unread_count || 0);
                    notificationsLoaded = true;
                    return;
                }

                renderNotificationState($notificationList, "Failed to load notifications", "bell-off");
            }).fail(function () {
                renderNotificationState($notificationList, "Error loading notifications", "bell-off");
            }).always(function () {
                isLoadingNotifications = false;
            });
        }

        function markAsRead(notificationId) {
            if (!markReadUrl) {
                return;
            }

            $.ajax({
                url: markReadUrl.replace("__ID__", notificationId),
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken
                }
            }).done(function (response) {
                if (!response.success) {
                    return;
                }

                var $notificationItem = $("#notification-" + notificationId);
                $notificationItem.removeClass("bg-light");
                $notificationItem.find(".notification-badge").remove();

                updateBadge($badge, response.unread_count || 0);
                $notificationCount.text((response.unread_count || 0) + " Unread");

                if (window.Swal) {
                    window.Swal.fire({
                        icon: "success",
                        title: "Marked as read",
                        showConfirmButton: false,
                        timer: 1000,
                        timerProgressBar: true
                    });
                }
            });
        }

        function markAllAsRead() {
            if (!markAllUrl) {
                return;
            }

            $.ajax({
                url: markAllUrl,
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken
                }
            }).done(function (response) {
                if (!response.success) {
                    return;
                }

                $(".notification-item").removeClass("bg-light");
                $(".notification-item .notification-badge").remove();
                updateBadge($badge, 0);
                $notificationCount.text("0 Unread");

                if (window.Swal) {
                    window.Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                }
            }).fail(function () {
                if (window.Swal) {
                    window.Swal.fire({
                        icon: "error",
                        title: "Error!",
                        text: "Failed to mark all as read",
                        confirmButtonColor: "#DC3545"
                    });
                }
            });
        }

        $dropdown.on("click.notifications", function () {
            loadNotifications(false);
        });

        $("#markAllReadBtn").on("click.notifications", function (event) {
            event.preventDefault();
            event.stopPropagation();
            markAllAsRead();
        });

        $("#viewAllNotifications").on("click.notifications", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (!window.Swal) {
                return;
            }

            window.Swal.fire({
                title: "Coming Soon!",
                text: "Notifications page will be available soon.",
                icon: "info",
                confirmButtonColor: "#3475db"
            });
        });

        $(document).on("click.notifications", ".mark-read-btn", function (event) {
            event.preventDefault();
            event.stopPropagation();
            markAsRead($(this).data("id"));
        });

        $(document).on("click.notifications", ".notification-item", function (event) {
            if ($(event.target).closest(".mark-read-btn").length) {
                return;
            }

            markAsRead($(this).data("id"));
        });

        loadUnreadCount();
    });
})();
