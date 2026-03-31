@extends('layouts.studio-photographer.app')
@section('title', 'View Requested Leave')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h4 class="mb-1">View Requested Leave</h4>
                            <p class="text-muted mb-0">Review and manage your submitted leave requests.</p>
                        </div>
                        <a href="{{ route('studio-photographer.leave-requests.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Request Leave
                        </a>
                    </div>
                </div>

                @php
                    $totalLeaveRequests = max(array_sum($leaveRequestSummary), 1);
                    $leaveSummaryCards = [
                        ['count' => $leaveRequestSummary['pending'], 'label' => 'Pending Requests', 'meta' => 'WAITING REVIEW', 'color' => 'warning', 'icon' => 'ti ti-clock-hour-4'],
                        ['count' => $leaveRequestSummary['approved'], 'label' => 'Approved Requests', 'meta' => 'APPROVAL RATE', 'color' => 'success', 'icon' => 'ti ti-checklist'],
                        ['count' => $leaveRequestSummary['rejected'], 'label' => 'Rejected Requests', 'meta' => 'NEEDS ACTION', 'color' => 'danger', 'icon' => 'ti ti-xbox-x'],
                        ['count' => $leaveRequestSummary['cancelled'], 'label' => 'Cancelled Requests', 'meta' => 'WITHDRAWN', 'color' => 'secondary', 'icon' => 'ti ti-ban'],
                    ];
                @endphp

                <div class="col-12">
                    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 align-items-center">
                        @foreach ($leaveSummaryCards as $leaveSummaryCard)
                            @php
                                $percentage = $leaveSummaryCard['count'] > 0
                                    ? round(($leaveSummaryCard['count'] / $totalLeaveRequests) * 100)
                                    : 0;
                            @endphp
                            <div class="col">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="avatar avatar-lg flex-shrink-0">
                                                <span class="avatar-title bg-{{ $leaveSummaryCard['color'] }}-subtle text-{{ $leaveSummaryCard['color'] }} rounded fs-24">
                                                    <i class="{{ $leaveSummaryCard['icon'] }}"></i>
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <h4 class="mb-0">{{ $leaveSummaryCard['count'] }}</h4>
                                                <p class="mb-0 text-muted">{{ $leaveSummaryCard['label'] }}</p>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted fs-xs fw-semibold">{{ $leaveSummaryCard['meta'] }}</span>
                                                <span class="text-muted">{{ $percentage }}%</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-{{ $leaveSummaryCard['color'] }}" style="width: {{ $percentage }}%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @php
                    $timelineEvents = collect();
                    $defaultTimelinePhoto = asset('assets/images/users/user-3.jpg');

                    foreach ($leaveRequests->take(5) as $timelineRequest) {
                        $timelineEvents->push([
                            'title' => 'Leave Request Submitted',
                            'description' => $timelineRequest->request_reference . ' for ' . $timelineRequest->leave_type_label .
                                ' covering ' . ($timelineRequest->start_date?->format('M d, Y') ?? 'N/A') . ' to ' .
                                ($timelineRequest->end_date?->format('M d, Y') ?? 'N/A') . '.',
                            'actor' => 'By You',
                            'photo' => auth()->user()->profile_photo_url ?? $defaultTimelinePhoto,
                            'event_at' => $timelineRequest->created_at,
                        ]);

                        if ($timelineRequest->approved_at) {
                            $timelineEvents->push([
                                'title' => 'Leave Request Approved',
                                'description' => $timelineRequest->request_reference . ' was approved for ' . $timelineRequest->leave_type_label . '.',
                                'actor' => 'By ' . ($timelineRequest->approver->full_name ?? 'HR'),
                                'photo' => $timelineRequest->approver->profile_photo_url ?? $defaultTimelinePhoto,
                                'event_at' => $timelineRequest->approved_at,
                            ]);
                        }

                        if ($timelineRequest->rejected_at) {
                            $timelineEvents->push([
                                'title' => 'Leave Request Rejected',
                                'description' => $timelineRequest->request_reference . ' was rejected. Reason: ' . ($timelineRequest->rejection_reason ?? 'No reason provided.'),
                                'actor' => 'By ' . ($timelineRequest->rejector->full_name ?? 'HR'),
                                'photo' => $timelineRequest->rejector->profile_photo_url ?? $defaultTimelinePhoto,
                                'event_at' => $timelineRequest->rejected_at,
                            ]);
                        }

                        if ($timelineRequest->cancelled_at) {
                            $timelineEvents->push([
                                'title' => 'Leave Request Cancelled',
                                'description' => $timelineRequest->request_reference . ' was cancelled before final processing.',
                                'actor' => 'By You',
                                'photo' => auth()->user()->profile_photo_url ?? $defaultTimelinePhoto,
                                'event_at' => $timelineRequest->cancelled_at,
                            ]);
                        }
                    }

                    $timelineEvents = $timelineEvents->sortByDesc('event_at')->take(5)->values();
                @endphp

                <div class="col-12 col-xxl-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h4 class="card-title mb-0">Leave Request Timeline</h4>
                        </div>
                        <div class="card-body">
                            <div class="timeline timeline-users">
                                @forelse ($timelineEvents as $timelineEvent)
                                    <div class="timeline-item d-flex align-items-stretch">
                                        <div class="timeline-dot">
                                            <img src="{{ $timelineEvent['photo'] }}" alt="timeline-user" class="img-fluid rounded-circle">
                                        </div>
                                        <div class="timeline-content ps-3 {{ $loop->last ? '' : 'pb-4' }}">
                                            <h5 class="mb-1">{{ $timelineEvent['title'] }}</h5>
                                            <p class="mb-1 text-muted">{{ $timelineEvent['description'] }}</p>
                                            <span class="text-primary fw-semibold">{{ $timelineEvent['actor'] }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-4">
                                        <i class="ti ti-clock-off fs-1 text-muted"></i>
                                        <p class="mt-2 mb-0 text-muted">No leave activity yet.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xxl-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Requested Leave List</h5>
                                <p class="text-muted mb-0">Assigned Studio: {{ $assignedStudio->studio_name }}</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>Studio</th>
                                        <th>Leave Type</th>
                                        <th>Period</th>
                                        <th>Total Days</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($leaveRequests as $leaveRequest)
                                        @php
                                            $statusBadgeClass = match ($leaveRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr>
                                            <td><span class="fw-semibold">{{ $leaveRequest->request_reference }}</span></td>
                                            <td>{{ $leaveRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $leaveRequest->leave_type_label }}</td>
                                            <td>{{ $leaveRequest->start_date?->format('M d, Y') ?? 'N/A' }} - {{ $leaveRequest->end_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td>{{ rtrim(rtrim(number_format((float) $leaveRequest->total_days, 2), '0'), '.') }} {{ (float) $leaveRequest->total_days === 1.0 ? 'day' : 'days' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $leaveRequest->status_label }}</span></td>
                                            <td>{{ $leaveRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button type="button" class="btn btn-sm view-leave-request-btn" data-id="{{ $leaveRequest->id }}" title="View or edit leave request"><i class="ti ti-edit fs-lg"></i></button>
                                                    @if ($leaveRequest->status === 'pending')
                                                        <button type="button" class="btn btn-sm cancel-leave-request-btn" data-id="{{ $leaveRequest->id }}" data-reference="{{ $leaveRequest->request_reference }}" title="Cancel leave request"><i class="ti ti-ban fs-lg"></i></button>
                                                    @endif
                                                    @if (in_array($leaveRequest->status, ['pending', 'cancelled', 'rejected'], true))
                                                        <button type="button" class="btn btn-sm delete-leave-request-btn" data-id="{{ $leaveRequest->id }}" data-reference="{{ $leaveRequest->request_reference }}" title="Delete leave request"><i class="ti ti-trash fs-lg"></i></button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-calendar-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No leave requests have been submitted yet.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLeaveRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Leave Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editLeaveRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Loading leave request details...</p>
                    </div>
                    <div id="editLeaveRequestModalContent" style="display: none;">
                        <div class="border rounded p-3 bg-light-subtle mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div><label class="text-muted small mb-1 d-block">Request Reference</label><h5 class="mb-0" id="modalRequestReference">N/A</h5></div>
                                <div class="text-md-end"><label class="text-muted small mb-1 d-block">Current Status</label><span class="badge" id="modalRequestStatusBadge">Pending</span></div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Studio</label><p class="mb-0 fw-medium" id="modalStudioName">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Submitted At</label><p class="mb-0 fw-medium" id="modalSubmittedAt">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Leave Period</label><p class="mb-0 fw-medium" id="modalPeriodDisplay">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Total Days</label><p class="mb-0 fw-medium" id="modalTotalDaysDisplay">0 day</p></div></div>
                        </div>
                        <div class="alert alert-danger d-none" id="modalRejectionReasonWrapper" role="alert"><strong>Rejection Reason:</strong> <span id="modalRejectionReasonText"></span></div>
                        <form id="updateLeaveRequestForm">
                            @csrf
                            <input type="hidden" id="modalLeaveRequestId" name="leave_request_id">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="modal_leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="modal_leave_type" name="leave_type">
                                        <option value="">Select leave type</option>
                                        @foreach ($leaveTypes as $leaveTypeValue => $leaveTypeLabel)
                                            <option value="{{ $leaveTypeValue }}">{{ $leaveTypeLabel }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-md-6"><label class="form-label">Computed Leave Duration</label><input type="text" class="form-control" id="modalComputedTotalDays" readonly value="0 day"></div>
                                <div class="col-md-6"><label for="modal_start_date" class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="modal_start_date" name="start_date" min="{{ now()->toDateString() }}"><div class="invalid-feedback"></div></div>
                                <div class="col-md-6"><label for="modal_end_date" class="form-label">End Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="modal_end_date" name="end_date" min="{{ now()->toDateString() }}"><div class="invalid-feedback"></div></div>
                                <div class="col-12"><label for="modal_reason" class="form-label">Reason for Leave <span class="text-danger">*</span></label><textarea class="form-control" id="modal_reason" name="reason" rows="5" placeholder="State the reason for your leave request..."></textarea><div class="invalid-feedback"></div></div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" id="updateLeaveRequestBtn">Update Leave Request</button></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const editLeaveRequestModal = new bootstrap.Modal(document.getElementById('editLeaveRequestModal'));
            const leaveRequestBaseUrl = '{{ url('/studio-photographer/leave-requests') }}';

            function getStatusBadgeClass(status) {
                if (status === 'approved') return 'badge-soft-success';
                if (status === 'rejected') return 'badge-soft-danger';
                if (status === 'cancelled') return 'badge-soft-secondary';
                return 'badge-soft-warning';
            }

            function resetValidationErrors(formElement) {
                formElement.find('.is-invalid').removeClass('is-invalid');
                formElement.find('.invalid-feedback').empty();
            }

            function showValidationErrors(formElement, errors) {
                resetValidationErrors(formElement);
                $.each(errors || {}, function (field, messages) {
                    const input = formElement.find(`[name="${field}"]`);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').html(messages[0]);
                    }
                });
            }

            function calculateModalTotalDays() {
                const startDate = $('#modal_start_date').val();
                const endDate = $('#modal_end_date').val();
                if (!startDate || !endDate) return 0;
                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T00:00:00');
                if (end < start) return 0;
                return Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
            }

            function updateModalTotalDaysPreview() {
                const totalDays = calculateModalTotalDays();
                const text = totalDays + ' ' + (totalDays === 1 ? 'day' : 'days');
                $('#modalComputedTotalDays').val(text);
                $('#modalTotalDaysDisplay').text(text);
            }

            function toggleEditableFields(isEditable) {
                $('#modal_leave_type, #modal_start_date, #modal_end_date, #modal_reason').prop('disabled', !isEditable);
                $('#updateLeaveRequestBtn').toggleClass('d-none', !isEditable);
            }

            function resetEditLeaveRequestModal() {
                $('#editLeaveRequestModalLoading').show();
                $('#editLeaveRequestModalContent').hide();
                $('#modalRequestReference, #modalStudioName, #modalSubmittedAt, #modalPeriodDisplay').text('N/A');
                $('#modalTotalDaysDisplay').text('0 day');
                $('#modalComputedTotalDays').val('0 day');
                $('#modalLeaveRequestId').val('');
                $('#modal_leave_type').val('');
                $('#modal_start_date').val('');
                $('#modal_end_date').val('');
                $('#modal_reason').val('');
                $('#modalRequestStatusBadge').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass('badge-soft-warning').text('Pending');
                $('#modalRejectionReasonWrapper').addClass('d-none');
                $('#modalRejectionReasonText').text('');
                resetValidationErrors($('#updateLeaveRequestForm'));
                toggleEditableFields(true);
            }

            function populateEditLeaveRequestModal(data) {
                $('#modalLeaveRequestId').val(data.id);
                $('#modalRequestReference').text(data.request_reference);
                $('#modalStudioName').text(data.studio_name);
                $('#modalSubmittedAt').text(data.submitted_at || 'N/A');
                $('#modalPeriodDisplay').text(data.period_display || 'N/A');
                $('#modalTotalDaysDisplay').text(data.total_days_display || '0 day');
                $('#modalComputedTotalDays').val(data.total_days_display || '0 day');
                $('#modal_leave_type').val(data.leave_type);
                $('#modal_start_date').val(data.start_date);
                $('#modal_end_date').val(data.end_date);
                $('#modal_reason').val(data.reason);
                $('#modalRequestStatusBadge').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass(getStatusBadgeClass(data.status)).text(data.status_display);

                if (data.rejection_reason) {
                    $('#modalRejectionReasonWrapper').removeClass('d-none');
                    $('#modalRejectionReasonText').text(data.rejection_reason);
                }

                toggleEditableFields(data.can_edit === true);
                updateModalTotalDaysPreview();
            }

            $(document).on('click', '.view-leave-request-btn', function () {
                resetEditLeaveRequestModal();
                editLeaveRequestModal.show();

                $.ajax({
                    url: leaveRequestBaseUrl + '/' + $(this).data('id'),
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateEditLeaveRequestModal(response.data);
                            $('#editLeaveRequestModalLoading').hide();
                            $('#editLeaveRequestModalContent').show();
                            return;
                        }

                        editLeaveRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to load leave request details.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        editLeaveRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to load leave request details.', confirmButtonColor: '#3475db' });
                    }
                });
            });

            $('#modal_start_date, #modal_end_date').on('change', function () {
                updateModalTotalDaysPreview();
            });

            $('#updateLeaveRequestBtn').on('click', function () {
                $.ajax({
                    url: leaveRequestBaseUrl + '/' + $('#modalLeaveRequestId').val(),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'PUT',
                        leave_type: $('#modal_leave_type').val(),
                        start_date: $('#modal_start_date').val(),
                        end_date: $('#modal_end_date').val(),
                        reason: $('#modal_reason').val()
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Leave Request Updated',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () { window.location.reload(); }
                            });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to update the leave request.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors($('#updateLeaveRequestForm'), xhr.responseJSON?.errors || {});
                            Swal.fire({ icon: 'error', title: 'Validation Error', text: xhr.responseJSON?.message || 'Please review the highlighted fields.', confirmButtonColor: '#3475db' });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to update the leave request.', confirmButtonColor: '#3475db' });
                    }
                });
            });

            $(document).on('click', '.cancel-leave-request-btn', function () {
                const leaveRequestId = $(this).data('id');
                const requestReference = $(this).data('reference');

                Swal.fire({
                    icon: 'warning',
                    title: 'Cancel Leave Request?',
                    text: 'Request ' + requestReference + ' will be marked as cancelled.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: leaveRequestBaseUrl + '/' + leaveRequestId + '/cancel',
                            method: 'POST',
                            data: { _token: $('meta[name="csrf-token"]').attr('content') },
                            headers: { 'Accept': 'application/json' },
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Leave Request Cancelled',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: function () { window.location.reload(); }
                                    });
                                    return;
                                }

                                Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to cancel the leave request.', confirmButtonColor: '#3475db' });
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to cancel the leave request.', confirmButtonColor: '#3475db' });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.delete-leave-request-btn', function () {
                const leaveRequestId = $(this).data('id');
                const requestReference = $(this).data('reference');

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Leave Request?',
                    text: 'Request ' + requestReference + ' will be deleted.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: leaveRequestBaseUrl + '/' + leaveRequestId,
                            method: 'POST',
                            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                            headers: { 'Accept': 'application/json' },
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Leave Request Deleted',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: function () { window.location.reload(); }
                                    });
                                    return;
                                }

                                Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to delete the leave request.', confirmButtonColor: '#3475db' });
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to delete the leave request.', confirmButtonColor: '#3475db' });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
