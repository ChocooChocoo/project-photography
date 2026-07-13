@extends('layouts.client.app')
@section('title', 'Set Budget')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="card-title">
                                <h4 class="card-title">Set Budget</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="col">
                                <ul class="nav nav-tabs mb-3" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#view_budget" data-bs-toggle="tab" aria-expanded="false" class="nav-link active" aria-selected="true" role="tab">
                                            View Budgets
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#set_budget" data-bs-toggle="tab" aria-expanded="false" class="nav-link" aria-selected="false" role="tab" tabindex="-1">
                                            Set-up Budget
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    {{-- VIEW BUDGET TAB --}}
                                    <div class="tab-pane active show" id="view_budget" role="tabpanel">
                                        {{-- FILTERS --}}
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="fw-semibold">
                                                        <i class="ti ti-filter me-1"></i>Filter By:
                                                    </span>
                                                    <div class="app-filter">
                                                        <select id="filterStatus" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">All Status</option>
                                                            <option value="active">Active</option>
                                                            <option value="inactive">Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select id="filterCategory" class="form-select form-control" style="min-width: 200px;">
                                                            <option value="">All Categories</option>
                                                            @foreach($categories as $category)
                                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="app-filter">
                                                        <select id="filterType" class="form-select form-control" style="min-width: 150px;">
                                                            <option value="">All Types</option>
                                                            <option value="service">Service</option>
                                                            <option value="package">Package</option>
                                                            <option value="equipment">Equipment</option>
                                                            <option value="other">Other</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- BUDGETS TABLE --}}
                                        <div class="table-responsive">
                                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0" id="budgetsTable">
                                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                    <tr class="text-uppercase fs-xxs">
                                                        <th>Budget Name</th>
                                                        <th>Category</th>
                                                        <th>Budget Range</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Created</th>
                                                        <th class="text-center" style="width: 12%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="budgetsTableBody">
                                                    @forelse($budgets as $budget)
                                                        <tr>
                                                            <td>
                                                                <div class="fw-semibold">{{ $budget->budget_name ?? 'Unnamed Budget' }}</div>
                                                                <small class="text-muted">{{ $budget->description ? Str::limit($budget->description, 50) : 'No description' }}</small>
                                                            </td>
                                                            <td>{{ $budget->category->category_name ?? '—' }}</td>
                                                            <td>
                                                                <div class="budget-amount">{{ $budget->budget_range }}</div>
                                                            </td>
                                                            <td>{{ $budget->budget_type ? ucfirst($budget->budget_type) : '—' }}</td>
                                                            <td>
                                                                @if($budget->status === 'active')
                                                                    <span class="badge badge-soft-success">Active</span>
                                                                @else
                                                                    <span class="badge badge-soft-secondary">Inactive</span>
                                                                @endif
                                                            </td>
                                                            <td><small>{{ $budget->created_at->format('M d, Y') }}</small></td>
                                                            <td>
                                                                <div class="d-flex justify-content-center gap-1">
                                                                    <button class="btn btn-sm view-budget" data-id="{{ $budget->id }}" title="View">
                                                                        <i class="ti ti-eye fs-lg"></i>
                                                                    </button>
                                                                    <button class="btn btn-sm edit-budget" data-id="{{ $budget->id }}" title="Edit">
                                                                        <i class="ti ti-edit fs-lg"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="7" class="text-center py-4">
                                                                <div class="text-muted">
                                                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                                                    <span>No budgets found. Create your first budget!</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    {{-- SET-UP BUDGET TAB --}}
                                    <div class="tab-pane" id="set_budget" role="tabpanel">
                                        <form id="budgetForm" class="needs-validation" novalidate>
                                            <input type="hidden" id="budgetId" name="budgetId">
                                            
                                            <div class="row mb-3">
                                                <div class="col-12">
                                                    <h4 class="card-title text-primary mb-3">
                                                        Budget Information
                                                    </h4>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">
                                                        Budget Name <span class="text-muted">(Optional)</span>
                                                    </label>
                                                    <input type="text" id="budget_name" name="budget_name" class="form-control" 
                                                           placeholder="e.g., Wedding Budget 2024">
                                                    <small class="text-muted">Give your budget a name for easy reference</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">
                                                        Category <span class="text-muted">(Optional)</span>
                                                    </label>
                                                    <select id="category_id" name="category_id" class="form-select">
                                                        <option value="">Select Category</option>
                                                        @foreach($categories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Link budget to a service category</small>
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Minimum Budget (₱)</label>
                                                    <input type="number" id="minimum_budget" name="minimum_budget" class="form-control" 
                                                           placeholder="0.00" min="0" step="0.01">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Maximum Budget (₱)</label>
                                                    <input type="number" id="maximum_budget" name="maximum_budget" class="form-control" 
                                                           placeholder="0.00" min="0" step="0.01">
                                                </div>

                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Preferred Budget (₱)</label>
                                                    <input type="number" id="preferred_budget" name="preferred_budget" class="form-control" 
                                                           placeholder="0.00" min="0" step="0.01">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">
                                                        Budget Type <span class="text-muted">(Optional)</span>
                                                    </label>
                                                    <select id="budget_type" name="budget_type" class="form-select">
                                                        <option value="">Select Type</option>
                                                        <option value="service">Service</option>
                                                        <option value="package">Package</option>
                                                        <option value="equipment">Equipment</option>
                                                        <option value="other">Other</option>
                                                    </select>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select id="status" name="status" class="form-select" required>
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="col-12 mb-3">
                                                    <label class="form-label">
                                                        Description <span class="text-muted">(Optional)</span>
                                                    </label>
                                                    <textarea id="description" name="description" class="form-control" 
                                                              rows="3" placeholder="Add any notes or details about this budget..."></textarea>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary" id="saveBudgetBtn">
                                                        <i class="ti ti-device-floppy me-1"></i> Save Budget
                                                    </button>
                                                    <button type="button" class="btn btn-light" id="cancelEdit" style="display: none;">
                                                        <i class="ti ti-x me-1"></i> Cancel Edit
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
            </div>
        </div>
    </div>

    {{-- View Budget Modal --}}
    <div class="modal fade" id="viewBudgetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="viewBudgetModalTitle">
                        Budget Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="viewBudgetModalBody">
                    <div class="row align-items-center mb-4">
                        <div class="col-12 col-lg-8">
                            <div class="d-flex align-items-center flex-column flex-md-row">
                                <div class="flex-grow-1 ms-md-2 text-center text-md-start">
                                    <h2 class="mb-1 h3 h3-md" id="view_budget_name">Sample Budget</h2>
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                        <span class="badge badge-soft-success p-1" id="view_budget_status">Active</span>
                                    </div>
                                
                                    <p class="text-muted mb-0" id="view_budget_description">
                                        No description provided
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">Budget Information</h5>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-category fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Category</label>
                                            <p class="mb-0 fw-medium" id="view_budget_category">—</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-coin fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Budget Type</label>
                                            <p class="mb-0 fw-medium" id="view_budget_type">—</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Minimum Budget</label>
                                            <p class="mb-0 fw-medium" id="view_minimum_budget">₱0.00</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Maximum Budget</label>
                                            <p class="mb-0 fw-medium" id="view_maximum_budget">₱0.00</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-star fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Preferred Budget</label>
                                            <p class="mb-0 fw-medium" id="view_preferred_budget">₱0.00</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">Additional Information</h5>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-calendar fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Created At</label>
                                            <p class="mb-0 fw-medium" id="view_created_at">—</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-calendar-check fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Last Updated</label>
                                            <p class="mb-0 fw-medium" id="view_updated_at">—</p>
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

    {{-- Edit Budget Modal --}}
    <div class="modal fade" id="editBudgetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="editBudgetModalTitle">
                        Edit Budget
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Content will be dynamically loaded via AJAX -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
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
            // ==================== INITIALIZATION ====================
            let selectedBudgetId = null;

            // ==================== LOAD BUDGETS WITH FILTERS ====================
            function loadBudgets() {
                let filters = {
                    status: $('#filterStatus').val(),
                    category_id: $('#filterCategory').val(),
                    budget_type: $('#filterType').val()
                };

                $.ajax({
                    url: '{{ route("client.budget.data") }}',
                    type: 'GET',
                    data: filters,
                    beforeSend: function() {
                        $('#budgetsTableBody').html(`
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        `);
                    },
                    success: function(response) {
                        if (response.success) {
                            renderBudgetsTable(response.budgets);
                        }
                    },
                    error: function(xhr) {
                        console.error('Failed to load budgets:', xhr);
                        $('#budgetsTableBody').html(`
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-danger">
                                        <i class="ti ti-alert-circle fs-1 d-block mb-2"></i>
                                        <span>Failed to load budgets. Please try again.</span>
                                    </div>
                                </td>
                            </tr>
                        `);
                    }
                });
            }

            // ==================== RENDER BUDGETS TABLE ====================
            function renderBudgetsTable(budgets) {
                if (!budgets || budgets.length === 0) {
                    $('#budgetsTableBody').html(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ti ti-inbox fs-1 d-block mb-2"></i>
                                    <span>No budgets found. Create your first budget!</span>
                                </div>
                            </td>
                        </tr>
                    `);
                    return;
                }

                let html = '';
                budgets.forEach(function(budget) {
                    let statusBadge = budget.status === 'active' 
                        ? '<span class="badge badge-soft-success">Active</span>' 
                        : '<span class="badge badge-soft-secondary">Inactive</span>';
                    
                    let categoryName = budget.category ? budget.category.category_name : '—';
                    let budgetType = budget.budget_type ? budget.budget_type.charAt(0).toUpperCase() + budget.budget_type.slice(1) : '—';
                    let createdDate = new Date(budget.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    // Calculate budget range if not available
                    let budgetRange = budget.budget_range || (() => {
                        if (budget.minimum_budget && budget.maximum_budget) {
                            return '₱' + parseFloat(budget.minimum_budget).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + 
                                ' - ₱' + parseFloat(budget.maximum_budget).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        } else if (budget.minimum_budget) {
                            return 'From ₱' + parseFloat(budget.minimum_budget).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        } else if (budget.maximum_budget) {
                            return 'Up to ₱' + parseFloat(budget.maximum_budget).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        } else if (budget.preferred_budget) {
                            return '₱' + parseFloat(budget.preferred_budget).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' (Preferred)';
                        }
                        return 'Not specified';
                    })();

                    html += `
                        <tr>
                            <td>
                                <div class="fw-semibold">${budget.budget_name || 'Unnamed Budget'}</div>
                                <small class="text-muted">${budget.description ? budget.description.substring(0, 50) + (budget.description.length > 50 ? '...' : '') : 'No description'}</small>
                            </td>
                            <td>${categoryName}</td>
                            <td>
                                <div class="budget-amount">${budgetRange}</div>
                            </td>
                            <td>${budgetType}</td>
                            <td>${statusBadge}</td>
                            <td><small>${createdDate}</small></td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <button class="btn btn-sm view-budget" data-id="${budget.id}" title="View">
                                        <i class="ti ti-eye fs-lg"></i>
                                    </button>
                                    <button class="btn btn-sm edit-budget" data-id="${budget.id}" title="Edit">
                                        <i class="ti ti-edit fs-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                $('#budgetsTableBody').html(html);
            }

            // ==================== FORM SUBMIT ====================
            $('#budgetForm').on('submit', function(e) {
                e.preventDefault();
                
                let budgetId = $('#budgetId').val();
                let url = budgetId 
                    ? '{{ route("client.budget.update", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', budgetId)
                    : '{{ route("client.budget.store") }}';
                let method = budgetId ? 'PUT' : 'POST';

                // Basic validation
                let minBudget = parseFloat($('#minimum_budget').val()) || 0;
                let maxBudget = parseFloat($('#maximum_budget').val()) || 0;
                
                if (maxBudget > 0 && minBudget > maxBudget) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Maximum budget must be greater than or equal to minimum budget',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _method: method,
                        _token: '{{ csrf_token() }}',
                        budget_name: $('#budget_name').val(),
                        description: $('#description').val(),
                        minimum_budget: $('#minimum_budget').val(),
                        maximum_budget: $('#maximum_budget').val(),
                        preferred_budget: $('#preferred_budget').val(),
                        category_id: $('#category_id').val(),
                        budget_type: $('#budget_type').val(),
                        status: $('#status').val()
                    },
                    beforeSend: function() {
                        $('#saveBudgetBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Saving...');
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });
                            
                            // Reset form and switch tabs
                            resetForm();
                            $('#budgetForm')[0].reset();
                            $('#budgetId').val('');
                            $('#cancelEdit').hide();
                            
                            // Reload budgets - this will fetch fresh data from server
                            loadBudgets();
                            
                            // Switch to view tab
                            $('a[href="#view_budget"]').tab('show');
                        }
                    },
                    error: function(xhr) {
                        let message = 'An error occurred';
                        let errors = '';
                        
                        if (xhr.responseJSON) {
                            message = xhr.responseJSON.message || message;
                            if (xhr.responseJSON.errors) {
                                errors = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: message + (errors ? '<br><br>' + errors : ''),
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $('#saveBudgetBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Save Budget');
                    }
                });
            });

            // ==================== EDIT BUDGET MODAL ====================
            $(document).on('click', '.edit-budget', function() {
                let id = $(this).data('id');
                
                // Reset modal content to show loading
                $('#editBudgetModal .modal-body').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                
                // Show modal immediately with loading spinner
                $('#editBudgetModal').modal('show');
                
                $.ajax({
                    url: '{{ route("client.budget.show", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', id),
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            let budget = response.budget;
                            
                            // Build category options with selected value
                            let categoryOptions = '<option value="">Select Category</option>';
                            @foreach($categories as $category)
                                categoryOptions += `<option value="{{ $category->id }}" ${budget.category_id == {{ $category->id }} ? 'selected' : ''}>{{ $category->category_name }}</option>`;
                            @endforeach
                            
                            // Build type options with selected value
                            let typeOptions = `
                                <option value="">Select Type</option>
                                <option value="service" ${budget.budget_type === 'service' ? 'selected' : ''}>Service</option>
                                <option value="package" ${budget.budget_type === 'package' ? 'selected' : ''}>Package</option>
                                <option value="equipment" ${budget.budget_type === 'equipment' ? 'selected' : ''}>Equipment</option>
                                <option value="other" ${budget.budget_type === 'other' ? 'selected' : ''}>Other</option>
                            `;
                            
                            // Build edit form with the same layout as creation form
                            let editFormContent = `
                                <form id="editBudgetForm" class="needs-validation" novalidate>
                                    <input type="hidden" id="edit_budget_id" name="budgetId" value="${budget.id}">
                                    
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h4 class="card-title text-primary mb-3">
                                                Budget Information
                                            </h4>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Budget Name <span class="text-muted">(Optional)</span>
                                            </label>
                                            <input type="text" id="edit_budget_name" name="budget_name" class="form-control" 
                                                placeholder="e.g., Wedding Budget 2024" value="${budget.budget_name || ''}">
                                            <small class="text-muted">Give your budget a name for easy reference</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Category <span class="text-muted">(Optional)</span>
                                            </label>
                                            <select id="edit_category_id" name="category_id" class="form-select">
                                                ${categoryOptions}
                                            </select>
                                            <small class="text-muted">Link budget to a service category</small>
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Minimum Budget (₱)</label>
                                            <input type="number" id="edit_minimum_budget" name="minimum_budget" class="form-control" 
                                                placeholder="0.00" min="0" step="0.01" value="${budget.minimum_budget || ''}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Maximum Budget (₱)</label>
                                            <input type="number" id="edit_maximum_budget" name="maximum_budget" class="form-control" 
                                                placeholder="0.00" min="0" step="0.01" value="${budget.maximum_budget || ''}">
                                        </div>

                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Preferred Budget (₱)</label>
                                            <input type="number" id="edit_preferred_budget" name="preferred_budget" class="form-control" 
                                                placeholder="0.00" min="0" step="0.01" value="${budget.preferred_budget || ''}">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">
                                                Budget Type <span class="text-muted">(Optional)</span>
                                            </label>
                                            <select id="edit_budget_type" name="budget_type" class="form-select">
                                                ${typeOptions}
                                            </select>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Status</label>
                                            <select id="edit_status" name="status" class="form-select" required>
                                                <option value="active" ${budget.status === 'active' ? 'selected' : ''}>Active</option>
                                                <option value="inactive" ${budget.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                            </select>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label class="form-label">
                                                Description <span class="text-muted">(Optional)</span>
                                            </label>
                                            <textarea id="edit_description" name="description" class="form-control" 
                                                    rows="3" placeholder="Add any notes or details about this budget...">${budget.description || ''}</textarea>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 text-end">
                                            <button type="button" class="btn btn-light me-2" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary" id="updateBudgetBtn">
                                                <i class="ti ti-device-floppy me-1"></i> Update Budget
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            `;
                            
                            $('#editBudgetModal .modal-body').html(editFormContent);
                        }
                    },
                    error: function() {
                        $('#editBudgetModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load budget details',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            });

            // ==================== EDIT FORM SUBMIT ====================
            $(document).on('submit', '#editBudgetForm', function(e) {
                e.preventDefault();
                
                let budgetId = $('#edit_budget_id').val();
                let url = '{{ route("client.budget.update", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', budgetId);

                // Basic validation
                let minBudget = parseFloat($('#edit_minimum_budget').val()) || 0;
                let maxBudget = parseFloat($('#edit_maximum_budget').val()) || 0;
                
                if (maxBudget > 0 && minBudget > maxBudget) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Maximum budget must be greater than or equal to minimum budget',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _method: 'PUT',
                        _token: '{{ csrf_token() }}',
                        budget_name: $('#edit_budget_name').val(),
                        description: $('#edit_description').val(),
                        minimum_budget: $('#edit_minimum_budget').val(),
                        maximum_budget: $('#edit_maximum_budget').val(),
                        preferred_budget: $('#edit_preferred_budget').val(),
                        category_id: $('#edit_category_id').val(),
                        budget_type: $('#edit_budget_type').val(),
                        status: $('#edit_status').val()
                    },
                    beforeSend: function() {
                        $('#updateBudgetBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Updating...');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#editBudgetModal').modal('hide');
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });
                            
                            // Reload budgets
                            loadBudgets();
                        }
                    },
                    error: function(xhr) {
                        let message = 'An error occurred';
                        let errors = '';
                        
                        if (xhr.responseJSON) {
                            message = xhr.responseJSON.message || message;
                            if (xhr.responseJSON.errors) {
                                errors = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                            }
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: message + (errors ? '<br><br>' + errors : ''),
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $('#updateBudgetBtn').prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Update Budget');
                    }
                });
            });

            // ==================== VIEW BUDGET MODAL ====================
            $(document).on('click', '.view-budget', function() {
                let id = $(this).data('id');
                let budgetId = id; // Store for delete function
                
                // Reset modal content to show loading
                $('#viewBudgetModalBody').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                
                // Show modal immediately with loading spinner
                $('#viewBudgetModal').modal('show');
                
                $.ajax({
                    url: '{{ route("client.budget.show", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', id),
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            let budget = response.budget;
                            let categoryName = budget.category ? budget.category.category_name : '—';
                            let budgetType = budget.budget_type ? budget.budget_type.charAt(0).toUpperCase() + budget.budget_type.slice(1) : '—';
                            let statusBadge = budget.status === 'active' 
                                ? '<span class="badge badge-soft-success">Active</span>' 
                                : '<span class="badge badge-soft-secondary">Inactive</span>';
                            
                            // Format dates
                            let createdDate = budget.created_at ? new Date(budget.created_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) : '—';
                            
                            let updatedDate = budget.updated_at ? new Date(budget.updated_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            }) : '—';
                            
                            // Rebuild the modal content with actual data and delete button
                            let modalContent = `
                                <div class="row align-items-center mb-4">
                                    <div class="col-12 col-lg-8">
                                        <div class="d-flex align-items-center flex-column flex-md-row">
                                            <div class="flex-grow-1 ms-md-2 text-center text-md-start">
                                                <h2 class="mb-1 h3 h3-md">${budget.budget_name || 'Unnamed Budget'}</h2>
                                                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                                    ${statusBadge}
                                                </div>
                                            
                                                <p class="text-muted mb-0">
                                                    <i class="ti ti-info-circle me-1"></i> ${budget.description || 'No description provided'}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col">
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Budget Information</h5>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-category fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Category</label>
                                                        <p class="mb-0 fw-medium">${categoryName}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-coin fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Budget Type</label>
                                                        <p class="mb-0 fw-medium">${budgetType}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Minimum Budget</label>
                                                        <p class="mb-0 fw-medium">${budget.formatted_minimum_budget}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-currency-peso fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Maximum Budget</label>
                                                        <p class="mb-0 fw-medium">${budget.formatted_maximum_budget}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-star fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Preferred Budget</label>
                                                        <p class="mb-0 fw-medium">${budget.formatted_preferred_budget}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-warning rounded-circle p-2">
                                                            <i class="ti ti-wallet fs-20 text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Spent So Far</label>
                                                        <p class="mb-0 fw-medium">${budget.formatted_spent_amount || '₱0.00'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Additional Information</h5>
                                            
                                            <div class="col-12">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-calendar fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Created At</label>
                                                        <p class="mb-0 fw-medium">${createdDate}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-calendar-check fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Last Updated</label>
                                                        <p class="mb-0 fw-medium">${updatedDate}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-4">
                                            <div class="col-12 text-start">
                                                <button type="button" class="btn btn-danger delete-from-view" data-id="${budget.id}">
                                                    <i class="ti ti-trash me-1"></i> Delete Budget
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            $('#viewBudgetModalBody').html(modalContent);
                        }
                    },
                    error: function() {
                        $('#viewBudgetModal').modal('hide');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load budget details',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            });

            $('#confirmDelete').on('click', function() {
                if (!selectedBudgetId) return;
                
                $.ajax({
                    url: '{{ route("client.budget.destroy", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', selectedBudgetId),
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        $('#confirmDelete').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Deleting...');
                    },
                    success: function(response) {
                        if (response.success) {
                            deleteModal.hide();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                timerProgressBar: true
                            });
                            
                            loadBudgets();
                        }
                    },
                    error: function() {
                        deleteModal.hide();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to delete budget',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    complete: function() {
                        $('#confirmDelete').prop('disabled', false).html('Delete');
                        selectedBudgetId = null;
                    }
                });
            });

            // ==================== CANCEL EDIT ====================
            $('#cancelEdit').on('click', function() {
                resetForm();
                $('#budgetForm')[0].reset();
                $('#budgetId').val('');
                $(this).hide();
            });

            // ==================== RESET FORM ====================
            function resetForm() {
                $('#budgetForm')[0].reset();
                $('#budgetId').val('');
                $('#cancelEdit').hide();
            }

            // ==================== TAB CHANGE HANDLER ====================
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                if ($(e.target).attr('href') === '#view_budget') {
                    loadBudgets();
                }
            });

            // ==================== DELETE FROM VIEW MODAL ====================
            $(document).on('click', '.delete-from-view', function() {
                let id = $(this).data('id');
                
                Swal.fire({
                    title: 'Delete Budget',
                    text: 'Are you sure you want to delete this budget? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("client.budget.destroy", ["id" => "PLACEHOLDER"]) }}'.replace('PLACEHOLDER', id),
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Deleting...',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#viewBudgetModal').modal('hide');
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        timer: 2000,
                                        showConfirmButton: false,
                                        timerProgressBar: true
                                    });
                                    
                                    loadBudgets();
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to delete budget',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        });
                    }
                });
            });

            // ==================== FILTERS - AUTO SUBMIT ON CHANGE ====================
            $('#filterStatus, #filterCategory, #filterType').on('change', function() {
                loadBudgets();
            });
        });
    </script>
@endsection