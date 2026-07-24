@extends('layouts.admin.app')
@section('title', 'Subscription Plans')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- Success Message --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    {{-- TABLE --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Subscription Plans</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input type="search" id="tableSearch" class="form-control" placeholder="Search...">
                                    <i class="ti ti-search app-search-icon text-muted"></i>
                                </div>
                                <div>
                                    <select id="userTypeFilter" class="form-select">
                                        <option value="">All User Types</option>
                                        <option value="studio">Studio</option>
                                        <option value="freelancer">Freelancer</option>
                                    </select>
                                </div>
                                <div>
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="subscriptionPlansTable">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>ID</th>
                                        <th>User Type</th>
                                        <th>Plan Type</th>
                                        <th>Billing</th>
                                        <th>Plan Name</th>
                                        <th>Price</th>
                                        <th>Commission</th>
                                        <th>Priority</th>
                                        <th>Max Booking</th>
                                        <th>Max Studios</th>
                                        <th>Max Photogs</th>
                                        <th>Staff Limit</th>
                                        <th>Support</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width: 120px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    @forelse($plans as $plan)
                                        <tr>
                                            <td>{{ $plan->id }}</td>
                                            <td>{{ ucfirst($plan->user_type) }}</td>
                                            <td>{{ $plan->formatted_plan_type }}</td>
                                            <td>{{ $plan->formatted_billing_cycle }}</td>
                                            <td>{{ $plan->name }}</td>
                                            <td>{{ $plan->formatted_price }}</td>
                                            <td>
                                                {{ $plan->commission_rate }}%
                                                @if($plan->trial_days > 0)
                                                    <span class="badge badge-soft-success d-block mt-1">{{ $plan->trial_days }}-day trial</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $plan->priority_badge_class }}">
                                                    Level {{ $plan->priority_level }}
                                                </span>
                                            </td>
                                            <td>{{ $plan->max_booking_display }}</td>
                                            <td>
                                                @if($plan->user_type === 'studio')
                                                    {{ $plan->max_studios_display }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->user_type === 'studio')
                                                    {{ $plan->max_studio_photographers_display }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($plan->user_type === 'studio')
                                                    {{ $plan->staff_limit_display }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $plan->formatted_support_level }}</td>
                                            <td>
                                                @if($plan->status === 'active')
                                                    <span class="badge badge-soft-success">Active</span>
                                                @else
                                                    <span class="badge badge-soft-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm btn-view" data-id="{{ $plan->id }}" data-bs-toggle="modal" data-bs-target="#viewPlanModal">
                                                        <i class="ti ti-eye"></i>
                                                    </button>
                                                    <a href="{{ route('admin.subscription.edit', $plan->id) }}" class="btn btn-sm btn-edit">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-delete" data-id="{{ $plan->id }}">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="15" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="ti ti-file-text fs-2"></i>
                                                    <p class="mb-0">No subscription plans found.</p>
                                                    <a href="{{ route('admin.subscription.create') }}" class="btn btn-primary btn-sm mt-2">Create your first plan</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="dataTables_info">
                                    Showing {{ $plans->firstItem() ?? 0 }} to {{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }} entries
                                </div>
                                <div class="dataTables_paginate">
                                    {{ $plans->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Plan Modal --}}
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

                    {{-- Loading Spinner --}}
                    <div class="text-center py-5" id="planLoadingSpinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading plan details...</p>
                    </div>

                    {{-- Plan Details --}}
                    <div id="planDetails" style="display: none;">

                        {{-- Plan Header --}}
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <div class="bg-light-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                            <i data-lucide="crown" class="fs-32 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3" id="view_name_header">—</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                            <span class="badge badge-soft-primary p-1" id="view_plan_type_badge">—</span>
                                            <span id="view_status_header"></span>
                                        </div>
                                        <p class="text-muted mb-0">
                                            <i class="ti ti-tag me-1"></i>
                                            Plan Code: <span class="fw-medium font-monospace" id="view_plan_code_header">—</span>
                                            &nbsp;|&nbsp;
                                            <i class="ti ti-users me-1"></i>
                                            <span id="view_user_type_header">—</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">

                                {{-- PLAN IDENTIFICATION INFORMATION --}}
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PLAN IDENTIFICATION INFORMATION</h5>

                                    {{-- Plan Name --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="badge" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Name</label>
                                                <p class="mb-0 fw-medium" id="view_name">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Plan Code --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="key-round" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Code</label>
                                                <p class="mb-0 fw-medium font-monospace" id="view_plan_code">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- User Type --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="user-circle" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">User Type</label>
                                                <p class="mb-0 fw-medium" id="view_user_type">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Plan Type --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="layers" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Plan Type</label>
                                                <p class="mb-0 fw-medium" id="view_plan_type">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Plan Description --}}
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Description</label>
                                                <p class="mb-0 fw-medium" id="view_description">—</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- PRICING & BILLING INFORMATION --}}
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PRICING & BILLING INFORMATION</h5>

                                    {{-- Price --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="philippine-peso" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Price</label>
                                                <p class="mb-0 fw-medium" id="view_price">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Billing Cycle --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="refresh-cw" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Billing Cycle</label>
                                                <p class="mb-0 fw-medium" id="view_billing_cycle">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Commission Rate --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="percent" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Commission Rate</label>
                                                <p class="mb-0 fw-medium" id="view_commission_rate">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Priority Level --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="arrow-up-circle" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Priority Level</label>
                                                <div id="view_priority_level">—</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- PLAN LIMITS & CAPABILITIES --}}
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">PLAN LIMITS & CAPABILITIES</h5>

                                    {{-- Max Bookings --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="calendar-check" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Bookings</label>
                                                <p class="mb-0 fw-medium" id="view_max_booking">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Max Photographers --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="camera" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Photographers</label>
                                                <p class="mb-0 fw-medium" id="view_max_photographers">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Max Studios --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="building-2" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Max Studios</label>
                                                <p class="mb-0 fw-medium" id="view_max_studios">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Staff Limit --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="users" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Staff Limit</label>
                                                <p class="mb-0 fw-medium" id="view_staff_limit">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Support Level --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="headphones" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Support Level</label>
                                                <p class="mb-0 fw-medium" id="view_support_level">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Status --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="shield-check" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Status</label>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span id="view_status"></span>
                                                    <span class="text-muted small">Created on <span id="view_created_at">—</span></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- PLAN FEATURES --}}
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
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // ===== SEARCH FUNCTIONALITY =====
            $('#tableSearch').on('keyup', function() {
                let searchText = $(this).val().toLowerCase();
                
                $('#tableBody tr').each(function() {
                    let rowText = $(this).text().toLowerCase();
                    if (rowText.indexOf(searchText) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            });

            // ===== FILTER FUNCTIONALITY =====
            function applyFilters() {
                let userType = $('#userTypeFilter').val().toLowerCase();
                let status = $('#statusFilter').val().toLowerCase();
                
                $('#tableBody tr').each(function() {
                    let showRow = true;
                    let row = $(this);
                    
                    // Skip empty row
                    if (row.find('td').length === 1 && row.find('td').attr('colspan')) {
                        return;
                    }
                    
                    // User Type filter
                    if (userType) {
                        let rowUserType = row.find('td:eq(1)').text().toLowerCase();
                        if (rowUserType !== userType) {
                            showRow = false;
                        }
                    }
                    
                    // Status filter
                    if (status && showRow) {
                        let rowStatus = row.find('td:eq(10) .badge').text().toLowerCase();
                        if (rowStatus !== status) {
                            showRow = false;
                        }
                    }
                    
                    if (showRow) {
                        row.show();
                    } else {
                        row.hide();
                    }
                });
                
                // Update showing count
                updateShowingCount();
            }

            $('#userTypeFilter, #statusFilter').on('change', applyFilters);

            // Update showing entries count
            function updateShowingCount() {
                let visibleRows = $('#tableBody tr:visible').length;
                let totalRows = $('#tableBody tr').length;
                let showingText = 'Showing ';
                
                if (visibleRows === 0) {
                    showingText += '0 to 0 of ' + totalRows + ' entries';
                } else {
                    let firstVisible = 1;
                    let lastVisible = visibleRows;
                    showingText += firstVisible + ' to ' + lastVisible + ' of ' + totalRows + ' entries';
                }
                
                $('.dataTables_info').text(showingText);
            }

            // ===== VIEW PLAN MODAL =====
            $(document).on('click', '.btn-view', function() {
                let planId = $(this).data('id');

                // Show loading spinner, hide details
                $('#planLoadingSpinner').show();
                $('#planDetails').hide();

                // Fetch plan details
                $.ajax({
                    url: '/admin/subscription/' + planId,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            let plan = response.data;

                            let userTypeLabel = plan.user_type === 'studio' ? 'Studio Owner' : 'Freelancer';
                            let planTypeLabel = plan.plan_type.charAt(0).toUpperCase() + plan.plan_type.slice(1);
                            let billingLabel  = plan.billing_cycle.charAt(0).toUpperCase() + plan.billing_cycle.slice(1);
                            let statusBadge   = plan.status === 'active'
                                ? '<span class="badge badge-soft-success px-2 fw-medium">Active</span>'
                                : '<span class="badge badge-soft-secondary px-2 fw-medium">Inactive</span>';
                            let priorityBadge = '<span class="badge ' + plan.priority_badge_class + '">Level ' + plan.priority_level + ' – ' + plan.priority_level_label + '</span>';

                            // --- Header area ---
                            $('#view_name_header').text(plan.name);
                            $('#view_plan_type_badge').text(planTypeLabel);
                            $('#view_status_header').html(statusBadge);
                            $('#view_plan_code_header').text(plan.plan_code);
                            $('#view_user_type_header').text(userTypeLabel);

                            // --- Plan Identification ---
                            $('#view_name').text(plan.name);
                            $('#view_plan_code').text(plan.plan_code);
                            $('#view_user_type').text(userTypeLabel);
                            $('#view_plan_type').text(planTypeLabel);
                            $('#view_description').text(plan.description || 'No description provided.');

                            // --- Pricing & Billing ---
                            $('#view_price').text('₱' + parseFloat(plan.price).toFixed(2));
                            $('#view_billing_cycle').text(billingLabel);
                            $('#view_commission_rate').text(plan.commission_rate + '%');
                            $('#view_priority_level').html(priorityBadge);

                            // --- Limits & Capabilities ---
                            $('#view_max_booking').text(plan.max_booking ? plan.max_booking + ' per ' + plan.billing_cycle : 'Unlimited');
                            $('#view_max_photographers').text(plan.max_studio_photographers || 'N/A');
                            $('#view_max_studios').text(plan.max_studios || 'Unlimited');
                            $('#view_staff_limit').text(plan.staff_limit || 'Unlimited');
                            $('#view_support_level').text(plan.support_level.charAt(0).toUpperCase() + plan.support_level.slice(1) + ' Support');
                            $('#view_status').html(statusBadge);
                            $('#view_created_at').text(new Date(plan.created_at).toLocaleDateString());

                            // --- Features (split into two columns) ---
                            $('#view_features_col_1, #view_features_col_2').empty();
                            if (plan.features && plan.features.length > 0) {
                                let mid = Math.ceil(plan.features.length / 2);
                                plan.features.forEach(function(feature, index) {
                                    let item = '<li class="list-group-item"><div class="d-flex align-items-start">'
                                        + '<i class="ti ti-check text-success me-2 mt-1"></i>'
                                        + '<div><h5 class="mb-1 fw-semibold">' + feature + '</h5></div>'
                                        + '</div></li>';
                                    if (index < mid) {
                                        $('#view_features_col_1').append(item);
                                    } else {
                                        $('#view_features_col_2').append(item);
                                    }
                                });
                            } else {
                                $('#view_features_col_1').append('<li class="list-group-item text-muted">No features listed.</li>');
                            }

                            // Re-init lucide icons for dynamically loaded content
                            if (typeof lucide !== 'undefined') { lucide.createIcons(); }

                            // Hide loading, show details
                            $('#planLoadingSpinner').hide();
                            $('#planDetails').show();
                        }
                    },
                    error: function(xhr) {
                        console.log('Error:', xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load plan details.',
                            confirmButtonColor: '#3475db'
                        });
                        $('#viewPlanModal').modal('hide');
                    }
                });
            });

            // ===== DELETE PLAN =====
            $(document).on('click', '.btn-delete', function() {
                let planId = $(this).data('id');
                let row = $(this).closest('tr');
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '/admin/subscription/' + planId,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true
                                    }).then(() => {
                                        row.fadeOut(300, function() {
                                            $(this).remove();
                                            
                                            // Check if table is empty
                                            if ($('#tableBody tr').length === 0) {
                                                location.reload(); // Reload to show empty state
                                            }
                                            
                                            updateShowingCount();
                                        });
                                    });
                                }
                            },
                            error: function(xhr) {
                                let message = 'Failed to delete plan.';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message,
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        });
                    }
                });
            });

            // Initialize showing count
            updateShowingCount();
        });
    </script>
@endsection