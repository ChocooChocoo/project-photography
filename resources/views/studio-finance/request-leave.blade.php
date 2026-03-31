@extends('layouts.studio-finance.app')
@section('title', 'Request Leave')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
                        <div>
                            <h4 class="mb-1">Request Leave</h4>
                            <p class="text-muted mb-0">Submit your leave request for HR review and approval.</p>
                        </div>
                        <a href="{{ route('studio-finance.leave-requests.index') }}" class="btn btn-light">
                            <i class="ti ti-list-details me-1"></i> View Requested Leave
                        </a>
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Request Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Employee</label>
                                <h5 class="mb-0">{{ $financeUser->full_name }}</h5>
                                <small class="text-muted">{{ $financeUser->email }}</small>
                            </div>

                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Assigned Studio</label>
                                <h5 class="mb-0">{{ $assignedStudio->studio_name }}</h5>
                                <small class="text-muted">Studio ID: {{ $assignedStudio->id }}</small>
                            </div>

                            <div class="border rounded p-3 bg-light-subtle mb-3">
                                <label class="text-muted small mb-1 d-block">Request Status</label>
                                <span class="badge badge-soft-warning">Pending HR Approval</span>
                                <p class="text-muted mb-0 mt-2 small">
                                    All submitted leave requests remain pending until HR reviews the request.
                                </p>
                            </div>

                            <div class="border rounded p-3">
                                <label class="text-muted small mb-1 d-block">Estimated Leave Duration</label>
                                <h3 class="mb-0" id="totalDaysPreview">0 day</h3>
                                <small class="text-muted">Calculated automatically based on the selected start and end dates.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Leave Request Form</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="alert">
                                <i class="ti ti-alert-triangle fs-18 mt-1"></i>
                                <div>
                                    <strong>Reminder:</strong> Your leave request is subject to HR approval. Please provide complete and accurate details before submitting.
                                </div>
                            </div>

                            <form id="requestLeaveForm">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label for="leave_type" class="form-label">Leave Type <span class="text-danger">*</span></label>
                                        <select class="form-select" id="leave_type" name="leave_type">
                                            <option value="">Select leave type</option>
                                            @foreach ($leaveTypes as $leaveTypeValue => $leaveTypeLabel)
                                                <option value="{{ $leaveTypeValue }}">{{ $leaveTypeLabel }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Studio</label>
                                        <input type="text" class="form-control" value="{{ $assignedStudio->studio_name }}" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" min="{{ now()->toDateString() }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" min="{{ now()->toDateString() }}">
                                        <div class="invalid-feedback"></div>
                                    </div>

                                    <div class="col-12">
                                        <label for="reason" class="form-label">Reason for Leave <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="reason" name="reason" rows="6" placeholder="State the reason for your leave request..."></textarea>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="reset" class="btn btn-light" id="resetRequestLeaveFormBtn">Reset</button>
                                    <button type="submit" class="btn btn-primary" id="submitRequestLeaveBtn">
                                        <span id="submitRequestLeaveText">Submit Leave Request</span>
                                        <span class="spinner-border spinner-border-sm d-none ms-1" id="submitRequestLeaveSpinner" role="status" aria-hidden="true"></span>
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

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function () {
            // ==================== HELPER FUNCTIONS ====================
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

            function calculateTotalDays() {
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();

                if (!startDate || !endDate) {
                    return 0;
                }

                const start = new Date(startDate + 'T00:00:00');
                const end = new Date(endDate + 'T00:00:00');

                if (end < start) {
                    return 0;
                }

                const millisecondsPerDay = 1000 * 60 * 60 * 24;
                return Math.floor((end - start) / millisecondsPerDay) + 1;
            }

            function updateTotalDaysPreview() {
                const totalDays = calculateTotalDays();
                const label = totalDays === 1 ? 'day' : 'days';

                $('#totalDaysPreview').text(totalDays + ' ' + label);
            }

            function toggleSubmitButtonState(isSubmitting) {
                $('#submitRequestLeaveBtn').prop('disabled', isSubmitting);
                $('#submitRequestLeaveSpinner').toggleClass('d-none', !isSubmitting);
                $('#submitRequestLeaveText').text(isSubmitting ? 'Submitting...' : 'Submit Leave Request');
            }

            function resetRequestLeaveForm() {
                $('#requestLeaveForm')[0].reset();
                resetValidationErrors($('#requestLeaveForm'));
                updateTotalDaysPreview();
            }

            function submitLeaveRequest() {
                const formElement = $('#requestLeaveForm');

                toggleSubmitButtonState(true);

                $.ajax({
                    url: '{{ route('studio-finance.leave-requests.store') }}',
                    method: 'POST',
                    data: formElement.serialize(),
                    headers: {
                        'Accept': 'application/json'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            resetRequestLeaveForm();

                            Swal.fire({
                                icon: 'success',
                                title: 'Leave Request Submitted',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: function () {
                                    window.location.href = '{{ route('studio-finance.leave-requests.index') }}';
                                }
                            });

                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Failed to submit the leave request.',
                            showConfirmButton: true,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    error: function (xhr) {
                        if (xhr.status === 422) {
                            showValidationErrors(formElement, xhr.responseJSON?.errors || {});

                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                text: xhr.responseJSON?.message || 'Please review the highlighted fields.',
                                showConfirmButton: true,
                                confirmButtonColor: '#3475db'
                            });

                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to submit the leave request.',
                            showConfirmButton: true,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function () {
                        toggleSubmitButtonState(false);
                    }
                });
            }

            // ==================== INPUT HANDLERS ====================
            $('#start_date, #end_date').on('change', function () {
                updateTotalDaysPreview();
            });

            $('#resetRequestLeaveFormBtn').on('click', function () {
                setTimeout(function () {
                    resetValidationErrors($('#requestLeaveForm'));
                    updateTotalDaysPreview();
                }, 0);
            });

            // ==================== FORM SUBMIT ====================
            $('#requestLeaveForm').on('submit', function (event) {
                event.preventDefault();
                submitLeaveRequest();
            });

            updateTotalDaysPreview();
        });
    </script>
@endsection
