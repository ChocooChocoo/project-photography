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

                                    <div class="col-12 mb-3" id="multipleLocationsSection" style="display: none;">
                                        <div class="card border-primary bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-check-primary form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" id="allowMultipleLocations" name="allow_multiple_locations" value="1" role="switch">
                                                            <label class="form-check-label fw-semibold" for="allowMultipleLocations">
                                                                Allow Multiple Shooting Locations
                                                            </label>
                                                        </div>
                                                        <p class="text-muted small mb-0">
                                                            Enable this to allow clients to shoot at multiple locations within the same booking.
                                                        </p>
                                                    </div>
                                                    
                                                    <div class="col-md-6" id="maxLocationsField" style="display: none;">
                                                        <label class="form-label fw-semibold">
                                                            Maximum Number of Locations <span class="text-danger">*</span>
                                                        </label>
                                                        <div class="input-group">
                                                            <span class="input-group-text">
                                                                <i class="ti ti-map-pin"></i>
                                                            </span>
                                                            <input type="number" class="form-control" name="max_locations" 
                                                                id="maxLocations" placeholder="Enter max locations (1-10)" 
                                                                min="1" max="10" step="1" value="1">
                                                            <span class="input-group-text">locations</span>
                                                        </div>
                                                        <small class="text-muted">
                                                            Maximum of 10 locations allowed per booking.
                                                        </small>
                                                        <div class="invalid-feedback" id="maxLocationsError">
                                                            Please enter a valid number between 1 and 10.
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Info Alert for In-Studio Only -->
                                                <div class="alert alert-warning alert-sm mt-3 mb-0" id="inStudioOnlyWarning" style="display: none;">
                                                    <i class="ti ti-alert-triangle me-1"></i>
                                                    <strong>Note:</strong> Multiple locations can only be enabled for packages that include On-Location.
                                                </div>
                                            </div>
                                        </div>
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
                                        <label class="form-label">Cover Images <small class="text-muted">(optional, up to 5)</small></label>
                                        <input type="file" class="form-control" name="cover_images[]" id="coverImages" accept=".jpg,.jpeg,.png,.gif" multiple>
                                        <div class="form-text">JPG/PNG/GIF, max 5MB each. Shown to clients when choosing this package.</div>
                                        <div id="coverImagesPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
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

            // ==== START: Multiple Locations Feature JavaScript ====
            let currentLocationSelection = [];

            /**
             * Toggle multiple locations section visibility based on location checkboxes
             */
            function toggleMultipleLocationsSection() {
                const multipleLocationsSection = $('#multipleLocationsSection');
                const allowMultipleCheckbox = $('#allowMultipleLocations');
                const maxLocationsField = $('#maxLocationsField');
                const inStudioOnlyWarning = $('#inStudioOnlyWarning');
                const onLocationSelected = $('input[name="package_location[]"][value="On-Location"]').is(':checked');
                const inStudioSelected = $('input[name="package_location[]"][value="In-Studio"]').is(':checked');
                
                // Update current selection array
                currentLocationSelection = [];
                $('input[name="package_location[]"]:checked').each(function() {
                    currentLocationSelection.push($(this).val());
                });
                
                if (onLocationSelected) {
                    // Show multiple locations section when On-Location is selected
                    multipleLocationsSection.fadeIn(300);
                    
                    // Show warning if only In-Studio is selected (shouldn't happen since onLocationSelected is true)
                    if (!onLocationSelected && inStudioSelected) {
                        inStudioOnlyWarning.show();
                        allowMultipleCheckbox.prop('disabled', true);
                    } else {
                        inStudioOnlyWarning.hide();
                        allowMultipleCheckbox.prop('disabled', false);
                    }
                } else {
                    // Hide and reset multiple locations section when On-Location is not selected
                    multipleLocationsSection.fadeOut(300);
                    allowMultipleCheckbox.prop('checked', false);
                    maxLocationsField.fadeOut(300);
                    inStudioOnlyWarning.hide();
                    
                    // Reset values
                    allowMultipleCheckbox.val('0');
                    $('#maxLocations').val('1');
                }
            }

            /**
             * Handle allow multiple locations toggle change
             */
            function handleAllowMultipleLocationsChange() {
                const allowMultiple = $('#allowMultipleLocations').is(':checked');
                const maxLocationsField = $('#maxLocationsField');
                const maxLocationsInput = $('#maxLocations');
                
                if (allowMultiple) {
                    // Show max locations field
                    maxLocationsField.fadeIn(300);
                    maxLocationsInput.prop('required', true);
                    $('#allowMultipleLocations').val('1');
                } else {
                    // Hide and reset max locations field
                    maxLocationsField.fadeOut(300);
                    maxLocationsInput.prop('required', false);
                    maxLocationsInput.val('1');
                    $('#allowMultipleLocations').val('0');
                }
            }

            /**
             * Validate max locations input
             */
            function validateMaxLocations() {
                const allowMultiple = $('#allowMultipleLocations').is(':checked');
                const maxLocations = parseInt($('#maxLocations').val());
                const errorElement = $('#maxLocationsError');
                
                if (allowMultiple) {
                    if (isNaN(maxLocations) || maxLocations < 1 || maxLocations > 10) {
                        $('#maxLocations').addClass('is-invalid');
                        errorElement.show();
                        return false;
                    } else {
                        $('#maxLocations').removeClass('is-invalid');
                        errorElement.hide();
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
                $('#inStudioOnlyWarning').hide();
                validateMaxLocations();
            }

            // Event listeners for location checkboxes
            $(document).on('change', '.location-checkbox', function() {
                toggleMultipleLocationsSection();
                updateLocationCardStyles();
                toggleCoverageScope();
            });

            // Event listener for allow multiple locations toggle
            $('#allowMultipleLocations').on('change', function() {
                handleAllowMultipleLocationsChange();
                validateMaxLocations();
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
            // ==== END: Multiple Locations Feature JavaScript ====

            // Handle location checkbox changes
            $(document).on('change', '.location-checkbox', function() {
                toggleCoverageScope();
                updateLocationCardStyles();
                toggleMultipleLocationsSection(); // Added this line
            });

            // Initialize location-related UI on page load
            toggleCoverageScope();
            updateLocationCardStyles();
            toggleMultipleLocationsSection(); // Added this line
            validateMaxLocations(); // Added this line

            // Toggle coverage scope on location change (legacy support)
            $('#packageLocation').on('change', function() {
                toggleCoverageScope();
            });

            // Cover images: cap at 5 and show a quick preview
            $('#coverImages').on('change', function() {
                const files = Array.from(this.files);
                if (files.length > 5) {
                    Swal.fire('Too Many Images', 'You can upload up to 5 cover images.', 'warning');
                    $(this).val('');
                    $('#coverImagesPreview').empty();
                    return;
                }

                $('#coverImagesPreview').empty();
                files.forEach(file => {
                    const url = URL.createObjectURL(file);
                    $('#coverImagesPreview').append(
                        `<img src="${url}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;">`
                    );
                });
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

                // ==== START: Validate multiple locations fields ====
                const allowMultipleLocations = $('#allowMultipleLocations').is(':checked');
                
                // Validate max locations if multiple locations is enabled
                if (allowMultipleLocations) {
                    if (!validateMaxLocations()) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please enter a valid maximum number of locations (1-10).',
                            confirmButtonColor: '#3475db'
                        });
                        
                        // Re-enable submit button
                        const submitBtn = $(this).find('button[type="submit"]');
                        submitBtn.prop('disabled', false).html(originalText);
                        return false;
                    }
                    
                    // Ensure max_locations is properly set
                    formData.set('allow_multiple_locations', '1');
                    
                    // Ensure max_locations is within bounds
                    let maxLoc = parseInt($('#maxLocations').val());
                    if (maxLoc < 1) maxLoc = 1;
                    if (maxLoc > 10) maxLoc = 10;
                    formData.set('max_locations', maxLoc);
                } else {
                    // If multiple locations not allowed, ensure values are properly set
                    formData.set('allow_multiple_locations', '0');
                    formData.set('max_locations', '');
                }
                // ==== END: Validate multiple locations fields ====
                
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
                                
                                // ==== NEW: Reset multiple locations fields ====
                                resetMultipleLocations();
                                // ==== END: Reset multiple locations fields ====
                                
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

            // Add CSS for multiple locations UI
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .form-switch.form-switch-md .form-check-input {
                        height: 1.5rem;
                        width: calc(2rem + 0.75rem);
                        margin-right: 0.5rem;
                    }
                    .border-primary {
                        border-color: #3475db !important;
                    }
                    #maxLocationsField {
                        transition: all 0.3s ease;
                    }
                    #multipleLocationsSection .card {
                        transition: all 0.3s ease;
                    }
                    #multipleLocationsSection .card:hover {
                        box-shadow: 0 4px 12px rgba(52, 117, 219, 0.15);
                    }
                    .btn-check:checked + .package-card {
                        border-color: #3475db !important;
                    }
                `)
                .appendTo('head');
        });
    </script>
@endsection