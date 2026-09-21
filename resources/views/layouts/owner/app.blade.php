<!DOCTYPE html>
<html lang="en" class="sidebar-with-line">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Default Title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- FAVICON --}}
    <link href="{{ asset('assets/images/favicon.ico') }}"/>

    {{-- THEME CONFIG --}}
    <script src="{{ asset('assets/js/config.js') }}"></script>

    {{-- SWEETALERT2 CSS --}}
    <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">

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
        @include('layouts.owner.sidebar')
        @include('layouts.owner.topbar')
        {{-- Keep the banner aligned with the sidebar offset used by .content-page --}}
        <div class="content-page" style="min-height: auto;">
            @include('partials.subscription-access-banner')
        </div>
        @yield('content')
        @include('layouts.owner.theme')
    </div>

    @include('layouts.partials.portal-base-scripts')

    {{-- PHOTOGRAPHY AI ASSISTANT --}}
    @if($hasStudioSubscriptionAccess ?? false)
        @include('partials.chatbot-widget', ['ownerId' => auth()->id()])
    @endif

    {{-- YIELD SCRIPT --}}
    @yield('scripts')
</body>
</html>
