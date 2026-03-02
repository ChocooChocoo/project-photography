@extends('layouts.freelancer.app')
@section('title', 'Create Packages')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Create Packages</h4>
                        </div>
                        <div class="card-body">
                            <form id="createPackageForm" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" id="categorySelect" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a category.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Name</label>
                                        <input type="text" class="form-control" name="package_name" placeholder="Enter package name" required>
                                        <div class="invalid-feedback">Please enter package name.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Description</label>
                                        <textarea class="form-control" name="package_description" rows="3" placeholder="Enter package description" required></textarea>
                                        <div class="invalid-feedback">Please enter package description.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Inclusion</label>                                        
                                        <div id="inclusionsContainer">
                                            <div class="input-group mb-2 inclusion-field">
                                                <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                                                <button class="btn btn-default add-inclusion-btn" type="button">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                                <button class="btn btn-default remove-inclusion-btn" type="button" disabled>
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <small id="inclusionCounter">1 of 50 inclusions added</small>
                                        <div class="invalid-feedback">
                                            Please enter at least one package inclusion.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Allow Time Customization</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Time Customization Toggle">
                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationYes" value="1" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="timeCustomizationYes">
                                                <i class="ti ti-clock-edit me-1"></i> Yes, clients can customize duration
                                            </label>

                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationNo" value="0" checked autocomplete="off">
                                            <label class="btn btn-outline-primary" for="timeCustomizationNo">
                                                <i class="ti ti-clock me-1"></i> No, fixed duration only
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">Please select if time customization is allowed.</div>
                                        <small class="text-muted">
                                            <i class="ti ti-info-circle me-1"></i>
                                            When enabled, clients can choose their own duration during booking. When disabled, you must specify a fixed duration.
                                        </small>
                                    </div>

                                    <div class="col-12 mb-3" id="durationField">
                                        <label class="form-label">Duration (hours) <span class="text-danger" id="durationRequired">*</span></label>
                                        <input type="number" class="form-control" name="duration" id="durationInput" placeholder="Enter duration in hours" min="1" max="24">
                                        <div class="invalid-feedback">Please enter valid duration (1-24 hours).</div>
                                        <small class="text-muted" id="durationHelpText">Fixed duration for this package.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Location</label>
                                        <div class="card border-primary bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <label class="text-primary fw-semibold mb-2">
                                                            Multiple Shooting Locations
                                                        </label>
                                                        <p class="text-muted small">
                                                            Freelancers can offer both In-Studio and On-Location services. Enable this option to allow clients to book multiple locations within the same session.
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="col-md-6">
                                                        <div class="form-check form-check-primary form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" id="allowMultipleLocations" name="allow_multiple_locations" value="1" role="switch">
                                                            <label class="form-check-label fw-semibold" for="allowMultipleLocations">
                                                                Allow Multiple Shooting Locations
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="col-md-6" id="maxLocationsField" style="display: none;">
                                                        <label class="form-label fw-semibold">
                                                            Maximum Number of Locations <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="ti ti-map-pin"></i>
                                                            </span>
                                                            <input type="number" class="form-control" name="max_locations" id="maxLocations" placeholder="Enter max locations (1-10)" min="1" max="10" step="1" value="1">
                                                        </div>
                                                        <small class="text-muted">
                                                            Maximum of 10 locations allowed per booking.
                                                        </small>
                                                        <div class="invalid-feedback" id="maxLocationsError">
                                                            Please enter a valid number between 1 and 10.
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Info Alert for Single Location -->
                                                <div class="alert alert-info alert-sm mt-2 mb-0" id="singleLocationInfo" style="display: none;">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    <strong>Single Location:</strong> Package will be limited to one location only.
                                                </div>
                                                
                                                <!-- Info Alert for Multiple Locations -->
                                                <div class="alert alert-success alert-sm mt-2 mb-0" id="multipleLocationInfo" style="display: none;">
                                                    <i class="ti ti-check-circle me-1"></i>
                                                    <strong>Multiple Locations Enabled:</strong> Clients can book up to <span id="maxLocationsDisplay">3</span> locations.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Maximum Edited Photos</label>
                                        <input type="number" class="form-control" name="maximum_edited_photos" placeholder="Enter maximum edited photos" min="1" max="1000" required>
                                        <div class="invalid-feedback">Please enter valid number (1-1000).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Coverage Scope</label>
                                        <input type="text" class="form-control" name="coverage_scope" placeholder="Enter coverage scope">
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Price (PHP)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">PHP</span>
                                            <input type="number" class="form-control" name="package_price" placeholder="00.00" step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter valid package price.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Online Gallery</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Online Gallery Toggle">
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryYes" value="1" autocomplete="off">
                                            <label class="btn btn-outline-primary" for="galleryYes">
                                                <i class="ti ti-check me-1"></i> Yes, include online gallery
                                            </label>
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryNo" value="0" checked autocomplete="off">
                                            <label class="btn btn-outline-primary" for="galleryNo">
                                                <i class="ti ti-x me-1"></i> No, exclude online gallery
                                            </label>
                                        </div>
                                        <small class="text-muted">Online gallery allows clients to view and download photos online.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">Please select status.</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Create Package</button>
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
            // Initialize inclusions counter
            let inclusionCount = 1;
            const maxInclusions = 50;
            
            // Update counter display
            function updateCounter() {
                $('#inclusionCounter').text(`${inclusionCount} of ${maxInclusions} inclusions added`);
            }
            
            // Add new inclusion field
            $(document).on('click', '.add-inclusion-btn', function() {
                if (inclusionCount >= maxInclusions) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Reached',
                        text: `Maximum of ${maxInclusions} inclusions allowed.`,
                        confirmButtonColor: '#6C757D'
                    });
                    return;
                }
                
                const newField = `
                    <div class="input-group mb-2 inclusion-field">
                        <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                        <button class="btn btn-default add-inclusion-btn" type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                        <button class="btn btn-default remove-inclusion-btn" type="button">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                `;
                
                $('#inclusionsContainer').append(newField);
                inclusionCount++;
                updateCounter();
                
                // Enable remove button for all fields except first
                $('.remove-inclusion-btn').prop('disabled', false);
            });
            
            // Remove inclusion field
            $(document).on('click', '.remove-inclusion-btn', function() {
                if (inclusionCount <= 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Minimum Required',
                        text: 'At least one inclusion is required.',
                        confirmButtonColor: '#6C757D'
                    });
                    return;
                }
                
                $(this).closest('.inclusion-field').remove();
                inclusionCount--;
                updateCounter();
                
                // Disable remove button if only one field remains
                if (inclusionCount === 1) {
                    $('.remove-inclusion-btn').prop('disabled', true);
                }
            });

            // ==== Start: Time Customization Toggle Logic ====
            function toggleDurationField() {
                const allowCustomization = $('input[name="allow_time_customization"]:checked').val();
                const durationField = $('#durationField');
                const durationInput = $('#durationInput');
                const durationRequired = $('#durationRequired');
                const durationHelpText = $('#durationHelpText');
                
                if (allowCustomization === '1') {
                    // Time customization is ALLOWED - hide duration field, remove required
                    durationField.fadeOut(300);
                    durationInput.prop('required', false);
                    durationInput.val(''); // Clear any existing value
                    durationRequired.hide();
                    durationHelpText.text('Clients can choose their preferred duration during booking.');
                } else {
                    // Time customization is NOT allowed - show duration field, make it required
                    durationField.fadeIn(300);
                    durationInput.prop('required', true);
                    durationRequired.show();
                    durationHelpText.text('Fixed duration for this package.');
                }
            }

            // Trigger on time customization radio change
            $('input[name="allow_time_customization"]').on('change', function() {
                toggleDurationField();
                
                // Trigger Bootstrap validation update if needed
                $('#durationInput').removeClass('is-invalid');
            });

            // Initial check on page load (default is "No" - value 0, so duration should be visible)
            toggleDurationField();
            // ==== End: Time Customization Toggle Logic ====

            // ==== START: Multiple Locations Feature JavaScript ====

            /**
             * Handle allow multiple locations toggle change
             */
            function handleAllowMultipleLocationsChange() {
                const allowMultiple = $('#allowMultipleLocations').is(':checked');
                const maxLocationsField = $('#maxLocationsField');
                const maxLocationsInput = $('#maxLocations');
                const singleLocationInfo = $('#singleLocationInfo');
                const multipleLocationInfo = $('#multipleLocationInfo');
                const maxLocationsDisplay = $('#maxLocationsDisplay');
                
                if (allowMultiple) {
                    // Show max locations field
                    maxLocationsField.fadeIn(300);
                    maxLocationsInput.prop('required', true);
                    $('#allowMultipleLocations').val('1');
                    
                    // Update info alerts
                    singleLocationInfo.fadeOut(200);
                    
                    // Update max locations display in info alert
                    const currentMax = maxLocationsInput.val() || 3;
                    maxLocationsDisplay.text(currentMax);
                    multipleLocationInfo.fadeIn(200);
                } else {
                    // Hide and reset max locations field
                    maxLocationsField.fadeOut(300);
                    maxLocationsInput.prop('required', false);
                    maxLocationsInput.val('1');
                    $('#allowMultipleLocations').val('0');
                    
                    // Update info alerts
                    multipleLocationInfo.fadeOut(200);
                    singleLocationInfo.fadeIn(200);
                }
                
                validateMaxLocations();
            }

            /**
             * Validate max locations input
             */
            function validateMaxLocations() {
                const allowMultiple = $('#allowMultipleLocations').is(':checked');
                const maxLocations = parseInt($('#maxLocations').val());
                const errorElement = $('#maxLocationsError');
                const maxLocationsDisplay = $('#maxLocationsDisplay');
                
                if (allowMultiple) {
                    if (isNaN(maxLocations) || maxLocations < 1 || maxLocations > 10) {
                        $('#maxLocations').addClass('is-invalid');
                        errorElement.show();
                        return false;
                    } else {
                        $('#maxLocations').removeClass('is-invalid');
                        errorElement.hide();
                        
                        // Update the display in info alert
                        maxLocationsDisplay.text(maxLocations);
                    }
                }
                return true;
            }

            /**
             * Reset multiple locations section (called when form is reset)
             */
            function resetMultipleLocations() {
                $('#allowMultipleLocations').prop('checked', false).val('0');
                $('#maxLocationsField').fadeOut(300);
                $('#maxLocations').val('1').prop('required', false);
                $('#singleLocationInfo').show();
                $('#multipleLocationInfo').hide();
                validateMaxLocations();
            }

            // Event listener for allow multiple locations toggle
            $('#allowMultipleLocations').on('change', function() {
                handleAllowMultipleLocationsChange();
            });

            // Event listener for max locations input
            $('#maxLocations').on('input', function() {
                validateMaxLocations();
                
                // Enforce min/max
                let value = parseInt($(this).val());
                if (!isNaN(value)) {
                    if (value < 1) $(this).val(1);
                    if (value > 10) $(this).val(10);
                }
            });

            // Initialize on page load
            resetMultipleLocations();

            // Handle form reset
            $('#createPackageForm').on('reset', function() {
                setTimeout(function() {
                    resetMultipleLocations();
                    // Reset other dynamic fields
                    inclusionCount = 1;
                    $('#inclusionsContainer').html(`
                        <div class="input-group mb-2 inclusion-field">
                            <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                            <button class="btn btn-default add-inclusion-btn" type="button">
                                <i class="ti ti-plus"></i>
                            </button>
                            <button class="btn btn-default remove-inclusion-btn" type="button" disabled>
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    `);
                    updateCounter();
                    $('.remove-inclusion-btn').prop('disabled', true);
                    
                    // Reset time customization to default (No)
                    $('#timeCustomizationNo').prop('checked', true);
                    toggleDurationField();
                }, 100);
            });

            // Add CSS for better UI
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .form-switch.form-switch-md .form-check-input {
                        height: 1.5rem;
                        width: calc(2rem + 0.75rem);
                        margin-right: 0.5rem;
                        cursor: pointer;
                    }
                    .form-switch.form-switch-md .form-check-input:checked {
                        background-color: #3475db;
                        border-color: #3475db;
                    }
                    .border-primary {
                        border-color: #3475db !important;
                    }
                    #maxLocationsField {
                        transition: all 0.3s ease;
                    }
                    .card.border-primary {
                        transition: all 0.3s ease;
                    }
                    .card.border-primary:hover {
                        box-shadow: 0 4px 12px rgba(52, 117, 219, 0.15);
                    }
                    .alert-sm {
                        padding: 0.5rem 0.75rem;
                        font-size: 0.875rem;
                    }
                    #maxLocationsError {
                        display: none;
                        margin-top: 0.25rem;
                    }
                `)
                .appendTo('head');
            // ==== END: Multiple Locations Feature JavaScript ====
            
            // Form submission with AJAX
            $('#createPackageForm').on('submit', function(e) {
                e.preventDefault();
                
                // Collect form data as object
                const formData = {
                    category_id: $('#categorySelect').val(),
                    package_name: $('input[name="package_name"]').val(),
                    package_description: $('textarea[name="package_description"]').val(),
                    allow_time_customization: $('input[name="allow_time_customization"]:checked').val(),
                    duration: $('input[name="duration"]').val(),
                    maximum_edited_photos: $('input[name="maximum_edited_photos"]').val(),
                    coverage_scope: $('input[name="coverage_scope"]').val(),
                    package_price: $('input[name="package_price"]').val(),
                    status: $('select[name="status"]').val(),
                    online_gallery: $('input[name="online_gallery"]:checked').val(),
                    package_inclusions: []
                };
                
                // Debug: Log the values
                console.log('Form data:', formData);
                
                // Collect inclusions as array
                $('input[name="package_inclusions[]"]').each(function() {
                    if ($(this).val().trim() !== '') {
                        formData.package_inclusions.push($(this).val().trim());
                    }
                });
                
                // Validate at least one inclusion
                if (formData.package_inclusions.length === 0) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please enter at least one package inclusion.',
                        confirmButtonColor: '#DC3545'
                    });
                    return;
                }

                // Validate duration based on time customization
                const allowCustomization = formData.allow_time_customization;
                
                // If time customization is NOT allowed, duration is required
                if (allowCustomization === '0' && (!formData.duration || formData.duration === '')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Duration is required when time customization is not allowed.',
                        confirmButtonColor: '#DC3545'
                    });
                    
                    // Highlight the duration field
                    $('#durationInput').addClass('is-invalid');
                    
                    // Re-enable submit button
                    const submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', false).html('Create Package');
                    return false;
                }
                
                // If time customization is allowed, ensure duration is not sent
                if (allowCustomization === '1') {
                    delete formData.duration;
                }

                // ==== START: Validate multiple locations fields ====
                const allowMultipleLocations = $('#allowMultipleLocations').is(':checked');

                // Validate max locations if multiple locations is enabled
                if (allowMultipleLocations) {
                    if (!validateMaxLocations()) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please enter a valid maximum number of locations (1-10).',
                            confirmButtonColor: '#DC3545'
                        });
                        
                        // Re-enable submit button
                        const submitBtn = $(this).find('button[type="submit"]');
                        submitBtn.prop('disabled', false).html('Create Package');
                        return false;
                    }
                    
                    // Ensure max_locations is within bounds
                    let maxLoc = parseInt($('#maxLocations').val());
                    if (maxLoc < 1) maxLoc = 1;
                    if (maxLoc > 10) maxLoc = 10;
                    formData.max_locations = maxLoc;
                    formData.allow_multiple_locations = '1';
                } else {
                    // If multiple locations not allowed, ensure values are properly set
                    formData.allow_multiple_locations = '0';
                    formData.max_locations = null;
                }
                // ==== END: Validate multiple locations fields ====
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="ti ti-loader me-2"></i>Creating...');
                
                // Send AJAX request
                $.ajax({
                    url: "{{ route('freelancer.packages.store') }}",
                    type: "POST",
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = "{{ route('freelancer.packages.index') }}";
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to create package.',
                                timer: 1500,
                                timerProgressBar: true
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while creating the package.';
                        
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error!',
                            html: errorMessage,
                            confirmButtonColor: '#DC3545'
                        });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Bootstrap validation
            (function() {
                'use strict';
                window.addEventListener('load', function() {
                    var forms = document.getElementsByClassName('needs-validation');
                    var validation = Array.prototype.filter.call(forms, function(form) {
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