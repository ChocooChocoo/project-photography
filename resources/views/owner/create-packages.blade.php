@extends('layouts.owner.app')
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
                                        <label class="form-label">Select Studio</label>
                                        <select class="form-select" name="studio_id" required>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a studio.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" required>
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
                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationYes" value="1" required>
                                            <label class="btn btn-outline-primary" for="timeCustomizationYes">
                                                <i class="ti ti-clock-edit me-1"></i> Yes, clients can customize duration
                                            </label>

                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationNo" value="0" checked required>
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
                                        <input type="number" class="form-control" name="duration" placeholder="Enter duration in hours" min="1" max="24">
                                        <div class="invalid-feedback">Please enter valid duration (1-24 hours).</div>
                                        <small class="text-muted" id="durationHelpText">Fixed duration for this package.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Maximum Edited Photos</label>
                                        <input type="number" class="form-control" name="maximum_edited_photos" placeholder="Enter maximum edited photos" min="1" max="1000" required>
                                        <div class="invalid-feedback">Please enter valid number (1-1000).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Package Location</label>
                                        <div class="alert alert-info alert-sm py-2 mb-2">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Select one or both locations where this package can be offered.
                                        </div>
                                        
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="card location-card h-100">
                                                    <div class="card-body p-3 text-center">
                                                        <div class="form-check form-check-inline mb-2">
                                                            <input class="form-check-input location-checkbox" type="checkbox" 
                                                                name="package_location[]" id="locationInStudio" value="In-Studio">
                                                            <label class="form-check-label fw-semibold" for="locationInStudio">
                                                                <i class="ti ti-building me-1 text-primary"></i> In-Studio
                                                            </label>
                                                        </div>
                                                        <p class="text-muted small mb-0">Session takes place at the studio</p>
                                                        <div class="mt-2 location-details in-studio-details" style="display: none;">
                                                            <hr class="my-2">
                                                            <small class="text-success">
                                                                <i class="ti ti-check-circle me-1"></i> Selected
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="card location-card h-100">
                                                    <div class="card-body p-3 text-center">
                                                        <div class="form-check form-check-inline mb-2">
                                                            <input class="form-check-input location-checkbox" type="checkbox" 
                                                                name="package_location[]" id="locationOnLocation" value="On-Location">
                                                            <label class="form-check-label fw-semibold" for="locationOnLocation">
                                                                <i class="ti ti-map-pin me-1 text-info"></i> On-Location
                                                            </label>
                                                        </div>
                                                        <p class="text-muted small mb-0">Session takes place at client's location</p>
                                                        <div class="mt-2 location-details onlocation-details" style="display: none;">
                                                            <hr class="my-2">
                                                            <small class="text-success">
                                                                <i class="ti ti-check-circle me-1"></i> Selected
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="locationError" class="invalid-feedback d-block" style="display: none !important;">
                                            Please select at least one location type.
                                        </div>
                                        <small class="text-muted">
                                            <i class="ti ti-info-circle me-1"></i>
                                            Packages offering both locations will be available for booking in either setting.
                                        </small>
                                    </div>

                                    <div class="col-12 mb-3" id="coverageScopeField" style="display: none;">
                                        <label class="form-label">Coverage Scope <span class="text-danger" id="coverageRequired">*</span></label>
                                        <input type="text" class="form-control" name="coverage_scope" 
                                            placeholder="Enter coverage scope (e.g., Metro Manila, Luzon)">
                                        <div class="invalid-feedback">Please enter coverage scope for on-location sessions.</div>
                                        <small class="text-muted">Required when "On-Location" is selected.</small>
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
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryYes" value="1" required>
                                            <label class="btn btn-outline-primary" for="galleryYes">
                                                <i class="ti ti-check me-1"></i> Yes, include online gallery
                                            </label>

                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryNo" value="0" checked required>
                                            <label class="btn btn-outline-primary" for="galleryNo">
                                                <i class="ti ti-x me-1"></i> No, exclude online gallery
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">Please select if online gallery is included.</div>
                                        <small class="text-muted">Online gallery allows clients to view and download photos online.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Number of Photographers</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ti ti-camera"></i>
                                            </span>
                                            <input type="number" class="form-control" name="photographer_count" 
                                                placeholder="Enter number of photographers" 
                                                min="0" max="10" step="1" value="1" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter valid number of photographers (0-10).</div>
                                        <small class="text-muted">Maximum of 10 photographers can be assigned to this package.</small>
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
            // Package inclusions repeater functionality
            let inclusionCount = 1;
            const maxInclusions = 50;
            
            // Add inclusion field
            $(document).on('click', '.add-inclusion-btn', function() {
                if (inclusionCount >= maxInclusions) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit Reached',
                        text: `Maximum ${maxInclusions} inclusions allowed.`,
                        confirmButtonText: 'OK'
                    });
                    return;
                }
                
                inclusionCount++;
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
                updateInclusionCounter();
                updateRemoveButtons();
            });
            
            // Remove inclusion field
            $(document).on('click', '.remove-inclusion-btn', function() {
                if (inclusionCount <= 1) return;
                
                $(this).closest('.inclusion-field').remove();
                inclusionCount--;
                updateInclusionCounter();
                updateRemoveButtons();
            });
            
            // Update inclusion counter
            function updateInclusionCounter() {
                $('#inclusionCounter').text(`${inclusionCount} of ${maxInclusions} inclusions added`);
            }
            
            // Update remove buttons state
            function updateRemoveButtons() {
                $('.remove-inclusion-btn').prop('disabled', inclusionCount <= 1);
            }
            
            // Initialize
            updateRemoveButtons();

            // ==== Start: Time Customization Toggle Logic ====
            function toggleDurationField() {
                const allowCustomization = $('input[name="allow_time_customization"]:checked').val();
                const durationField = $('#durationField');
                const durationInput = $('input[name="duration"]');
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
                $('input[name="duration"]').removeClass('is-invalid');
            });

            // Initial check on page load (default is "No" - value 0, so duration should be visible)
            toggleDurationField();
            // ==== End: Time Customization Toggle Logic ====

            // === START: toggleCoverageScope function ===
            function toggleCoverageScope() {
                // Check if "On-Location" is selected among the checkboxes
                const onLocationSelected = $('input[name="package_location[]"][value="On-Location"]').is(':checked');
                const coverageScopeField = $('#coverageScopeField');
                const coverageScopeInput = $('input[name="coverage_scope"]');
                const coverageRequired = $('#coverageRequired');
                
                if (onLocationSelected) {
                    // Show coverage scope field, make it required
                    coverageScopeField.fadeIn(300);
                    coverageScopeInput.prop('required', true);
                    coverageRequired.show();
                } else {
                    // Hide coverage scope field, remove required
                    coverageScopeField.fadeOut(300);
                    coverageScopeInput.prop('required', false);
                    coverageScopeInput.val(''); // Clear the value
                }
            }
            // === END: toggleCoverageScope function ===

            // === START: Location checkbox styling ===
            function updateLocationCardStyles() {
                $('.location-checkbox').each(function() {
                    const $checkbox = $(this);
                    const $card = $checkbox.closest('.location-card');
                    const $details = $card.find('.location-details');
                    
                    if ($checkbox.is(':checked')) {
                        $card.addClass('border-primary shadow-sm');
                        $details.fadeIn(200);
                    } else {
                        $card.removeClass('border-primary shadow-sm');
                        $details.fadeOut(200);
                    }
                });
            }
            // === END: Location checkbox styling ===

            // Handle location checkbox changes
            $(document).on('change', '.location-checkbox', function() {
                toggleCoverageScope();
                updateLocationCardStyles();
            });

            // Initialize location-related UI on page load
            toggleCoverageScope();
            updateLocationCardStyles();

            // Toggle coverage scope on location change (legacy support)
            $('#packageLocation').on('change', function() {
                toggleCoverageScope();
            });

            // Handle form submission
            $('#createPackageForm').submit(function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(this);
                
                // Get all inclusion values as array
                const inclusions = [];
                $('input[name="package_inclusions[]"]').each(function() {
                    const value = $(this).val().trim();
                    if (value) {
                        inclusions.push(value);
                    }
                });
                
                // Remove existing inclusions from form data and add as JSON array
                formData.delete('package_inclusions[]');
                formData.append('package_inclusions', JSON.stringify(inclusions));

                // ==== Start: Handle duration validation based on time customization ====
                const allowCustomization = formData.get('allow_time_customization');
                const duration = formData.get('duration');
                
                // If time customization is NOT allowed, duration is required
                if (allowCustomization === '0' && (!duration || duration === '')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Duration is required when time customization is not allowed.',
                        confirmButtonColor: '#3475db',
                        confirmButtonText: 'OK'
                    });
                    
                    // Re-enable submit button
                    const submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }
                
                // If time customization is allowed, remove duration from formData to ensure it's null
                if (allowCustomization === '1') {
                    formData.delete('duration');
                }
                // ==== End: Handle duration validation based on time customization ====

                // ==== Start: Validate at least one location is selected ====
                const selectedLocations = [];
                $('input[name="package_location[]"]:checked').each(function() {
                    selectedLocations.push($(this).val());
                });

                if (selectedLocations.length === 0) {
                    $('#locationError').show();
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please select at least one location type.',
                        confirmButtonColor: '#3475db',
                        confirmButtonText: 'OK'
                    });
                    
                    // Re-enable submit button
                    const submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                } else {
                    $('#locationError').hide();
                }

                // Remove any existing package_location entries and add as array
                formData.delete('package_location[]');
                selectedLocations.forEach(function(location) {
                    formData.append('package_location[]', location);
                });
                // ==== End: Validate at least one location is selected ====

                // ==== Start: Handle coverage scope validation based on on-location selection ====
                const onLocationSelected = selectedLocations.includes('On-Location');
                const coverageScope = formData.get('coverage_scope');

                if (onLocationSelected && (!coverageScope || coverageScope.trim() === '')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Coverage scope is required for on-location packages.',
                        confirmButtonColor: '#3475db',
                        confirmButtonText: 'OK'
                    });
                    
                    // Re-enable submit button
                    const submitBtn = $(this).find('button[type="submit"]');
                    submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }
                // ==== End: Handle coverage scope validation based on on-location selection ====
                
                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.prop('disabled', true).html('<i class="ti ti-loader me-1"></i> Creating...');
                
                // Submit via AJAX
                $.ajax({
                    url: "{{ route('owner.packages.store') }}",
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });

                            setTimeout(() => {
                                $('#createPackageForm')[0].reset();
                                
                                // Reset inclusions repeater to single field
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
                                inclusionCount = 1;
                                updateInclusionCounter();
                                updateRemoveButtons();
                                
                                // Reset location checkboxes and styling
                                $('.location-checkbox').prop('checked', false);
                                updateLocationCardStyles();
                                toggleCoverageScope();
                                
                                // Reset radio buttons
                                $('input[name="online_gallery"]').prop('checked', false);
                                $('#galleryNo').prop('checked', true);
                                
                                // Reset time customization radios and duration field
                                $('input[name="allow_time_customization"]').prop('checked', false);
                                $('#timeCustomizationNo').prop('checked', true);
                                toggleDurationField(); // Ensure duration field is visible and properly configured
                                
                                window.location.href = "{{ route('owner.packages.index') }}";
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            
                            for (let field in errors) {
                                let fieldName = field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                errorMessages += errors[field].join('<br>') + '<br>';
                            }
                            
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: errorMessages,
                                confirmButtonColor: '#3475db',
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to create package. Please try again.',
                                confirmButtonColor: '#3475db',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });

            // Bootstrap form validation
            (function() {
                'use strict';
                var forms = document.querySelectorAll('.needs-validation');
                Array.prototype.slice.call(forms).forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            })();
        });
    </script>
@endsection