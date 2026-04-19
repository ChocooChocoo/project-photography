@extends('layouts.studio-hr.app')
@section('title', 'Human Resource Dashboard')

@section('content')
    @include('partials.dashboard-page', ['dashboard' => $dashboard, 'dashboardConfig' => $dashboardConfig])
@endsection

@section('scripts')
    <script src="{{ asset('assets/plugins/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        window.dashboardPageConfig = @json($dashboardConfig);
        window.dashboardInitialData = @json($dashboard);
    </script>
    <script src="{{ asset('assets/js/pages/role-dashboard.js') }}"></script>
@endsection
