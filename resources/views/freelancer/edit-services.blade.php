@extends('layouts.freelancer.app')
@section('title', 'Edit Services')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Edit Category Services</h4>
                        </div>
                        <div class="card-body">
                            <form id="editServiceForm" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" value="{{ $service->id }}">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Select Category</label>
                                        <select class="form-select" name="category_id" id="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ $service->category_id == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a category.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Service Name (Contents)</label>
                                        <div id="serviceNamesContainer">
                                            @php $serviceNames = $service->services_name ?: []; @endphp
                                            @forelse($serviceNames as $index => $serviceName)
                                                <div class="input-group mb-2 service-name-field">
                                                    <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" value="{{ $serviceName }}" required>
                                                    <button class="btn btn-default add-service-name-btn" type="button">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <button class="btn btn-default remove-service-name-btn" type="button" {{ $index === 0 ? 'disabled' : '' }}>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            @empty
                                                <div class="input-group mb-2 service-name-field">
                                                    <input type="text" class="form-control" name="service_name[]" placeholder="Enter service name" required>
                                                    <button class="btn btn-default add-service-name-btn" type="button">
                                                        <i class="ti ti-plus"></i>
                                                    </button>
                                                    <button class="btn btn-default remove-service-name-btn" type="button" disabled>
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter at least one service name.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Starting Price (₱)</label>
                                        <input type="number" class="form-control" name="starting_from" id="starting_from" min="0" step="0.01" value="{{ $service->starting_from }}" placeholder="Optional — shown to clients as 'from ₱X'">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <span id="submitText">Update Services</span>
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
            function updateRemoveButtons() {
                const serviceFields = $('.service-name-field');
                serviceFields.find('.remove-service-name-btn').prop('disabled', serviceFields.length === 1);
            }

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

            $(document).on('click', '.remove-service-name-btn', function() {
                if ($('.service-name-field').length > 1) {
                    $(this).closest('.service-name-field').remove();
                    updateRemoveButtons();
                }
            });

            $('#editServiceForm').submit(function(e) {
                e.preventDefault();

                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }

                const serviceId = $('input[name="id"]').val();
                const formData = $(this).serialize();
                const submitBtn = $('#submitBtn');
                const submitText = $('#submitText');
                const spinner = $('#spinner');

                submitBtn.prop('disabled', true);
                submitText.text('Updating...');
                spinner.removeClass('d-none');

                $.ajax({
                    url: `{{ url('freelancer/services') }}/${serviceId}`,
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
                                window.location.href = '{{ route("freelancer.services.index") }}';
                            });
                        } else {
                            showErrorAlert(response.message || 'Failed to update service.');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showErrorAlert(errorMessage);
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        submitText.text('Update Services');
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

            updateRemoveButtons();
        });
    </script>
@endsection
