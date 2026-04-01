@extends('layouts.studio-photographer.app')
@section('title', 'Photographer Attendance')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3 g-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-1">Photographer Attendance</h5>
                                <p class="text-muted mb-0">Check in, check out, and monitor your own attendance records.</p>
                            </div>
                            <span class="badge bg-soft-primary p-2">
                                <i class="ti ti-info-circle me-1"></i>
                                Grace Period: 15 minutes
                            </span>
                        </div>

                        @php
                            $monthTotal = max($attendanceStats['month']['total'], 1);
                            $latePercentage = (int) round(($attendanceStats['month']['late'] / $monthTotal) * 100);
                            $undertimePercentage = (int) round(($attendanceStats['month']['undertime'] / $monthTotal) * 100);
                            $completedPercentage = (int) round(($attendanceStats['month']['completed'] / $monthTotal) * 100);
                            $onTimeCount = max($attendanceStats['month']['total'] - $attendanceStats['month']['late'], 0);
                            $onTimePercentage = (int) round(($onTimeCount / $monthTotal) * 100);
                        @endphp

                        <ul class="nav nav-tabs mt-2">
                            <li class="nav-item">
                                <a href="#photographer-check-in-check-out" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                    <i class="ti ti-clock-check me-1"></i> Check-In / Check-Out
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#photographer-attendance-overview" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                    <i class="ti ti-calendar-stats me-1"></i> Attendance Overview
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane show active p-3" id="photographer-check-in-check-out">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label for="studioSelector" class="form-label">Assigned Studio</label>
                                        <select class="form-select" id="studioSelector">
                                            <option value="">Select Assigned Studio</option>
                                            @forelse($assignedStudios as $assignedStudio)
                                                <option value="{{ $assignedStudio->studio_id }}" {{ (string) $defaultStudioId === (string) $assignedStudio->studio_id ? 'selected' : '' }}>
                                                    {{ $assignedStudio->studio->studio_name ?? 'Unknown Studio' }}
                                                </option>
                                            @empty
                                                <option value="">No assigned studio found</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-center border rounded py-3 h-100">
                                            <h1 class="display-6 mb-0" id="liveClock">--:--:-- --</h1>
                                            <p class="text-muted mb-0" id="liveDate">---</p>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100" id="todayStatusCard">
                                            <span class="text-muted d-block mb-1">Today Status</span>
                                            <h5 class="mb-1" id="todayStatusTitle">Waiting for selection</h5>
                                            <p class="mb-0 text-muted" id="todayStatusText">Choose an assigned studio to load your attendance status.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning mt-3 mb-0" role="alert" id="scheduleAlert">
                                    @if($scheduleInfo)
                                        <h4 class="alert-heading">
                                            <i class="ti ti-calendar-clock me-2"></i>
                                            {{ $scheduleInfo['studio_name'] }} Schedule
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
                                        <p class="mb-0 text-muted">Please select your assigned studio before checking in or checking out.</p>
                                    @else
                                        <h4 class="alert-heading">
                                            <i class="ti ti-alert-triangle me-2"></i>
                                            No Assigned Studio Found
                                        </h4>
                                        <p class="mb-0">You currently do not have an active assigned studio for attendance.</p>
                                    @endif
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <button class="btn btn-primary w-100" id="openCheckInModalBtn" disabled>
                                            <i class="ti ti-login me-1"></i>
                                            Check-In
                                        </button>
                                    </div>
                                    <div class="col-md-6">
                                        <button class="btn btn-danger w-100" id="openCheckOutModalBtn" disabled>
                                            <i class="ti ti-logout me-1"></i>
                                            Check-Out
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-3 mt-1">
                                    <div class="col-md-6 col-xl-3">
                                        <div class="card bg-light border-0 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="text-muted small d-block">This Month</span>
                                                        <h4 class="mb-1">{{ $attendanceStats['month']['total'] }}</h4>
                                                        <span class="text-muted small">Attendance records</span>
                                                    </div>
                                                    <span class="badge badge-soft-primary p-2">
                                                        <i class="ti ti-calendar-event"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="card bg-light border-0 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="text-muted small d-block">On Time</span>
                                                        <h4 class="mb-1">{{ $onTimeCount }}</h4>
                                                        <span class="text-success small">{{ $onTimePercentage }}% of monthly records</span>
                                                    </div>
                                                    <span class="badge badge-soft-success p-2">
                                                        <i class="ti ti-circle-check"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="card bg-light border-0 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="text-muted small d-block">Late Records</span>
                                                        <h4 class="mb-1">{{ $attendanceStats['month']['late'] }}</h4>
                                                        <span class="text-warning small">{{ $latePercentage }}% of monthly records</span>
                                                    </div>
                                                    <span class="badge badge-soft-warning p-2">
                                                        <i class="ti ti-clock-exclamation"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-3">
                                        <div class="card bg-light border-0 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <span class="text-muted small d-block">Undertime</span>
                                                        <h4 class="mb-1">{{ $attendanceStats['month']['undertime'] }}</h4>
                                                        <span class="text-danger small">{{ $undertimePercentage }}% of monthly records</span>
                                                    </div>
                                                    <span class="badge badge-soft-danger p-2">
                                                        <i class="ti ti-alert-triangle"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane p-3" id="photographer-attendance-overview">
                                <div class="row g-3">
                                    <div class="col-xl-4">
                                        <div class="card border-0 bg-light mb-0 h-100">
                                            <div class="card-body">
                                                <span class="text-muted small d-block mb-2">Monthly Completion</span>
                                                <h3 class="mb-1">{{ $attendanceStats['month']['completed'] }}</h3>
                                                <p class="text-muted mb-3">Completed check-outs this month</p>
                                                <div class="progress progress-sm">
                                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $completedPercentage }}%"></div>
                                                </div>
                                                <small class="text-muted d-block mt-2">{{ $completedPercentage }}% completion rate</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card border-0 bg-light mb-0 h-100">
                                            <div class="card-body">
                                                <span class="text-muted small d-block mb-2">Today Snapshot</span>
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <span>Checked In</span>
                                                    <span class="badge badge-soft-primary">{{ $attendanceStats['today']['checked_in'] }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span>Checked Out</span>
                                                    <span class="badge badge-soft-success">{{ $attendanceStats['today']['checked_out'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4">
                                        <div class="card border-0 bg-light mb-0 h-100">
                                            <div class="card-body">
                                                <span class="text-muted small d-block mb-2">Attendance Quality</span>
                                                <div class="mb-3">
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span>On Time</span>
                                                        <span>{{ $onTimePercentage }}%</span>
                                                    </div>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $onTimePercentage }}%"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="d-flex justify-content-between small mb-1">
                                                        <span>Late / Undertime</span>
                                                        <span>{{ min($latePercentage + $undertimePercentage, 100) }}%</span>
                                                    </div>
                                                    <div class="progress progress-sm">
                                                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ min($latePercentage + $undertimePercentage, 100) }}%"></div>
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

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="card-title mb-1">My Attendance History</h5>
                                <p class="text-muted mb-0">Only your own attendance records are shown here.</p>
                            </div>
                            <span class="badge badge-soft-primary p-1">Total Records: {{ $myAttendance->total() }}</span>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input type="search" class="form-control" placeholder="Search attendance..." id="attendanceSearch">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select class="form-select form-control" id="attendanceStatusFilter">
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
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="attendanceHistoryTable">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Date</th>
                                        <th>Studio</th>
                                        <th>Scheduled Time</th>
                                        <th>Check-In</th>
                                        <th>Status (In)</th>
                                        <th>Check-Out</th>
                                        <th>Status (Out)</th>
                                        <th>Total Hours</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($myAttendance as $record)
                                        @php
                                            $isLeaveRecord = ($record->record_type ?? 'attendance') === 'leave';
                                        @endphp
                                        <tr data-record-type="{{ $record->record_type ?? 'attendance' }}">
                                            <td>{{ $record->attendance_date?->format('M d, Y') }}</td>
                                            <td>{{ $record->studio->studio_name ?? 'N/A' }}</td>
                                            <td>
                                                @if($record->scheduled_start_time && $record->scheduled_end_time)
                                                    {{ \Carbon\Carbon::parse($record->scheduled_start_time)->format('h:i A') }} -
                                                    {{ \Carbon\Carbon::parse($record->scheduled_end_time)->format('h:i A') }}
                                                @else
                                                    <span class="text-muted">{{ $isLeaveRecord ? 'Approved Leave' : 'No schedule' }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $record->formatted_check_in }}</td>
                                            <td>
                                                @if($record->check_in_status)
                                                    <span class="badge {{ $isLeaveRecord ? 'badge-soft-info' : $record->check_in_status_badge }}">{{ $record->check_in_status }}</span>
                                                    @if(!$isLeaveRecord && $record->late_minutes > 0)
                                                        <small class="d-block text-muted">{{ $record->late_display }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>{{ $record->display_check_out ?? $record->formatted_check_out }}</td>
                                            <td>
                                                @if($record->check_out_status)
                                                    <span class="badge {{ $record->check_out_status_badge }}">{{ $record->check_out_status }}</span>
                                                    @if($record->undertime_minutes > 0)
                                                        <small class="d-block text-muted">{{ $record->undertime_display }}</small>
                                                    @endif
                                                    @if(($record->is_overtime_applied ?? false) === true)
                                                        <small class="d-block text-primary">OT Applied until {{ $record->counted_check_out }}</small>
                                                    @endif
                                                @elseif($isLeaveRecord)
                                                    <span class="text-muted">Leave Day</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($record->display_duration ?? $record->duration)
                                                    <span class="badge badge-soft-info">{{ $record->display_duration ?? $record->duration }}</span>
                                                    @if(($record->is_overtime_applied ?? false) === true && ($record->actual_duration ?? null) !== ($record->display_duration ?? $record->duration))
                                                        <small class="d-block text-muted">Actual: {{ $record->actual_duration }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if(!$isLeaveRecord)
                                                    <button class="btn btn-sm btn-light view-attendance-details-btn" data-attendance-id="{{ $record->id }}">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                @else
                                                    <span class="text-muted">View Only</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-4">
                                                <i class="ti ti-clock-off fs-1 d-block mb-2 text-muted"></i>
                                                <span class="text-muted">No attendance records found</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    Showing {{ $myAttendance->firstItem() ?? 0 }} to {{ $myAttendance->lastItem() ?? 0 }} of {{ $myAttendance->total() }} records
                                </div>
                                <div>
                                    {{ $myAttendance->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="checkInModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photographer Check-In</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="checkInForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="studio_id" id="checkInStudioId">

                        <div class="mb-3">
                            <label for="checkInImage" class="form-label">Check-In Photo</label>
                            <input type="file" class="form-control" id="checkInImage" name="image" accept="image/*" capture="user" required>
                            <small class="text-muted">Take or upload a clear photo before checking in.</small>
                        </div>

                        <div class="mb-0">
                            <label for="checkInNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="checkInNotes" name="notes" rows="3" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Submit Check-In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="checkOutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Photographer Check-Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="checkOutForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="attendance_id" id="checkOutAttendanceId">

                        <div class="mb-3">
                            <label for="checkOutImage" class="form-label">Check-Out Photo</label>
                            <input type="file" class="form-control" id="checkOutImage" name="image" accept="image/*" capture="user">
                            <small class="text-muted">Photo is optional during check-out.</small>
                        </div>

                        <div class="mb-0">
                            <label for="checkOutNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="checkOutNotes" name="notes" rows="3" placeholder="Optional notes"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-danger">Submit Check-Out</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Attendance Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="attendanceDetailsContent">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        let currentAttendanceId = null;

        // ==================== CSRF SETUP ====================

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // ==================== PAGE LOAD ====================

        $(document).ready(function () {
            loadCurrentTime();
            setInterval(loadCurrentTime, 1000);

            initializeAttendancePage();

            $('#studioSelector').on('change', function () {
                initializeAttendancePage();
            });

            $('#openCheckInModalBtn').on('click', function () {
                $('#checkInStudioId').val($('#studioSelector').val());
                $('#checkInModal').modal('show');
            });

            $('#openCheckOutModalBtn').on('click', function () {
                $('#checkOutAttendanceId').val(currentAttendanceId);
                $('#checkOutModal').modal('show');
            });

            $('#checkInForm').on('submit', function (event) {
                event.preventDefault();
                submitAttendanceForm($(this), '{{ route('studio-photographer.attendance.check-in') }}', '#checkInModal');
            });

            $('#checkOutForm').on('submit', function (event) {
                event.preventDefault();
                submitAttendanceForm($(this), '{{ route('studio-photographer.attendance.check-out') }}', '#checkOutModal');
            });

            $('#attendanceSearch, #attendanceStatusFilter').on('keyup change', function () {
                filterAttendanceTable();
            });

            $(document).on('click', '.view-attendance-details-btn', function () {
                viewAttendanceDetails($(this).data('attendance-id'));
            });
        });

        // ==================== LIVE CLOCK ====================

        function loadCurrentTime() {
            $.ajax({
                url: '{{ route('studio-photographer.attendance.current-time') }}',
                type: 'GET',
                success: function (response) {
                    $('#liveClock').text(response.time);
                    $('#liveDate').text(response.date);
                }
            });
        }

        // ==================== SCHEDULE LOAD ====================

        function initializeAttendancePage() {
            const studioId = $('#studioSelector').val();

            if (!studioId) {
                currentAttendanceId = null;
                updateActionButtons({
                    has_schedule: false,
                    is_checked_in: false,
                    is_checked_out: false,
                    blocked_by_leave: false
                });
                $('#todayStatusTitle').text('No studio selected');
                $('#todayStatusText').text('Select one of your assigned studios to continue.');
                return;
            }

            loadPhotographerSchedule(studioId);
        }

        function loadPhotographerSchedule(studioId) {
            $.ajax({
                url: '{{ route('studio-photographer.attendance.schedule') }}',
                type: 'GET',
                data: {
                    studio_id: studioId
                },
                success: function (response) {
                    currentAttendanceId = response.today_attendance_id;
                    renderScheduleAlert(response);
                    renderTodayStatus(response);
                    updateActionButtons(response);
                },
                error: function (xhr) {
                    currentAttendanceId = null;
                    updateActionButtons({
                        has_schedule: false,
                        is_checked_in: false,
                        is_checked_out: false,
                        blocked_by_leave: false
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Schedule Error',
                        text: xhr.responseJSON?.message || 'Failed to load your studio schedule.',
                        confirmButtonColor: '#3475db'
                    });
                }
            });
        }

        function renderScheduleAlert(response) {
            if (response.blocked_by_leave && response.leave_summary) {
                $('#scheduleAlert').html(`
                    <h4 class="alert-heading">
                        <i class="ti ti-beach me-2"></i>
                        Approved Leave for Today
                    </h4>
                    <p class="mb-2"><strong>Leave Type:</strong> ${response.leave_summary.leave_type}</p>
                    <p class="mb-2"><strong>Covered Dates:</strong> ${response.leave_summary.start_date} - ${response.leave_summary.end_date}</p>
                    <hr class="border-warning border-opacity-25">
                    <p class="mb-0 text-muted">${response.blocked_message}</p>
                `);
                return;
            }

            if (response.has_approved_overtime && response.overtime_summary) {
                $('#scheduleAlert').html(`
                    <h4 class="alert-heading">
                        <i class="ti ti-clock-plus me-2"></i>
                        Approved Overtime for Today
                    </h4>
                    <p class="mb-2"><strong>Approved Window:</strong> ${response.overtime_summary.time_range}</p>
                    <p class="mb-2"><strong>Effective Check-Out Cutoff:</strong> ${response.overtime_summary.effective_checkout_cutoff || 'N/A'}</p>
                    <hr class="border-warning border-opacity-25">
                    <p class="mb-0 text-muted">Your attendance can count overtime only up to the approved cutoff.</p>
                `);
                return;
            }

            const schedule = response.schedule || {};
            const operatingDays = Array.isArray(schedule.operating_days) && schedule.operating_days.length
                ? schedule.operating_days.map(day => day.charAt(0).toUpperCase() + day.slice(1)).join(', ')
                : 'Not set';
            const startTime = schedule.start_time ? formatTime(schedule.start_time) : 'Not set';
            const endTime = schedule.end_time ? formatTime(schedule.end_time) : 'Not set';
            const message = response.has_schedule
                ? 'Today is part of this studio schedule.'
                : 'Today is not part of this studio schedule, but your attendance history remains visible.';

            $('#scheduleAlert').html(`
                <h4 class="alert-heading">
                    <i class="ti ti-calendar-clock me-2"></i>
                    ${response.studio_name} Schedule
                </h4>
                <p class="mb-2"><strong>Operating Days:</strong> ${operatingDays}</p>
                <p class="mb-2"><strong>Working Hours:</strong> ${startTime} - ${endTime}</p>
                <hr class="border-warning border-opacity-25">
                <p class="mb-0 text-muted">${message}</p>
            `);
        }

        function renderTodayStatus(response) {
            if (response.blocked_by_leave) {
                $('#todayStatusTitle').text('On Leave');
                $('#todayStatusText').text(response.blocked_message);
                return;
            }

            if (response.has_approved_overtime && !response.is_checked_out) {
                $('#todayStatusTitle').text(response.is_checked_in ? 'Overtime Active' : 'Overtime Ready');
                $('#todayStatusText').text(`Approved overtime is active today up to ${response.overtime_summary?.effective_checkout_cutoff || 'the approved cutoff'}.`);
                if (response.is_checked_in) {
                    return;
                }
            }

            if (response.is_checked_in && response.is_checked_out) {
                $('#todayStatusTitle').text('Completed');
                $('#todayStatusText').text('You already checked in and checked out today.');
                return;
            }

            if (response.is_checked_in) {
                $('#todayStatusTitle').text('Checked In');
                $('#todayStatusText').text('You can now complete your check-out for today.');
                return;
            }

            $('#todayStatusTitle').text('Ready');
            $('#todayStatusText').text('You can submit your attendance for the selected studio.');
        }

        function updateActionButtons(response) {
            const isBlocked = response.blocked_by_leave === true;
            $('#openCheckInModalBtn').prop('disabled', isBlocked || !response.has_schedule || response.is_checked_in);
            $('#openCheckOutModalBtn').prop('disabled', isBlocked || !response.is_checked_in || response.is_checked_out);
        }

        // ==================== FORM SUBMIT ====================

        function submitAttendanceForm(formElement, url, modalId) {
            const formData = new FormData(formElement[0]);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $(formElement).find('button[type="submit"]').prop('disabled', true);
                },
                success: function (response) {
                    $(modalId).modal('hide');
                    formElement[0].reset();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function () {
                        window.location.reload();
                    });
                },
                error: function (xhr) {
                    showAttendanceError(xhr);
                },
                complete: function () {
                    $(formElement).find('button[type="submit"]').prop('disabled', false);
                }
            });
        }

        function showAttendanceError(xhr) {
            let message = xhr.responseJSON?.message || 'Something went wrong while processing attendance.';

            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat().join('\n');
                message = errors || message;
            }

            Swal.fire({
                icon: 'error',
                title: 'Attendance Error',
                text: message,
                confirmButtonColor: '#3475db'
            });
        }

        // ==================== DETAILS MODAL ====================

        function viewAttendanceDetails(attendanceId) {
            $('#attendanceDetailsContent').html(`
                <div class="d-flex justify-content-center align-items-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `);

            $('#attendanceDetailsModal').modal('show');

            $.ajax({
                url: `/studio-photographer/attendance/${attendanceId}/details`,
                type: 'GET',
                success: function (response) {
                    const attendance = response.attendance;
                    const checkInImageHtml = attendance.check_in_image
                        ? `<img src="/storage/${attendance.check_in_image}" class="img-fluid rounded" style="max-height: 220px; width: 100%; object-fit: contain;">`
                        : '<div class="bg-light rounded p-4 text-center text-muted">No check-in photo</div>';
                    const checkOutImageHtml = attendance.check_out_image
                        ? `<img src="/storage/${attendance.check_out_image}" class="img-fluid rounded" style="max-height: 220px; width: 100%; object-fit: contain;">`
                        : '<div class="bg-light rounded p-4 text-center text-muted">No check-out photo</div>';

                    $('#attendanceDetailsContent').html(`
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded h-100">
                                    <span class="text-muted small d-block mb-1">Studio</span>
                                    <span class="fw-medium">${attendance.studio_name}</span>
                                    <span class="text-muted small d-block mt-3 mb-1">Date</span>
                                    <span class="fw-medium">${attendance.attendance_date}</span>
                                    <span class="text-muted small d-block mt-3 mb-1">Scheduled Time</span>
                                    <span class="fw-medium">${attendance.scheduled_start_time} - ${attendance.scheduled_end_time}</span>
                                    <span class="text-muted small d-block mt-3 mb-1">Total Hours</span>
                                    <span class="fw-medium">${attendance.counted_duration || attendance.duration || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded h-100">
                                    <span class="text-muted small d-block mb-1">Notes</span>
                                    <span>${attendance.notes || 'No notes provided.'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-2">Check-In Details</h6>
                                ${checkInImageHtml}
                                <div class="mt-2">
                                    <div><strong>Time:</strong> ${attendance.formatted_check_in}</div>
                                    <div><strong>Status:</strong> ${attendance.check_in_status || '-'}</div>
                                    <div><strong>Late:</strong> ${attendance.late_display || '-'}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-2">Check-Out Details</h6>
                                ${checkOutImageHtml}
                                <div class="mt-2">
                                    <div><strong>Actual Time:</strong> ${attendance.formatted_check_out || '-'}</div>
                                    <div><strong>Counted Time:</strong> ${attendance.counted_check_out || attendance.formatted_check_out || '-'}</div>
                                    <div><strong>Status:</strong> ${attendance.check_out_status || '-'}</div>
                                    <div><strong>Undertime:</strong> ${attendance.undertime_display || '-'}</div>
                                </div>
                            </div>
                            ${attendance.has_approved_overtime ? `
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <div>
                                                <span class="text-muted small d-block mb-1">Approved Overtime</span>
                                                <span class="fw-medium">${attendance.overtime_summary?.time_range || 'N/A'}</span>
                                            </div>
                                            <div>
                                                <span class="badge badge-soft-primary">${attendance.is_overtime_applied ? 'OT Applied' : 'OT Approved'}</span>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-muted">
                                            Cutoff: ${attendance.overtime_summary?.effective_checkout_cutoff || 'N/A'} |
                                            Counted Hours: ${attendance.counted_duration || attendance.duration || '-'}
                                            ${attendance.actual_duration && attendance.actual_duration !== attendance.counted_duration ? `| Actual Hours: ${attendance.actual_duration}` : ''}
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `);
                },
                error: function (xhr) {
                    $('#attendanceDetailsContent').html(`
                        <div class="alert alert-danger mb-0">
                            ${xhr.responseJSON?.message || 'Failed to load attendance details.'}
                        </div>
                    `);
                }
            });
        }

        // ==================== TABLE FILTER ====================

        function filterAttendanceTable() {
            const searchValue = $('#attendanceSearch').val().toLowerCase();
            const statusValue = $('#attendanceStatusFilter').val();

            $('#attendanceHistoryTable tbody tr').each(function () {
                const rowText = $(this).text().toLowerCase();
                const hasStatus = statusValue === '' || $(this).text().includes(statusValue) || (statusValue === 'ON_LEAVE' && $(this).data('record-type') === 'leave');
                const hasSearch = searchValue === '' || rowText.includes(searchValue);

                $(this).toggle(hasStatus && hasSearch);
            });
        }

        // ==================== HELPERS ====================

        function formatTime(timeValue) {
            const date = new Date(`1970-01-01T${timeValue}`);

            return date.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });
        }
    </script>
@endsection
