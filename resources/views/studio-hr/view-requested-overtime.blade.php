@extends('layouts.studio-hr.app')
@section('title', 'View Requested Overtime')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h4 class="mb-1">View Requested Overtime</h4>
                            <p class="text-muted mb-0">Review and manage your submitted overtime requests while waiting for Studio Owner approval.</p>
                        </div>
                        <a href="{{ route('studio-hr.overtime-requests.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Request Overtime
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Pending Requests</span><h3 class="mb-0">{{ $overtimeRequestSummary['pending'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Approved Requests</span><h3 class="mb-0">{{ $overtimeRequestSummary['approved'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Rejected Requests</span><h3 class="mb-0">{{ $overtimeRequestSummary['rejected'] }}</h3></div></div></div>
                <div class="col-md-6 col-xl-3"><div class="card"><div class="card-body"><span class="text-muted small d-block mb-2">Cancelled Requests</span><h3 class="mb-0">{{ $overtimeRequestSummary['cancelled'] }}</h3></div></div></div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="card-title mb-1">Requested Overtime List</h5>
                                <p class="text-muted mb-0">Assigned Studio: {{ $assignedStudio->studio_name }}</p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Reference</th>
                                        <th>Studio</th>
                                        <th>Overtime Date</th>
                                        <th>Time Range</th>
                                        <th>Total Hours</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($overtimeRequests as $overtimeRequest)
                                        @php
                                            $statusBadgeClass = match ($overtimeRequest->status) {
                                                'approved' => 'badge-soft-success',
                                                'rejected' => 'badge-soft-danger',
                                                'cancelled' => 'badge-soft-secondary',
                                                default => 'badge-soft-warning',
                                            };
                                        @endphp
                                        <tr>
                                            <td><span class="fw-semibold">{{ $overtimeRequest->request_reference }}</span></td>
                                            <td>{{ $overtimeRequest->studio->studio_name ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->overtime_date?->format('M d, Y') ?? 'N/A' }}</td>
                                            <td>{{ $overtimeRequest->start_time?->format('h:i A') ?? 'N/A' }} - {{ $overtimeRequest->end_time?->format('h:i A') ?? 'N/A' }}</td>
                                            <td>{{ rtrim(rtrim(number_format((float) $overtimeRequest->total_hours, 2), '0'), '.') }} {{ (float) $overtimeRequest->total_hours === 1.0 ? 'hour' : 'hours' }}</td>
                                            <td><span class="badge {{ $statusBadgeClass }}">{{ $overtimeRequest->status_label }}</span></td>
                                            <td>{{ $overtimeRequest->created_at?->format('M d, Y h:i A') ?? 'N/A' }}</td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button type="button" class="btn btn-sm view-overtime-request-btn" data-id="{{ $overtimeRequest->id }}" title="View or edit overtime request"><i class="ti ti-edit fs-lg"></i></button>
                                                    @if ($overtimeRequest->status === 'pending')
                                                        <button type="button" class="btn btn-sm cancel-overtime-request-btn" data-id="{{ $overtimeRequest->id }}" data-reference="{{ $overtimeRequest->request_reference }}" title="Cancel overtime request"><i class="ti ti-ban fs-lg"></i></button>
                                                    @endif
                                                    @if (in_array($overtimeRequest->status, ['pending', 'cancelled', 'rejected'], true))
                                                        <button type="button" class="btn btn-sm delete-overtime-request-btn" data-id="{{ $overtimeRequest->id }}" data-reference="{{ $overtimeRequest->request_reference }}" title="Delete overtime request"><i class="ti ti-trash fs-lg"></i></button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-clock-off fs-1 text-muted"></i>
                                                <p class="mt-2 mb-0">No overtime requests have been submitted yet.</p>
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

    <div class="modal fade" id="editOvertimeRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Overtime Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editOvertimeRequestModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Loading overtime request details...</p>
                    </div>
                    <div id="editOvertimeRequestModalContent" style="display: none;">
                        <div class="border rounded p-3 bg-light-subtle mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div><label class="text-muted small mb-1 d-block">Request Reference</label><h5 class="mb-0" id="modalRequestReference">N/A</h5></div>
                                <div class="text-md-end"><label class="text-muted small mb-1 d-block">Current Status</label><span class="badge" id="modalRequestStatusBadge">Pending</span></div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Studio</label><p class="mb-0 fw-medium" id="modalStudioName">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Submitted At</label><p class="mb-0 fw-medium" id="modalSubmittedAt">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Overtime Date</label><p class="mb-0 fw-medium" id="modalOvertimeDateDisplay">N/A</p></div></div>
                            <div class="col-md-6"><div class="border rounded p-3 h-100"><label class="text-muted small mb-1 d-block">Total Hours</label><p class="mb-0 fw-medium" id="modalTotalHoursDisplay">0 hour</p></div></div>
                        </div>

                        <div class="alert alert-danger d-none" id="modalRejectionReasonWrapper" role="alert"><strong>Rejection Reason:</strong> <span id="modalRejectionReasonText"></span></div>

                        <form id="updateOvertimeRequestForm">
                            @csrf
                            <input type="hidden" id="modalOvertimeRequestId">
                            <div class="row g-4">
                                <div class="col-md-6"><label for="modal_overtime_date" class="form-label">Overtime Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="modal_overtime_date" name="overtime_date" min="{{ now()->toDateString() }}"><div class="invalid-feedback"></div></div>
                                <div class="col-md-6"><label class="form-label">Computed Overtime Duration</label><input type="text" class="form-control" id="modalComputedTotalHours" readonly value="0 hour"></div>
                                <div class="col-md-6"><label for="modal_start_time" class="form-label">Start Time <span class="text-danger">*</span></label><input type="time" class="form-control" id="modal_start_time" name="start_time"><div class="invalid-feedback"></div></div>
                                <div class="col-md-6"><label for="modal_end_time" class="form-label">End Time <span class="text-danger">*</span></label><input type="time" class="form-control" id="modal_end_time" name="end_time"><div class="invalid-feedback"></div></div>
                                <div class="col-12"><label for="modal_reason" class="form-label">Reason for Overtime <span class="text-danger">*</span></label><textarea class="form-control" id="modal_reason" name="reason" rows="5" placeholder="State the reason for your overtime request..."></textarea><div class="invalid-feedback"></div></div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" id="updateOvertimeRequestBtn">Update Overtime Request</button></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            const editOvertimeRequestModal = new bootstrap.Modal(document.getElementById('editOvertimeRequestModal'));
            const overtimeRequestBaseUrl = '{{ url('/studio-hr/overtime-requests') }}';

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

            function formatHours(hoursValue) {
                const normalizedHours = parseFloat(hoursValue || 0);
                const displayValue = Number.isInteger(normalizedHours)
                    ? normalizedHours.toString()
                    : normalizedHours.toFixed(2).replace(/\.?0+$/, '');

                return displayValue + ' ' + (normalizedHours === 1 ? 'hour' : 'hours');
            }

            function calculateModalTotalHours() {
                const startTime = $('#modal_start_time').val();
                const endTime = $('#modal_end_time').val();

                if (!startTime || !endTime) {
                    return 0;
                }

                const start = new Date('2000-01-01T' + startTime + ':00');
                const end = new Date('2000-01-01T' + endTime + ':00');

                if (end <= start) {
                    return 0;
                }

                return ((end - start) / (1000 * 60 * 60)).toFixed(2);
            }

            function updateModalTotalHoursPreview() {
                const hoursText = formatHours(calculateModalTotalHours());
                $('#modalComputedTotalHours').val(hoursText);
                $('#modalTotalHoursDisplay').text(hoursText);
            }

            function toggleEditableFields(isEditable) {
                $('#modal_overtime_date, #modal_start_time, #modal_end_time, #modal_reason').prop('disabled', !isEditable);
                $('#updateOvertimeRequestBtn').toggleClass('d-none', !isEditable);
            }

            function resetEditOvertimeRequestModal() {
                $('#editOvertimeRequestModalLoading').show();
                $('#editOvertimeRequestModalContent').hide();
                $('#modalRequestReference, #modalStudioName, #modalSubmittedAt, #modalOvertimeDateDisplay').text('N/A');
                $('#modalTotalHoursDisplay').text('0 hour');
                $('#modalComputedTotalHours').val('0 hour');
                $('#modalOvertimeRequestId').val('');
                $('#modal_overtime_date, #modal_start_time, #modal_end_time, #modal_reason').val('');
                $('#modalRequestStatusBadge').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass('badge-soft-warning').text('Pending');
                $('#modalRejectionReasonWrapper').addClass('d-none');
                $('#modalRejectionReasonText').text('');
                resetValidationErrors($('#updateOvertimeRequestForm'));
                toggleEditableFields(true);
            }

            function populateEditOvertimeRequestModal(data) {
                $('#modalOvertimeRequestId').val(data.id);
                $('#modalRequestReference').text(data.request_reference);
                $('#modalStudioName').text(data.studio_name);
                $('#modalSubmittedAt').text(data.submitted_at || 'N/A');
                $('#modalOvertimeDateDisplay').text(data.overtime_date_display || 'N/A');
                $('#modalTotalHoursDisplay').text(data.total_hours_display || '0 hour');
                $('#modalComputedTotalHours').val(data.total_hours_display || '0 hour');
                $('#modal_overtime_date').val(data.overtime_date);
                $('#modal_start_time').val(data.start_time);
                $('#modal_end_time').val(data.end_time);
                $('#modal_reason').val(data.reason);
                $('#modalRequestStatusBadge').removeClass('badge-soft-warning badge-soft-success badge-soft-danger badge-soft-secondary').addClass(getStatusBadgeClass(data.status)).text(data.status_display);

                if (data.rejection_reason) {
                    $('#modalRejectionReasonWrapper').removeClass('d-none');
                    $('#modalRejectionReasonText').text(data.rejection_reason);
                }

                toggleEditableFields(data.can_edit === true);
                updateModalTotalHoursPreview();
            }

            $(document).on('click', '.view-overtime-request-btn', function () {
                resetEditOvertimeRequestModal();
                editOvertimeRequestModal.show();

                $.ajax({
                    url: overtimeRequestBaseUrl + '/' + $(this).data('id'),
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            populateEditOvertimeRequestModal(response.data);
                            $('#editOvertimeRequestModalLoading').hide();
                            $('#editOvertimeRequestModalContent').show();
                            return;
                        }

                        editOvertimeRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to load overtime request details.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        editOvertimeRequestModal.hide();
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to load overtime request details.', confirmButtonColor: '#3475db' });
                    }
                });
            });

            $('#modal_start_time, #modal_end_time').on('change', function () {
                updateModalTotalHoursPreview();
            });

            $('#updateOvertimeRequestBtn').on('click', function () {
                $.ajax({
                    url: overtimeRequestBaseUrl + '/' + $('#modalOvertimeRequestId').val(),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        _method: 'PUT',
                        overtime_date: $('#modal_overtime_date').val(),
                        start_time: $('#modal_start_time').val(),
                        end_time: $('#modal_end_time').val(),
                        reason: $('#modal_reason').val()
                    },
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Overtime Request Updated',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () { window.location.reload(); }
                            });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to update the overtime request.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors($('#updateOvertimeRequestForm'), xhr.responseJSON?.errors || {});
                            Swal.fire({ icon: 'error', title: 'Validation Error', text: xhr.responseJSON?.message || 'Please review the highlighted fields.', confirmButtonColor: '#3475db' });
                            return;
                        }

                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to update the overtime request.', confirmButtonColor: '#3475db' });
                    }
                });
            });

            $(document).on('click', '.cancel-overtime-request-btn', function () {
                const overtimeRequestId = $(this).data('id');
                const requestReference = $(this).data('reference');

                Swal.fire({
                    icon: 'warning',
                    title: 'Cancel Overtime Request?',
                    text: 'Request ' + requestReference + ' will be marked as cancelled.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, cancel'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: overtimeRequestBaseUrl + '/' + overtimeRequestId + '/cancel',
                            method: 'POST',
                            data: { _token: $('meta[name="csrf-token"]').attr('content') },
                            headers: { 'Accept': 'application/json' },
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Overtime Request Cancelled',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: function () { window.location.reload(); }
                                    });
                                    return;
                                }

                                Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to cancel the overtime request.', confirmButtonColor: '#3475db' });
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to cancel the overtime request.', confirmButtonColor: '#3475db' });
                            }
                        });
                    }
                });
            });

            $(document).on('click', '.delete-overtime-request-btn', function () {
                const overtimeRequestId = $(this).data('id');
                const requestReference = $(this).data('reference');

                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Overtime Request?',
                    text: 'Request ' + requestReference + ' will be deleted.',
                    showConfirmButton: true,
                    confirmButtonColor: '#3475db',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete'
                }).then(function (result) {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: overtimeRequestBaseUrl + '/' + overtimeRequestId,
                            method: 'POST',
                            data: { _token: $('meta[name="csrf-token"]').attr('content'), _method: 'DELETE' },
                            headers: { 'Accept': 'application/json' },
                            success: function (response) {
                                if (response.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Overtime Request Deleted',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: function () { window.location.reload(); }
                                    });
                                    return;
                                }

                                Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to delete the overtime request.', confirmButtonColor: '#3475db' });
                            },
                            error: function (xhr) {
                                Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to delete the overtime request.', confirmButtonColor: '#3475db' });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
