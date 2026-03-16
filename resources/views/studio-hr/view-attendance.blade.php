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

                        <ul class="nav nav-tabs nav-bordered border-bottom mt-2">
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
                                        <div class="row g-3 mb-4" id="attendanceSummary" style="display: none;">
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
                                        </div>
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
                                                                            <span class="badge bg-soft-info">{{ $record->duration }}</span>
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
                            <button class="btn btn-sm btn-soft-info" onclick="viewAttendanceDetails(${record.id})">
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
            const modalHtml = `
                <div class="modal fade" id="attendanceDetailsModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Attendance Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    ${attendance.check_in_image ? 
                                        `<img src="/storage/${attendance.check_in_image}" class="img-fluid rounded" style="max-height: 200px;">` : 
                                        '<p class="text-muted">No check-in photo</p>'
                                    }
                                </div>
                                
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Employee</th>
                                        <td>${attendance.employee_name}</td>
                                    </tr>
                                    <tr>
                                        <th>Date</th>
                                        <td>${attendance.attendance_date}</td>
                                    </tr>
                                    <tr>
                                        <th>Scheduled Time</th>
                                        <td>${attendance.scheduled_start_time} - ${attendance.scheduled_end_time}</td>
                                    </tr>
                                    <tr>
                                        <th>Check-In Time</th>
                                        <td>
                                            ${attendance.formatted_check_in}
                                            ${attendance.late_display ? `<span class="badge badge-soft-warning ms-2">${attendance.late_display}</span>` : ''}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Check-Out Time</th>
                                        <td>
                                            ${attendance.formatted_check_out || '—'}
                                            ${attendance.undertime_display ? `<span class="badge badge-soft-danger ms-2">${attendance.undertime_display}</span>` : ''}
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Total Hours</th>
                                        <td>${attendance.duration || '—'}</td>
                                    </tr>
                                    ${attendance.notes ? `
                                    <tr>
                                        <th>Notes</th>
                                        <td>${attendance.notes}</td>
                                    </tr>
                                    ` : ''}
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#attendanceDetailsModal').remove();
            $('body').append(modalHtml);
            new bootstrap.Modal(document.getElementById('attendanceDetailsModal')).show();
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