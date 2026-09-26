@extends('layouts.auth.app')
@section('title', 'Login')

{{-- STYLES --}}
@section('styles')
    <style>
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #007BFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #6c757d;
        }
        
        .password-input-group {
            position: relative;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-4 col-md-6 col-sm-8">
                <div class="card p-4">
                    <div class="auth-brand text-center mb-4">
                        <a href="" class="logo-dark">
                            <img src="{{ asset('assets/images/logo-black.png') }}" alt="dark logo" height="28">
                        </a>
                        <a href="" class="logo-light">
                            <img src="{{ asset('assets/images/logo.png') }}" alt="logo" height="28">
                        </a>
                        <p class="text-muted w-lg-75 mt-3 mx-auto">Let's get you signed in. Enter your email and password to continue.</p>
                    </div>

                    <!-- Display session messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- Visible error message for failed or blocked attempts --}}
                    <div class="alert alert-danger d-none mb-3" id="loginErrorAlert" role="alert"></div>

                    <form id="loginForm" method="POST" action="{{ route('auth.login.store') }}" novalidate>
                        @csrf
                        
                        <div class="form-group mb-2">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" placeholder="Enter Email Address" required>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>

                        <div class="form-group mb-2">
                            <label for="password" class="form-label">Password</label>   
                            <div class="password-input-group">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter Password" required>
                                <span class="password-toggle" id="togglePassword">
                                    <i data-lucide="eye" id="eyeIcon"></i>
                                </span>
                            </div>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center mb-3">
                            <a href="" class="text-primary">Forgot Password?</a>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-semibold" id="submitBtn">Sign In</button>
                        </div>
                    </form>

                    <p class="text-muted text-center mt-4 mb-0">
                        Don't have account? <a href="{{ route('register') }}" class="text-primary">Create an account</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Loading overlay shown while the sign-in request is in flight --}}
    <div id="loadingOverlay" class="loading-overlay" aria-hidden="true">
        <div class="loading-spinner" role="status">
            <span class="visually-hidden">Signing in...</span>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Password visibility toggle
            $('#togglePassword').on('click', function() {
                const passwordInput = $('#password');
                const eyeIcon = $('#eyeIcon');
                const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
                
                passwordInput.attr('type', type);
                
                // Toggle eye icon
                if (type === 'text') {
                    eyeIcon.attr('data-lucide', 'eye-off');
                } else {
                    eyeIcon.attr('data-lucide', 'eye');
                }
                
                // Refresh lucide icons
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
            
            // Show a message above the form so a failed attempt is never silent.
            function showLoginError(message) {
                $('#loginErrorAlert').removeClass('d-none').text(message);
            }

            function hideLoginError() {
                $('#loginErrorAlert').addClass('d-none').text('');
            }

            // Mark the fields the server rejected and show their messages.
            function markInvalidFields(errors) {
                if (!errors) {
                    return;
                }

                $.each(errors, function(field, messages) {
                    var input = $('#' + field);
                    if (!input.length) {
                        input = $('[name="' + field + '"]');
                    }

                    input.addClass('is-invalid');
                    if (messages && messages.length) {
                        input.siblings('.invalid-feedback').text(messages[0]).show();
                    }
                });
            }

            // Form validation
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();

                // One email rule for the client and the server.
                var emailInput = $('#email');
                if (!window.PlatinumEmail.isValid($.trim(emailInput.val() || ''))) {
                    emailInput.addClass('is-invalid').siblings('.invalid-feedback').show();
                    e.stopPropagation();
                    return false;
                }

                emailInput.removeClass('is-invalid').siblings('.invalid-feedback').hide();

                // Basic form validation
                const form = $(this)[0];
                if (!form.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return false;
                }

                hideLoginError();

                // Show loading state
                $('#loadingOverlay').show();
                $('#submitBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Signing in...');
                
                // Submit through the shared session helper. It reads the token at
                // request time and retries the request one time after a 419.
                window.PlatinumSession.post($(this).attr('action'), $(this).serialize())
                    .done(function(response) {
                        if (response.success) {
                            // Show success SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                                didClose: () => {
                                    // Redirect to dashboard
                                    window.location.href = response.redirect;
                                }
                            });
                        } else if (response.needs_verification) {
                            // Check if email needs verification
                            showLoginError(response.message);
                            Swal.fire({
                                icon: 'warning',
                                title: 'Email Verification Required',
                                text: response.message,
                                showCancelButton: true,
                                confirmButtonText: 'Go to Verification',
                                cancelButtonText: 'OK',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirect to verification page
                                    window.location.href = '{{ route("verify") }}';
                                }
                            });
                        } else {
                            // Keep the entered email so the user can correct the password.
                            showLoginError(response.message || 'Login failed. Please try again.');
                            Swal.fire({
                                icon: 'error',
                                title: 'Login Failed',
                                text: response.message || 'Login failed. Please try again.',
                                confirmButtonColor: '#DC3545',
                            });
                        }
                    })
                    .fail(function(xhr) {
                        var response = xhr.responseJSON || {};
                        var errorMessage = response.message || 'Login failed. Please try again.';

                        if (xhr.status === 422) {
                            errorMessage = response.message || 'Please check the highlighted fields and try again.';
                            markInvalidFields(response.errors);
                            showLoginError(errorMessage);
                        } else if (xhr.status >= 500) {
                            errorMessage = response.message || 'Server error. Please try again later.';
                            showLoginError(errorMessage);
                        } else {
                            showLoginError(errorMessage);
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Login Failed',
                            text: errorMessage,
                            confirmButtonColor: '#DC3545',
                        });
                    })
                    .always(function() {
                        // Always reset the loading state.
                        $('#loadingOverlay').hide();
                        $('#submitBtn').prop('disabled', false).html('Sign In');
                    });
            });
            
            // Editing a field clears its error. Feedback returns only on a failed submit.
            $('#loginForm input').on('input', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            });

            // Remove validation on focus
            $('#loginForm input').on('focus', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').hide();
            });
        });
    </script>
@endsection