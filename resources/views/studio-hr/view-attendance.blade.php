@extends('layouts.studio-hr.app')
@section('title', 'Employee Attendance')

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
                                            <div class="alert alert-warning p-3 mb-4" role="alert">
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
                                            <div class="alert alert-warning p-3 mb-4" role="alert">
                                                <h4 class="alert-heading">
                                                    <i class="ti ti-alert-triangle me-2"></i>
                                                    No Schedule Found
                                                </h4>
                                                <p class="mb-0">You don't have an active work schedule set. Please contact your administrator.</p>
                                            </div>
                                        @endif

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
                                                                <tr>
                                                                    <td>{{ $record->attendance_date->format('M d, Y') }}</td>
                                                                    <td>
                                                                        @if($record->scheduled_start_time && $record->scheduled_end_time)
                                                                            {{ \Carbon\Carbon::parse($record->scheduled_start_time)->format('h:i A') }} - 
                                                                            {{ \Carbon\Carbon::parse($record->scheduled_end_time)->format('h:i A') }}
                                                                        @else
                                                                            <span class="text-muted">No schedule</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $record->formatted_check_in }}</td>
                                                                    <td>
                                                                        @if($record->check_in_status)
                                                                            <span class="badge {{ $record->check_in_status_badge }}">
                                                                                {{ $record->check_in_status }}
                                                                            </span>
                                                                            @if($record->late_minutes > 0)
                                                                                <small class="d-block text-muted">{{ $record->late_display }}</small>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $record->formatted_check_out }}</td>
                                                                    <td>
                                                                        @if($record->check_out_status)
                                                                            <span class="badge {{ $record->check_out_status_badge }}">
                                                                                {{ $record->check_out_status }}
                                                                            </span>
                                                                            @if($record->undertime_minutes > 0)
                                                                                <small class="d-block text-muted">{{ $record->undertime_display }}</small>
                                                                            @endif
                                                                        @else
                                                                            <span class="text-muted">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @if($record->duration)
                                                                            <span class="badge badge-soft-info">{{ $record->duration }}</span>
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
                                                        <select class="me-0 form-select form-control" id="filterDate">
                                                            <option value="today">Today</option>
                                                            <option value="yesterday">Yesterday</option>
                                                            <option value="this-week">This Week</option>
                                                            <option value="this-month">This Month</option>
                                                            <option value="custom">Custom Range</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select class="me-0 form-select form-control" id="filterStatus">
                                                            <option value="">All Status</option>
                                                            <option value="ON_TIME">On Time</option>
                                                            <option value="LATE">Late</option>
                                                            <option value="UNDERTIME">Undertime</option>
                                                        </select>
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
    <script>
        // ==================== ATTENDANCE MODULE ====================

        // Global variables
        let cameraStream = null;
        let currentAttendanceId = null;

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
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load schedule:', xhr);
                }
            });
        }

        function loadTodaysAttendance() {
            $.ajax({
                url: '/studio-hr/attendance/today',
                type: 'GET',
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
                            <span class="text-muted">No attendance records for today</span>
                        </td>
                    </tr>
                `);
                return;
            }
            
            attendance.forEach(function(record) {
                const checkInStatusClass = record.check_in_status === 'ON_TIME' ? 'badge-soft-success' : 'badge-soft-warning';
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
                            <button class="btn btn-sm" onclick="viewAttendanceDetails(${record.id})">
                                <i class="ti ti-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }

        function updateButtonStates(data) {
            const checkInBtn = $('#checkInBtn');
            const checkOutBtn = $('#checkOutBtn');
            
            // Update check-in button state
            if (data.has_schedule && !data.is_checked_in) {
                checkInBtn.prop('disabled', false).removeClass('disabled');
            } else {
                checkInBtn.prop('disabled', true).addClass('disabled');
                
                if (!data.has_schedule) {
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
            if (!data.is_checked_in || data.is_checked_out) {
                checkOutBtn.prop('disabled', true).addClass('disabled');
                
                if (!data.is_checked_in) {
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

        // ==================== CAMERA MODAL FUNCTIONS ====================

        async function openCameraModal(type) {
            const modalTitle = type === 'check-in' ? 'Check-In Photo' : 'Check-Out Photo';
            const actionText = type === 'check-in' ? 'Check In' : 'Check Out';
            
            const modalHtml = `
                <div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${modalTitle}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <div class="camera-container bg-light rounded d-flex align-items-center justify-content-center" style="min-height: 300px; width: 100%;">
                                        <video id="cameraPreview" autoplay playsinline class="img-fluid w-100 rounded" style="max-height: 300px; object-fit: cover;"></video>
                                        <canvas id="photoCanvas" style="display: none;"></canvas>
                                        <img id="photoPreview" style="display: none; width: 100%; max-height: 300px; object-fit: contain;" class="img-fluid rounded">
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-center gap-2">
                                    <button type="button" class="btn btn-primary" id="capturePhotoBtn">
                                        <i class="ti ti-camera me-1"></i> Capture Photo
                                    </button>
                                    <button type="button" class="btn btn-primary w-100" id="retakePhotoBtn" style="display: none;">
                                        <i class="ti ti-refresh me-1"></i> Retake
                                    </button>
                                </div>
                                
                                <div id="photoLoading" class="text-center mt-3" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-soft-primary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="confirmActionBtn" data-type="${type}" style="display: none;">
                                    <i class="ti ti-check me-1"></i> ${actionText}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('#cameraModal').remove();
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('cameraModal'));
            modal.show();
            
            // Initialize camera
            await initializeCamera();
            
            // Handle capture button
            $('#capturePhotoBtn').off('click').on('click', function() {
                capturePhoto();
            });
            
            // Handle retake button
            $('#retakePhotoBtn').off('click').on('click', function() {
                $('#photoPreview').hide();
                $('#cameraPreview').show();
                $('#capturePhotoBtn').show();
                $('#retakePhotoBtn').hide();
                $('#confirmActionBtn').hide();
            });
            
            // Handle confirm button
            $('#confirmActionBtn').off('click').on('click', function() {
                const type = $(this).data('type');
                submitAttendance(type);
            });
        }

        async function initializeCamera() {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }, 
                    audio: false 
                });
                
                const video = document.getElementById('cameraPreview');
                video.srcObject = cameraStream;
            } catch (error) {
                console.error('Camera error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Camera Error',
                    text: 'Unable to access camera. Please ensure camera permissions are granted.',
                    confirmButtonColor: '#3475db'
                });
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraPreview');
            const canvas = document.getElementById('photoCanvas');
            const context = canvas.getContext('2d');
            
            // Set canvas dimensions to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Draw video frame to canvas
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Convert to data URL
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            
            // Show preview with full width
            const photoPreview = document.getElementById('photoPreview');
            photoPreview.src = imageData;
            photoPreview.style.display = 'block';
            photoPreview.style.width = '100%';
            photoPreview.style.maxHeight = '300px';
            photoPreview.style.objectFit = 'contain';
            
            // Hide video
            video.style.display = 'none';
            
            // Update buttons
            $('#capturePhotoBtn').hide();
            $('#retakePhotoBtn').show();
            $('#confirmActionBtn').show();
            
            // Stop camera stream
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
        }

        function dataURLtoFile(dataURL, filename) {
            try {
                // Handle different data URL formats
                if (!dataURL || dataURL === '#') {
                    throw new Error('Invalid image data');
                }
                
                // Check if it's a valid data URL
                if (!dataURL.startsWith('data:')) {
                    throw new Error('Invalid data URL format');
                }
                
                const arr = dataURL.split(',');
                
                // Check if we have both parts
                if (arr.length < 2) {
                    throw new Error('Invalid data URL structure');
                }
                
                // Extract mime type
                const mimeMatch = arr[0].match(/:(.*?);/);
                if (!mimeMatch || mimeMatch.length < 2) {
                    // Default to JPEG if can't extract
                    console.warn('Could not extract mime type, using image/jpeg');
                    var mime = 'image/jpeg';
                } else {
                    var mime = mimeMatch[1];
                }
                
                // Decode base64 data
                const bstr = atob(arr[1]);
                let n = bstr.length;
                const u8arr = new Uint8Array(n);
                
                while (n--) {
                    u8arr[n] = bstr.charCodeAt(n);
                }
                
                return new File([u8arr], filename, { type: mime });
                
            } catch (error) {
                console.error('Error converting data URL to file:', error);
                
                // Return a fallback - this will be handled by the calling function
                Swal.fire({
                    icon: 'error',
                    title: 'Image Error',
                    text: 'Failed to process the captured image. Please try again.',
                    confirmButtonColor: '#3475db'
                });
                
                throw error; // Re-throw to be caught by submitAttendance
            }
        }

        function submitAttendance(type) {
            const photoPreview = document.getElementById('photoPreview');
            
            if (!photoPreview.src || photoPreview.src === '#' || photoPreview.src === '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Photo',
                    text: 'Please capture a photo first.',
                    confirmButtonColor: '#3475db'
                });
                return;
            }
            
            // Show loading
            $('#photoLoading').show();
            $('#confirmActionBtn').prop('disabled', true);
            
            try {
                // Convert data URL to file
                const imageFile = dataURLtoFile(photoPreview.src, `attendance-${type}-${Date.now()}.jpg`);
                
                const formData = new FormData();
                formData.append('image', imageFile);
                
                if (type === 'check-out' && currentAttendanceId) {
                    formData.append('attendance_id', currentAttendanceId);
                }
                
                // Add notes if any
                const notes = prompt('Add notes (optional):');
                if (notes !== null) {
                    formData.append('notes', notes);
                }
                
                // Add location if available
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        formData.append('latitude', position.coords.latitude);
                        formData.append('longitude', position.coords.longitude);
                        sendAttendanceRequest(type, formData);
                    }, function(error) {
                        console.warn('Geolocation error:', error);
                        sendAttendanceRequest(type, formData);
                    });
                } else {
                    sendAttendanceRequest(type, formData);
                }
                
            } catch (error) {
                console.error('Error in submitAttendance:', error);
                $('#photoLoading').hide();
                $('#confirmActionBtn').prop('disabled', false);
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to process the image. Please try again.',
                    confirmButtonColor: '#3475db'
                });
            }
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
                        
                        // Reload data
                        loadEmployeeSchedule();
                        loadTodaysAttendance();
                        
                        // Reload the page to show updated personal attendance
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
                    $('#photoLoading').hide();
                    $('#confirmActionBtn').prop('disabled', false);
                }
            });
        }

        // ==================== CHECK-IN / CHECK-OUT HANDLERS ====================

        $(document).ready(function() {
            // Initial load
            loadEmployeeSchedule();
            loadTodaysAttendance();
            loadAttendanceStats(); // Add this line
            checkScheduleMatch();
            
            // Load stats when Employees Attendance tab is clicked
            $('a[href="#employees-attendance"]').on('shown.bs.tab', function() {
                loadAttendanceStats();
                loadTodaysAttendance(); // Refresh table data when tab is shown
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
            
            // Clean up camera on modal close
            $('#cameraModal').on('hidden.bs.modal', function() {
                if (cameraStream) {
                    cameraStream.getTracks().forEach(track => track.stop());
                    cameraStream = null;
                }
            });
            
            // Refresh button click
            $('#refreshAttendanceBtn').on('click', function() {
                loadTodaysAttendance();
                loadAttendanceStats(); // Also refresh stats
            });
        });

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
            // Format check-in image HTML
            const checkInImageHtml = attendance.check_in_image 
                ? `<img src="/storage/${attendance.check_in_image}" class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: contain;">` 
                : '<div class="bg-light rounded p-4 text-center"><i class="ti ti-camera-off fs-1 d-block mb-2 text-muted"></i><span class="text-muted">No check-in photo</span></div>';
            
            // Format check-out image HTML
            const checkOutImageHtml = attendance.check_out_image 
                ? `<img src="/storage/${attendance.check_out_image}" class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: contain;">` 
                : '<div class="bg-light rounded p-4 text-center"><i class="ti ti-camera-off fs-1 d-block mb-2 text-muted"></i><span class="text-muted">No check-out photo</span></div>';
            
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
                                                <div class="text-center mb-3">
                                                    <span class="text-muted small d-block mb-2">Check-In Photo</span>
                                                    ${checkInImageHtml}
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
                                                <div class="text-center mb-3">
                                                    <span class="text-muted small d-block mb-2">Check-Out Photo</span>
                                                    ${checkOutImageHtml}
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
    </script>
@endsection