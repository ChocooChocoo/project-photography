    <style>
        #notificationMenu {
            width: min(420px, calc(100vw - 2rem));
            max-height: min(70vh, 560px);
            overflow: hidden;
        }

        #notificationList {
            max-height: min(50vh, 320px) !important;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            overscroll-behavior: contain;
        }

        #notificationList .simplebar-content-wrapper,
        #notificationList .simplebar-mask,
        #notificationList .simplebar-offset,
        #notificationList .simplebar-content {
            max-height: inherit;
        }

        .notification-item {
            white-space: normal;
        }
    </style>

    {{-- VENDOR JS --}}
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    {{-- APP JS --}}
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- JQUERY --}}
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>

    {{-- CUSTOM TABLE --}}
    <script src="{{ asset('assets/js/pages/custom-table.js') }}"></script>

    {{-- SWEETALERT2 JS --}}
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/misc-sweetalerts.js') }}"></script>

    {{-- SHARED NOTIFICATIONS --}}
    <script src="{{ asset('assets/js/pages/notifications.js') }}"></script>
