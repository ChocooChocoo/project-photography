@extends('layouts.owner.app')
@section('title', 'Create Services')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Create Category Services</h4>
                        </div>
                        <div class="card-body">
                            <form id="createServiceForm" class="needs-validation" novalidate>
                                @csrf
                                <div class="row">

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Verified Studios</label>
                                        <select class="form-select" name="studio_id" id="studio_id" required>
                                            <option value="">Select Verified Studios</option>
                                            @foreach($verifiedStudios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a verified studio.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" id="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a category.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Service Name (Contents)</label>                                        
                                        <div id="serviceNamesContainer">
                                            <div class="input-group mb-2 service-name-field">
                                                <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                                                <button class="btn btn-default add-service-name-btn" type="button">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                                <button class="btn btn-default remove-service-name-btn" type="button" disabled>
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter at least one service name.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Starting Price (₱)</label>
                                        <input type="number" class="form-control" name="starting_from" id="starting_from" min="0" step="0.01" placeholder="Optional — shown to clients as 'from ₱X'">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span id="submitText">Create Services</span>
                                    <span id="spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                                </button>
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
            // Function to update remove button states
            function updateRemoveButtons() {
                const serviceFields = $('.service-name-field');
                if (serviceFields.length === 1) {
                    serviceFields.find('.remove-service-name-btn').prop('disabled', true);
                } else {
                    serviceFields.find('.remove-service-name-btn').prop('disabled', false);
                }
            }
            
            // Add new service name field
            $(document).on('click', '.add-service-name-btn', function() {
                const newField = $(`
                    <div class="input-group mb-2 service-name-field">
                        <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                        <button class="btn btn-default add-service-name-btn" type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                        <button class="btn btn-default remove-service-name-btn" type="button">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                `);
                
                $('#serviceNamesContainer').append(newField);
                updateRemoveButtons();
            });
            
            // Remove service name field
            $(document).on('click', '.remove-service-name-btn', function() {
                if ($('.service-name-field').length > 1) {
                    $(this).closest('.service-name-field').remove();
                    updateRemoveButtons();
                }
            });
            
            // Form submission
            $('#createServiceForm').submit(function(e) {
                e.preventDefault();
                
                // Validate that at least one service name is filled
                const serviceNames = $('input[name="service_name[]"]').filter(function() {
                    return $(this).val().trim() !== '';
                });
                
                if (serviceNames.length === 0) {
                    $('#serviceNamesContainer').next('.invalid-feedback').show();
                    $('#serviceNamesContainer').addClass('is-invalid');
                    return;
                } else {
                    $('#serviceNamesContainer').next('.invalid-feedback').hide();
                    $('#serviceNamesContainer').removeClass('is-invalid');
                }
                
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }
                
                const formData = $(this).serialize();
                const submitBtn = $('#submitBtn');
                const submitText = $('#submitText');
                const spinner = $('#spinner');
                
                // Show loading state
                submitBtn.prop('disabled', true);
                submitText.text('Creating...');
                spinner.removeClass('d-none');
                
                $.ajax({
                    url: '{{ route("owner.services.store") }}',
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Show success alert
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 1500
                            }).then(() => {
                                // Reset form
                                $('#createServiceForm')[0].reset();
                                $('#createServiceForm').removeClass('was-validated');
                                // Reset service names container to single field
                                $('#serviceNamesContainer').html(`
                                    <div class="input-group mb-2 service-name-field">
                                        <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                                        <button class="btn btn-default add-service-name-btn" type="button">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                        <button class="btn btn-default remove-service-name-btn" type="button" disabled>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                `);
                                window.location.href = '{{ route("owner.services.index") }}';
                            });
                        } else {
                            showErrorAlert(response.message || 'Failed to create service.');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                            
                            // Show validation errors if any
                            if (xhr.responseJSON.errors) {
                                const errors = xhr.responseJSON.errors;
                                errorMessage = '';
                                for (const key in errors) {
                                    if (key === 'service_name') {
                                        errorMessage += errors[key][0] + '\n';
                                    } else if (Array.isArray(errors[key])) {
                                        errorMessage += errors[key][0] + '\n';
                                    } else {
                                        errorMessage += errors[key] + '\n';
                                    }
                                }
                            }
                        }
                        
                        showErrorAlert(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.prop('disabled', false);
                        submitText.text('Create Services');
                        spinner.addClass('d-none');
                    }
                });
            });
            
            function showErrorAlert(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonColor: '#DC3545',
                    confirmButtonText: 'OK'
                });
            }
            
            // Initial button state
            updateRemoveButtons();
        });
    </script>
@endsection