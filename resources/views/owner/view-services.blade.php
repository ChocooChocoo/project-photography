@extends('layouts.owner.app')
@section('title', 'View Services')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Services</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search schedules...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control">
                                        <option value="">All Status</option>
                                        <option value="Verified">Verified</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="servicesTable" class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Studio</th>
                                        <th data-table-sort>Category</th>
                                        <th data-table-sort>Services</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="servicesTableBody">
                                    @php
                                        // Get services with their relationships
                                        $services = \App\Models\StudioOwner\ServicesModel::with(['studio', 'category'])
                                            ->whereHas('studio', function ($query) {
                                                $query->where('user_id', auth()->id());
                                            })
                                            ->orderBy('created_at', 'desc')
                                            ->get();
                                    @endphp
                                    
                                    @if($services->count() > 0)
                                        @foreach($services as $service)
                                            @php
                                                // Decode JSON service names
                                                $serviceNames = $service->service_name ? json_decode($service->service_name, true) : [];
                                                $servicesList = implode(', ', $serviceNames);
                                                $truncatedList = strlen($servicesList) > 50 ? substr($servicesList, 0, 50) . '...' : $servicesList;
                                            @endphp
                                            <tr data-id="{{ $service->id }}">
                                                <td>{{ $service->studio ? $service->studio->studio_name : 'N/A' }}</td>
                                                <td>{{ $service->category ? $service->category->category_name : 'N/A' }}</td>
                                                <td>{{ $truncatedList }}</td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-eye-service">
                                                            <i class="ti ti-eye fs-lg"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-edit-service" data-id="{{ $service->id }}">
                                                            <i class="ti ti-edit fs-lg"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-delete-service" data-id="{{ $service->id }}">
                                                            <i class="ti ti-trash fs-lg"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="services"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- EDIT MODAL --}}
    <div class="modal fade" id="editServiceModal" tabindex="-1" aria-labelledby="editServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editServiceModalLabel">Edit Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editServiceForm" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="id" id="edit_service_id">
                        
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Select Verified Studios</label>
                                <select class="form-select" name="studio_id" id="edit_studio_id" required>
                                    <option value="">Select Verified Studios</option>
                                    @php
                                        $verifiedStudios = \App\Models\StudioOwner\StudiosModel::where('user_id', auth()->id())
                                            ->where('status', 'verified')
                                            ->get(['id', 'studio_name']);
                                    @endphp
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
                                <select class="form-select" name="category_id" id="edit_category_id" required>
                                    <option value="">Select Category</option>
                                    @php
                                        $categories = \App\Models\Admin\CategoriesModel::where('status', 'active')
                                            ->get(['id', 'category_name']);
                                    @endphp
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
                                <div id="editServiceNamesContainer">
                                    <!-- Dynamic fields will be added here by JavaScript -->
                                </div>
                                <div class="invalid-feedback">
                                    Please enter at least one service name.
                                </div>
                            </div>

                            <div class="col-12 mb-3">
                                <label class="form-label">Starting Price (₱)</label>
                                <input type="number" class="form-control" name="starting_from" id="edit_starting_from" min="0" step="0.01" placeholder="Optional — shown to clients as 'from ₱X'">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateServiceBtn">Update Service</button>
                </div>
            </div>
        </div>
    </div>

    {{-- VIEW MODAL --}}
    <div class="modal fade" id="viewServiceModal" tabindex="-1" aria-labelledby="viewServiceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="viewServiceModalLabel">
                        <span id="viewStudioName"></span> - Services
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h5 class="card-title text-primary mb-2 fs-4" id="viewCategoryName"></h5>
                    <ul class="list-group border-0" id="viewServiceList">
                        <!-- Service names will be dynamically added here -->
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Edit service - Load data via AJAX
            $(document).on('click', '.btn-edit-service', function() {
                const serviceId = $(this).data('id');
                
                // Show loading state
                $('#editServiceModal').modal('show');
                $('#updateServiceBtn').prop('disabled', true).text('Loading...');
                
                $.ajax({
                    url: `{{ url('owner/services') }}/${serviceId}/edit`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const service = response.data.service;
                            
                            $('#edit_service_id').val(service.id);
                            $('#edit_studio_id').val(service.studio_id);
                            $('#edit_category_id').val(service.category_id);
                            $('#edit_service_name').val(service.service_name);
                            $('#edit_starting_from').val(service.starting_from);
                            // Removed: service_description and status fields
                            
                            $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                        } else {
                            $('#editServiceModal').modal('hide');
                            showErrorAlert(response.message || 'Failed to load service data.');
                        }
                    },
                    error: function() {
                        $('#editServiceModal').modal('hide');
                        showErrorAlert('An error occurred while loading service data.');
                    }
                });
            });
            
            // Update service
            $('#updateServiceBtn').click(function() {
                const form = $('#editServiceForm');
                
                if (!form[0].checkValidity()) {
                    form[0].classList.add('was-validated');
                    return;
                }
                
                const formData = form.serialize();
                const serviceId = $('#edit_service_id').val();
                
                $(this).prop('disabled', true).text('Updating...');
                
                $.ajax({
                    url: `{{ url('owner/services') }}/${serviceId}`,
                    type: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 1500
                            }).then(() => {
                                $('#editServiceModal').modal('hide');
                                location.reload(); // Reload page to show updated data
                            });
                        } else {
                            showErrorAlert(response.message || 'Failed to update service.');
                            $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                            
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
                        $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                    }
                });
            });
            
            // Delete service
            $(document).on('click', '.btn-delete-service', function() {
                const serviceId = $(this).data('id');
                const serviceRow = $(this).closest('tr');
                const serviceName = serviceRow.find('td:nth-child(3)').text();
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete "${serviceName}". This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('owner/services') }}/${serviceId}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timerProgressBar: true,
                                        timer: 1500
                                    }).then(() => {
                                        location.reload(); // Reload page to reflect deletion
                                    });
                                } else {
                                    showErrorAlert(response.message || 'Failed to delete service.');
                                }
                            },
                            error: function() {
                                showErrorAlert('An error occurred while deleting the service.');
                            }
                        });
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
        });

        // Function to update remove button states for edit modal
        function updateEditRemoveButtons() {
            const serviceFields = $('.edit-service-name-field');
            if (serviceFields.length === 1) {
                serviceFields.find('.remove-edit-service-name-btn').prop('disabled', true);
            } else {
                serviceFields.find('.remove-edit-service-name-btn').prop('disabled', false);
            }
        }

        // Add new service name field in edit modal
        $(document).on('click', '.add-edit-service-name-btn', function() {
            const newField = $(`
                <div class="input-group mb-2 edit-service-name-field">
                    <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                    <button class="btn btn-default add-edit-service-name-btn" type="button">
                        <i class="ti ti-plus"></i>
                    </button>
                    <button class="btn btn-default remove-edit-service-name-btn" type="button">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            `);
            
            $('#editServiceNamesContainer').append(newField);
            updateEditRemoveButtons();
        });

        // Remove service name field in edit modal
        $(document).on('click', '.remove-edit-service-name-btn', function() {
            if ($('.edit-service-name-field').length > 1) {
                $(this).closest('.edit-service-name-field').remove();
                updateEditRemoveButtons();
            }
        });

        // Edit service - Load data via AJAX
        $(document).on('click', '.btn-edit-service', function() {
            const serviceId = $(this).data('id');
            
            // Show loading state
            $('#editServiceModal').modal('show');
            $('#updateServiceBtn').prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: `{{ url('owner/services') }}/${serviceId}/edit`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const service = response.data.service;
                        
                        $('#edit_service_id').val(service.id);
                        $('#edit_studio_id').val(service.studio_id);
                        $('#edit_category_id').val(service.category_id);
                        $('#edit_starting_from').val(service.starting_from);

                        // Clear and populate service names container
                        $('#editServiceNamesContainer').empty();
                        
                        if (service.service_names_array && service.service_names_array.length > 0) {
                            service.service_names_array.forEach(function(serviceName, index) {
                                const field = $(`
                                    <div class="input-group mb-2 edit-service-name-field">
                                        <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" value="${serviceName}" required>
                                        <button class="btn btn-default add-edit-service-name-btn" type="button">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                        <button class="btn btn-default remove-edit-service-name-btn" type="button" ${index === 0 ? 'disabled' : ''}>
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                `);
                                $('#editServiceNamesContainer').append(field);
                            });
                        } else {
                            // Add one empty field if no service names
                            const field = $(`
                                <div class="input-group mb-2 edit-service-name-field">
                                    <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                                    <button class="btn btn-default add-edit-service-name-btn" type="button">
                                        <i class="ti ti-plus"></i>
                                    </button>
                                    <button class="btn btn-default remove-edit-service-name-btn" type="button" disabled>
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            `);
                            $('#editServiceNamesContainer').append(field);
                        }
                        
                        updateEditRemoveButtons();
                        $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                    } else {
                        $('#editServiceModal').modal('hide');
                        showErrorAlert(response.message || 'Failed to load service data.');
                    }
                },
                error: function() {
                    $('#editServiceModal').modal('hide');
                    showErrorAlert('An error occurred while loading service data.');
                }
            });
        });

        // Update service
        $('#updateServiceBtn').click(function() {
            const form = $('#editServiceForm');
            
            // Validate that at least one service name is filled
            const serviceNames = $('#editServiceNamesContainer input[name="service_name[]"]').filter(function() {
                return $(this).val().trim() !== '';
            });
            
            if (serviceNames.length === 0) {
                $('#editServiceNamesContainer').next('.invalid-feedback').show();
                $('#editServiceNamesContainer').addClass('is-invalid');
                return;
            } else {
                $('#editServiceNamesContainer').next('.invalid-feedback').hide();
                $('#editServiceNamesContainer').removeClass('is-invalid');
            }
            
            if (!form[0].checkValidity()) {
                form[0].classList.add('was-validated');
                return;
            }
            
            const formData = form.serialize();
            const serviceId = $('#edit_service_id').val();
            
            $(this).prop('disabled', true).text('Updating...');
            
            $.ajax({
                url: `{{ url('owner/services') }}/${serviceId}`,
                type: 'PUT',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timerProgressBar: true,
                            timer: 1500
                        }).then(() => {
                            $('#editServiceModal').modal('hide');
                            location.reload(); // Reload page to show updated data
                        });
                    } else {
                        showErrorAlert(response.message || 'Failed to update service.');
                        $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'An error occurred. Please try again.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                        
                        if (xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            errorMessage = '';
                            for (const key in errors) {
                                if (key.startsWith('service_name.')) {
                                    errorMessage += errors[key][0] + '\n';
                                } else {
                                    errorMessage += errors[key][0] + '\n';
                                }
                            }
                        }
                    }
                    
                    showErrorAlert(errorMessage);
                    $('#updateServiceBtn').prop('disabled', false).text('Update Service');
                }
            });
        });

        // View service details
        $(document).on('click', '.btn-eye-service', function() {
            const serviceId = $(this).closest('tr').data('id');
            const serviceRow = $(this).closest('tr');
            const studioName = serviceRow.find('td:first-child').text();
            const categoryName = serviceRow.find('td:nth-child(2)').text();
            
            // Set static data
            $('#viewStudioName').text(studioName);
            $('#viewCategoryName').text(categoryName);
            
            // Show loading in modal
            $('#viewServiceList').html('<li class="list-group-item text-center"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Loading services...</li>');
            $('#viewServiceModal').modal('show');
            
            // Load service data via AJAX
            $.ajax({
                url: `{{ url('owner/services') }}/${serviceId}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const service = response.data;
                        let serviceListHtml = '';
                        
                        if (service.service_names_array && service.service_names_array.length > 0) {
                            service.service_names_array.forEach(function(serviceName) {
                                serviceListHtml += `
                                    <li class="list-group-item">
                                        <i class="ti ti-check text-success me-1 align-middle fs-xl"></i>
                                        ${serviceName}
                                    </li>`;
                            });
                        } else {
                            serviceListHtml = '<li class="list-group-item text-center text-muted">No services found</li>';
                        }
                        
                        $('#viewServiceList').html(serviceListHtml);
                    } else {
                        $('#viewServiceList').html('<li class="list-group-item text-center text-danger">Failed to load service details</li>');
                    }
                },
                error: function() {
                    $('#viewServiceList').html('<li class="list-group-item text-center text-danger">Error loading service details</li>');
                }
            });
        });
    </script>
@endsection