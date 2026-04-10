@extends('layouts.auth.app')
@section('title', 'Register')

{{-- STYLES --}}
@section('styles')
    <style>
        .user-type-card {
            transition: all 0.2s ease;
            border: 1px dashed #dee2e6;
        }

        .user-type-card:hover {
            border-color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.02);
        }

        .btn-check:checked + .user-type-card {
            border-color: #0d6efd;
            border-width: 2px;
            border-style: solid;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .btn-check:checked + .user-type-card .ti-user {
            color: #fff;
        }

        .btn-check:checked + .user-type-card .ti-camera {
            color: #fff;
        }

        .btn-check:checked + .user-type-card .rounded-circle {
            background-color: #ffffff !important;
        }

        .btn-check:checked + .user-type-card .rounded-circle .ti-user {
            color: #fff;
        }

        .btn-check:checked + .user-type-card .rounded-circle .ti-camera {
            color: #fff;
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .password-match-error {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-8">
                <div class="card p-4">
                    <div class="auth-brand text-center mb-4">
                        <a href="" class="logo-dark">
                            <img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo" height="28">
                        </a>
                        <a href="" class="logo-light">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" height="28">
                        </a>
                        <p class="text-muted w-lg-75 mt-3 mx-auto">Let's create your account. Enter your credentials to continue.</p>
                    </div>

                    <form id="registerForm" method="POST" novalidate>
                        @csrf
                        <div class="row g-3 mb-3">
                            <h4 class="text-center">Hello! I am a....</h4>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="userType" id="clientType" value="client" required>
                                <label class="card border-1 border-dashed h-100 cursor-pointer p-2 mb-0 user-type-card" for="clientType">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle bg-light-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                    <i data-lucide="user" class="text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1 fw-semibold text-dark">Client</h5>
                                                <p class="small mb-0">Looking for Photographer Services</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="userType" id="freelancerType" value="freelancer" required>
                                <label class="card border-1 border-dashed h-100 cursor-pointer p-2 mb-0 user-type-card" for="freelancerType">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle bg-light-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                    <i data-lucide="camera" class="text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1 fw-semibold text-dark">Freelancer</h5>
                                                <p class="small mb-0">Offering Photography Services</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="userType" id="studioOwnerType" value="owner" required>
                                <label class="card border-1 border-dashed h-100 cursor-pointer p-2 mb-0 user-type-card" for="studioOwnerType">
                                    <div class="card-body p-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="rounded-circle bg-light-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                    <i data-lucide="clapperboard" class="text-primary fs-3"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="mb-1 fw-semibold text-dark">Studio Owner</h5>
                                                <p class="small mb-0">Owner of Photography Studio</p>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-12">
                                <div class="invalid-feedback d-block text-center" id="userTypeError"></div>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter First Name" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid first name.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="middleName" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middleName" name="middleName" placeholder="Enter Middle Name">
                                    <div class="invalid-feedback">
                                        Please enter a valid middle name.
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter Last Name" required>
                                    <div class="invalid-feedback">
                                        Please enter a valid last name.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="userEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="userEmail" name="userEmail" placeholder="email@example.com" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="userMobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" data-toggle="input-mask" data-mask-format="+(63)000 000 0000" inputmode="tel" name="userMobile" placeholder="Enter Mobile Number" required>
                            <div class="invalid-feedback">
                                Please enter a valid mobile number.
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="userPassword" class="form-label">Password</label>   
                            <input type="password" class="form-control mb-1" id="userPassword" name="userPassword" placeholder="Enter Password" required>
                            <div class="password-bar mb-1"></div>
                            <p class="text-muted fs-xs mb-0">Use 8 or more characters with a mix of letters, numbers & symbols.</p>
                            <div class="invalid-feedback">
                                Please enter a valid password.
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="userConfirmPassword" class="form-label">Confirm Password</label>   
                            <input type="password" class="form-control" id="userConfirmPassword" name="userConfirmPassword" placeholder="Enter Confirm Password" required>
                            <div class="invalid-feedback">
                                Please enter a correct confirm password.
                            </div>
                            <div class="password-match-error" id="passwordMatchError">
                                Passwords do not match.
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="province" class="form-label">Province</label>
                            <input type="text" class="form-control" id="province" name="province" value="Cavite" readonly disabled>
                        </div>

                        <div class="form-group mb-3">
                            <label for="municipality" class="form-label">Select Municipality</label>
                            <select class="form-select" id="municipality" name="municipality" required>
                                <option value="" disabled selected>Select Municipality</option>
                                @foreach($municipalities ?? [] as $municipality)
                                    <option value="{{ $municipality }}">{{ $municipality }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback">
                                Please select your municipality.
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="agreeTerms" name="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms">
                                I agree to the <a href="" class="text-primary" data-bs-toggle="modal" data-bs-target="#termsAgreementModal">Terms and Agreement</a>
                            </label>
                            <div class="invalid-feedback">
                                Please agree to the Terms and Agreement.
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-1 d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                <span id="submitText">Create Account</span>
                            </button>
                        </div>
                    </form>

                    <p class="text-muted text-center mt-4 mb-0">
                        Already have an account? <a href="{{ route('login') }}" class="text-primary">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Scrollable Modal --}}
    <div class="modal fade" id="termsAgreementModal" tabindex="-1" role="dialog" aria-labelledby="scrollableModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="scrollableModalTitle">Terms and Conditions</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card-title fw-semibold">
                        Welcome to the Web-Based Integrated Management Platform for Photographers and Photographic Studios in Cavite (hereinafter referred to as the “System”).
                    </div>
                    <hr class="my-3 border-dashed">
                    <div class="mt-2">
                        <div class="col">
                            <h5 class="text-primary text-semibold">1. Acceptance of Terms</h5>
                            <p>By creating an account and using the System, you acknowledge that you have read, understood, and agreed to these Terms and Conditions. These terms apply to all users, including Administrators, Photographers/Studio Owners, and Clients.</p>
                        </div>
                        <div class="col">
                            <h5 class="text-primary text-semibold">2. User Eligibility</h5>
                            <ul>
                                <li>Users must provide accurate, complete, and truthful information during registration.</li>
                                <li>Photographers and studio owners represent that they are authorized to offer photography services.</li>
                                <li>Clients must be legally capable of entering into service agreements.</li>
                            </ul>
                        </div>
                        <div class="col">
                            <h5 class="text-primary text-semibold">3. User Roles and Responsibilities</h5>
                            <p><strong>3.1 Administrator</strong><br>
                                Manages system-level operations, user verification, and platform integrity.<br>
                                Does not participate in photography services, bookings, or client transactions.
                            </p>
                            <p><strong>3.2 Photographer / Studio Owner</strong><br>
                                Responsible for managing studio profiles, services, pricing, bookings, and uploaded content.<br>
                                Must ensure that uploaded photos do not violate intellectual property, privacy, or any applicable laws.<br>
                                Is fully responsible for service fulfillment, pricing accuracy, and client communication.
                            </p>
                            <p><strong>3.3 Client</strong><br>
                                Responsible for providing accurate booking information.<br>
                                Must respect photographers’ copyrights and usage restrictions of photos.<br>
                                Shall not misuse or redistribute images without permission.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">4. Account Registration and Security</h5>
                            <p>
                                Users are responsible for maintaining the confidentiality of their login credentials.<br>
                                Any activity performed using a registered account is the responsibility of the account holder.<br>
                                The System is not liable for unauthorized access resulting from user negligence.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">5. Email Verification</h5>
                            <p>
                                Users are required to verify their email address through a verification link sent upon registration.<br>
                                Accounts may have limited access until email verification is completed.<br>
                                Verification tokens expire after a set period for security purposes.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">6. Bookings and Transactions</h5>
                            <p>
                                The System acts only as a management and facilitation platform.<br>
                                All agreements, payments, and service deliveries are strictly between the photographer and the client.<br>
                                The System is not responsible for disputes, cancellations, delays, or dissatisfaction related to services rendered.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">7. Online Gallery and Uploaded Content</h5>
                            <p>
                                Photographers retain ownership of uploaded photos unless otherwise agreed with the client.<br>
                                Clients are granted access only according to permissions set by the photographer.<br>
                                Unauthorized downloading, sharing, or commercial use of photos is strictly prohibited.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">8. Decision Support System (DSS)</h5>
                            <p>
                                The DSS provides analytics and recommendations based on available data.<br>
                                Insights generated by the DSS are for informational purposes only.<br>
                                Final business decisions remain the responsibility of the photographer or studio owner.
                            </p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">9. Prohibited Activities</h5>
                            <p>Users agree not to:</p>
                            <ul>
                                <li>Upload false, misleading, or illegal content</li>
                                <li>Impersonate another individual or studio</li>
                                <li>Attempt to gain unauthorized access to the system</li>
                                <li>Use the system for fraudulent or unlawful purposes</li>
                            </ul>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">10. Account Suspension or Termination</h5>
                            <p>The Administrator reserves the right to:</p>
                            <ul>
                                <li>Suspend or terminate accounts that violate these terms</li>
                                <li>Remove content that is inappropriate or unlawful</li>
                                <li>Deny access to users who misuse the platform</li>
                            </ul>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">11. Data Privacy</h5>
                            <p>User data is collected and processed in accordance with the System’s Privacy Policy.<br>
                            Personal information will not be shared without consent, except as required by law.</p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">12. Limitation of Liability</h5>
                            <p>The System shall not be held liable for:</p>
                            <ul>
                                <li>Loss of data due to system downtime or technical issues</li>
                                <li>Disputes between photographers and clients</li>
                                <li>Service quality, delays, or financial losses arising from bookings</li>
                            </ul>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">13. Modifications to Terms</h5>
                            <p>The System reserves the right to modify these Terms and Conditions at any time. Continued use of the System constitutes acceptance of updated terms.</p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">14. Governing Law</h5>
                            <p>These Terms and Conditions shall be governed by and interpreted in accordance with the laws of the Republic of the Philippines.</p>
                        </div>

                        <div class="col">
                            <h5 class="text-primary text-semibold">15. Contact Information</h5>
                            <p>For concerns or inquiries, users may contact the System Administrator through the official communication channels provided within the platform.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script src="{{ asset('assets/plugins/inputmask/inputmask.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-inputmask.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Initially hide password match error
            $('#passwordMatchError').hide();
            
            // Real-time password validation
            $('#userPassword, #userConfirmPassword').on('input', function() {
                var password = $('#userPassword').val();
                var confirmPassword = $('#userConfirmPassword').val();
                
                if (confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $('#userConfirmPassword').addClass('is-invalid').removeClass('is-valid');
                        $('#passwordMatchError').show();
                    } else {
                        $('#userConfirmPassword').removeClass('is-invalid').addClass('is-valid');
                        $('#passwordMatchError').hide();
                    }
                } else {
                    $('#userConfirmPassword').removeClass('is-invalid is-valid');
                    $('#passwordMatchError').hide();
                }
            });
            
            // Form validation before submission
            $('#registerForm').on('submit', function(e) {
                e.preventDefault();
                
                // Reset all validation states
                $('.is-invalid').removeClass('is-invalid');
                $('.is-valid').removeClass('is-valid');
                $('#userTypeError').hide();
                $('#passwordMatchError').hide();
                
                // Validate required fields
                var isValid = true;
                var errorFields = [];
                
                // Check user type
                var userType = $('input[name="userType"]:checked').val();
                if (!userType) {
                    $('#userTypeError').text('Please select your account type.').show();
                    isValid = false;
                }
                
                // Check name fields
                if (!$('#firstName').val().trim()) {
                    $('#firstName').addClass('is-invalid');
                    isValid = false;
                }
                
                if (!$('#lastName').val().trim()) {
                    $('#lastName').addClass('is-invalid');
                    isValid = false;
                }
                
                // Check email
                if (!$('#userEmail').val().trim()) {
                    $('#userEmail').addClass('is-invalid');
                    isValid = false;
                }
                
                // Check municipality
                if (!$('#municipality').val()) {
                    $('#municipality').addClass('is-invalid');
                    isValid = false;
                }
                
                // Check password match
                var password = $('#userPassword').val();
                var confirmPassword = $('#userConfirmPassword').val();
                
                if (password !== confirmPassword) {
                    $('#userConfirmPassword').addClass('is-invalid');
                    $('#passwordMatchError').show();
                    isValid = false;
                }
                
                // Check terms agreement
                if (!$('#agreeTerms').is(':checked')) {
                    $('#agreeTerms').addClass('is-invalid');
                    isValid = false;
                }
                
                if (!isValid) {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fill all required fields correctly.',
                        showConfirmButton: true,
                        confirmButtonColor: '#dc3545'
                    });
                    return false;
                }
                
                // Show spinner and disable button
                $('#submitSpinner').removeClass('d-none');
                $('#submitText').text('Creating Account...');
                $('#submitBtn').prop('disabled', true);
                
                // Submit form via AJAX
                $.ajax({
                    url: '{{ route("auth.register.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success SweetAlert with progress bar
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                willClose: () => {
                                    window.location.href = response.redirect;
                                }
                            });
                        } else {
                            // Show error SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                showConfirmButton: true,
                                confirmButtonColor: '#dc3545'
                            });
                            resetSubmitButton();
                        }
                    },
                    error: function(xhr) {
                        var errors = xhr.responseJSON?.errors;
                        var errorMessage = 'Registration failed. Please check the form.';
                        
                        if (errors) {
                            // Display validation errors
                            $.each(errors, function(key, value) {
                                var field = $('[name="' + key + '"]');
                                if (field.length) {
                                    field.addClass('is-invalid');
                                    field.next('.invalid-feedback').text(value[0]);
                                }
                            });
                            errorMessage = 'Please correct the errors in the form.';
                        }
                        
                        // Show error SweetAlert
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            showConfirmButton: true,
                            confirmButtonColor: '#dc3545'
                        });
                        resetSubmitButton();
                    }
                });
                
                // Function to reset submit button state
                function resetSubmitButton() {
                    $('#submitSpinner').addClass('d-none');
                    $('#submitText').text('Create Account');
                    $('#submitBtn').prop('disabled', false);
                }
            });
            
            // Real-time user type validation
            $('input[name="userType"]').on('change', function() {
                $('#userTypeError').hide();
            });
            
            // Real-time validation for required fields
            $('input[required], select[required]').on('input change', function() {
                if ($(this).val()) {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                } else {
                    $(this).removeClass('is-valid');
                }
            });
            
            // Terms checkbox validation
            $('#agreeTerms').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).removeClass('is-invalid');
                }
            });
        });
    </script>
@endsection
