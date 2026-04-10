<!DOCTYPE html>
<html lang="en" class="sidebar-with-line">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Default Title')</title>

    {{-- FAVICON --}}
    <link href="{{ asset('assets/images/favicon.ico') }}"/>

    {{-- SWEETALERT2 CSS --}}
    <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">

    {{-- THEME CONFIG --}}
    <script src="{{ asset('assets/js/config.js') }}"></script>

    {{-- VENDOR CSS --}}
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- CSS --}}
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- CUSTOM STYLES --}}
    @yield('styles')
</head>
<body>

    {{-- MAIN WRAPPER --}}
    <div class="auth-box overflow-hidden align-items-center d-flex">
        @yield('content')
        @include('layouts.admin.theme')
    </div>

    {{-- JQUERY JS --}}
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>

    {{-- VENDOR JS --}}
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    {{-- APP JS --}}
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- PASSWORD METER --}}
    <script src="{{ asset('assets/js/pages/misc-pass-meter.js') }}"></script>

    {{-- SWEETALERT2 JS --}}
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/misc-sweetalerts.js') }}"></script>

    {{-- YIELD SCRIPT --}}
    @yield('scripts')
</body>
</html>
