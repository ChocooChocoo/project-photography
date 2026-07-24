@extends('layouts.admin.app')
@section('title', 'Create Subscription Plan')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Create Subscription Plan</h4>
                        </div>
                        <div class="card-body">
                            <form id="subscriptionPlanForm" class="needs-validation" novalidate>
                                @csrf
                                
                                {{-- Row 1: User Type Selection --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">User Type</label>
                                        <select class="form-select" name="user_type" id="user_type" required>
                                            <option value="">Select User Type</option>
                                            <option value="studio">Studio Owner</option>
                                            <option value="freelancer">Freelancer</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a user type.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Plan Type</label>
                                        <select class="form-select" name="plan_type" id="plan_type" required>
                                            <option value="">Select Plan Type</option>
                                            <option value="basic">Basic</option>
                                            <option value="premium">Premium</option>
                                            <option value="enterprise">Enterprise</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a plan type.
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 2: Billing Cycle and Price --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Billing Cycle</label>
                                        <select class="form-select" name="billing_cycle" id="billing_cycle" required>
                                            <option value="">Select Billing Cycle</option>
                                            <option value="monthly">Monthly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a billing cycle.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="price" id="price" placeholder="0.00" step="0.01" min="0" required>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a valid price.
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 3: Commission Rate and Support Level --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Commission Rate (%)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">%</span>
                                            <input type="number" class="form-control" name="commission_rate" id="commission_rate" placeholder="0.00" step="0.01" min="0" max="100" required>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter a valid commission rate (0-100%).
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Support Level</label>
                                        <select class="form-select" name="support_level" id="support_level" required>
                                            <option value="">Select Support Level</option>
                                            <option value="basic">Basic Support</option>
                                            <option value="priority">Priority Support</option>
                                            <option value="dedicated">Dedicated Support</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a support level.
                                        </div>
                                    </div>
                                </div>

                                {{-- Row 3b: Free Trial --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Free Trial (days)</label>
                                        <input type="number" class="form-control" name="trial_days" id="trial_days" placeholder="0" min="0" max="365" value="0">
                                        <small class="text-muted">Owners subscribing to this plan get this many days free before being charged. Leave 0 for no trial.</small>
                                    </div>
                                </div>

                                {{-- Row 4: Conditional Fields (Studio only) --}}
                                <div class="row" id="studioFields" style="display: none;">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Maximum Studio Photographers</label>
                                        <input type="number" class="form-control" name="max_studio_photographers" id="max_studio_photographers" placeholder="Leave empty for unlimited" min="1">
                                        <small class="text-muted">Maximum number of photographers this studio can have.</small>
                                    </div>
                                    
                                    {{-- NEW: Maximum Studios --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Maximum Studios</label>
                                        <input type="number" class="form-control" name="max_studios" id="max_studios" placeholder="Leave empty for unlimited" min="1">
                                        <small class="text-muted">How many studios can the owner register?</small>
                                    </div>
                                    
                                    {{-- NEW: Staff Limit --}}
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Staff Limit</label>
                                        <input type="number" class="form-control" name="staff_limit" id="staff_limit" placeholder="Leave empty for unlimited" min="1">
                                        <small class="text-muted">Maximum number of staff/employees</small>
                                    </div>
                                </div>

                                {{-- Row 5: Maximum Booking (applies to both) --}}
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maximum Bookings Per Period</label>
                                        <input type="number" class="form-control" name="max_booking" id="max_booking" placeholder="Leave empty for unlimited" min="0">
                                        <small class="text-muted">Maximum number of bookings allowed per billing cycle. Leave empty for unlimited.</small>
                                        <div class="invalid-feedback">
                                            Please enter a valid number.
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="status" required>
                                            <option value="" disabled selected>Select Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select a status.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Priority Level (0-5)</label>
                                        <select class="form-select" name="priority_level" id="priority_level">
                                            <option value="0">0 - Normal (Default)</option>
                                            <option value="1">1 - Low Priority</option>
                                            <option value="2">2 - Medium Priority</option>
                                            <option value="3">3 - High Priority</option>
                                            <option value="4">4 - Very High Priority</option>
                                            <option value="5">5 - Top Priority</option>
                                        </select>
                                        <small class="text-muted">Higher priority shows first in client dashboard</small>
                                    </div>
                                </div>

                                {{-- Row 6: Description --}}
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="description" rows="3" placeholder="Enter plan description..."></textarea>
                                    </div>
                                </div>

                                {{-- Row 7: Features --}}
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Features</label>
                                        <div id="featuresContainer">
                                            <div class="input-group feature-field mb-2">
                                                <input type="text" class="form-control feature-input" name="features[]" placeholder="Enter feature" required>
                                                <button class="btn btn-default add-feature-btn" type="button">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                                <button class="btn btn-default remove-feature-btn" type="button" disabled>
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted">Add features that will be displayed in the plan</small>
                                        <div class="invalid-feedback" id="featureError">
                                            Please enter at least one feature.
                                        </div>
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="row">
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                            <span id="submitText">Create Plan</span>
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
            // ===== FORM VALIDATION =====
            'use strict';
            
            // Fetch all forms we want to apply validation to
            var forms = document.querySelectorAll('.needs-validation');
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // ===== TOGGLE STUDIO FIELDS BASED ON USER TYPE =====
            $('#user_type').on('change', function() {
                if ($(this).val() === 'studio') {
                    $('#studioFields').slideDown();
                    $('#max_studio_photographers').prop('required', false); // Not required, can be empty
                } else {
                    $('#studioFields').slideUp();
                    $('#max_studio_photographers').val('').prop('required', false);
                }
            });

            // ===== DYNAMIC FEATURES =====
            function updateFeatureButtons() {
                let featureCount = $('.feature-field').length;
                
                $('.feature-field').each(function(index) {
                    let removeBtn = $(this).find('.remove-feature-btn');
                    if (featureCount === 1) {
                        removeBtn.prop('disabled', true);
                    } else {
                        removeBtn.prop('disabled', false);
                    }
                });
            }

            // Add feature
            $(document).on('click', '.add-feature-btn', function() {
                let newField = `
                    <div class="input-group feature-field mb-2">
                        <input type="text" class="form-control feature-input" name="features[]" placeholder="Enter feature" required>
                        <button class="btn btn-default add-feature-btn" type="button">
                            <i class="ti ti-plus"></i>
                        </button>
                        <button class="btn btn-default remove-feature-btn" type="button">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                `;
                $('#featuresContainer').append(newField);
                updateFeatureButtons();
            });

            // Remove feature
            $(document).on('click', '.remove-feature-btn', function() {
                if ($('.feature-field').length > 1) {
                    $(this).closest('.feature-field').remove();
                    updateFeatureButtons();
                }
            });

            // Initialize feature buttons
            updateFeatureButtons();

            // ===== FORM SUBMISSION WITH AJAX =====
            $('#subscriptionPlanForm').on('submit', function(e) {
                e.preventDefault();
                
                // Check form validity
                if (!this.checkValidity()) {
                    $(this).addClass('was-validated');
                    return;
                }

                // Check if at least one feature is added
                let featureInputs = $('input[name="features[]"]');
                let hasValidFeature = false;
                featureInputs.each(function() {
                    if ($(this).val().trim() !== '') {
                        hasValidFeature = true;
                    }
                });

                if (!hasValidFeature) {
                    $('#featureError').show();
                    return;
                } else {
                    $('#featureError').hide();
                }

                // Show loading state
                $('#submitBtn').prop('disabled', true);
                $('#submitSpinner').removeClass('d-none');
                $('#submitText').text('Creating...');

                // Collect form data
                let formData = {
                    user_type: $('#user_type').val(),
                    plan_type: $('#plan_type').val(),
                    billing_cycle: $('#billing_cycle').val(),
                    price: $('#price').val(),
                    commission_rate: $('#commission_rate').val(),
                    trial_days: $('#trial_days').val() || 0,
                    max_booking: $('#max_booking').val() || null,
                    max_studio_photographers: $('#user_type').val() === 'studio' ? ($('#max_studio_photographers').val() || null) : null,
                    description: $('#description').val(),
                    support_level: $('#support_level').val(),
                    status: $('#status').val(),
                    max_studios: $('#user_type').val() === 'studio' ? ($('#max_studios').val() || null) : null,
                    staff_limit: $('#user_type').val() === 'studio' ? ($('#staff_limit').val() || null) : null,
                    priority_level: $('#priority_level').val() || 0,
                    features: []
                };

                // Collect features
                $('input[name="features[]"]').each(function() {
                    if ($(this).val().trim() !== '') {
                        formData.features.push($(this).val().trim());
                    }
                });

                // Send AJAX request
                $.ajax({
                    url: '{{ route("admin.subscription.store") }}',
                    type: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
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
                                window.location.href = response.redirect || '{{ route("admin.subscription.index") }}';
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#submitBtn').prop('disabled', false);
                        $('#submitSpinner').addClass('d-none');
                        $('#submitText').text('Create Plan');
                        
                        let errorMessage = 'An error occurred. Please try again.';
                        
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                // Validation errors
                                let errors = xhr.responseJSON.errors;
                                errorMessage = '<ul>';
                                $.each(errors, function(key, value) {
                                    errorMessage += '<li>' + value[0] + '</li>';
                                });
                                errorMessage += '</ul>';
                            } else if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMessage,
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            });

            // ===== AUTO-GENERATE PLAN NAME PREVIEW =====
            function updatePlanNamePreview() {
                let userType = $('#user_type').val();
                let planType = $('#plan_type').val();
                let billingCycle = $('#billing_cycle').val();
                
                if (userType && planType && billingCycle) {
                    let userTypeLabel = userType === 'studio' ? 'Studio' : 'Freelancer';
                    let planTypeLabel = planType.charAt(0).toUpperCase() + planType.slice(1);
                    let cycleLabel = billingCycle.charAt(0).toUpperCase() + billingCycle.slice(1);
                    
                    let previewName = `${userTypeLabel} ${planTypeLabel} (${cycleLabel})`;
                    $('#planNamePreview').text(`Plan will be named: ${previewName}`);
                } else {
                    $('#planNamePreview').text('');
                }
            }

            $('#user_type, #plan_type, #billing_cycle').on('change', updatePlanNamePreview);
        });
    </script>
@endsection