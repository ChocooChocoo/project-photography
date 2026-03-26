<!DOCTYPE html>
<html lang="en" class="sidebar-with-line">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Default Title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    {{-- FAVICON --}}
    <link href="{{ asset('assets/images/favicon.ico') }}"/>

    {{-- THEME CONFIG --}}
    <script src="{{ asset('assets/js/config.js') }}"></script>

    {{-- SWEETALERT2 CSS --}}
    <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">

    {{-- CHOICES CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/plugins/choices/choices.min.css') }}">

    {{-- VENDOR CSS --}}
    <link href="{{ asset('assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- CSS --}}
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    {{-- CUSTOM STYLES --}}
    @yield('styles')
</head>
<body>

    {{-- MAIN WRAPPER --}}
    <div class="wrapper">
        @include('layouts.studio-finance.sidebar')
        @include('layouts.studio-finance.topbar')
        @yield('content')
        @include('layouts.studio-finance.theme')
    </div>

    {{-- VENDOR JS --}}
    <script src="{{ asset('assets/js/vendors.min.js') }}"></script>

    {{-- APP JS --}}
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- E CHARTS JS --}}
    <script src="{{ asset('assets/plugins/chartjs/chart.umd.js') }}"></script>

    {{-- CUSTOM TABLE --}}
    <script src="{{ asset('assets/js/pages/custom-table.js') }}"></script>

    {{-- DASHBOARD PAGE JS --}}
    <script src="{{ asset('assets/js/pages/dashboard.js') }}"></script>

    {{-- CUSTOM TABLE --}}
    <script src="{{ asset('assets/js/pages/custom-table.js') }}"></script>

    {{-- SWEETALERT2 JS --}}
    <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/misc-sweetalerts.js') }}"></script>

    {{-- INPUT MASK --}}
    <script src="{{ asset('assets/plugins/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/inputmask/inputmask.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/handlebars/handlebars.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/typeahead/typeahead.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-inputmask.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-typehead.js') }}"></script>

    {{-- CHOICES JS --}}
    <script src="{{ asset('assets/plugins/choices/choices.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-choice.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-select2.js') }}"></script>

    {{-- YIELD SCRIPT --}}
    @yield('scripts')
</body>
</html>
