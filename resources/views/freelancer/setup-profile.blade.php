@extends('layouts.freelancer.app')
@section('title', 'Setup Profile')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/choices/choices.min.css') }}">
@endsection

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Setup your Personal and Brand Profile</h4>
                        </div>
                        <div class="card-body">
                            <form id="profileSetupForm" method="POST" action="{{ route('freelancer.profile.store') }}" enctype="multipart/form-data" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Personal Information</h4>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Freelancer Name</label>
                                        <input type="text" class="form-control" name="freelancer_name" value="{{ auth()->user()->first_name . ' ' . auth()->user()->last_name }}" readonly required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="freelancer_email" value="{{ auth()->user()->email }}" readonly required>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="tel" class="form-control" name="freelancer_mobile_number" value="{{ auth()->user()->mobile_number }}" required>
                                        <div class="form-text">To change your mobile number, please contact support.</div>
                                    </div>

                                    <div class="col-md-12 mb-3">
                                        <label class="form-label fw-semibold">Upload Profile Picture</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="profilePicture" name="profile_picture" accept=".jpg,.jpeg,.png" required>
                                        </div>
                                        <div class="form-text">Upload a clear copy of your profile picture.</div>
                                        <div class="invalid-feedback">
                                            Please upload a valid profile picture.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Brand Identity</h4>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Brand Name</label>
                                        <input type="text" class="form-control" placeholder="Enter your brand name" name="brand_name" required>
                                        <div class="invalid-feedback">
                                            Please enter your brand name.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Professional Tagline</label>
                                        <input type="text" class="form-control" name="professional_tagline" placeholder="Enter your professional tagline" required>
                                        <small>Optional - You can leave this blank</small>
                                        <div class="invalid-feedback">
                                            Please enter your professional tagline.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">About Me / Bio</label>
                                        <textarea class="form-control" name="bio" rows="5" placeholder="Enter your bio" required></textarea>
                                        <div class="invalid-feedback">
                                            Please enter your bio.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" placeholder="Enter years of experience" name="years_of_experience" required>
                                        <div class="invalid-feedback">
                                            Please enter years of experience.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-semibold">Brand Logo</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="brandLogo" name="brand_logo" accept=".jpg,.jpeg,.png" required>
                                        </div>
                                        <div class="form-text">Upload a clear copy of your brand logo.</div>
                                        <div class="invalid-feedback">
                                            Please upload a valid brand logo.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Location and Coverage</h4>
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Province</label>
                                        <input type="text" class="form-control" name="province" value="Cavite" readonly disabled required>
                                        <small>cannot be changed</small>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Municipality</label>
                                        <select class="form-control" name="municipality" id="municipalitySelect" required>
                                            <option value="" disabled selected>Select your municipality</option>
                                            @foreach($municipalities as $municipality)
                                                <option value="{{ $municipality }}">{{ $municipality }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select your municipality.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Barangay</label>
                                        <select class="form-control" name="barangay" id="barangaySelect" required disabled>
                                            <option value="" disabled selected>Select barangay</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select your barangay.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">ZIP Code</label>
                                        <input type="text" class="form-control" name="zip_code" id="zipCodeInput" value="" readonly required>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Street Address</label>
                                        <input type="text" class="form-control" placeholder="Enter your street address" name="street" required>
                                        <div class="invalid-feedback">
                                            Please enter your street address.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label mb-2">Service Area</label>
                                        <div class="mb-2">
                                            <div class="btn-group w-100 mb-1" role="group" aria-label="Service area toggle button group" id="serviceAreaGroup">
                                                <input type="radio" class="btn-check" id="btnWithinCity" name="service_area" value="Within my city only" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary" for="btnWithinCity">Within my city only</label>

                                                <input type="radio" class="btn-check" id="btnWithinCavite" name="service_area" value="Within Cavite province" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="btnWithinCavite">Within Cavite province</label>
                                            </div>
                                            <small class="d-block text-muted">Select which areas you are willing to provide services</small>
                                            <div class="invalid-feedback" id="service_area_error">Please select at least one area.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Services Information</h4>
                                    <div class="col-12">
                                        <label class="form-label">Service Categories</label>
                                        <select class="form-control" id="choices-multiple-remove-button" data-choices data-choices-removeItem name="category_services[]" multiple required>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select at least one service category.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Starting Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">PHP</span>
                                            <input type="number" class="form-control" name="starting_price" placeholder="00.00" required>
                                            <div class="invalid-feedback">
                                                Please enter your starting price.
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ==== Start: Deposit Policy Enhancement ==== --}}
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label mb-2">Deposit Policy</label>
                                        <div class="mb-2">
                                            <div class="btn-group w-100 mb-1" role="group" aria-label="Deposit policy toggle button group" id="depositPolicyGroup">
                                                <input type="radio" class="btn-check" id="btnDepositRequired" name="deposit_policy" value="required" autocomplete="off">
                                                <label class="btn btn-outline-primary" for="btnDepositRequired">Required</label>

                                                <input type="radio" class="btn-check" id="btnDepositNotRequired" name="deposit_policy" value="not_required" autocomplete="off" checked>
                                                <label class="btn btn-outline-primary" for="btnDepositNotRequired">Not Required</label>
                                            </div>
                                            <small class="d-block text-muted">Do you require a deposit from clients? If not required, client pays full amount upon booking.</small>
                                            <div class="invalid-feedback" id="deposit_policy_error">Please select a deposit policy.</div>
                                        </div>
                                    </div>

                                    {{-- Deposit Configuration Fields (Hidden by default) --}}
                                    <div class="col-12" id="depositConfigSection" style="display: none;">
                                        <div class="row">
                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label">Deposit Type</label>
                                                <select class="form-control" name="deposit_type" id="depositType">
                                                    <option value="" disabled selected>Select deposit type</option>
                                                    <option value="fixed">Fixed Amount (PHP)</option>
                                                    <option value="percentage">Percentage (%)</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a deposit type.
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6 mb-3">
                                                <label class="form-label" id="depositAmountLabel">Deposit Amount</label>
                                                <div class="input-group">
                                                    <span class="input-group-text" id="depositAmountSymbol">PHP</span>
                                                    <input type="number" class="form-control" name="deposit_amount" id="depositAmount" placeholder="Enter amount" step="0.01" min="0.01">
                                                </div>
                                                <small class="text-muted" id="depositAmountHint">Enter the fixed amount required as deposit</small>
                                                <div class="invalid-feedback">
                                                    Please enter a valid deposit amount.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- ==== End: Deposit Policy Enhancement ==== --}}
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Availability and Schedule</h4>
                                    <div class="col-12 mb-3">
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

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="start_time" required>
                                        <div class="invalid-feedback">
                                            Please enter the start time.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="end_time" required>
                                        <div class="invalid-feedback">
                                            Please enter the end time.
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label">Maximum Client per Day</label>
                                        <input type="number" class="form-control" name="max_clients_per_day" min="1" max="100" placeholder="Enter the maximum client per day" required>
                                        <div class="invalid-feedback">
                                            Please enter the maximum client per day.
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label">Advance Booking</label>
                                        <input type="number" class="form-control" name="advance_booking_days" min="1" max="31" placeholder="Enter the advance booking days" required>
                                        <small class="form-text text-muted">The minimum number of days before the freelancer can be reserved</small>
                                        <div class="invalid-feedback">
                                            Please enter the advance booking days.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Personal Portfolio</h4>                                    
                                    <div class="col-12 mb-3">                                    
                                        <label class="form-label fw-semibold">Portfolio / Sample Works</label>
                                        <input type="file" class="form-control" name="portfolios[]" accept=".pdf,.jpg,.jpeg,.png" multiple required>
                                        <div class="form-text">Upload multiple clear copies of your portfolio / sample works</div>
                                        <div class="invalid-feedback">
                                            Please upload your portfolio / sample works.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Facebook URL <small class="text-muted">(Optional)</small></label>
                                        <input type="url" class="form-control" placeholder="https://facebook.com/yourpage" name="facebook_url">
                                        <div class="invalid-feedback">
                                            Please enter a valid Facebook URL.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Instagram URL <small class="text-muted">(Optional)</small></label>
                                        <input type="url" class="form-control" placeholder="https://instagram.com/yourprofile" name="instagram_url">
                                        <div class="invalid-feedback">
                                            Please enter a valid Instagram URL.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Website URL <small class="text-muted">(Optional)</small></label>
                                        <input type="url" class="form-control" placeholder="https://yourwebsite.com" name="website_url">
                                        <div class="invalid-feedback">
                                            Please enter a valid website URL.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-1">Verification Documents</h4>
                                    <p class="text-muted mb-3">Please upload the required documents for verification. Maximum file size: 3MB per file. Supported formats: PDF, JPG, PNG.</p>
                                    
                                    <div class="col-12 mb-3">                                            
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Valid Government ID (Freelancer)</label>
                                            <input type="file" class="form-control" name="freelancer_id_document" accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Upload a clear copy of any valid government ID</div>
                                            <div class="invalid-feedback">
                                                Please upload a valid government ID.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span id="submitText">Submit Form</span>
                                            <div id="submitSpinner" class="spinner-border spinner-border-sm d-none" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </button>
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
    <script src="{{ asset('assets/plugins/choices/choices.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initialize Choices.js for multiple select
            if (document.querySelector('#choices-multiple-remove-button')) {
                const choices = new Choices('#choices-multiple-remove-button', {
                    removeItemButton: true,
                    searchEnabled: true,
                    placeholder: true,
                    placeholderValue: 'Select service categories...',
                    searchPlaceholderValue: 'Search categories...',
                });
            }

            // ==== Start: Deposit Policy Enhancement ==== //
            
            // Deposit Policy toggle handler
            function toggleDepositConfig() {
                const depositRequired = $('#btnDepositRequired').is(':checked');
                
                if (depositRequired) {
                    $('#depositConfigSection').slideDown(300);
                    // Make deposit fields required
                    $('#depositType, #depositAmount').prop('required', true);
                } else {
                    $('#depositConfigSection').slideUp(300);
                    // Remove required and clear values
                    $('#depositType, #depositAmount').prop('required', false).val('');
                    $('#depositAmountSymbol').text('PHP');
                    $('#depositAmountHint').text('Enter the fixed amount required as deposit');
                }
            }

            // Initial check
            toggleDepositConfig();

            // Listen to deposit policy changes
            $('#btnDepositRequired, #btnDepositNotRequired').change(function() {
                toggleDepositConfig();
            });

            // Deposit Type change handler
            $('#depositType').change(function() {
                const depositType = $(this).val();
                
                if (depositType === 'fixed') {
                    $('#depositAmountSymbol').text('PHP');
                    $('#depositAmountLabel').text('Deposit Amount (PHP)');
                    $('#depositAmountHint').text('Enter the fixed amount required as deposit');
                    $('#depositAmount').attr({
                        'min': '1',
                        'max': '1000000',
                        'step': '0.01'
                    });
                } else if (depositType === 'percentage') {
                    $('#depositAmountSymbol').text('%');
                    $('#depositAmountLabel').text('Deposit Percentage (%)');
                    $('#depositAmountHint').text('Enter the percentage of total amount required as deposit (1-100%)');
                    $('#depositAmount').attr({
                        'min': '1',
                        'max': '100',
                        'step': '0.01'
                    });
                }
            });

            // ==== End: Deposit Policy Enhancement ==== //

            // Municipality change handler
            $('#municipalitySelect').change(function() {
                const municipality = $(this).val();
                
                if (!municipality) {
                    $('#barangaySelect').html('<option value="" disabled selected>Select barangay</option>').prop('disabled', true);
                    $('#zipCodeInput').val('');
                    return;
                }

                // Show loading
                $('#barangaySelect').html('<option value="" disabled selected>Loading barangays...</option>').prop('disabled', true);

                $.ajax({
                    url: '{{ route("freelancer.profile.get-barangays") }}',
                    type: 'POST',
                    data: {
                        municipality: municipality,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        let options = '<option value="" disabled selected>Select barangay</option>';
                        response.barangays.forEach(function(barangay) {
                            options += `<option value="${barangay}">${barangay}</option>`;
                        });
                        
                        $('#barangaySelect').html(options).prop('disabled', false);
                        $('#zipCodeInput').val(response.zip_code);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load barangays. Please try again.',
                            confirmButtonColor: '#007BFF'
                        });
                        $('#barangaySelect').html('<option value="" disabled selected>Error loading barangays</option>').prop('disabled', true);
                    }
                });
            });

            // Form submission handler
            $('#profileSetupForm').submit(function(e) {
                e.preventDefault();
                
                // Disable submit button and show spinner
                $('#submitBtn').prop('disabled', true);
                $('#submitText').addClass('d-none');
                $('#submitSpinner').removeClass('d-none');

                // Get form data
                const formData = new FormData(this);

                // Submit via AJAX
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonColor: '#007BFF',
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = response.redirect;
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#submitBtn').prop('disabled', false);
                        $('#submitText').removeClass('d-none');
                        $('#submitSpinner').addClass('d-none');

                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Handle validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors)[0][0];
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonColor: '#007BFF'
                        });
                    }
                });
            });

            // Bootstrap validation
            (function() {
                'use strict';
                window.addEventListener('load', function() {
                    var forms = document.getElementsByClassName('needs-validation');
                    Array.prototype.filter.call(forms, function(form) {
                        form.addEventListener('submit', function(event) {
                            if (form.checkValidity() === false) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                            form.classList.add('was-validated');
                        }, false);
                    });
                }, false);
            })();
        });
    </script>
@endsection
