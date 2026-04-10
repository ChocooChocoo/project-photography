@extends('layouts.owner.app')
@section('title', 'Add Studio Photographers')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Register Studio Photographer</h4>
                        </div>
                        <div class="card-body">
                            <form class="needs-validation" novalidate>
                                <div class="row mb-3">
                                    <h4 class="card-title text-primary mb-3">Studio Selection</h4>
                                    <div class="form-group">
                                        <label class="form-label">Select Studio</label>
                                        <select class="form-select" name="studio_id" id="studioSelect" required>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a studio.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="row">
                                        <h4 class="card-title text-primary mb-3">Photographer Information</h4>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">First Name</label>
                                                <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid first name.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" placeholder="Enter middle name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid middle name.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid last name.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3 g-2">
                                    <div class="form-group">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" name="mobile_number" placeholder="Enter contact number" required data-toggle="input-mask" data-mask-format="+(63)000 000 0000">
                                        <div class="invalid-feedback">
                                            Please enter a valid contact number.
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="form-label fw-semibold">Profile Photo</label>
                                        <input type="file" class="form-control" name="profile_photo" accept=".jpg,.jpeg,.png" required>
                                        <div class="form-text">Upload a clear copy of your profile photo.</div>
                                        <div class="invalid-feedback">
                                            Please upload a valid profile photo.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <h4 class="card-title text-primary mb-3">Photography Details</h4>
                                    <div class="form-group mb-2">
                                        <label class="form-label">Position</label>
                                        <select class="form-select" name="position" required>
                                            <option value="">Select Position</option>
                                            <option value="Lead Photographer">Lead Photographer</option>
                                            <option value="Senior Photographer">Senior Photographer</option>
                                            <option value="Photographer">Photographer</option>
                                            <option value="Assistant Photographer">Assistant Photographer</option>
                                            <option value="Second Shooter">Second Shooter</option>
                                            <option value="Photography Assistant">Photography Assistant</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a valid position.
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="form-label">Specialization</label>
                                        <select class="form-select" id="specializationSelect" name="specialization" required>
                                            <option value="">Select specialization</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a valid specialization.
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="form-label">Years of Experience</label>
                                        <input type="number" class="form-control" name="years_experience" placeholder="Enter years of experience" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid years of experience.
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a valid status.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span id="submitText">Create Photographer</span>
                                            <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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
    <script src="{{ asset('assets/plugins/inputmask/inputmask.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-inputmask.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Load categories when studio is selected
            $('#studioSelect').on('change', function() {
                const studioId = $(this).val();
                const $specializationSelect = $('#specializationSelect');
                
                // Clear existing options except the first one
                $specializationSelect.find('option:not(:first)').remove();
                $specializationSelect.prop('disabled', true);
                
                if (!studioId) {
                    return;
                }
                
                // Add loading option
                const $loadingOption = $('<option value="" disabled>Loading categories...</option>');
                $specializationSelect.append($loadingOption);
                
                $.ajax({
                    url: "{{ route('owner.studio.services', ['id' => '__ID__']) }}".replace('__ID__', studioId),
                    method: 'GET',
                    success: function(response) {
                        // Remove loading option specifically
                        $loadingOption.remove();
                        
                        if (response.success && response.categories && response.categories.length > 0) {
                            response.categories.forEach(category => {
                                $specializationSelect.append(
                                    `<option value="${category.id}">${category.category_name}</option>`
                                );
                            });
                            $specializationSelect.prop('disabled', false);
                        } else {
                            $specializationSelect.append('<option value="" disabled>No categories available for this studio</option>');
                        }
                    },
                    error: function() {
                        // Remove loading option on error
                        $loadingOption.remove();
                        $specializationSelect.append('<option value="" disabled>Failed to load categories</option>');
                    }
                });
            });
            
            // Rest of the form submission code remains the same...
            $('form.needs-validation').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $('#submitBtn');
                const $submitText = $('#submitText');
                const $spinner = $('#spinner');
                
                // Validate form
                if (!$form[0].checkValidity()) {
                    e.stopPropagation();
                    $form.addClass('was-validated');
                    return;
                }
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Show loading
                $submitBtn.prop('disabled', true);
                $submitText.text('Creating...');
                $spinner.removeClass('d-none');
                
                // Send AJAX request
                $.ajax({
                    url: "{{ route('owner.studio-photographers.store') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
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
                                timerProgressBar: true,
                                didClose: () => {
                                    // Remove the password display Swal and redirect immediately
                                    window.location.href = "{{ route('owner.studio-photographers.index') }}";
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors).flat().join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    },
                    complete: function() {
                        $submitBtn.prop('disabled', false);
                        $submitText.text('Create Photographer');
                        $spinner.addClass('d-none');
                    }
                });
            });
        });
    </script>
@endsection
