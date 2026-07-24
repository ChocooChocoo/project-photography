@extends('layouts.owner.app')
@section('title', 'Edit Package')

{{-- CONTENTS --}}
@section('content')
    @php
        $inclusions = $package->package_inclusions ?? [];
        $locations = $package->package_location ?? [];
    @endphp
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Edit Package</h4>
                        </div>
                        <div class="card-body">
                            <form id="editPackageForm" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Studio</label>
                                        <select class="form-select" name="studio_id" required>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}" @selected($studio->id === $package->studio_id)>{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a studio.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" @selected($category->id === $package->category_id)>{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">Please select a category.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Name</label>
                                        <input type="text" class="form-control" name="package_name" value="{{ $package->package_name }}" placeholder="Enter package name" required>
                                        <div class="invalid-feedback">Please enter package name.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Description</label>
                                        <textarea class="form-control" name="package_description" rows="3" placeholder="Enter package description" required>{{ $package->package_description }}</textarea>
                                        <div class="invalid-feedback">Please enter package description.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Inclusion</label>
                                        <div id="inclusionsContainer">
                                            @forelse($inclusions as $inclusion)
                                                <div class="input-group mb-2 inclusion-field">
                                                    <input type="text" class="form-control" name="package_inclusions[]" value="{{ $inclusion }}" placeholder="Enter inclusion" required>
                                                    <button class="btn btn-default add-inclusion-btn" type="button">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <button class="btn btn-default remove-inclusion-btn" type="button" @disabled(count($inclusions) <= 1)>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="input-group mb-2 inclusion-field">
                                                    <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                                                    <button class="btn btn-default add-inclusion-btn" type="button">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <button class="btn btn-default remove-inclusion-btn" type="button" disabled>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            @endforelse
                                        </div>

                                        <small id="inclusionCounter">{{ max(count($inclusions), 1) }} of 50 inclusions added</small>
                                        <div class="invalid-feedback">
                                            Please enter at least one package inclusion.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Allow Time Customization</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Time Customization Toggle">
                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationYes" value="1" @checked($package->allow_time_customization) required>
                                            <label class="btn btn-outline-primary" for="timeCustomizationYes">
                                                <i class="ti ti-clock-edit me-1"></i> Yes, clients can customize duration
                                            </label>

                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationNo" value="0" @checked(!$package->allow_time_customization) required>
                                            <label class="btn btn-outline-primary" for="timeCustomizationNo">
                                                <i class="ti ti-clock me-1"></i> No, fixed duration only
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">Please select if time customization is allowed.</div>
                                    </div>

                                    <div class="col-12 mb-3" id="durationField">
                                        <label class="form-label">Duration (hours) <span class="text-danger" id="durationRequired">*</span></label>
                                        <input type="number" class="form-control" name="duration" value="{{ $package->duration }}" placeholder="Enter duration in hours" min="1" max="24">
                                        <div class="invalid-feedback">Please enter valid duration (1-24 hours).</div>
                                        <small class="text-muted" id="durationHelpText">Fixed duration for this package.</small>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Maximum Edited Photos</label>
                                        <input type="number" class="form-control" name="maximum_edited_photos" value="{{ $package->maximum_edited_photos }}" placeholder="Enter maximum edited photos" min="1" max="1000" required>
                                        <div class="invalid-feedback">Please enter valid number (1-1000).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Package Location</label>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <div class="card location-card h-100">
                                                    <div class="card-body p-3 text-center">
                                                        <div class="form-check form-check-inline mb-2">
                                                            <input class="form-check-input location-checkbox" type="checkbox"
                                                                name="package_location[]" id="locationInStudio" value="In-Studio" @checked(in_array('In-Studio', $locations))>
                                                            <label class="form-check-label fw-semibold" for="locationInStudio">
                                                                <i class="ti ti-building me-1 text-primary"></i> In-Studio
                                                            </label>
                                                        </div>
                                                        <p class="text-muted small mb-0">Session takes place at the studio</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="card location-card h-100">
                                                    <div class="card-body p-3 text-center">
                                                        <div class="form-check form-check-inline mb-2">
                                                            <input class="form-check-input location-checkbox" type="checkbox"
                                                                name="package_location[]" id="locationOnLocation" value="On-Location" @checked(in_array('On-Location', $locations))>
                                                            <label class="form-check-label fw-semibold" for="locationOnLocation">
                                                                <i class="ti ti-map-pin me-1 text-info"></i> On-Location
                                                            </label>
                                                        </div>
                                                        <p class="text-muted small mb-0">Session takes place at client's location</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="locationError" class="invalid-feedback d-block" style="display: none !important;">
                                            Please select at least one location type.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3" id="multipleLocationsSection" style="display: none;">
                                        <div class="card border-primary bg-light">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-check form-check-primary form-switch mb-2">
                                                            <input class="form-check-input" type="checkbox" id="allowMultipleLocations" name="allow_multiple_locations" value="1" role="switch" @checked($package->allow_multiple_locations)>
                                                            <label class="form-check-label fw-semibold" for="allowMultipleLocations">
                                                                Allow Multiple Shooting Locations
                                                            </label>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6" id="maxLocationsField" style="display: none;">
                                                        <label class="form-label fw-semibold">Maximum Number of Locations <span class="text-danger">*</span></label>
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="ti ti-map-pin"></i></span>
                                                            <input type="number" class="form-control" name="max_locations"
                                                                id="maxLocations" placeholder="Enter max locations (1-10)"
                                                                min="1" max="10" step="1" value="{{ $package->max_locations ?: 1 }}">
                                                            <span class="input-group-text">locations</span>
                                                        </div>
                                                        <div class="invalid-feedback" id="maxLocationsError">
                                                            Please enter a valid number between 1 and 10.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3" id="coverageScopeField" style="display: none;">
                                        <label class="form-label">Coverage Scope <span class="text-danger" id="coverageRequired">*</span></label>
                                        <input type="text" class="form-control" name="coverage_scope" value="{{ $package->coverage_scope }}"
                                            placeholder="Enter coverage scope (e.g., Metro Manila, Luzon)">
                                        <div class="invalid-feedback">Please enter coverage scope for on-location sessions.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Package Price (PHP)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">PHP</span>
                                            <input type="number" class="form-control" name="package_price" value="{{ $package->package_price }}" placeholder="00.00" step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter valid package price.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Online Gallery</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Online Gallery Toggle">
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryYes" value="1" @checked($package->online_gallery) required>
                                            <label class="btn btn-outline-primary" for="galleryYes">
                                                <i class="ti ti-check me-1"></i> Yes, include online gallery
                                            </label>

                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryNo" value="0" @checked(!$package->online_gallery) required>
                                            <label class="btn btn-outline-primary" for="galleryNo">
                                                <i class="ti ti-x me-1"></i> No, exclude online gallery
                                            </label>
                                        </div>
                                        <div class="invalid-feedback">Please select if online gallery is included.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Number of Photographers</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ti ti-camera"></i></span>
                                            <input type="number" class="form-control" name="photographer_count"
                                                value="{{ $package->photographer_count }}"
                                                placeholder="Enter number of photographers"
                                                min="0" max="10" step="1" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter valid number of photographers (0-10).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Cover Images <small class="text-muted">(optional, up to 5)</small></label>

                                        @if (!empty($package->cover_images))
                                            <div id="existingCoverImages" class="d-flex flex-wrap gap-2 mb-2">
                                                @foreach ($package->cover_images as $image)
                                                    <div class="position-relative existing-cover-image" data-path="{{ $image }}">
                                                        <img src="{{ asset('storage/' . $image) }}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-cover" style="padding:0 6px;line-height:1.4;">
                                                            &times;
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <input type="file" class="form-control" name="cover_images[]" id="coverImages" accept=".jpg,.jpeg,.png,.gif" multiple>
                                        <div class="form-text">JPG/PNG/GIF, max 5MB each. Shown to clients when choosing this package.</div>
                                        <div id="coverImagesPreview" class="d-flex flex-wrap gap-2 mt-2"></div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="active" @selected($package->status === 'active')>Active</option>
                                            <option value="inactive" @selected($package->status === 'inactive')>Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">Please select status.</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                    <a href="{{ route('owner.packages.index') }}" class="btn btn-default">Cancel</a>
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
            let inclusionCount = $('#inclusionsContainer .inclusion-field').length || 1;
            const maxInclusions = 50;
            let keepCoverImages = $('#existingCoverImages .existing-cover-image').map(function() {
                return $(this).data('path');
            }).get();

            $(document).on('click', '.add-inclusion-btn', function() {
                if (inclusionCount >= maxInclusions) {
                    Swal.fire({ icon: 'warning', title: 'Limit Reached', text: `Maximum ${maxInclusions} inclusions allowed.` });
                    return;
                }
                inclusionCount++;
                $('#inclusionsContainer').append(`
                    <div class="input-group mb-2 inclusion-field">
                        <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                        <button class="btn btn-default add-inclusion-btn" type="button"><i class="ti ti-plus"></i></button>
                        <button class="btn btn-default remove-inclusion-btn" type="button"><i class="ti ti-trash"></i></button>
                    </div>
                `);
                updateInclusionCounter();
                updateRemoveButtons();
            });

            $(document).on('click', '.remove-inclusion-btn', function() {
                if (inclusionCount <= 1) return;
                $(this).closest('.inclusion-field').remove();
                inclusionCount--;
                updateInclusionCounter();
                updateRemoveButtons();
            });

            function updateInclusionCounter() {
                $('#inclusionCounter').text(`${inclusionCount} of ${maxInclusions} inclusions added`);
            }

            function updateRemoveButtons() {
                $('.remove-inclusion-btn').prop('disabled', inclusionCount <= 1);
            }
            updateRemoveButtons();

            function toggleDurationField() {
                const allowCustomization = $('input[name="allow_time_customization"]:checked').val();
                const durationField = $('#durationField');
                const durationInput = $('input[name="duration"]');
                if (allowCustomization === '1') {
                    durationField.hide();
                    durationInput.prop('required', false).val('');
                } else {
                    durationField.show();
                    durationInput.prop('required', true);
                }
            }
            $('input[name="allow_time_customization"]').on('change', toggleDurationField);
            toggleDurationField();

            function toggleCoverageScope() {
                const onLocationSelected = $('input[name="package_location[]"][value="On-Location"]').is(':checked');
                const coverageScopeField = $('#coverageScopeField');
                const coverageScopeInput = $('input[name="coverage_scope"]');
                if (onLocationSelected) {
                    coverageScopeField.show();
                    coverageScopeInput.prop('required', true);
                } else {
                    coverageScopeField.hide();
                    coverageScopeInput.prop('required', false).val('');
                }
            }

            function updateLocationCardStyles() {
                $('.location-checkbox').each(function() {
                    const $card = $(this).closest('.location-card');
                    $card.toggleClass('border-primary shadow-sm', $(this).is(':checked'));
                });
            }

            function toggleMultipleLocationsSection() {
                const onLocationSelected = $('input[name="package_location[]"][value="On-Location"]').is(':checked');
                if (onLocationSelected) {
                    $('#multipleLocationsSection').show();
                } else {
                    $('#multipleLocationsSection').hide();
                    $('#allowMultipleLocations').prop('checked', false);
                    $('#maxLocationsField').hide();
                }
                handleAllowMultipleLocationsChange();
            }

            function handleAllowMultipleLocationsChange() {
                if ($('#allowMultipleLocations').is(':checked')) {
                    $('#maxLocationsField').show();
                    $('#maxLocations').prop('required', true);
                } else {
                    $('#maxLocationsField').hide();
                    $('#maxLocations').prop('required', false);
                }
            }

            function validateMaxLocations() {
                if (!$('#allowMultipleLocations').is(':checked')) return true;
                const maxLocations = parseInt($('#maxLocations').val());
                if (isNaN(maxLocations) || maxLocations < 1 || maxLocations > 10) {
                    $('#maxLocations').addClass('is-invalid');
                    $('#maxLocationsError').show();
                    return false;
                }
                $('#maxLocations').removeClass('is-invalid');
                $('#maxLocationsError').hide();
                return true;
            }

            $(document).on('change', '.location-checkbox', function() {
                toggleCoverageScope();
                updateLocationCardStyles();
                toggleMultipleLocationsSection();
            });
            $('#allowMultipleLocations').on('change', handleAllowMultipleLocationsChange);

            // Initialize UI state from pre-filled values
            toggleCoverageScope();
            updateLocationCardStyles();
            toggleMultipleLocationsSection();

            // Remove an existing cover image (marks it for deletion on save)
            $(document).on('click', '.remove-existing-cover', function() {
                const $wrapper = $(this).closest('.existing-cover-image');
                const path = $wrapper.data('path');
                keepCoverImages = keepCoverImages.filter(p => p !== path);
                $wrapper.remove();
            });

            // Cover images: cap new uploads at 5 total (existing kept + new)
            $('#coverImages').on('change', function() {
                const files = Array.from(this.files);
                if (keepCoverImages.length + files.length > 5) {
                    Swal.fire('Too Many Images', 'You can have up to 5 cover images in total.', 'warning');
                    $(this).val('');
                    $('#coverImagesPreview').empty();
                    return;
                }
                $('#coverImagesPreview').empty();
                files.forEach(file => {
                    const url = URL.createObjectURL(file);
                    $('#coverImagesPreview').append(`<img src="${url}" style="width:70px;height:70px;object-fit:cover;border-radius:6px;">`);
                });
            });

            $('#editPackageForm').submit(function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                const formData = new FormData(this);
                formData.append('_method', 'PUT');

                const inclusions = [];
                $('input[name="package_inclusions[]"]').each(function() {
                    const value = $(this).val().trim();
                    if (value) inclusions.push(value);
                });
                formData.delete('package_inclusions[]');
                formData.append('package_inclusions', JSON.stringify(inclusions));

                const allowCustomization = formData.get('allow_time_customization');
                if (allowCustomization === '1') {
                    formData.delete('duration');
                }

                const selectedLocations = [];
                $('input[name="package_location[]"]:checked').each(function() {
                    selectedLocations.push($(this).val());
                });
                if (selectedLocations.length === 0) {
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please select at least one location type.' });
                    return;
                }
                formData.delete('package_location[]');
                selectedLocations.forEach(location => formData.append('package_location[]', location));

                if (selectedLocations.includes('On-Location') && !formData.get('coverage_scope')) {
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Coverage scope is required for on-location packages.' });
                    return;
                }

                if ($('#allowMultipleLocations').is(':checked')) {
                    if (!validateMaxLocations()) {
                        Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please enter a valid maximum number of locations (1-10).' });
                        return;
                    }
                    formData.set('allow_multiple_locations', '1');
                } else {
                    formData.set('allow_multiple_locations', '0');
                    formData.set('max_locations', '');
                }

                keepCoverImages.forEach(path => formData.append('keep_cover_images[]', path));

                submitBtn.prop('disabled', true).html('<i class="ti ti-loader me-1"></i> Saving...');

                $.ajax({
                    url: "{{ route('owner.packages.update', $package->id) }}",
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
                            }).then(() => {
                                window.location.href = "{{ route('owner.packages.index') }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorMessages = '';
                            for (let field in errors) {
                                errorMessages += errors[field].join('<br>') + '<br>';
                            }
                            Swal.fire({ icon: 'error', title: 'Validation Error', html: errorMessages });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to update package. Please try again.' });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endsection
