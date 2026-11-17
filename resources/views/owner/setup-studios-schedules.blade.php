@extends('layouts.owner.app')
@section('title', 'Setup Studio Schedules')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Setup Studio Schedules</h4>
                        </div>
                        <div class="card-body">
                            <form id="studioScheduleForm" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col">
                                        <div class="form-group mb-3">
                                            <label class="form-label mb-2">Select Studio</label>
                                            <select class="form-select mb-1" name="studio_id" id="studio_id" required>
                                                <option value="">Select Studio</option>
                                                @foreach($studios as $studio)
                                                    <option value="{{ $studio->id }}" data-saved-notice="{{ $studio->advance_booking_days }}">{{ $studio->studio_name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="d-block text-muted">Select your verified studio</small>
                                            <div class="invalid-feedback" id="studio_id_error">Please select a studio.</div>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label mb-2">Select Operating Days</label>
                                            <div class="mb-2">
                                                <div class="btn-group w-100 mb-1" role="group" aria-label="Weekday toggle button group" id="operatingDaysGroup">
                                                    <input type="checkbox" class="btn-check" id="btnMonday" name="operating_days[]" value="monday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnMonday">Monday</label>

                                                    <input type="checkbox" class="btn-check" id="btnTuesday" name="operating_days[]" value="tuesday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnTuesday">Tuesday</label>

                                                    <input type="checkbox" class="btn-check" id="btnWednesday" name="operating_days[]" value="wednesday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnWednesday">Wednesday</label>

                                                    <input type="checkbox" class="btn-check" id="btnThursday" name="operating_days[]" value="thursday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnThursday">Thursday</label>

                                                    <input type="checkbox" class="btn-check" id="btnFriday" name="operating_days[]" value="friday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnFriday">Friday</label>

                                                    <input type="checkbox" class="btn-check" id="btnSaturday" name="operating_days[]" value="saturday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnSaturday">Saturday</label>

                                                    <input type="checkbox" class="btn-check" id="btnSunday" name="operating_days[]" value="sunday" autocomplete="off">
                                                    <label class="btn btn-outline-primary" for="btnSunday">Sunday</label>
                                                </div>
                                                <small class="d-block text-muted">Check which days you accept bookings</small>
                                                <div class="invalid-feedback" id="operating_days_error">Please select at least one day.</div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group mb-3">
                                            <label class="form-label">Select Operating Hours</label>
                                            <div class="row">
                                                <div class="col">
                                                    <small class="d-block text-muted mb-1">Opening Time</small>
                                                    <input type="time" class="form-control" id="openingTime" name="opening_time" required>
                                                    <div class="invalid-feedback" id="opening_time_error">Please enter a valid opening time.</div>
                                                </div>
                                                <div class="col">
                                                    <small class="d-block text-muted mb-1">Closing Time</small>
                                                    <input type="time" class="form-control" id="closingTime" name="closing_time" required>
                                                    <div class="invalid-feedback" id="closing_time_error">Please enter a valid closing time.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row form-group">
                                            <div class="col-md-6">
                                                <label class="form-label">Booking Limits</label>
                                                <div class="mb-3">
                                                    <small class="d-block text-muted mb-1">Maximum Booking Per Day</small>
                                                    <input type="number" class="form-control" id="maxBookingPerDay" name="booking_limit" value="1" min="1" max="100" required>
                                                    <div class="invalid-feedback" id="booking_limit_error">Please enter a valid number (1-100).</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">Advance Booking</label>
                                                <div class="mb-3">
                                                    <small class="d-block text-muted mb-1">Minimum Advance Notice (Days)</small>
                                                    @include('partials.advance-booking-select', ['name' => 'advance_booking', 'value' => 1, 'id' => 'minAdvanceNotice'])
                                                    <div class="invalid-feedback" id="advance_booking_error">Please enter a valid number (1-30).</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">Create Studio Schedule</button>
                                    </div>
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
        $(document).ready(function() {
            // Show the chosen studio's saved advance notice so saving a schedule does not reset it.
            $('#studio_id').on('change', function () {
                const days = Number($(this).find(':selected').data("saved-notice"));
                if (!days) return;
                const choice = $('#minAdvanceNotice').closest('[data-advance-booking]').find('[data-advance-choice]');
                choice.val(days >= 3 ? '3+' : String(days))[0].dispatchEvent(new Event('change', { bubbles: true }));
                if (days >= 3) $('#minAdvanceNotice').val(days);
            });
            // Handle form submission via button click
            $('#submitBtn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Submit button clicked'); // Debug
                
                submitForm();
            });
            
            // Also prevent form submission on Enter key
            $('#studioScheduleForm').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                }
            });

            // Prevent form from submitting normally
            $('#studioScheduleForm').on('submit', function(e) {
                e.preventDefault();
                submitForm();
                return false;
            });

            function submitForm() {
                // Reset validation states
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').hide();
                
                // Validate form
                let isValid = true;
                
                // Validate studio selection
                const studioId = $('#studio_id').val();
                console.log('Studio ID:', studioId);
                if (!studioId) {
                    $('#studio_id').addClass('is-invalid');
                    $('#studio_id_error').show();
                    isValid = false;
                }
                
                // Validate operating days
                const operatingDays = $('#operatingDaysGroup input:checked');
                console.log('Operating days checked:', operatingDays.length);
                if (operatingDays.length === 0) {
                    $('#operatingDaysGroup').addClass('is-invalid');
                    $('#operating_days_error').show();
                    isValid = false;
                }
                
                // Validate operating hours
                const openingTime = $('#openingTime').val();
                const closingTime = $('#closingTime').val();
                console.log('Opening Time:', openingTime, 'Closing Time:', closingTime);
                if (!openingTime) {
                    $('#openingTime').addClass('is-invalid');
                    $('#opening_time_error').show();
                    isValid = false;
                }
                if (!closingTime) {
                    $('#closingTime').addClass('is-invalid');
                    $('#closing_time_error').show();
                    isValid = false;
                }
                if (openingTime && closingTime && openingTime >= closingTime) {
                    $('#closingTime').addClass('is-invalid');
                    $('#closing_time_error').text('Closing time must be after opening time.').show();
                    isValid = false;
                }
                
                // Validate booking limit
                const bookingLimit = $('#maxBookingPerDay').val();
                if (!bookingLimit || bookingLimit < 1 || bookingLimit > 100) {
                    $('#maxBookingPerDay').addClass('is-invalid');
                    $('#booking_limit_error').show();
                    isValid = false;
                }
                
                // Validate advance booking
                const advanceBooking = $('#minAdvanceNotice').val();
                if (!advanceBooking || advanceBooking < 1 || advanceBooking > 30) {
                    $('#minAdvanceNotice').addClass('is-invalid');
                    $('#advance_booking_error').show();
                    isValid = false;
                }
                
                console.log('Form validation result:', isValid);
                
                if (!isValid) {
                    console.log('Form validation failed');
                    return;
                }
                
                // Prepare form data
                const operatingDaysValues = [];
                operatingDays.each(function() {
                    operatingDaysValues.push($(this).val());
                });
                
                const formData = {
                    studio_id: studioId,
                    operating_days: operatingDaysValues,
                    opening_time: openingTime,
                    closing_time: closingTime,
                    booking_limit: bookingLimit,
                    advance_booking: advanceBooking
                };
                
                console.log('Form data prepared:', formData);
                
                // Show loading state
                const submitBtn = $('#submitBtn');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Creating...');
                
                // Submit via AJAX
                $.ajax({
                    url: '{{ route("owner.studio-schedule.store") }}',
                    type: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('AJAX success:', response);
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonColor: '#007BFF',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = '{{ route("owner.studio-schedule.index") }}';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', xhr, status, error);
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Display validation errors
                            const errors = xhr.responseJSON.errors;
                            console.log('Validation errors:', errors);
                            for (const field in errors) {
                                const errorField = $(`[name="${field}"]`);
                                if (errorField.length) {
                                    errorField.addClass('is-invalid');
                                    $(`#${field}_error`).text(errors[field][0]).show();
                                } else if (field.includes('operating_days')) {
                                    $('#operatingDaysGroup').addClass('is-invalid');
                                    $('#operating_days_error').text(errors[field][0]).show();
                                } else if (field.includes('coverage_area')) {
                                    // Ignore coverage_area errors since it's removed
                                } else {
                                    // General error
                                    errorMessage = errors[field][0];
                                }
                            }
                            errorMessage = 'Please fix the errors above.';
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#DC3545'
                        });
                    },
                    complete: function() {
                        console.log('AJAX complete');
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            }
        });
    </script>
@endsection