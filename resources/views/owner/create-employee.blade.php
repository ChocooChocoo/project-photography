@extends('layouts.owner.app')
@section('title', 'Create Studio Employee')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Create Studio Employee</h4>
                        </div>
                        <div class="card-body">
                            <form class="needs-validation" novalidate id="employeeForm">
                                @csrf
                                
                                {{-- STUDIO SELECTION --}}
                                <div class="row">
                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Studio Selection</h4>
                                        <label class="form-label">Select Studio <span class="text-danger">*</span></label>
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

                                    {{-- EMPLOYEE INFORMATION --}}
                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Employee Information</h4>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid first name.
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Middle Name</label>
                                                <input type="text" class="form-control" name="middle_name" placeholder="Enter middle name">
                                                <div class="invalid-feedback">
                                                    Please enter a valid middle name.
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid last name.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-3">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                                                <div class="invalid-feedback">
                                                    Please enter a valid email address.
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Contact Number <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="mobile_number" placeholder="Enter contact number" required data-toggle="input-mask" data-mask-format="+(63)000 000 0000">
                                                <div class="invalid-feedback">
                                                    Please enter a valid contact number.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PROFILE PHOTO --}}
                                    <div class="form-group mb-3">
                                        <div class="row">
                                            <div class="col-12">
                                                <label class="form-label">Profile Photo</label>
                                                <input type="file" class="form-control" name="profile_photo" accept=".jpg,.jpeg,.png">
                                                <div class="form-text">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Upload a clear profile photo (optional). Accepted formats: JPG, JPEG, PNG (max 2MB).
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- STUDIO POSITION --}}
                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Studio Position</h4>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Employee's Role <span class="text-danger">*</span></label>
                                                <select class="form-select" name="role" id="roleSelect" required>
                                                    <option value="">Select Role</option>
                                                    <option value="studio-hr">Human Resource</option>
                                                    <option value="studio-finance">Finance</option>
                                                    <option value="studio-photographer">Photographer</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a role.
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Role Type <span class="text-danger">*</span></label>
                                                <select class="form-select" name="role_type" id="roleTypeSelect" required>
                                                    <option value="">Select Role Type</option>
                                                </select>
                                                <div class="invalid-feedback" id="roleTypeFeedback">
                                                    Please select a role type.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- PHOTOGRAPHER-SPECIFIC FIELDS (Hidden by default) --}}
                                    <div id="photographerFields" style="display: none;">
                                        <div class="form-group mb-3">
                                            <h4 class="card-title text-primary mb-3">Photographer Details</h4>
                                            <div class="row g-3">
                                                <div class="col-md-4">
                                                    <label class="form-label">Position <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="position" id="positionSelect">
                                                        <option value="">Select Position</option>
                                                        <option value="Lead Photographer">Lead Photographer</option>
                                                        <option value="Senior Photographer">Senior Photographer</option>
                                                        <option value="Photographer">Photographer</option>
                                                        <option value="Assistant Photographer">Assistant Photographer</option>
                                                        <option value="Second Shooter">Second Shooter</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select a position.
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                                                    <select class="form-select" name="specialization" id="specializationSelect">
                                                        <option value="">Select Specialization</option>
                                                    </select>
                                                    <div class="invalid-feedback">
                                                        Please select a specialization.
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label">Years of Experience <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control" name="years_experience" placeholder="Enter years" min="0" max="50">
                                                    <div class="invalid-feedback">
                                                        Please enter valid years of experience (0-50).
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- STATUS --}}
                                    <div class="form-group mb-3">
                                        <label class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" name="status" required>
                                            <option value="">Select Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a status.
                                        </div>
                                    </div>

                                    {{-- ROLE-BASED ACCESS CONTROL (RBAC) --}}
                                    <div class="form-group">
                                        <h4 class="card-title text-primary mb-3">Role-Based Access Control (RBAC)</h4>
                                        <div class="card border">
                                            <div class="card-body">
                                                <p class="text-muted mb-4">
                                                    <i class="ti ti-shield-lock me-1"></i>
                                                    Configure granular permissions for this role. Each toggle controls specific CRUD operations.
                                                </p>
                                                
                                                <div class="row g-4">
                                                    <!-- Create Permission -->
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-plus-circle text-success me-2"></i>
                                                                <span class="fw-medium">Create</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacCreate" name="can_create" value="1">
                                                                <label class="form-check-label" for="rbacCreate"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Create: Allows user to add new records, upload files, and create new entries in the system
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Read Permission -->
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-eye-fill text-info me-2"></i>
                                                                <span class="fw-medium">Read</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacRead" name="can_read" value="1">
                                                                <label class="form-check-label" for="rbacRead"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Read: Enables viewing, searching, and accessing existing records and information
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Update Permission -->
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-pencil-square text-warning me-2"></i>
                                                                <span class="fw-medium">Update</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacUpdate" name="can_update" value="1">
                                                                <label class="form-check-label" for="rbacUpdate"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Update: Allows modification, editing, and updating of existing records and information
                                                        </small>
                                                    </div>
                                                    
                                                    <!-- Delete Permission -->
                                                    <div class="col-md-3">
                                                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                                            <div>
                                                                <i class="ti ti-trash-fill text-danger me-2"></i>
                                                                <span class="fw-medium">Delete</span>
                                                            </div>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" role="switch" id="rbacDelete" name="can_delete" value="1">
                                                                <label class="form-check-label" for="rbacDelete"></label>
                                                            </div>
                                                        </div>
                                                        <small class="text-muted d-block mt-2 ps-1">
                                                            Delete: Grants ability to remove, archive, or permanently delete records from the system
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- EMPLOYEE SCHEDULE --}}
                                    <div class="form-group mb-3">
                                        <h4 class="card-title text-primary mb-3">Employee Schedule</h4>
                                        <div class="row g-4 mb-4">
                                            <div class="col-md-6">
                                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" name="start_time" value="09:00" required>
                                                <small class="text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Regular work start time
                                                </small>
                                                <div class="invalid-feedback">
                                                    Please select a start time.
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                                <input type="time" class="form-control" name="end_time" value="18:00" required>
                                                <small class="text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>
                                                    Regular work end time
                                                </small>
                                                <div class="invalid-feedback">
                                                    Please select an end time.
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <label class="form-label mb-2">Select Operating Days <span class="text-danger">*</span></label>
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
                                                <small class="d-block text-muted">Check which days the employee will work</small>
                                                <div class="invalid-feedback" id="operating_days_error">Please select at least one day.</div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- SUBMIT BUTTON --}}
                                    <div class="d-flex justify-content-start">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span id="submitText">Submit Employee</span>
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
    <script>
        $(document).ready(function() {
            // ==================== ROLE SELECTION HANDLER ====================
            $('#roleSelect').on('change', function() {
                const role = $(this).val();
                const $roleTypeSelect = $('#roleTypeSelect');
                const $photographerFields = $('#photographerFields');
                const $roleTypeFeedback = $('#roleTypeFeedback');
                
                // Clear role type options
                $roleTypeSelect.find('option:not(:first)').remove();
                
                if (role === 'studio-photographer') {
                    // Show photographer fields
                    $photographerFields.show();
                    
                    // Set role type to Photographer (disabled)
                    $roleTypeSelect.append('<option value="Photographer" selected>Photographer</option>');
                    $roleTypeSelect.prop('disabled', true);
                    $roleTypeFeedback.text('Role type is fixed for photographers.');
                    
                    // Load categories for specialization
                    loadCategories();
                    
                } else {
                    // Hide photographer fields
                    $photographerFields.hide();
                    
                    // Enable role type and add options
                    $roleTypeSelect.prop('disabled', false);
                    $roleTypeSelect.append('<option value="Manager">Manager</option>');
                    $roleTypeSelect.append('<option value="Staff">Staff</option>');
                    $roleTypeFeedback.text('Please select a role type.');
                    
                    // Clear photographer fields
                    $('#positionSelect').val('');
                    $('#specializationSelect').find('option:not(:first)').remove();
                    $('#specializationSelect').prop('disabled', true);
                    $('input[name="years_experience"]').val('');
                }
            });

            // ==================== LOAD CATEGORIES FOR PHOTOGRAPHER ====================
            function loadCategories() {
                const $specializationSelect = $('#specializationSelect');
                $specializationSelect.find('option:not(:first)').remove();
                $specializationSelect.append('<option value="" disabled>Loading categories...</option>');
                
                $.ajax({
                    url: "{{ route('owner.employee.categories') }}",
                    method: 'GET',
                    success: function(response) {
                        $specializationSelect.find('option:disabled').remove();
                        
                        if (response.success && response.data && response.data.length > 0) {
                            response.data.forEach(category => {
                                $specializationSelect.append(
                                    `<option value="${category.id}">${category.category_name}</option>`
                                );
                            });
                            $specializationSelect.prop('disabled', false);
                        } else {
                            $specializationSelect.append('<option value="" disabled>No categories available</option>');
                            console.warn('No categories found:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        $specializationSelect.find('option:disabled').remove();
                        $specializationSelect.append('<option value="" disabled>Failed to load categories</option>');
                        
                        console.error('AJAX Error:', {
                            status: status,
                            error: error,
                            response: xhr.responseJSON
                        });
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to load categories. Please refresh the page and try again.'
                        });
                    }
                });
            }

            // ==================== FORM SUBMIT HANDLER ====================
            $('#employeeForm').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $submitBtn = $('#submitBtn');
                const $submitText = $('#submitText');
                const $spinner = $('#spinner');
                
                // Validate operating days
                const operatingDays = $('input[name="operating_days[]"]:checked').length;
                if (operatingDays === 0) {
                    $('#operating_days_error').show();
                    $form.addClass('was-validated');
                    return;
                } else {
                    $('#operating_days_error').hide();
                }
                
                // Validate form
                if (!$form[0].checkValidity()) {
                    e.stopPropagation();
                    $form.addClass('was-validated');
                    return;
                }
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Ensure checkboxes send values when checked
                if (!$('#rbacCreate').is(':checked')) formData.delete('can_create');
                if (!$('#rbacRead').is(':checked')) formData.delete('can_read');
                if (!$('#rbacUpdate').is(':checked')) formData.delete('can_update');
                if (!$('#rbacDelete').is(':checked')) formData.delete('can_delete');
                
                // Show loading
                $submitBtn.prop('disabled', true);
                $submitText.text('Creating...');
                $spinner.removeClass('d-none');
                
                // Send AJAX request
                $.ajax({
                    url: "{{ route('owner.employee.store') }}",
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
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: () => {
                                    window.location.href = "{{ route('owner.employee.index') }}";
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
                        $submitText.text('Submit Employee');
                        $spinner.addClass('d-none');
                    }
                });
            });

            // ==================== OPERATING DAYS VALIDATION ====================
            $('input[name="operating_days[]"]').on('change', function() {
                const operatingDays = $('input[name="operating_days[]"]:checked').length;
                if (operatingDays > 0) {
                    $('#operating_days_error').hide();
                }
            });

            // ==================== TIME VALIDATION ====================
            $('input[name="end_time"]').on('change', function() {
                const startTime = $('input[name="start_time"]').val();
                const endTime = $(this).val();
                
                if (startTime && endTime && endTime <= startTime) {
                    $(this)[0].setCustomValidity('End time must be after start time');
                } else {
                    $(this)[0].setCustomValidity('');
                }
            });

            $('input[name="start_time"]').on('change', function() {
                const endTime = $('input[name="end_time"]').val();
                if (endTime) {
                    $('input[name="end_time"]').trigger('change');
                }
            });
        });
    </script>
@endsection