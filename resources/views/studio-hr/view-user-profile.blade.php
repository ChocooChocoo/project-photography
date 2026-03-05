@extends('layouts.studio-hr.app')
@section('title', 'View Profile')

{{-- STYLES --}}
@section('styles')
    <style>
        /* Minimal styles only for photo upload functionality */
        .profile-photo-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-photo-container .photo-upload-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3475db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid #fff;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .profile-photo-container:hover .photo-upload-btn {
            opacity: 1;
        }
        
        .photo-upload-btn input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .cover-photo-container {
            position: relative;
            min-height: 300px;
        }
        
        .cover-photo-container .cover-upload-btn {
            position: absolute;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            opacity: 0;
        }
        
        .cover-photo-container:hover .cover-upload-btn {
            opacity: 1;
        }
        
        .cover-upload-btn input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            left: 0;
            top: 0;
        }
        
        /* Password meter styles (copied from register page) */
        .password-bar {
            height: 5px;
            width: 0%;
            background-color: #ddd;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .password-bar.weak {
            background-color: #dc3545;
            width: 33.33%;
        }
        
        .password-bar.medium {
            background-color: #ffc107;
            width: 66.66%;
        }
        
        .password-bar.strong {
            background-color: #28a745;
            width: 100%;
        }
        
        .password-strength-text {
            font-size: 0.875rem;
            margin-top: 5px;
            font-weight: 500;
        }
        
        .password-strength-text.weak {
            color: #dc3545;
        }
        
        .password-strength-text.medium {
            color: #ffc107;
        }
        
        .password-strength-text.strong {
            color: #28a745;
        }
        
        .password-match-error {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        
        .fs-xs {
            font-size: 0.75rem;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="page-title-head d-flex align-items-center">
                <div class="flex-grow-1">
                    <h4 class="fs-xl fw-bold m-0">My Profile</h4>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <article class="card overflow-hidden mb-0">
                        <div id="coverPhotoPreview" class="position-relative card-side-img overflow-hidden cover-photo-container" style="min-height: 300px; background-image: url({{ asset('assets/images/profile-bg.jpg') }}); background-size: cover; background-position: center;">
                            <div class="cover-upload-btn">
                                <i class="ti ti-camera me-2"></i>Change Cover
                                <input type="file" id="cover_photo" name="cover_photo" form="profileForm" accept="image/jpeg,image/png,image/jpg,image/gif">
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <div class="px-3 mt-n2">
                <div class="row">
                    <div class="col-xl-4">
                        <div class="card card-top-sticky">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3 position-relative profile-photo-container">
                                        <img id="profilePhotoPreview" src="{{ asset('assets/images/users/user-3.jpg') }}" alt="avatar" class="rounded-circle" width="72" height="72" style="object-fit: cover;">
                                        <div class="photo-upload-btn">
                                            <i class="ti ti-camera"></i>
                                            <input type="file" id="profile_photo" name="profile_photo" form="profileForm" accept="image/jpeg,image/png,image/jpg,image/gif">
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            <a href="#" class="link-reset" id="displayName">Loading...</a>
                                        </h5>
                                        <p class="text-muted" id="displayEmail">Loading...</p>
                                        <div class="badge badge-soft-primary p-1" id="displayRole">Client</div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="avatar-sm d-flex align-items-center justify-content-center">
                                            <i class="ti ti-calendar text-primary fs-xl"></i>
                                        </div>
                                        <p class="mb-0 fs-sm">Member Since: <span id="createdAt">Loading...</span></p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="avatar-sm d-flex align-items-center justify-content-center">
                                            <i class="ti ti-shield-check text-primary fs-xl"></i>
                                        </div>
                                        <p class="mb-0 fs-sm">Email Status: <span id="emailStatus">Loading...</span></p>
                                    </div>
                                </div>
                            </div> 
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="card">
                            <div class="card-header card-tabs d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="card-title">Edit Profile</h4>
                                </div>
                            </div>

                            <div class="card-body">
                                <div id="loadingSpinner" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading profile data...</p>
                                </div>

                                <form id="profileForm" style="display: none;" class="needs-validation" novalidate enctype="multipart/form-data">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="uuid" class="form-label">UUID</label>
                                                <input type="text" class="form-control" id="uuid" name="uuid" disabled readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter your first name" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="middle_name" class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Enter your middle name">
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter your last name" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="mobile_number" class="form-label">Mobile Number</label>
                                                <input type="text" class="form-control" id="mobile_number" name="mobile_number" placeholder="Enter your mobile number" required>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <hr>
                                            <h5 class="">Change Password <small>(Leave blank to keep current)</small></h5>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="current_password" class="form-label">Current Password</label>
                                                <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password">
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="password" class="form-label">New Password</label>
                                                <input type="password" class="form-control mb-1" id="password" name="password" placeholder="Enter new password">
                                                <div class="password-bar mb-1"></div>
                                                <p class="text-muted fs-xs mb-0">Use 8 or more characters with a mix of letters, numbers & symbols.</p>
                                                <div class="invalid-feedback"></div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password">
                                                <div class="invalid-feedback"></div>
                                                <div class="password-match-error" id="passwordMatchError">
                                                    Passwords do not match.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col mt-4">
                                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                                <i class="ti ti-device-floppy me-2"></i>Update Profile
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
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            'use strict';
            
            console.log('Profile page loaded');
            
            const profileForm = $('#profileForm');
            const submitBtn = $('#submitBtn');
            const originalBtnText = submitBtn.html();
            const loadingSpinner = $('#loadingSpinner');
            
            // Initially hide password match error
            $('#passwordMatchError').hide();
            
            // ==================== PASSWORD STRENGTH METER ====================
            // Function to check password strength
            function checkPasswordStrength(password) {
                let strength = 0;
                
                // Check length
                if (password.length >= 8) strength += 1;
                
                // Check for numbers
                if (/\d/.test(password)) strength += 1;
                
                // Check for special characters and mixed case
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password) || (/(?=.*[a-z])(?=.*[A-Z])/.test(password))) strength += 1;
                
                return strength;
            }
            
            // Update password meter
            $('#password').on('input', function() {
                var password = $(this).val();
                var strength = checkPasswordStrength(password);
                var meter = $('.password-bar');
                var strengthText = '';
                
                // Remove existing classes
                meter.removeClass('weak medium strong');
                
                if (password.length === 0) {
                    meter.css('width', '0%');
                } else if (strength === 1) {
                    meter.addClass('weak');
                    strengthText = '<span class="password-strength-text weak">Weak</span>';
                } else if (strength === 2) {
                    meter.addClass('medium');
                    strengthText = '<span class="password-strength-text medium">Medium</span>';
                } else if (strength >= 3) {
                    meter.addClass('strong');
                    strengthText = '<span class="password-strength-text strong">Strong</span>';
                }
                
                // Update or add strength text
                if ($('.password-strength-text').length) {
                    $('.password-strength-text').replaceWith(strengthText);
                } else if (strengthText) {
                    $(this).after(strengthText);
                }
                
                // Check password match if confirm field has value
                if ($('#password_confirmation').val() !== '') {
                    checkPasswordMatch();
                }
            });
            
            // Password match validation
            function checkPasswordMatch() {
                var password = $('#password').val();
                var confirmPassword = $('#password_confirmation').val();
                
                if (confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $('#password_confirmation').addClass('is-invalid').removeClass('is-valid');
                        $('#passwordMatchError').show();
                    } else {
                        $('#password_confirmation').removeClass('is-invalid').addClass('is-valid');
                        $('#passwordMatchError').hide();
                    }
                } else {
                    $('#password_confirmation').removeClass('is-invalid is-valid');
                    $('#passwordMatchError').hide();
                }
            }
            
            $('#password_confirmation').on('input', function() {
                checkPasswordMatch();
            });
            
            // Load user data
            function loadUserData() {
                $.ajax({
                    url: '/profile/data',
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        console.log('User data loaded:', response);
                        
                        if (response.status === 'success') {
                            // Populate form fields
                            $('#uuid').val(response.data.uuid);
                            $('#first_name').val(response.data.first_name);
                            $('#middle_name').val(response.data.middle_name);
                            $('#last_name').val(response.data.last_name);
                            $('#email').val(response.data.email);
                            $('#mobile_number').val(response.data.mobile_number);
                            
                            // Update profile info
                            $('#displayName').text(response.data.full_name);
                            $('#displayEmail').text(response.data.email);
                            $('#displayRole').text(response.data.role_display);
                            $('#createdAt').text(response.data.created_at);
                            $('#emailStatus').text(response.data.email_verified ? 'Verified' : 'Not Verified');
                            
                            // Update photos
                            if (response.data.profile_photo) {
                                $('#profilePhotoPreview').attr('src', response.data.profile_photo);
                            }
                            
                            if (response.data.cover_photo) {
                                $('#coverPhotoPreview').css('background-image', `url(${response.data.cover_photo})`);
                            }
                            
                            // Hide loading spinner and show form
                            loadingSpinner.hide();
                            profileForm.show();
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load user data', xhr);
                        
                        let errorMessage = 'Failed to load profile data. Please refresh the page.';
                        
                        loadingSpinner.html(`
                            <div class="text-center py-5">
                                <i class="ti ti-alert-circle text-danger fs-1 mb-3"></i>
                                <p class="text-danger">${errorMessage}</p>
                                <button class="btn btn-primary mt-3" onclick="location.reload()">
                                    <i class="ti ti-refresh me-2"></i>Refresh Page
                                </button>
                            </div>
                        `);
                    }
                });
            }
            
            // Form submit handler (enhanced version with better error display)
            profileForm.on('submit', function(e) {
                e.preventDefault();
                
                // Check password match if password field is not empty
                var password = $('#password').val();
                var confirmPassword = $('#password_confirmation').val();
                
                if (password !== '' || confirmPassword !== '') {
                    if (password !== confirmPassword) {
                        $('#password_confirmation').addClass('is-invalid');
                        $('#passwordMatchError').show();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Password Mismatch',
                            text: 'New password and confirm password do not match.',
                            confirmButtonColor: '#3475db'
                        });
                        return false;
                    }
                    
                    // Check password strength if password is being changed
                    if (password !== '') {
                        var strength = checkPasswordStrength(password);
                        if (strength < 2) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Weak Password',
                                text: 'Please use a stronger password with at least 8 characters, numbers, and special characters.',
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                    }
                }
                
                submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...').prop('disabled', true);
                
                let formData = new FormData(this);
                
                $.ajax({
                    url: '/profile/update',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            
                            if (response.data) {
                                if (response.data.profile_photo) {
                                    $('#profilePhotoPreview').attr('src', response.data.profile_photo);
                                }
                                if (response.data.cover_photo) {
                                    $('#coverPhotoPreview').css('background-image', `url(${response.data.cover_photo})`);
                                }
                                if (response.data.full_name) {
                                    $('#displayName').text(response.data.full_name);
                                }
                            }
                            
                            // Clear password fields and reset meter
                            $('#password, #password_confirmation, #current_password').val('');
                            $('.password-bar').css('width', '0%').removeClass('weak medium strong');
                            $('.password-strength-text').remove();
                            $('#password_confirmation').removeClass('is-valid is-invalid');
                            $('#passwordMatchError').hide();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let response = xhr.responseJSON;
                            let errors = response.errors;
                            let errorMessage = response.message || 'Validation error occurred.';
                            
                            // Display validation errors on form fields
                            displayValidationErrors(errors);
                            
                            // Build formatted error message for SweetAlert
                            let errorHtml = `
                                <div class="text-start">
                                    <p class="fw-semibold text-dark mb-2">${errorMessage}</p>
                                    <div class="bg-light p-3 rounded" style="max-height: 250px; overflow-y: auto;">
                            `;
                            
                            $.each(errors, function(field, messages) {
                                // Format field name for display
                                let fieldName = field.split('_').map(word => 
                                    word.charAt(0).toUpperCase() + word.slice(1)
                                ).join(' ');
                                
                                errorHtml += `
                                    <div class="mb-2 pb-2 border-bottom border-light">
                                        <small class="text-muted d-block">${fieldName}:</small>
                                        <span class="text-danger">${messages[0]}</span>
                                    </div>
                                `;
                            });
                            
                            errorHtml += `
                                    </div>
                                </div>
                            `;
                            
                            // Show SweetAlert with detailed errors
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: errorHtml,
                                confirmButtonColor: '#3475db',
                                width: 450
                            });
                            
                        } else if (xhr.status === 500) {
                            // Server error
                            let errorMessage = 'Server error occurred. Please try again later.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: errorMessage,
                                confirmButtonColor: '#3475db'
                            });
                        } else {
                            // Other errors
                            let errorMessage = 'An error occurred. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.html(originalBtnText).prop('disabled', false);
                    }
                });
            });

            // Display validation errors on form fields
            function displayValidationErrors(errors) {
                // Clear all previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').empty();
                
                // Display new errors
                $.each(errors, function(field, messages) {
                    let input = $(`#${field}`);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.siblings('.invalid-feedback').html(messages[0]);
                    } else {
                        // Handle field if ID doesn't match exactly
                        if (field === 'current_password') {
                            $('#current_password').addClass('is-invalid');
                            $('#current_password').siblings('.invalid-feedback').html(messages[0]);
                        } else if (field === 'password') {
                            $('#password').addClass('is-invalid');
                            $('#password').siblings('.invalid-feedback').html(messages[0]);
                        }
                    }
                });
            }
            
            // Photo preview
            $('#profile_photo').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profilePhotoPreview').attr('src', e.target.result);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            $('#cover_photo').on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#coverPhotoPreview').css('background-image', `url(${e.target.result})`);
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Remove validation errors on input
            profileForm.find('input').on('keyup change', function() {
                $(this).removeClass('is-invalid');
                $(this).siblings('.invalid-feedback').empty();
            });
            
            // Initialize
            loadUserData();
        });
    </script>
@endsection