@extends('layouts.freelancer.app')
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
            // Dynamic service name fields
            let serviceFieldCount = 1;

            // Add new service name field
            $(document).on('click', '.add-service-name-btn', function() {
                serviceFieldCount++;
                const newField = `
                    <div class="input-group mb-2 service-name-field">
                        <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                        <button class="btn btn-default add-service-name-btn" type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                        <button class="btn btn-default remove-service-name-btn" type="button">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                `;
                $('#serviceNamesContainer').append(newField);
                updateRemoveButtons();
            });

            // Remove service name field
            $(document).on('click', '.remove-service-name-btn', function() {
                if ($('.service-name-field').length > 1) {
                    $(this).closest('.service-name-field').remove();
                    serviceFieldCount--;
                    updateRemoveButtons();
                }
            });

            // Update remove buttons state
            function updateRemoveButtons() {
                $('.remove-service-name-btn').prop('disabled', $('.service-name-field').length <= 1);
            }

            // Form submission
            $('#createServiceForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!this.checkValidity()) {
                    this.classList.add('was-validated');
                    return;
                }

                // Show loading
                $('#submitBtn').prop('disabled', true);
                $('#submitText').addClass('d-none');
                $('#spinner').removeClass('d-none');

                // Get form data
                const formData = $(this).serializeArray();
                
                // Filter out empty service names
                const serviceNames = $('input[name="service_name[]"]').map(function() {
                    return $(this).val().trim();
                }).get().filter(name => name !== '');

                // Prepare final data
                const data = {
                    category_id: $('#category_id').val(),
                    service_name: serviceNames
                };

                // AJAX request
                $.ajax({
                    url: "{{ route('freelancer.services.store') }}",
                    type: 'POST',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // SweetAlert2 success
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                confirmButtonColor: '#007BFF',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = "{{ route('freelancer.services.index') }}";
                            });
                        } else {
                            showError(response.message);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create services. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = Object.values(errors)[0][0];
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        showError(errorMessage);
                    },
                    complete: function() {
                        // Reset loading state
                        $('#submitBtn').prop('disabled', false);
                        $('#submitText').removeClass('d-none');
                        $('#spinner').addClass('d-none');
                    }
                });
            });

            // Error handler
            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonColor: '#DC3545',
                    confirmButtonText: 'OK'
                });
            }

            // Initialize
            updateRemoveButtons();
        });
    </script>
@endsection