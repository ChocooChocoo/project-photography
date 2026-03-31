@extends('layouts.studio-photographer.app')
@section('title', 'Request Overtime')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h4 class="mb-1">Request Overtime</h4>
                            <p class="text-muted mb-0">Submit your overtime request for HR review and approval.</p>
                        </div>
                        <a href="{{ route('studio-photographer.overtime-requests.index') }}" class="btn btn-light">
                            <i class="ti ti-list-details me-1"></i> View Requested Overtime
                        </a>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Request Information</h5></div>
                        <div class="card-body">
                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Employee</label>
                                <h5 class="mb-0">{{ $photographerUser->full_name }}</h5>
                                <small class="text-muted">{{ $photographerUser->email }}</small>
                            </div>
                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Assigned Studio</label>
                                <h5 class="mb-0">{{ $assignedStudio->studio_name }}</h5>
                                <small class="text-muted">Studio ID: {{ $assignedStudio->id }}</small>
                            </div>
                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Request Status</label>
                                <span class="badge badge-soft-warning">Pending HR Approval</span>
                                <p class="text-muted mb-0 mt-2 small">All submitted overtime requests remain pending until HR reviews the request.</p>
                            </div>
                            <div class="border rounded p-3">
                                <label class="text-muted small mb-1 d-block">Estimated Overtime Duration</label>
                                <h3 class="mb-0" id="totalHoursPreview">0 hour</h3>
                                <small class="text-muted">Calculated automatically based on the selected start and end times.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header"><h5 class="card-title mb-0">Overtime Request Form</h5></div>
                        <div class="card-body">
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                                <i class="ti ti-alert-triangle fs-18 mt-1"></i>
                                <div><strong>Reminder:</strong> Your overtime request is subject to HR approval. Please provide complete and accurate details before submitting.</div>
                            </div>

                            <form id="requestOvertimeForm">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-6"><label class="form-label">Studio</label><input type="text" class="form-control" value="{{ $assignedStudio->studio_name }}" readonly></div>
                                    <div class="col-md-6"><label for="overtime_date" class="form-label">Overtime Date <span class="text-danger">*</span></label><input type="date" class="form-control" id="overtime_date" name="overtime_date" min="{{ now()->toDateString() }}"><div class="invalid-feedback"></div></div>
                                    <div class="col-md-6"><label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label><input type="time" class="form-control" id="start_time" name="start_time"><div class="invalid-feedback"></div></div>
                                    <div class="col-md-6"><label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label><input type="time" class="form-control" id="end_time" name="end_time"><div class="invalid-feedback"></div></div>
                                    <div class="col-12"><label for="reason" class="form-label">Reason for Overtime <span class="text-danger">*</span></label><textarea class="form-control" id="reason" name="reason" rows="6" placeholder="State the reason for your overtime request..."></textarea><div class="invalid-feedback"></div></div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="reset" class="btn btn-light" id="resetRequestOvertimeFormBtn">Reset</button>
                                    <button type="submit" class="btn btn-primary" id="submitRequestOvertimeBtn">
                                        <span id="submitRequestOvertimeText">Submit Overtime Request</span>
                                        <span class="spinner-border spinner-border-sm d-none ms-1" id="submitRequestOvertimeSpinner" role="status" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
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
                const displayValue = Number.isInteger(normalizedHours) ? normalizedHours.toString() : normalizedHours.toFixed(2).replace(/\.?0+$/, '');
                return displayValue + ' ' + (normalizedHours === 1 ? 'hour' : 'hours');
            }

            function calculateTotalHours() {
                const startTime = $('#start_time').val();
                const endTime = $('#end_time').val();
                if (!startTime || !endTime) return 0;
                const start = new Date('2000-01-01T' + startTime + ':00');
                const end = new Date('2000-01-01T' + endTime + ':00');
                if (end <= start) return 0;
                return ((end - start) / (1000 * 60 * 60)).toFixed(2);
            }

            function updateTotalHoursPreview() {
                $('#totalHoursPreview').text(formatHours(calculateTotalHours()));
            }

            function toggleSubmitButtonState(isSubmitting) {
                $('#submitRequestOvertimeBtn').prop('disabled', isSubmitting);
                $('#submitRequestOvertimeSpinner').toggleClass('d-none', !isSubmitting);
                $('#submitRequestOvertimeText').text(isSubmitting ? 'Submitting...' : 'Submit Overtime Request');
            }

            $('#start_time, #end_time').on('change', updateTotalHoursPreview);

            $('#resetRequestOvertimeFormBtn').on('click', function () {
                setTimeout(function () {
                    resetValidationErrors($('#requestOvertimeForm'));
                    updateTotalHoursPreview();
                }, 0);
            });

            $('#requestOvertimeForm').on('submit', function (event) {
                event.preventDefault();

                const formElement = $('#requestOvertimeForm');
                toggleSubmitButtonState(true);

                $.ajax({
                    url: '{{ route('studio-photographer.overtime-requests.store') }}',
                    method: 'POST',
                    data: formElement.serialize(),
                    headers: { 'Accept': 'application/json' },
                    success: function (response) {
                        if (response.status === 'success') {
                            formElement[0].reset();
                            updateTotalHoursPreview();
                            resetValidationErrors(formElement);
                            Swal.fire({
                                icon: 'success',
                                title: 'Overtime Request Submitted',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () {
                                    window.location.href = '{{ route('studio-photographer.overtime-requests.index') }}';
                                }
                            });
                            return;
                        }
                        Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to submit the overtime request.', confirmButtonColor: '#3475db' });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors(formElement, xhr.responseJSON?.errors || {});
                            Swal.fire({ icon: 'error', title: 'Validation Error', text: xhr.responseJSON?.message || 'Please review the highlighted fields.', confirmButtonColor: '#3475db' });
                            return;
                        }
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to submit the overtime request.', confirmButtonColor: '#3475db' });
                    },
                    complete: function () {
                        toggleSubmitButtonState(false);
                    }
                });
            });

            updateTotalHoursPreview();
        });
    </script>
@endsection
