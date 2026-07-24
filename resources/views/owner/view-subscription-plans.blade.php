@extends('layouts.owner.app')
@section('title', 'Subscription Plans')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4 mt-3">
                <div class="col-12">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <h4 class="mt-1">Subscription Plans</h4>
                        <div>
                            <button class="btn btn-soft-primary me-2" id="viewHistoryBtn" data-bs-toggle="modal" data-bs-target="#subscriptionHistoryModal">
                                <i class="ti ti-history me-2"></i>View History
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Subscription Info -->
            @if($currentSubscription)
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="ti ti-info-circle fs-4 me-3"></i>
                        <div>
                            <strong>Current Plan:</strong> {{ $currentSubscription->plan->name ?? 'Unknown' }}
                            (Valid until {{ $currentSubscription->end_date->format('M d, Y') }})
                            @if($currentSubscription->end_date->diffInDays(now()) <= 7)
                                <span class="badge bg-warning ms-2">Expiring Soon</span>
                            @endif
                            @if($currentSubscription->isOnTrial())
                                <span class="badge bg-info ms-2">Trial ends in {{ now()->diffInDays($currentSubscription->trial_ends_at) }} day(s)</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Plans Container -->
            <div class="row mb-4">
                @forelse($plans as $plan)
                <div class="col">
                    <div class="card h-100 my-4 my-lg-0">
                        <div class="card-body p-lg-4 pb-0 text-center">
                            @if($plan->trial_days > 0)
                                <span class="badge badge-soft-success mb-2">{{ $plan->trial_days }}-Day Free Trial</span>
                            @endif
                            <h3 class="fw-bold mb-1">{{ $plan->name }}</h3>
                            <p class="text-muted mb-0">{{ $plan->description }}</p>

                            <div class="my-4">
                                <h1 class="display-6 fw-bold mb-0">₱{{ number_format($plan->price, 0) }}</h1>
                                <small class="d-block text-muted fs-base">Billed {{ $plan->billing_cycle }}</small>
                                @if($plan->max_booking)
                                <small class="d-block text-muted">{{ $plan->max_booking }} bookings included</small>
                                @endif
                            </div>

                            <!-- Preview Features (Only show first 3) -->
                            <ul class="list-unstyled text-start fs-sm mb-0">
                                @foreach(array_slice($plan->features ?? [], 0, 3) as $feature)
                                <li class="mb-2"><i class="ti ti-check text-success me-2"></i> {{ $feature }}</li>
                                @endforeach
                                @if(count($plan->features ?? []) > 3)
                                <li class="mb-2 text-primary">
                                    <i class="ti ti-dots me-2"></i> +{{ count($plan->features) - 3 }} more features
                                </li>
                                @endif
                            </ul>
                        </div>
                        <div class="card-footer bg-transparent">
                            @if($currentSubscription && $currentSubscription->plan_id == $plan->id)
                            <button class="btn btn-outline-success w-100 py-2 fw-semibold" disabled>
                                <i class="ti ti-check me-2"></i>Current Plan
                            </button>
                            @elseif($currentSubscription)
                            <button class="btn btn-outline-secondary w-100 py-2 fw-semibold view-details-btn" 
                                    data-plan-id="{{ $plan->id }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#viewPlanModal">
                                <i class="ti ti-eye me-2"></i>View Details
                            </button>
                            @else
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary w-100 py-2 fw-semibold subscribe-btn" 
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        data-plan-price="{{ number_format($plan->price, 2) }}"
                                        data-billing-cycle="{{ $plan->billing_cycle }}">
                                    <i class="ti ti-credit-card me-2"></i>Subscribe
                                </button>
                                <button class="btn btn-soft-primary w-100 py-2 fw-semibold view-details-btn" 
                                        data-plan-id="{{ $plan->id }}"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewPlanModal">
                                    <i class="ti ti-eye me-2"></i>View Details
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="ti ti-credit-card-off fs-1 text-muted mb-3"></i>
                        <h5>No Subscription Plans Available</h5>
                        <p class="text-muted">Please check back later for available plans.</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- View Plan Details Modal --}}
    <div class="modal fade" id="viewPlanModal" tabindex="-1" aria-labelledby="viewPlanModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="viewPlanModalLabel">
                        Plan Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">

                    <!-- Loading Spinner -->
                    <div class="text-center py-5" id="planLoadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading plan details...</p>
                    </div>

                    <!-- Plan Details -->
                    <div id="planDetails" style="display: none;">

                        <!-- Plan Header -->
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <div class="bg-light-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i class="ti ti-crown fs-32 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="view_name_header"></h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="p-1 badge badge-soft-primary" id="view_plan_type_badge"></span>
                                            <span id="view_status_header"></span>
                                        </div>
                                        <p class="text-muted mb-0">
                                            <i class="ti ti-tag me-1"></i>
                                            Plan Code: <span class="fw-medium font-monospace" id="view_plan_code_header"></span>
                                            &nbsp;|&nbsp;
                                            <i class="ti ti-users me-1"></i>
                                            <span id="view_user_type_header"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">

                                <!-- PLAN IDENTIFICATION INFORMATION -->
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PLAN IDENTIFICATION INFORMATION</h5>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-badge fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Name</label>
                                                <p class="mb-0 fw-medium" id="view_name"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-key fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Code</label>
                                                <p class="mb-0 fw-medium font-monospace" id="view_plan_code"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-user-circle fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">User Type</label>
                                                <p class="mb-0 fw-medium" id="view_user_type"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-layers-subtract fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Type</label>
                                                <p class="mb-0 fw-medium" id="view_plan_type"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-file-text fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Description</label>
                                                <p class="mb-0 fw-medium" id="view_description"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PRICING & BILLING INFORMATION -->
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PRICING & BILLING INFORMATION</h5>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Price</label>
                                                <p class="mb-0 fw-medium" id="view_price"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-refresh fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Billing Cycle</label>
                                                <p class="mb-0 fw-medium" id="view_billing_cycle"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-percentage fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Commission Rate</label>
                                                <p class="mb-0 fw-medium" id="view_commission_rate"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-arrow-up-circle fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Priority Level</label>
                                                <div id="view_priority_level"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PLAN LIMITS & CAPABILITIES -->
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PLAN LIMITS & CAPABILITIES</h5>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-calendar-check fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Bookings</label>
                                                <p class="mb-0 fw-medium" id="view_max_booking"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-camera fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Photographers</label>
                                                <p class="mb-0 fw-medium" id="view_max_photographers"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-building fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Studios</label>
                                                <p class="mb-0 fw-medium" id="view_max_studios"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-users fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Staff Limit</label>
                                                <p class="mb-0 fw-medium" id="view_staff_limit"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-headphones fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Support Level</label>
                                                <p class="mb-0 fw-medium" id="view_support_level"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-shield-check fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Status</label>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span id="view_status"></span>
                                                    <span class="text-muted small">Created on <span id="view_created_at"></span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PLAN FEATURES -->
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PLAN FEATURES</h5>
                                    <div class="col-12">
                                        <div class="row g-2">
                                            <div class="col-12 col-md-6">
                                                <div class="list-group" id="view_features_col_1"></div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="list-group" id="view_features_col_2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Subscription History Modal --}}
    <div class="modal fade" id="subscriptionHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subscription History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="subscriptionHistoryTable">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Plan</th>
                                    <th>Amount</th>
                                    <th>Period</th>
                                    <th>Payment Status</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        <div class="py-3">
                                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                            Loading history...
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirmation Modal --}}
    <div class="modal fade" id="subscribeConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>You are about to subscribe to:</p>
                    <h5 class="fw-bold" id="confirmPlanName"></h5>
                    <p class="mb-0">Amount: <span class="fw-bold text-primary" id="confirmPlanPrice"></span></p>
                    <p><small class="text-muted">You will be redirected to Stripe to complete the payment.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmSubscribeBtn">Proceed to Payment</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let selectedPlanId = null;

            // ===== VIEW DETAILS BUTTON CLICK =====
            $('.view-details-btn').on('click', function() {
                let planId = $(this).data('plan-id');
                
                console.log('Loading plan details for ID:', planId); // For debugging
                
                // Show loading, hide details
                $('#planLoadingSpinner').show();
                $('#planDetails').hide();
                
                // Clear previous data
                $('#view_name_header').empty();
                $('#view_plan_type_badge').empty().removeClass();
                $('#view_status_header').empty();
                $('#view_plan_code_header').empty();
                $('#view_user_type_header').empty();
                
                // Build URL properly
                let url = '{{ route("owner.subscription.show", ":id") }}';
                url = url.replace(':id', planId);
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('API Response:', response); // For debugging
                        
                        if (response.success) {
                            let data = response.data;
                            
                            // Format data
                            let planTypeFormatted = data.plan_type;
                            let billingCycleFormatted = data.billing_cycle;
                            
                            // Set header data
                            $('#view_name_header').text(data.name);
                            $('#view_plan_type_badge').text(planTypeFormatted).addClass('p-1 badge badge-soft-primary');
                            $('#view_status_header').html('<span class="p-1 badge badge-soft-success">Active</span>');
                            $('#view_plan_code_header').text(data.plan_code || 'N/A');
                            $('#view_user_type_header').text('Studio');
                            
                            // Set identification data
                            $('#view_name').text(data.name);
                            $('#view_plan_code').text(data.plan_code || 'N/A');
                            $('#view_user_type').text('Studio');
                            $('#view_plan_type').text(planTypeFormatted);
                            $('#view_description').text(data.description || 'No description available.');
                            
                            // Set pricing data
                            $('#view_price').text('₱' + data.price + ' /' + billingCycleFormatted);
                            $('#view_billing_cycle').text(billingCycleFormatted);
                            $('#view_commission_rate').text(data.commission_rate);
                            
                            // Set priority level (default to 0 if not provided)
                            let priorityLabels = {
                                0: '<span class="p-1 badge badge-soft-secondary">Normal</span>',
                                1: '<span class="p-1 badge badge-soft-info">Low Priority</span>',
                                2: '<span class="p-1 badge badge-soft-primary">Medium Priority</span>',
                                3: '<span class="p-1 badge badge-soft-warning">High Priority</span>',
                                4: '<span class="p-1 badge badge-soft-danger">Very High Priority</span>',
                                5: '<span class="p-1 badge badge-soft-success">Top Priority</span>'
                            };
                            $('#view_priority_level').html(priorityLabels[data.priority_level] || priorityLabels[0]);
                            
                            // Set limits
                            $('#view_max_booking').text(data.max_booking || 'Unlimited');
                            $('#view_max_photographers').text(data.max_studio_photographers || 'Unlimited');
                            $('#view_max_studios').text(data.max_studios || 'Unlimited');
                            $('#view_staff_limit').text(data.staff_limit || 'Unlimited');
                            $('#view_support_level').text(data.support_level);
                            
                            // Set status
                            $('#view_status').html('<span class="p-1 badge badge-soft-success">Active</span>');
                            $('#view_created_at').text('N/A');
                            
                            // Set features (split into two columns)
                            if (data.features && Array.isArray(data.features) && data.features.length > 0) {
                                let midpoint = Math.ceil(data.features.length / 2);
                                let col1Features = data.features.slice(0, midpoint);
                                let col2Features = data.features.slice(midpoint);
                                
                                $('#view_features_col_1').empty();
                                $('#view_features_col_2').empty();
                                
                                col1Features.forEach(function(feature) {
                                    $('#view_features_col_1').append('<div class="list-group-item"><i class="ti ti-check text-success me-2"></i>' + feature + '</div>');
                                });
                                
                                col2Features.forEach(function(feature) {
                                    $('#view_features_col_2').append('<div class="list-group-item"><i class="ti ti-check text-success me-2"></i>' + feature + '</div>');
                                });
                            } else {
                                $('#view_features_col_1').html('<div class="list-group-item text-muted">No features listed</div>');
                                $('#view_features_col_2').empty();
                            }
                            
                            // Hide loading, show details
                            $('#planLoadingSpinner').hide();
                            $('#planDetails').show();
                        } else {
                            $('#planLoadingSpinner').hide();
                            $('#planDetails').html('<div class="alert alert-danger">Failed to load plan details.</div>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        console.error('Response:', xhr.responseText);
                        
                        $('#planLoadingSpinner').hide();
                        $('#planDetails').html('<div class="alert alert-danger">Failed to load plan details. Please try again.</div>');
                    }
                });
            });

            // ===== SUBSCRIBE BUTTON CLICK =====
            $('.subscribe-btn').on('click', function() {
                selectedPlanId = $(this).data('plan-id');
                let planName = $(this).data('plan-name');
                let planPrice = $(this).data('plan-price');
                let billingCycle = $(this).data('billing-cycle');

                $('#confirmPlanName').text(planName + ' (' + billingCycle + ')');
                $('#confirmPlanPrice').text('₱' + planPrice + '/' + billingCycle);
                $('#subscribeConfirmModal').modal('show');
            });

            // ===== CONFIRM SUBSCRIPTION =====
            $('#confirmSubscribeBtn').on('click', function() {
                if (!selectedPlanId) return;

                $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

                $.ajax({
                    url: '{{ route("owner.subscription.subscribe") }}',
                    type: 'POST',
                    data: {
                        plan_id: selectedPlanId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#subscribeConfirmModal').modal('hide');

                            if (response.trial) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Free Trial Started!',
                                    text: response.message,
                                    confirmButtonText: 'Go to Subscription'
                                }).then(() => {
                                    window.location.href = '{{ route("owner.subscription.index") }}';
                                });
                                return;
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Redirecting to Payment',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                window.location.href = response.checkout_url;
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#confirmSubscribeBtn').prop('disabled', false).text('Proceed to Payment');
                        
                        let errorMessage = 'An error occurred. Please try again.';
                        let redirectUrl = null;
                        
                        if (xhr.responseJSON) {
                            if (xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            if (xhr.responseJSON.redirect_to_studio_creation) {
                                redirectUrl = xhr.responseJSON.studio_creation_url;
                            }
                        }
                        
                        if (redirectUrl) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Studio Required',
                                text: errorMessage,
                                confirmButtonColor: '#3475db',
                                confirmButtonText: 'Create Studio Now',
                                showCancelButton: true,
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href = redirectUrl;
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    }
                });
            });

            // ===== VIEW HISTORY MODAL =====
            $('#subscriptionHistoryModal').on('show.bs.modal', function() {
                loadSubscriptionHistory();
            });

            function loadSubscriptionHistory() {
                $.ajax({
                    url: '{{ route("owner.subscription.history") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            let tbody = $('#subscriptionHistoryTable tbody');
                            tbody.empty();

                            if (response.data.length === 0) {
                                tbody.html('<tr><td colspan="6" class="text-center py-4">No subscription history found.</td></tr>');
                            } else {
                                response.data.forEach(function(item) {
                                    let row = `
                                        <tr>
                                            <td><small>${item.subscription_reference}</small></td>
                                            <td>${item.plan_name}</td>
                                            <td>₱${item.amount}</td>
                                            <td><small>${item.start_date} - ${item.end_date}</small></td>
                                            <td>${item.payment_status_badge}</td>
                                            <td>${item.status_badge}</td>
                                        </tr>
                                    `;
                                    tbody.append(row);
                                });
                            }
                        }
                    },
                    error: function() {
                        $('#subscriptionHistoryTable tbody').html('<tr><td colspan="6" class="text-center text-danger py-4">Failed to load history.</td></tr>');
                    }
                });
            }
        });
    </script>
@endsection