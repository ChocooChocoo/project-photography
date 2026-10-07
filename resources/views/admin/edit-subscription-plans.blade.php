@extends('layouts.admin.app')
@section('title', 'Edit Subscription Plan')

{{-- CONTENT --}}
@section('content')
    @php
        $features = is_array($plan->features) && count($plan->features) ? $plan->features : [''];
        $isStudio = $plan->user_type === 'studio';
    @endphp
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Edit Subscription Plan</h4>
                        </div>
                        <div class="card-body">
                            <form id="subscriptionPlanForm" class="needs-validation" novalidate>
                                @csrf

                                {{-- Plan identity is kept as stored; the update request requires both. --}}
                                <input type="hidden" name="plan_code" id="plan_code" value="{{ $plan->plan_code }}">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Plan Name</label>
                                        <input type="text" class="form-control" name="name" id="name" maxlength="100" value="{{ $plan->name }}" required>
                                        <div class="invalid-feedback">Please enter a plan name.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Plan Code</label>
                                        <input type="text" class="form-control" value="{{ $plan->plan_code }}" disabled>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">User Type</label>
                                        <select class="form-select" name="user_type" id="user_type" required>
                                            <option value="studio" @selected($plan->user_type === 'studio')>Studio Owner</option>
                                            <option value="freelancer" @selected($plan->user_type === 'freelancer')>Freelancer</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Plan Type</label>
                                        <select class="form-select" name="plan_type" id="plan_type" required>
                                            <option value="basic" @selected($plan->plan_type === 'basic')>Basic</option>
                                            <option value="premium" @selected($plan->plan_type === 'premium')>Premium</option>
                                            <option value="enterprise" @selected($plan->plan_type === 'enterprise')>Enterprise</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Billing Cycle</label>
                                        <select class="form-select" name="billing_cycle" id="billing_cycle" required>
                                            <option value="monthly" @selected($plan->billing_cycle === 'monthly')>Monthly</option>
                                            <option value="yearly" @selected($plan->billing_cycle === 'yearly')>Yearly</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" value="{{ $plan->price }}" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid price.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Commission Rate (%)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">%</span>
                                            <input type="number" class="form-control" name="commission_rate" id="commission_rate" step="0.01" min="0" max="100" value="{{ $plan->commission_rate }}" required>
                                        </div>
                                        <div class="invalid-feedback">Please enter a valid commission rate (0-100%).</div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Support Level</label>
                                        <select class="form-select" name="support_level" id="support_level" required>
                                            <option value="basic" @selected($plan->support_level === 'basic')>Basic Support</option>
                                            <option value="priority" @selected($plan->support_level === 'priority')>Priority Support</option>
                                            <option value="dedicated" @selected($plan->support_level === 'dedicated')>Dedicated Support</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Free Trial (days)</label>
                                        <input type="number" class="form-control" name="trial_days" id="trial_days" min="0" max="365" value="{{ $plan->trial_days ?? 0 }}">
                                        <small class="text-muted">Leave 0 for no trial.</small>
                                    </div>
                                </div>

                                <div class="row" id="studioFields" style="{{ $isStudio ? '' : 'display: none;' }}">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Maximum Studio Photographers</label>
                                        <input type="number" class="form-control" name="max_studio_photographers" id="max_studio_photographers" placeholder="Leave empty for unlimited" min="1" value="{{ $plan->max_studio_photographers }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Maximum Studios</label>
                                        <input type="number" class="form-control" name="max_studios" id="max_studios" placeholder="Leave empty for unlimited" min="1" value="{{ $plan->max_studios }}">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Staff Limit</label>
                                        <input type="number" class="form-control" name="staff_limit" id="staff_limit" placeholder="Leave empty for unlimited" min="1" value="{{ $plan->staff_limit }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Maximum Bookings Per Period</label>
                                        <input type="number" class="form-control" name="max_booking" id="max_booking" placeholder="Leave empty for unlimited" min="0" value="{{ $plan->max_booking }}">
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status" id="plan_status" required>
                                            <option value="active" @selected($plan->status === 'active')>Active</option>
                                            <option value="inactive" @selected($plan->status === 'inactive')>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-bold">Priority Level (0-5)</label>
                                        <select class="form-select" name="priority_level" id="priority_level">
                                            @foreach ([0 => 'Normal (Default)', 1 => 'Low Priority', 2 => 'Medium Priority', 3 => 'High Priority', 4 => 'Very High Priority', 5 => 'Top Priority'] as $level => $label)
                                                <option value="{{ $level }}" @selected((int) $plan->priority_level === $level)>{{ $level }} - {{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" id="description" rows="3">{{ $plan->description }}</textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Features</label>
                                        <div id="featuresContainer">
                                            @foreach ($features as $feature)
                                                <div class="input-group feature-field mb-2">
                                                    <input type="text" class="form-control feature-input" name="features[]" placeholder="Enter feature" value="{{ $feature }}" required>
                                                    <button class="btn btn-default add-feature-btn" type="button"><i class="ti ti-plus"></i></button>
                                                    <button class="btn btn-default remove-feature-btn" type="button"><i class="ti ti-trash"></i></button>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="invalid-feedback" id="featureError">Please enter at least one feature.</div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-end">
                                        <a href="{{ route('admin.subscription.index') }}" class="btn btn-light me-1">Cancel</a>
                                        <button type="submit" class="btn btn-primary" id="submitBtn">
                                            <span class="spinner-border spinner-border-sm d-none" id="submitSpinner" role="status" aria-hidden="true"></span>
                                            <span id="submitText">Update Plan</span>
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
            $('#user_type').on('change', function() {
                if ($(this).val() === 'studio') {
                    $('#studioFields').slideDown();
                } else {
                    $('#studioFields').slideUp();
                    $('#max_studio_photographers, #max_studios, #staff_limit').val('');
                }
            });

            function updateFeatureButtons() {
                $('.remove-feature-btn').prop('disabled', $('.feature-field').length === 1);
            }

            $(document).on('click', '.add-feature-btn', function() {
                $('#featuresContainer').append(`
                    <div class="input-group feature-field mb-2">
                        <input type="text" class="form-control feature-input" name="features[]" placeholder="Enter feature" required>
                        <button class="btn btn-default add-feature-btn" type="button"><i class="ti ti-plus"></i></button>
                        <button class="btn btn-default remove-feature-btn" type="button"><i class="ti ti-trash"></i></button>
                    </div>`);
                updateFeatureButtons();
            });

            $(document).on('click', '.remove-feature-btn', function() {
                if ($('.feature-field').length > 1) {
                    $(this).closest('.feature-field').remove();
                    updateFeatureButtons();
                }
            });

            updateFeatureButtons();

            $('#subscriptionPlanForm').on('submit', function(e) {
                e.preventDefault();

                if (!this.checkValidity()) {
                    $(this).addClass('was-validated');
                    return;
                }

                const isStudio = $('#user_type').val() === 'studio';
                const features = $('input[name="features[]"]').map(function() {
                    return $(this).val().trim();
                }).get().filter(Boolean);

                if (!features.length) {
                    $('#featureError').show();
                    return;
                }
                $('#featureError').hide();

                $('#submitBtn').prop('disabled', true);
                $('#submitSpinner').removeClass('d-none');
                $('#submitText').text('Updating...');

                $.ajax({
                    url: '{{ route("admin.subscription.update", $plan->id) }}',
                    type: 'PUT',
                    data: {
                        user_type: $('#user_type').val(),
                        plan_type: $('#plan_type').val(),
                        billing_cycle: $('#billing_cycle').val(),
                        plan_code: $('#plan_code').val(),
                        name: $('#name').val(),
                        price: $('#price').val(),
                        commission_rate: $('#commission_rate').val(),
                        trial_days: $('#trial_days').val() || 0,
                        max_booking: $('#max_booking').val() || null,
                        max_studio_photographers: isStudio ? ($('#max_studio_photographers').val() || null) : null,
                        max_studios: isStudio ? ($('#max_studios').val() || null) : null,
                        staff_limit: isStudio ? ($('#staff_limit').val() || null) : null,
                        priority_level: $('#priority_level').val() || 0,
                        description: $('#description').val(),
                        support_level: $('#support_level').val(),
                        status: $('#plan_status').val(),
                        features: features
                    },
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
                        $('#submitText').text('Update Plan');

                        let errorMessage = 'An error occurred. Please try again.';
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.errors) {
                                errorMessage = '<ul>' + Object.values(xhr.responseJSON.errors)
                                    .map(value => '<li>' + value[0] + '</li>').join('') + '</ul>';
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
        });
    </script>
@endsection
