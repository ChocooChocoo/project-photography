@extends('layouts.freelancer.app')
@section('title', 'Edit Package')

{{-- CONTENTS --}}
@section('content')
    @php
        $inclusions = $package->package_inclusions ?? [];
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
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" id="categorySelect" required>
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
                                                    <button class="btn btn-default add-inclusion-btn" type="button"><i class="ti ti-plus"></i></button>
                                                    <button class="btn btn-default remove-inclusion-btn" type="button" @disabled(count($inclusions) <= 1)><i class="ti ti-trash"></i></button>
                                                </div>
                                            @empty
                                                <div class="input-group mb-2 inclusion-field">
                                                    <input type="text" class="form-control" name="package_inclusions[]" placeholder="Enter inclusion" required>
                                                    <button class="btn btn-default add-inclusion-btn" type="button"><i class="ti ti-plus"></i></button>
                                                    <button class="btn btn-default remove-inclusion-btn" type="button" disabled><i class="ti ti-trash"></i></button>
                                                </div>
                                            @endforelse
                                        </div>
                                        <small id="inclusionCounter">{{ max(count($inclusions), 1) }} of 50 inclusions added</small>
                                        <div class="invalid-feedback">Please enter at least one package inclusion.</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label d-block">Allow Time Customization</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Time Customization Toggle">
                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationYes" value="1" @checked($package->allow_time_customization) autocomplete="off">
                                            <label class="btn btn-outline-primary" for="timeCustomizationYes">
                                                <i class="ti ti-clock-edit me-1"></i> Yes, clients can customize duration
                                            </label>
                                            <input type="radio" class="btn-check" name="allow_time_customization" id="timeCustomizationNo" value="0" @checked(!$package->allow_time_customization) autocomplete="off">
                                            <label class="btn btn-outline-primary" for="timeCustomizationNo">
                                                <i class="ti ti-clock me-1"></i> No, fixed duration only
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3" id="durationField">
                                        <label class="form-label">Duration (hours) <span class="text-danger" id="durationRequired">*</span></label>
                                        <input type="number" class="form-control" name="duration" id="durationInput" value="{{ $package->duration }}" placeholder="Enter duration in hours" min="1" max="24">
                                        <div class="invalid-feedback">Please enter valid duration (1-24 hours).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Location</label>
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
                                                            <input type="number" class="form-control" name="max_locations" id="maxLocations" placeholder="Enter max locations (1-10)" min="1" max="10" step="1" value="{{ $package->max_locations ?: 1 }}">
                                                        </div>
                                                        <div class="invalid-feedback" id="maxLocationsError">Please enter a valid number between 1 and 10.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Maximum Edited Photos</label>
                                        <input type="number" class="form-control" name="maximum_edited_photos" value="{{ $package->maximum_edited_photos }}" placeholder="Enter maximum edited photos" min="1" max="1000" required>
                                        <div class="invalid-feedback">Please enter valid number (1-1000).</div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Coverage Scope</label>
                                        <input type="text" class="form-control" name="coverage_scope" value="{{ $package->coverage_scope }}" placeholder="Enter coverage scope">
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
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryYes" value="1" @checked($package->online_gallery) autocomplete="off">
                                            <label class="btn btn-outline-primary" for="galleryYes">
                                                <i class="ti ti-check me-1"></i> Yes, include online gallery
                                            </label>
                                            <input type="radio" class="btn-check" name="online_gallery" id="galleryNo" value="0" @checked(!$package->online_gallery) autocomplete="off">
                                            <label class="btn btn-outline-primary" for="galleryNo">
                                                <i class="ti ti-x me-1"></i> No, exclude online gallery
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Cover Images <small class="text-muted">(optional, up to 5)</small></label>

                                        @if (!empty($package->cover_images))
                                            <div id="existingCoverImages" class="d-flex flex-wrap gap-2 mb-2">
                                                @foreach ($package->cover_images as $image)
                                                    <div class="position-relative existing-cover-image" data-path="{{ $image }}">
                                                        <img src="{{ asset('storage/' . $image) }}" style="width:80px;height:80px;object-fit:cover;border-radius:6px;">
                                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-cover" style="padding:0 6px;line-height:1.4;">&times;</button>
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
                                    <a href="{{ route('freelancer.packages.index') }}" class="btn btn-default">Cancel</a>
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
                if (allowCustomization === '1') {
                    $('#durationField').hide();
                    $('#durationInput').prop('required', false).val('');
                } else {
                    $('#durationField').show();
                    $('#durationInput').prop('required', true);
                }
            }
            $('input[name="allow_time_customization"]').on('change', toggleDurationField);
            toggleDurationField();

            function handleAllowMultipleLocationsChange() {
                if ($('#allowMultipleLocations').is(':checked')) {
                    $('#maxLocationsField').show();
                    $('#maxLocations').prop('required', true);
                } else {
                    $('#maxLocationsField').hide();
                    $('#maxLocations').prop('required', false);
                }
            }
            $('#allowMultipleLocations').on('change', handleAllowMultipleLocationsChange);
            handleAllowMultipleLocationsChange();

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

            // Remove an existing cover image (marks it for deletion on save)
            $(document).on('click', '.remove-existing-cover', function() {
                const $wrapper = $(this).closest('.existing-cover-image');
                const path = $wrapper.data('path');
                keepCoverImages = keepCoverImages.filter(p => p !== path);
                $wrapper.remove();
            });

            // Cover images: cap new uploads so existing + new never exceeds 5
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

            $('#editPackageForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();

                const inclusions = [];
                $('input[name="package_inclusions[]"]').each(function() {
                    const value = $(this).val().trim();
                    if (value) inclusions.push(value);
                });
                if (inclusions.length === 0) {
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please enter at least one package inclusion.' });
                    return;
                }

                const allowCustomization = $('input[name="allow_time_customization"]:checked').val();
                if (allowCustomization === '0' && !$('#durationInput').val()) {
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Duration is required when time customization is not allowed.' });
                    return;
                }

                if ($('#allowMultipleLocations').is(':checked') && !validateMaxLocations()) {
                    Swal.fire({ icon: 'error', title: 'Validation Error', text: 'Please enter a valid maximum number of locations (1-10).' });
                    return;
                }

                const uploadData = new FormData();
                uploadData.append('_method', 'PUT');
                uploadData.append('category_id', $('#categorySelect').val());
                uploadData.append('package_name', $('input[name="package_name"]').val());
                uploadData.append('package_description', $('textarea[name="package_description"]').val());
                uploadData.append('allow_time_customization', allowCustomization);
                if (allowCustomization === '0') {
                    uploadData.append('duration', $('#durationInput').val());
                }
                uploadData.append('maximum_edited_photos', $('input[name="maximum_edited_photos"]').val());
                uploadData.append('coverage_scope', $('input[name="coverage_scope"]').val());
                uploadData.append('package_price', $('input[name="package_price"]').val());
                uploadData.append('status', $('select[name="status"]').val());
                uploadData.append('online_gallery', $('input[name="online_gallery"]:checked').val());
                inclusions.forEach(inclusion => uploadData.append('package_inclusions[]', inclusion));

                if ($('#allowMultipleLocations').is(':checked')) {
                    uploadData.append('allow_multiple_locations', '1');
                    uploadData.append('max_locations', $('#maxLocations').val());
                } else {
                    uploadData.append('allow_multiple_locations', '0');
                }

                keepCoverImages.forEach(path => uploadData.append('keep_cover_images[]', path));
                Array.from($('#coverImages')[0].files).forEach(file => uploadData.append('cover_images[]', file));

                submitBtn.prop('disabled', true).html('<i class="ti ti-loader me-2"></i>Saving...');

                $.ajax({
                    url: "{{ route('freelancer.packages.update', $package->id) }}",
                    type: 'POST',
                    data: uploadData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
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
                            Swal.fire({ icon: 'error', title: 'Error!', text: response.message || 'Failed to update package.' });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating the package.';
                        if (xhr.status === 422) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error', html: errorMessage });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
        });
    </script>
@endsection
