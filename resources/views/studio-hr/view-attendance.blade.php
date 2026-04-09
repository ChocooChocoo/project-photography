@extends('layouts.studio-hr.app')
@section('title', 'Employee Attendance')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}">
    <style>
        #attendanceMap {
            min-height: 320px;
            border-radius: 0.75rem;
        }

        .attendance-map-marker {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border: 3px solid #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
        }

        .attendance-map-marker i {
            font-size: 20px;
            line-height: 1;
        }

        .attendance-map-marker.studio-marker {
            background: #dc3545;
        }

        .attendance-map-marker.employee-marker {
            background: #0d6efd;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Employee Attendance</h5>
                            <div>
                                <span class="badge bg-soft-primary p-2">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Grace Period: 15 minutes
                                </span>
                            </div>
                        </div>

                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item">
                                <a href="#check-in-check-out" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    <i class="ti ti-clock-check me-1"></i> Check-In / Check-Out
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#employees-attendance" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <i class="ti ti-calendar-stats me-1"></i> Employees Attendance
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">

                            {{-- Check-In / Check-Out --}}
                            <div class="tab-pane show active p-3" id="check-in-check-out">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="text-center mb-4">
                                            <h1 class="display-3 mb-0" id="liveClock">--:--:-- --</h1>
                                            <p class="text-muted mb-0" id="liveDate">---</p>
                                        </div>

                                        <!-- Schedule Alert - Dynamic based on user's actual schedule -->
                                        @if(isset($scheduleInfo) && $scheduleInfo)
                                            <div class="alert alert-warning p-3 mb-4" role="alert" id="scheduleAlert">
                                                <h4 class="alert-heading">
                                                    <i class="ti ti-calendar-clock me-2"></i>
                                                    Your Work Schedule
                                                </h4>
                                                <p class="mb-2">
                                                    <strong>Operating Days:</strong> 
                                                    {{ implode(', ', array_map('ucfirst', $scheduleInfo['operating_days'])) }}
                                                </p>
                                                <p class="mb-2">
                                                    <strong>Working Hours:</strong> 
                                                    {{ $scheduleInfo['start_time'] }} - {{ $scheduleInfo['end_time'] }}
                                                </p>
                                                <hr class="border-warning border-opacity-25">
                                                <p class="mb-0 text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Please check-in within 15 minutes grace period. Check-out anytime after your shift ends.
                                                </p>
                                            </div>
                                        @else
                                            <div class="alert alert-warning p-3 mb-4" role="alert" id="scheduleAlert">
                                                <h4 class="alert-heading">
                                                    <i class="ti ti-alert-triangle me-2"></i>
                                                    No Schedule Found
                                                </h4>
                                                <p class="mb-0">You don't have an active work schedule set. Please contact your administrator.</p>
                                            </div>
                                        @endif

                                        <div class="card border-0 bg-light mb-4">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                                    <div>
                                                        <h5 class="mb-1">Attendance Location Map</h5>
                                                        <p class="text-muted mb-0">See your live location and the saved studio attendance pin before you submit attendance.</p>
                                                    </div>
                                                    <button type="button" class="btn btn-soft-primary btn-sm" id="refreshAttendanceMapBtn">
                                                        <i class="ti ti-current-location me-1"></i> Refresh My Location
                                                    </button>
                                                </div>
                                                <div id="attendanceMap"></div>
                                                <div class="row g-3 mt-1">
                                                    <div class="col-md-6">
                                                        <div class="border rounded p-3 h-100 bg-white">
                                                            <span class="text-muted small d-block mb-1">My Current Location</span>
                                                            <div id="currentLocationText" class="fw-medium">Waiting for location permission...</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="border rounded p-3 h-100 bg-white">
                                                            <span class="text-muted small d-block mb-1">Studio Attendance Pin</span>
                                                            <div id="studioLocationText" class="fw-medium">Waiting for studio geofence data...</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-3 mb-4">
                                            <div class="col-6">
                                                <button class="btn btn-primary w-100" id="checkInBtn">
                                                    <span>Check-In</span>
                                                </button>
                                            </div>
                                            <div class="col-6">
                                                <button class="btn btn-danger w-100" id="checkOutBtn">
                                                    <span>Check-Out</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Today's Attendance Summary -->
                                        {{-- <div class="row g-3 mb-4" id="attendanceSummary" style="display: none;">
                                            <div class="col-12">
                                                <div class="card bg-light">
                                                    <div class="card-body">
                                                        <h6 class="card-title">
                                                            <i class="ti ti-clipboard-list me-1"></i>
                                                            Today's Attendance Summary
                                                        </h6>
                                                        <div id="summaryContent">
                                                            <p class="text-muted mb-0">No attendance yet</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div> --}}
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h5 class="card-title mb-0">
                                                    <i class="ti ti-history me-2"></i>
                                                    My Attendance History
                                                </h5>
                                                <span class="badge badge-soft-primary p-1">
                                                    Total Records: {{ $myAttendance->total() }}
                                                </span>
                                            </div>
                                            
                                            <div data-table data-table-rows-per-page="10">
                                                <div class="card-header border-light justify-content-between">
                                                    <div class="d-flex gap-2">
                                                        <div class="app-search">
                                                            <form id="myAttendanceFilterForm" onsubmit="return false;">
                                                                <input type="search" class="form-control" placeholder="Search by date..."
                                                                    id="myAttendanceSearch">
                                                                <i data-lucide="search" class="app-search-icon text-muted"></i>
                                                            </form>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-semibold">
                                                            <i class="ti ti-filter me-1"></i>Filter By:
                                                        </span>
                                                        <div class="app-filter">
                                                            <select class="me-0 form-select form-control" id="filterMonth">
                                                                <option value="">All Months</option>
                                                                <option value="01">January</option>
                                                                <option value="02">February</option>
                                                                <option value="03">March</option>
                                                                <option value="04">April</option>
                                                                <option value="05">May</option>
                                                                <option value="06">June</option>
                                                                <option value="07">July</option>
                                                                <option value="08">August</option>
                                                                <option value="09">September</option>
                                                                <option value="10">October</option>
                                                                <option value="11">November</option>
                                                                <option value="12">December</option>
                                                            </select>
                                                        </div>
                                                        <div class="app-filter">
                                                            <select class="me-0 form-select form-control" id="filterYear">
                                                                <option value="">All Years</option>
                                                                @for($year = now()->year; $year >= now()->subYears(2)->year; $year--)
                                                                    <option value="{{ $year }}">{{ $year }}</option>
                                                                @endfor
                                                            </select>
                                                        </div>
                                                        <div class="app-filter">
                                                            <select class="me-0 form-select form-control" id="filterStatus">
                                                                <option value="">All Status</option>
                                                                <option value="ON_TIME">On Time</option>
                                                                <option value="LATE">Late</option>
                                                                <option value="UNDERTIME">Undertime</option>
                                                                <option value="ON_LEAVE">On Leave</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="myAttendanceTable">
                                                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                            <tr class="text-uppercase fs-xxs">
                                                                <th>Date</th>
                                                                <th>Scheduled Time</th>
                                                                <th>Check-In</th>
                                                                <th>Status (In)</th>
                                                                <th>Check-Out</th>
                                                                <th>Status (Out)</th>
                                                                <th>Total Hours</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($myAttendance as $record)
                                                                @php
                                                                    $isLeaveRecord = ($record->record_type ?? 'attendance') === 'leave';
                                                                @endphp
                                                                <tr data-record-type="{{ $record->record_type ?? 'attendance' }}">
                                                                    <td>{{ $record->attendance_date->format('M d, Y') }}</td>
                                                                    <td>
                                                                        @if($record->scheduled_start_time && $record->scheduled_end_time)
                                                                            {{ \Carbon\Carbon::parse($record->scheduled_start_time)->format('h:i A') }} - 
                                                                            {{ \Carbon\Carbon::parse($record->scheduled_end_time)->format('h:i A') }}
                                                                        @elseif($isLeaveRecord)
                                                                            <span class="text-muted">Leave Day</span>
                                                                        @else
                                                                            <span class="text-muted">{{ $isLeaveRecord ? 'Approved Leave' : 'No schedule' }}</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $record->formatted_check_in }}</td>
                                                                    <td>
                                                                        @if($record->check_in_status)
                                                                            <span class="badge {{ $isLeaveRecord ? 'badge-soft-info' : $record->check_in_status_badge }}">
                                                                                {{ $record->check_in_status }}
                                                                            </span>
                                                                            @if(!$isLeaveRecord && $record->late_minutes > 0)
                                                                                <small class="d-block text-muted">{{ $record->late_display }}</small>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $record->display_check_out ?? $record->formatted_check_out }}</td>
                                                                    <td>
                                                                        @if($record->check_out_status)
                                                                            <span class="badge {{ $record->check_out_status_badge }}">
                                                                                {{ $record->check_out_status }}
                                                                            </span>
                                                                            @if($record->undertime_minutes > 0)
                                                                                <small class="d-block text-muted">{{ $record->undertime_display }}</small>
                                                                            @endif
                                                                            @if(($record->is_overtime_applied ?? false) === true)
                                                                                <small class="d-block text-primary">OT Applied until {{ $record->counted_check_out }}</small>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($record->display_duration ?? $record->duration)
                                                                            <span class="badge badge-soft-info">{{ $record->display_duration ?? $record->duration }}</span>
                                                                            @if(($record->is_overtime_applied ?? false) === true && ($record->actual_duration ?? null) !== ($record->display_duration ?? $record->duration))
                                                                                <small class="d-block text-muted">Actual: {{ $record->actual_duration }}</small>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="7" class="text-center py-4">
                                                                        <i class="ti ti-clock-off fs-1 d-block mb-2 text-muted"></i>
                                                                        <span class="text-muted">No attendance records found</span>
                                                                    </td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="card-footer border-0 px-0 pb-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div data-table-pagination-info="my-attendance">
                                                            Showing {{ $myAttendance->firstItem() ?? 0 }} to {{ $myAttendance->lastItem() ?? 0 }} of {{ $myAttendance->total() }} records
                                                        </div>
                                                        <div data-table-pagination>
                                                            {{ $myAttendance->links() }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Employees Attendance --}}
                            <div class="tab-pane p-3" id="employees-attendance">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center mb-4">
                                            <!-- Total Attendance Card -->
                                            <div class="col">
                                                <div class="card border shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="avatar avatar-lg flex-shrink-0">
                                                                <span class="avatar-title bg-info-subtle text-info rounded fs-24">
                                                                    <i class="ti ti-clipboard-list"></i>
                                                                </span>
                                                            </div>
                                                            <div class="text-end">
                                                                <h4 class="mb-0" id="totalAttendanceCount">0</h4>
                                                                <p class="mb-0 text-muted">Total Attendance</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted fs-xs fw-semibold">All time records</span>
                                                                <span class="text-muted" id="totalAttendancePercent">100%</span>
                                                            </div>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar bg-info" id="totalAttendanceBar" style="width: 100%;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Late Count Card -->
                                            <div class="col">
                                                <div class="card border shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="avatar avatar-lg flex-shrink-0">
                                                                <span class="avatar-title bg-warning-subtle text-warning rounded fs-24">
                                                                    <i class="ti ti-clock-hour-4"></i>
                                                                </span>
                                                            </div>
                                                            <div class="text-end">
                                                                <h4 class="mb-0" id="lateCount">0</h4>
                                                                <p class="mb-0 text-muted">Lates</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted fs-xs fw-semibold">This month</span>
                                                                <span class="text-muted" id="latePercent">0%</span>
                                                            </div>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar bg-warning" id="lateBar" style="width: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- On-Time Card -->
                                            <div class="col">
                                                <div class="card border shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="avatar avatar-lg flex-shrink-0">
                                                                <span class="avatar-title bg-success-subtle text-success rounded fs-24">
                                                                    <i class="ti ti-checklist"></i>
                                                                </span>
                                                            </div>
                                                            <div class="text-end">
                                                                <h4 class="mb-0" id="onTimeCount">0</h4>
                                                                <p class="mb-0 text-muted">On-Time</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted fs-xs fw-semibold">This month</span>
                                                                <span class="text-muted" id="onTimePercent">0%</span>
                                                            </div>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar bg-success" id="onTimeBar" style="width: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Undertime Card -->
                                            <div class="col">
                                                <div class="card border shadow-none">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div class="avatar avatar-lg flex-shrink-0">
                                                                <span class="avatar-title bg-danger-subtle text-danger rounded fs-24">
                                                                    <i class="ti ti-user-cog"></i>
                                                                </span>
                                                            </div>
                                                            <div class="text-end">
                                                                <h4 class="mb-0" id="undertimeCount">0</h4>
                                                                <p class="mb-0 text-muted">Undertime</p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <div class="d-flex justify-content-between mb-1">
                                                                <span class="text-muted fs-xs fw-semibold">This month</span>
                                                                <span class="text-muted" id="undertimePercent">0%</span>
                                                            </div>
                                                            <div class="progress" style="height: 6px;">
                                                                <div class="progress-bar bg-danger" id="undertimeBar" style="width: 0%;"></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div data-table data-table-rows-per-page="10">
                                            <div class="card-header border-light justify-content-between px-0 pt-0">
                                                <div class="d-flex gap-2">
                                                    <div class="app-search">
                                                        <form id="filterForm">
                                                            <input type="search" class="form-control" placeholder="Search employees..."
                                                                id="attendanceSearchInput">
                                                            <i data-lucide="search" class="app-search-icon text-muted"></i>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="fw-semibold">
                                                        <i class="ti ti-filter me-1"></i>Filter By:
                                                    </span>
                                                    <div class="app-filter">
                                                        <select class="me-0 form-select form-control" id="employeeAttendanceDateFilter">
                                                            <option value="today">Today</option>
                                                            <option value="yesterday">Yesterday</option>
                                                            <option value="this-week">This Week</option>
                                                            <option value="this-month">This Month</option>
                                                            <option value="custom">Custom Range</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select class="me-0 form-select form-control" id="employeeAttendanceStatusFilter">
                                                            <option value="">All Status</option>
                                                            <option value="ON_TIME">On Time</option>
                                                            <option value="LATE">Late</option>
                                                            <option value="UNDERTIME">Undertime</option>
                                                            <option value="ON_LEAVE">On Leave</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter d-none" id="employeeAttendanceCustomRange">
                                                        <div class="d-flex gap-2">
                                                            <input type="date" class="form-control" id="employeeAttendanceDateFrom">
                                                            <input type="date" class="form-control" id="employeeAttendanceDateTo">
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <button class="btn btn-soft-primary" id="refreshAttendanceBtn">
                                                            <i class="ti ti-refresh"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="attendanceTable">
                                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                        <tr class="text-uppercase fs-xxs">
                                                            <th>Employee</th>
                                                            <th>Date</th>
                                                            <th>Check-In</th>
                                                            <th>Status (In)</th>
                                                            <th>Check-Out</th>
                                                            <th>Status (Out)</th>
                                                            <th>Total Hours</th>
                                                            <th class="text-center" style="width: 1%;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Data will be loaded via AJAX -->
                                                        <tr>
                                                            <td colspan="8" class="text-center py-4">
                                                                <div class="spinner-border text-primary" role="status">
                                                                    <span class="visually-hidden">Loading...</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="card-footer border-0 px-0 pb-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div data-table-pagination-info="attendance"></div>
                                                    <div data-table-pagination></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
    <script>
        // ==================== ATTENDANCE MODULE ====================

        // Global variables
        let currentAttendanceId = null;
        let attendanceMap = null;
        let studioMarker = null;
        let currentLocationMarker = null;
        let studioRadiusCircle = null;

        // ==================== REAL-TIME CLOCK ====================

        function updateClock() {
            const now = new Date();
            
            // Format time: HH:MM:SS AM/PM
            const timeStr = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
            
            // Format date: Day, Month DD, YYYY
            const dateStr = now.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            $('#liveClock').text(timeStr);
            $('#liveDate').text(dateStr);
        }

        // Update clock every second
        setInterval(updateClock, 1000);

        // ==================== LOAD EMPLOYEE DATA ====================

        function loadEmployeeSchedule() {
            $.ajax({
                url: '/studio-hr/attendance/schedule',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateButtonStates(response);
                        updateAttendanceMap(response);
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load schedule:', xhr);
                }
            });
        }

        function updateAttendanceMap(response) {
            updateStudioMarker(response.studio_geofence);
            requestCurrentMapLocation(response.studio_geofence);
        }

        function createAttendanceMapIcon(type) {
            const iconClass = type === 'studio' ? 'ti ti-map-pin' : 'ti ti-current-location';
            const markerClass = type === 'studio' ? 'studio-marker' : 'employee-marker';

            return L.divIcon({
                className: 'attendance-map-icon-wrapper',
                html: `<div class="attendance-map-marker ${markerClass}"><i class="${iconClass}"></i></div>`,
                iconSize: [42, 42],
                iconAnchor: [21, 21],
                popupAnchor: [0, -18]
            });
        }

        function updateStudioMarker(geofence) {
            if (!geofence || geofence.is_configured !== true) {
                $('#studioLocationText').text('Studio geofence has not been configured yet.');

                if (studioMarker) {
                    attendanceMap.removeLayer(studioMarker);
                    studioMarker = null;
                }

                if (studioRadiusCircle) {
                    attendanceMap.removeLayer(studioRadiusCircle);
                    studioRadiusCircle = null;
                }

                return;
            }

            const studioLatLng = [parseFloat(geofence.latitude), parseFloat(geofence.longitude)];

            if (studioMarker) {
                studioMarker.setLatLng(studioLatLng);
            } else {
                studioMarker = L.marker(studioLatLng, {
                    icon: createAttendanceMapIcon('studio')
                }).addTo(attendanceMap).bindPopup('Studio attendance pin');
            }

            if (studioRadiusCircle) {
                studioRadiusCircle.setLatLng(studioLatLng);
                studioRadiusCircle.setRadius(Number(geofence.radius_meters));
            } else {
                studioRadiusCircle = L.circle(studioLatLng, {
                    radius: Number(geofence.radius_meters),
                    color: '#3475db',
                    fillColor: '#3475db',
                    fillOpacity: 0.12
                }).addTo(attendanceMap);
            }

            $('#studioLocationText').text(`${Number(geofence.latitude).toFixed(6)}, ${Number(geofence.longitude).toFixed(6)} | Radius: ${geofence.radius_meters} meters`);
            focusAttendanceMap(studioLatLng);
        }

        function requestCurrentMapLocation(geofence = null) {
            if (!navigator.geolocation) {
                $('#currentLocationText').text('Geolocation is not supported by this browser.');
                return;
            }

            $('#currentLocationText').text('Fetching your live location...');

            navigator.geolocation.getCurrentPosition(function(position) {
                const currentLatLng = [position.coords.latitude, position.coords.longitude];

                if (currentLocationMarker) {
                    currentLocationMarker.setLatLng(currentLatLng);
                } else {
                    currentLocationMarker = L.marker(currentLatLng, {
                        icon: createAttendanceMapIcon('employee')
                    }).addTo(attendanceMap).bindPopup('My current location');
                }

                $('#currentLocationText').text(`${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)} | Accuracy: ${Math.round(position.coords.accuracy)} meters`);
                focusAttendanceMap(geofence && geofence.is_configured ? [parseFloat(geofence.latitude), parseFloat(geofence.longitude)] : currentLatLng, currentLatLng);
            }, function(error) {
                $('#currentLocationText').text(resolveGeolocationErrorMessage(error));
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        }

        function focusAttendanceMap(primaryLatLng, secondaryLatLng = null) {
            const bounds = [];

            if (primaryLatLng) {
                bounds.push(primaryLatLng);
            }

            if (secondaryLatLng) {
                bounds.push(secondaryLatLng);
            }

            if (bounds.length === 2) {
                attendanceMap.fitBounds(bounds, {
                    padding: [40, 40]
                });
                return;
            }

            if (bounds.length === 1) {
                attendanceMap.setView(bounds[0], 17);
            }
        }

        function loadEmployeeAttendance() {
            const filterDate = $('#employeeAttendanceDateFilter').val();
            const status = $('#employeeAttendanceStatusFilter').val();
            const search = $('#attendanceSearchInput').val();
            const dateFrom = $('#employeeAttendanceDateFrom').val();
            const dateTo = $('#employeeAttendanceDateTo').val();

            $.ajax({
                url: '/studio-hr/attendance/history',
                type: 'GET',
                data: {
                    filter_date: filterDate,
                    status: status,
                    search: search,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: function(response) {
                    if (response.success) {
                        renderAttendanceTable(response.attendance);
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load attendance:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load attendance data',
                        confirmButtonColor: '#3475db'
                    });
                }
            });
        }

        function renderAttendanceTable(attendance) {
            const tbody = $('#attendanceTable tbody');
            tbody.empty();
            
            if (attendance.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="ti ti-clock-off fs-1 d-block mb-2 text-muted"></i>
                            <span class="text-muted">No attendance records found for the selected filters</span>
                        </td>
                    </tr>
                `);
                return;
            }
            
            attendance.forEach(function(record) {
                const isLeaveRecord = record.record_type === 'leave';
                const checkInStatusClass = isLeaveRecord ? 'badge-soft-info' : (record.check_in_status === 'ON_TIME' ? 'badge-soft-success' : 'badge-soft-warning');
                const checkOutStatusClass = record.check_out_status === 'UNDERTIME' ? 'badge-soft-danger' : 'badge-soft-success';
                
                const row = `
                    <tr>
                        <td>${record.employee_name || 'N/A'}</td>
                        <td>${record.attendance_date || 'N/A'}</td>
                        <td>${record.formatted_check_in || '—'}</td>
                        <td>
                            <span class="badge ${checkInStatusClass}">${record.check_in_status || '—'}</span>
                            ${record.late_display ? `<small class="d-block text-muted">${record.late_display}</small>` : ''}
                        </td>
                        <td>${record.formatted_check_out || '—'}</td>
                        <td>
                            ${record.check_out_status ? 
                                `<span class="badge ${checkOutStatusClass}">${record.check_out_status}</span>` : 
                                '—'
                            }
                            ${record.undertime_display ? `<small class="d-block text-muted">${record.undertime_display}</small>` : ''}
                        </td>
                        <td>${record.duration || '—'}</td>
                        <td class="text-center">
                            ${isLeaveRecord ? '<span class="text-muted">View Only</span>' : `<button class="btn btn-sm" onclick="viewAttendanceDetails(${record.id})"><i class="ti ti-eye"></i></button>`}
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function updateButtonStates(data) {
            const checkInBtn = $('#checkInBtn');
            const checkOutBtn = $('#checkOutBtn');
            const isBlocked = data.blocked_by_leave === true;

            if (isBlocked) {
                $('#scheduleAlert').removeClass('alert-warning').addClass('alert-info').html(`
                    <h4 class="alert-heading">
                        <i class="ti ti-beach me-2"></i>
                        Approved Leave for Today
                    </h4>
                    <p class="mb-2"><strong>Leave Type:</strong> ${data.leave_summary?.leave_type || 'Approved Leave'}</p>
                    <p class="mb-2"><strong>Covered Dates:</strong> ${data.leave_summary?.start_date || ''} - ${data.leave_summary?.end_date || ''}</p>
                    <hr class="border-info border-opacity-25">
                    <p class="mb-0 text-muted">${data.blocked_message || 'Attendance is unavailable today because you have an approved leave request.'}</p>
                `);
            } else if (data.has_approved_overtime && data.overtime_summary) {
                $('#scheduleAlert').removeClass('alert-info').addClass('alert-warning').html(`
                    <h4 class="alert-heading">
                        <i class="ti ti-clock-plus me-2"></i>
                        Approved Overtime for Today
                    </h4>
                    <p class="mb-2"><strong>Approved Window:</strong> ${data.overtime_summary.time_range}</p>
                    <p class="mb-2"><strong>Effective Check-Out Cutoff:</strong> ${data.overtime_summary.effective_checkout_cutoff || 'N/A'}</p>
                    <p class="mb-2"><strong>Attendance Geofence:</strong> ${formatGeofenceText(data.studio_geofence)}</p>
                    <hr class="border-warning border-opacity-25">
                    <p class="mb-0 text-muted">Your attendance can count overtime only up to the approved cutoff.</p>
                `);
            } else if (data.studio_geofence) {
                $('#scheduleAlert').append(`
                    <hr class="border-warning border-opacity-25">
                    <p class="mb-0 text-muted"><strong>Attendance Geofence:</strong> ${formatGeofenceText(data.studio_geofence)}</p>
                `);
            }
            
            if (!isBlocked && data.has_schedule && !data.is_checked_in) {
                checkInBtn.prop('disabled', false).removeClass('disabled');
            } else {
                checkInBtn.prop('disabled', true).addClass('disabled');
                
                if (isBlocked) {
                    checkInBtn.attr('title', data.blocked_message || 'Attendance is blocked by approved leave');
                } else if (!data.has_schedule) {
                    checkInBtn.attr('title', 'No schedule for today');
                } else if (data.is_checked_in) {
                    checkInBtn.attr('title', 'Already checked in');
                }
            }
            
            // Store attendance ID for check-out
            if (data.today_attendance_id) {
                currentAttendanceId = data.today_attendance_id;
            }
            
            // Update check-out button state
            // Disable if: not checked in OR already checked out
            if (isBlocked || !data.is_checked_in || data.is_checked_out) {
                checkOutBtn.prop('disabled', true).addClass('disabled');
                
                if (isBlocked) {
                    checkOutBtn.attr('title', data.blocked_message || 'Attendance is blocked by approved leave');
                } else if (!data.is_checked_in) {
                    checkOutBtn.attr('title', 'You have not checked in today');
                } else if (data.is_checked_out) {
                    checkOutBtn.attr('title', 'Already checked out today');
                }
            } else {
                checkOutBtn.prop('disabled', false).removeClass('disabled');
                checkOutBtn.attr('title', 'Check out');
            }
        }

        // ==================== SCHEDULE MATCH INDICATOR ====================

        function checkScheduleMatch() {
            // Get the schedule info from the blade template
            const scheduleInfo = @json($scheduleInfo ?? null);
            
            if (!scheduleInfo || !scheduleInfo.operating_days) {
                console.log('No schedule info available');
                return;
            }
            
            // Get current day (lowercase)
            const today = new Date().toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
            
            // Check if today is in operating days
            const operatingDaysLower = scheduleInfo.operating_days.map(day => day.toLowerCase());
            
            if (!operatingDaysLower.includes(today)) {
                // Format the day name for display (capitalize first letter)
                const displayDay = today.charAt(0).toUpperCase() + today.slice(1);
                
                // Create indicator message
                const indicatorHtml = `
                    <div class="alert alert-warning mt-3 schedule-mismatch-indicator" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="ti ti-alert-triangle fs-4 me-2 text-warning"></i>
                            <div>
                                <strong>Schedule Notice:</strong> You do not have schedule for ${displayDay}
                            </div>
                        </div>
                    </div>
                `;
                
                // Insert after the schedule alert
                $('.alert-warning').first().after(indicatorHtml);
                
                console.log(`Schedule mismatch: Today is ${displayDay}, not in operating days`);
            } else {
                console.log(`Schedule match: Today (${today}) is a working day`);
            }
        }

        // ==================== LOAD ATTENDANCE STATISTICS ====================

        function loadAttendanceStats() {
            $.ajax({
                url: '/studio-hr/attendance/stats',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        updateDashboardCards(response.stats);
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load attendance stats:', xhr);
                }
            });
        }

        function updateDashboardCards(stats) {
            // Update Total Attendance
            $('#totalAttendanceCount').text(stats.month.total);
            $('#totalAttendancePercent').text('100%');
            $('#totalAttendanceBar').css('width', '100%');
            
            // Calculate percentages for month
            const monthTotal = stats.month.total || 1; // Avoid division by zero
            
            // Late
            const latePercent = Math.round((stats.month.late / monthTotal) * 100);
            $('#lateCount').text(stats.month.late);
            $('#latePercent').text(latePercent + '%');
            $('#lateBar').css('width', latePercent + '%');
            
            // On-Time (total - late - undertime for month, but note: some may have both or neither)
            // For simplicity, we'll use a calculation based on available data
            const onTimeCount = stats.month.total - stats.month.late;
            const onTimePercent = Math.round((onTimeCount / monthTotal) * 100);
            $('#onTimeCount').text(onTimeCount);
            $('#onTimePercent').text(onTimePercent + '%');
            $('#onTimeBar').css('width', onTimePercent + '%');
            
            // Undertime
            const undertimePercent = Math.round((stats.month.undertime / monthTotal) * 100);
            $('#undertimeCount').text(stats.month.undertime);
            $('#undertimePercent').text(undertimePercent + '%');
            $('#undertimeBar').css('width', undertimePercent + '%');
            
            // Update today's stats in the hidden summary section (optional)
            if (stats.today) {
                updateTodaySummary(stats.today);
            }
        }

        function updateTodaySummary(todayStats) {
            const summaryHtml = `
                <div class="row g-2">
                    <div class="col-4 text-center">
                        <span class="text-muted small d-block">Checked In</span>
                        <span class="fw-bold">${todayStats.checked_in}</span>
                    </div>
                    <div class="col-4 text-center">
                        <span class="text-muted small d-block">Checked Out</span>
                        <span class="fw-bold">${todayStats.checked_out}</span>
                    </div>
                    <div class="col-4 text-center">
                        <span class="text-muted small d-block">Late</span>
                        <span class="fw-bold">${todayStats.late}</span>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <span class="text-muted small">Total Today: ${todayStats.total}</span>
                </div>
            `;
            
            $('#attendanceSummary').show();
            $('#summaryContent').html(summaryHtml);
        }

        // ==================== GEOFENCE MODAL FUNCTIONS ====================

        function openCameraModal(type) {
            const modalTitle = type === 'check-in' ? 'Check-In Geolocation' : 'Check-Out Geolocation';
            const actionText = type === 'check-in' ? 'Confirm Check In' : 'Confirm Check Out';

            const modalHtml = `
                <div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${modalTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info mb-3">
                                    <strong>Location verification required.</strong> Your current location must be within your studio attendance geofence before this action can be submitted.
                                </div>
                                <div class="border rounded p-3 bg-light" id="geoAttendanceStatus">
                                    <span class="text-muted">Your location will be requested when you confirm this action.</span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-soft-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmActionBtn" data-type="${type}">
                                    <i class="ti ti-check me-1"></i> ${actionText}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#cameraModal').remove();
            $('body').append(modalHtml);

            const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
            modal.show();

            $('#confirmActionBtn').off('click').on('click', function() {
                submitAttendance($(this).data('type'));
            });
        }

        function submitAttendance(type) {
            if (!navigator.geolocation) {
                updateLocationStatus('#geoAttendanceStatus', 'Geolocation is not supported by this browser.');
                showGeolocationError('Your browser does not support geolocation.');
                return;
            }

            updateLocationStatus('#geoAttendanceStatus', 'Requesting your current location...');
            $('#confirmActionBtn').prop('disabled', true);

            navigator.geolocation.getCurrentPosition(function(position) {
                const formData = new FormData();
                formData.append('latitude', position.coords.latitude);
                formData.append('longitude', position.coords.longitude);

                if (type === 'check-out' && currentAttendanceId) {
                    formData.append('attendance_id', currentAttendanceId);
                }

                updateLocationStatus(
                    '#geoAttendanceStatus',
                    `Location captured: ${position.coords.latitude.toFixed(6)}, ${position.coords.longitude.toFixed(6)}`
                );

                sendAttendanceRequest(type, formData);
            }, function(error) {
                const message = resolveGeolocationErrorMessage(error);
                updateLocationStatus('#geoAttendanceStatus', message);
                $('#confirmActionBtn').prop('disabled', false);
                showGeolocationError(message);
            }, {
                enableHighAccuracy: true,
                timeout: 10000
            });
        }

        function sendAttendanceRequest(type, formData) {
            const url = type === 'check-in' ? '/studio-hr/attendance/check-in' : '/studio-hr/attendance/check-out';

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#cameraModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });

                        loadEmployeeSchedule();
                        loadEmployeeAttendance();
                        loadAttendanceStats();

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred';

                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).flat().join('\n');
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                        confirmButtonColor: '#3475db'
                    });
                },
                complete: function() {
                    $('#confirmActionBtn').prop('disabled', false);
                }
            });
        }

        // ==================== CHECK-IN / CHECK-OUT HANDLERS ====================

        $(document).ready(function() {
            initializeAttendanceMap();
            // Initial load
            loadEmployeeSchedule();
            loadEmployeeAttendance();
            loadAttendanceStats(); // Add this line
            checkScheduleMatch();
            
            // Load stats when Employees Attendance tab is clicked
            $('a[href="#employees-attendance"]').on('shown.bs.tab', function() {
                loadAttendanceStats();
                loadEmployeeAttendance(); // Refresh table data when tab is shown
            });
            
            // Check-in button click
            $('#checkInBtn').on('click', function(e) {
                e.preventDefault();
                
                // Check if button is disabled
                if ($(this).prop('disabled')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Check In',
                        text: $(this).attr('title') || 'You cannot check in at this time',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                openCameraModal('check-in');
            });
            
            // Check-out button click
            $('#checkOutBtn').on('click', function(e) {
                e.preventDefault();
                
                // Check if button is disabled
                if ($(this).prop('disabled')) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Check Out',
                        text: $(this).attr('title') || 'You cannot check out at this time',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                if (!currentAttendanceId) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Check Out',
                        text: 'You have not checked in today',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                openCameraModal('check-out');
            });
            
            // Clean up modal on close
            $('#cameraModal').on('hidden.bs.modal', function() {
                $('#cameraModal').remove();
            });
            
            // Refresh button click
            $('#refreshAttendanceBtn').on('click', function() {
                loadEmployeeAttendance();
                loadAttendanceStats(); // Also refresh stats
            });

            $('#attendanceSearchInput, #employeeAttendanceStatusFilter').on('keyup change', function() {
                loadEmployeeAttendance();
            });

            $('#employeeAttendanceDateFilter').on('change', function() {
                const isCustom = $(this).val() === 'custom';
                $('#employeeAttendanceCustomRange').toggleClass('d-none', !isCustom);
                loadEmployeeAttendance();
            });

            $('#employeeAttendanceDateFrom, #employeeAttendanceDateTo').on('change', function() {
                if ($('#employeeAttendanceDateFilter').val() === 'custom') {
                    loadEmployeeAttendance();
                }
            });

            $('#refreshAttendanceMapBtn').on('click', function() {
                requestCurrentMapLocation();
            });
        });

        function initializeAttendanceMap() {
            attendanceMap = L.map('attendanceMap').setView([14.2820, 120.8660], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(attendanceMap);
        }

        // ==================== ATTENDANCE TABLE FUNCTIONS ====================

        function viewAttendanceDetails(attendanceId) {
            $.ajax({
                url: `/studio-hr/attendance/${attendanceId}/details`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        showAttendanceDetailsModal(response.attendance);
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load attendance details',
                        confirmButtonColor: '#3475db'
                    });
                }
            });
        }

        function showAttendanceDetailsModal(attendance) {
            const modalHtml = `
                <div class="modal fade" id="attendanceDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="ti ti-clipboard-list me-2"></i>
                                    Attendance Details
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <!-- Employee Info Summary -->
                                <div class="bg-light p-3 rounded mb-3">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <span class="text-muted small d-block">Employee</span>
                                            <span class="fw-medium">${attendance.employee_name}</span>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="text-muted small d-block">Date</span>
                                            <span class="fw-medium">${attendance.attendance_date}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tab Navigation -->
                                <ul class="nav nav-tabs mb-3" id="attendanceDetailTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="checkin-tab" data-bs-toggle="tab" 
                                            data-bs-target="#checkin-details" type="button" role="tab">
                                            <i class="ti ti-login me-1"></i> Check In Details
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="checkout-tab" data-bs-toggle="tab" 
                                            data-bs-target="#checkout-details" type="button" role="tab">
                                            <i class="ti ti-logout me-1"></i> Check Out Details
                                        </button>
                                    </li>
                                </ul>
                                
                                <!-- Tab Content -->
                                <div class="tab-content" id="attendanceDetailTabsContent">
                                    <!-- Check In Details Tab -->
                                    <div class="tab-pane fade show active" id="checkin-details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bg-light rounded p-3 h-100">
                                                    <span class="text-muted small d-block mb-2">Check-In Location</span>
                                                    <div><strong>Latitude:</strong> ${attendance.check_in_location?.latitude || 'â€”'}</div>
                                                    <div><strong>Longitude:</strong> ${attendance.check_in_location?.longitude || 'â€”'}</div>
                                                    <div><strong>Distance:</strong> ${formatDistance(attendance.check_in_location?.distance_meters)}</div>
                                                    <div><strong>Location Result:</strong> ${attendance.check_in_location?.status || 'â€”'}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <th class="ps-0 text-muted" width="40%">Check-In Time</th>
                                                        <td class="fw-medium">${attendance.formatted_check_in}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Status</th>
                                                        <td>
                                                            ${attendance.check_in_status ? 
                                                                `<span class="badge ${attendance.check_in_status === 'ON_TIME' ? 'badge-soft-success' : 'badge-soft-warning'}">${attendance.check_in_status}</span>` : 
                                                                '—'
                                                            }
                                                            ${attendance.late_display ? `<small class="d-block text-muted">${attendance.late_display}</small>` : ''}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Scheduled Start</th>
                                                        <td>${attendance.scheduled_start_time || '—'}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Check-In IP</th>
                                                        <td><span class="text-muted small">${attendance.check_in_ip || '—'}</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Check Out Details Tab -->
                                    <div class="tab-pane fade" id="checkout-details" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="bg-light rounded p-3 h-100">
                                                    <span class="text-muted small d-block mb-2">Check-Out Location</span>
                                                    <div><strong>Latitude:</strong> ${attendance.check_out_location?.latitude || 'â€”'}</div>
                                                    <div><strong>Longitude:</strong> ${attendance.check_out_location?.longitude || 'â€”'}</div>
                                                    <div><strong>Distance:</strong> ${formatDistance(attendance.check_out_location?.distance_meters)}</div>
                                                    <div><strong>Location Result:</strong> ${attendance.check_out_location?.status || 'â€”'}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-borderless">
                                                    <tr>
                                                        <th class="ps-0 text-muted" width="40%">Check-Out Time</th>
                                                        <td class="fw-medium">${attendance.formatted_check_out || '—'}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Status</th>
                                                        <td>
                                                            ${attendance.check_out_status ? 
                                                                `<span class="badge ${attendance.check_out_status === 'UNDERTIME' ? 'badge-soft-danger' : 'badge-soft-success'}">${attendance.check_out_status}</span>` : 
                                                                '—'
                                                            }
                                                            ${attendance.undertime_display ? `<small class="d-block text-muted">${attendance.undertime_display}</small>` : ''}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Scheduled End</th>
                                                        <td>${attendance.scheduled_end_time || '—'}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="ps-0 text-muted">Check-Out IP</th>
                                                        <td><span class="text-muted small">${attendance.check_out_ip || '—'}</span></td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Notes Section (if exists) -->
                                ${attendance.notes ? `
                                <div class="mt-3 p-3 bg-light rounded">
                                    <span class="text-muted small d-block mb-1">Notes</span>
                                    <p class="mb-0">${attendance.notes}</p>
                                </div>
                                ` : ''}
                                
                                <!-- Duration Summary -->
                                <div class="mt-3 text-center">
                                    <span class="badge badge-soft-info p-2">
                                        <i class="ti ti-clock me-1"></i>
                                        Total Hours: ${attendance.duration || '—'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#attendanceDetailsModal').remove();
            $('body').append(modalHtml);
            
            const modal = new bootstrap.Modal(document.getElementById('attendanceDetailsModal'));
            modal.show();
        }

        // ==================== MY ATTENDANCE TABLE FILTERS ====================

        $(document).ready(function() {
            // Filter functionality for personal attendance table
            $('#myAttendanceSearch, #filterMonth, #filterYear, #filterStatus').on('change keyup', function() {
                filterMyAttendance();
            });
        });

        function filterMyAttendance() {
            const searchTerm = $('#myAttendanceSearch').val().toLowerCase();
            const month = $('#filterMonth').val();
            const year = $('#filterYear').val();
            const status = $('#filterStatus').val();
            
            $('#myAttendanceTable tbody tr').each(function() {
                const $row = $(this);
                const rowText = $row.text().toLowerCase();
                const rowDate = $row.find('td:first').text().trim();
                
                let showRow = true;
                
                // Search filter
                if (searchTerm && !rowText.includes(searchTerm)) {
                    showRow = false;
                }
                
                // Month filter
                if (month) {
                    const rowMonth = extractMonthFromDate(rowDate);
                    if (rowMonth !== month) {
                        showRow = false;
                    }
                }
                
                // Year filter
                if (year) {
                    const rowYear = extractYearFromDate(rowDate);
                    if (rowYear !== year) {
                        showRow = false;
                    }
                }
                
                // Status filter
                if (status) {
                    const hasStatus = $row.find('.badge').filter(function() {
                        return $(this).text().includes(status.replace('_', ' '));
                    }).length > 0;
                    
                    if (!hasStatus) {
                        showRow = false;
                    }
                }
                
                $row.toggle(showRow);
            });
            
            // Update the "showing X to Y of Z records" text
            updatePaginationInfo();
        }

        function extractMonthFromDate(dateStr) {
            const months = {
                'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
                'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
                'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
            };
            
            for (const [monthName, monthNum] of Object.entries(months)) {
                if (dateStr.includes(monthName)) {
                    return monthNum;
                }
            }
            return '';
        }

        function extractYearFromDate(dateStr) {
            const match = dateStr.match(/\d{4}/);
            return match ? match[0] : '';
        }

        function updatePaginationInfo() {
            const visibleRows = $('#myAttendanceTable tbody tr:visible').length;
            const totalRows = $('#myAttendanceTable tbody tr').length;
            
            if (totalRows === 0) return;
            
            let firstItem = 0;
            let lastItem = 0;
            
            $('#myAttendanceTable tbody tr').each(function(index) {
                if ($(this).is(':visible')) {
                    if (firstItem === 0) firstItem = index + 1;
                    lastItem = index + 1;
                }
            });
            
            if (firstItem === 0) {
                $('[data-table-pagination-info="my-attendance"]').text('Showing 0 records');
            } else {
                $('[data-table-pagination-info="my-attendance"]').text(
                    `Showing ${firstItem} to ${lastItem} of ${totalRows} records`
                );
            }
        }

        function formatGeofenceText(geofence) {
            if (!geofence || geofence.is_configured !== true) {
                return 'Not configured yet';
            }

            return `${geofence.radius_meters} meters from the saved studio pin`;
        }

        function updateLocationStatus(selector, message) {
            $(selector).html(`<span class="text-muted">${message}</span>`);
        }

        function resolveGeolocationErrorMessage(error) {
            if (!error) {
                return 'Unable to get your current location.';
            }

            if (error.code === 1) {
                return 'Location permission was denied. Please allow geolocation and try again.';
            }

            if (error.code === 2) {
                return 'Your current location could not be determined.';
            }

            if (error.code === 3) {
                return 'Location request timed out. Please try again.';
            }

            return 'Unable to get your current location.';
        }

        function showGeolocationError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Geolocation Required',
                text: message,
                confirmButtonColor: '#3475db'
            });
        }

        function formatDistance(distance) {
            return distance !== null && distance !== undefined && distance !== '' ? `${distance} meters` : 'â€”';
        }
    </script>
@endsection
