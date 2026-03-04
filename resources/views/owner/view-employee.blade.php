@extends('layouts.owner.app')
@section('title', 'View Studios Employee')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="10" class="card" id="employeesTable">
                        <div class="card-header">
                            <h5 class="card-title">List of Studios Employee</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search employees..." id="searchInput">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="studio" class="me-0 form-select form-control" id="studioFilter">
                                        <option value="">All Studios</option>
                                        @foreach(\App\Models\StudioOwner\StudiosModel::where('user_id', auth()->id())->get() as $studio)
                                            <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="app-filter">
                                    <select data-table-filter="role" class="me-0 form-select form-control" id="roleFilter">
                                        <option value="">All Roles</option>
                                        <option value="studio-hr">Human Resource</option>
                                        <option value="studio-finance">Finance</option>
                                        <option value="studio-photographer">Photographer</option>
                                    </select>
                                </div>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control" id="statusFilter">
                                        <option value="">All Status</option>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort="studio">Studio Name</th>
                                        <th data-table-sort="name">Full Name</th>
                                        <th data-table-sort="email">Email Address</th>
                                        <th data-table-sort="contact">Contact Number</th>
                                        <th data-table-sort="role">Role</th>
                                        <th>Access</th>
                                        <th data-table-sort="status">Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="employeesTableBody">
                                    {{-- Data will be loaded via AJAX --}}
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="employees"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Employee Modal --}}
    <div class="modal fade" id="viewEmployeeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="viewEmployeeModalLabel">
                        Employee Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading employee details...</p>
                    </div>
                    
                    <div id="modalContent" style="display: none;">
                        <!-- Content will be dynamically populated -->
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
            // ==================== LOAD EMPLOYEES ====================
            function loadEmployees() {
                const search = $('#searchInput').val();
                const studioId = $('#studioFilter').val();
                const role = $('#roleFilter').val();
                const status = $('#statusFilter').val();
                
                $.ajax({
                    url: "{{ route('owner.employee.data') }}",
                    method: 'GET',
                    data: {
                        search: search,
                        studio_id: studioId,
                        role: role,
                        status: status,
                        per_page: 10
                    },
                    success: function(response) {
                        if (response.success) {
                            renderEmployeesTable(response.data);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Failed to load employees.'
                        });
                    }
                });
            }

            // ==================== RENDER EMPLOYEES TABLE ====================
            function renderEmployeesTable(data) {
                const $tbody = $('#employeesTableBody');
                
                if (!data.data || data.data.length === 0) {
                    $tbody.html(`
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="ti ti-users fs-1 text-muted"></i>
                                <p class="mt-2">No employees found.</p>
                            </td>
                        </tr>
                    `);
                    return;
                }
                
                let html = '';
                
                data.data.forEach(employee => {
                    const statusBadgeClass = {
                        'active': 'badge-soft-success',
                        'inactive': 'badge-soft-secondary',
                        'suspended': 'badge-soft-danger'
                    }[employee.status] || 'badge-soft-secondary';
                    
                    const roleDisplay = {
                        'studio-hr': 'Human Resource',
                        'studio-finance': 'Finance',
                        'studio-photographer': 'Photographer'
                    }[employee.role] || employee.role;
                    
                    const roleTypeDisplay = employee.role_type ? ` - ${employee.role_type}` : '';
                    
                    html += `
                        <tr>
                            <td>
                                <div class="d-flex">
                                    <div>
                                        <h5 class="mb-1">
                                            <a href="#" class="link-reset">${employee.studio?.name || 'N/A'}</a>
                                        </h5>
                                        <p class="mb-0 fs-xxs">
                                            <span class="fw-medium">Studio ID:</span>
                                            <span class="text-muted">${employee.studio?.id || 'N/A'}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <h5 class="mb-1">
                                            <a href="javascript:void(0)" class="link-reset view-employee" data-id="${employee.id}">
                                                ${employee.full_name}
                                            </a>
                                        </h5>
                                        <p class="mb-0 fs-xxs">
                                            <span class="fw-medium">UUID:</span>
                                            <span class="text-muted">${employee.uuid.substring(0, 8)}...</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td>${employee.email}</td>
                            <td>${employee.mobile_number}</td>
                            <td>
                                ${roleDisplay}${roleTypeDisplay}
                                ${employee.photographer_details ? `
                                    <br><small class="text-muted">${employee.photographer_details.position}</small>
                                ` : ''}
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-4 align-items-center">
                                    ${renderPermissionSwitch('create', employee.rbac?.can_create, employee.id)}
                                    ${renderPermissionSwitch('read', employee.rbac?.can_read, employee.id)}
                                    ${renderPermissionSwitch('update', employee.rbac?.can_update, employee.id)}
                                    ${renderPermissionSwitch('delete', employee.rbac?.can_delete, employee.id)}
                                </div>
                            </td>
                            <td>
                                <span class="badge ${statusBadgeClass} fs-8 px-1 w-100">${employee.status.toUpperCase()}</span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="javascript:void(0)" class="btn btn-sm view-employee" data-id="${employee.id}" title="View Details">
                                        <i class="ti ti-eye fs-lg"></i>
                                    </a>
                                    <a href="javascript:void(0)" class="btn btn-sm edit-schedule" data-id="${employee.id}" title="Edit Schedule">
                                        <i class="ti ti-calendar-time fs-lg"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm delete-employee" data-id="${employee.id}" data-name="${employee.full_name}" title="Delete">
                                        <i class="ti ti-trash fs-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                
                $tbody.html(html);
                
                // Update pagination info
                $('[data-table-pagination-info="employees"]').html(
                    `Showing ${data.from || 0} to ${data.to || 0} of ${data.total || 0} employees`
                );
            }

            // ==================== RENDER PERMISSION SWITCH ====================
            function renderPermissionSwitch(permission, value, employeeId) {
                const colors = {
                    'create': 'success',
                    'read': 'info',
                    'update': 'warning',
                    'delete': 'danger'
                };
                
                const checked = value ? 'checked' : '';
                const color = colors[permission];
                
                return `
                    <div class="d-flex flex-column align-items-center">
                        <span class="fs-xxs text-muted mb-1">${permission.toUpperCase()}</span>
                        <div class="form-check form-check-${color} form-switch">
                            <input class="form-check-input permission-switch" type="checkbox" role="switch" 
                                data-permission="${permission}" data-employee-id="${employeeId}" 
                                ${checked} style="width: 2.5em; height: 1.3em;">
                        </div>
                    </div>
                `;
            }

            // ==================== VIEW EMPLOYEE DETAILS ====================
            $(document).on('click', '.view-employee', function() {
                const employeeId = $(this).data('id');
                
                $('#modalLoading').show();
                $('#modalContent').hide().html('');
                $('#viewEmployeeModal').modal('show');
                
                $.ajax({
                    url: `/owner/employee/${employeeId}`,
                    method: 'GET',
                    success: function(response) {
                        $('#modalLoading').hide();
                        
                        if (response.success) {
                            const emp = response.data;
                            
                            // Determine role display
                            const roleDisplay = {
                                'studio-hr': 'Human Resource',
                                'studio-finance': 'Finance',
                                'studio-photographer': 'Photographer'
                            }[emp.role] || emp.role;
                            
                            // Status badge class
                            const statusBadgeClass = {
                                'active': 'badge-soft-success',
                                'inactive': 'badge-soft-secondary',
                                'suspended': 'badge-soft-danger'
                            }[emp.status] || 'badge-soft-secondary';
                            
                            // Build photographer section if applicable
                            let photographerSection = '';
                            if (emp.photographer_details) {
                                photographerSection = `
                                    <div class="row g-2 mb-3">
                                        <h5 class="card-title text-primary">Photographer Details</h5>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-camera fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Position</label>
                                                    <p class="mb-0 fw-medium">${emp.photographer_details.position || 'Not specified'}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-briefcase fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Years of Experience</label>
                                                    <p class="mb-0 fw-medium">${emp.photographer_details.years_experience || '0'} years</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-category fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Specialization</label>
                                                    <p class="mb-0 fw-medium">${emp.photographer_details.service_name || 'Not specified'} (${emp.photographer_details.category_name || 'N/A'})</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            // Build schedule section
                            let scheduleSection = '';
                            if (emp.schedule) {
                                scheduleSection = `
                                    <div class="row g-2 mb-3">
                                        <h5 class="card-title text-primary">Work Schedule</h5>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-calendar fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Operating Days</label>
                                                    <p class="mb-0 fw-medium">${emp.schedule.days || 'Not set'}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i class="ti ti-clock fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Working Hours</label>
                                                    <p class="mb-0 fw-medium">${emp.schedule.hours || 'Not set'}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            // Build permissions section
                            let permissionsSection = '';
                            if (emp.rbac) {
                                permissionsSection = `
                                    <div class="row g-2 mb-3">
                                        <h5 class="card-title text-primary">Access Permissions</h5>
                                        <div class="col-12 col-md-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-${emp.rbac.can_create ? 'success' : 'danger'} rounded-circle p-2">
                                                        <i class="ti ti-plus-circle fs-20 text-${emp.rbac.can_create ? 'success' : 'danger'}"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Create</label>
                                                    <p class="mb-0 fw-medium"><span class="badge ${emp.rbac.can_create ? 'badge-soft-success' : 'badge-soft-danger'}">${emp.rbac.can_create ? 'ALLOWED' : 'DENIED'}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-${emp.rbac.can_read ? 'success' : 'danger'} rounded-circle p-2">
                                                        <i class="ti ti-eye fs-20 text-${emp.rbac.can_read ? 'success' : 'danger'}"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Read</label>
                                                    <p class="mb-0 fw-medium"><span class="badge ${emp.rbac.can_read ? 'badge-soft-success' : 'badge-soft-danger'}">${emp.rbac.can_read ? 'ALLOWED' : 'DENIED'}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-${emp.rbac.can_update ? 'success' : 'danger'} rounded-circle p-2">
                                                        <i class="ti ti-pencil fs-20 text-${emp.rbac.can_update ? 'success' : 'danger'}"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Update</label>
                                                    <p class="mb-0 fw-medium"><span class="badge ${emp.rbac.can_update ? 'badge-soft-success' : 'badge-soft-danger'}">${emp.rbac.can_update ? 'ALLOWED' : 'DENIED'}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-${emp.rbac.can_delete ? 'success' : 'danger'} rounded-circle p-2">
                                                        <i class="ti ti-trash fs-20 text-${emp.rbac.can_delete ? 'success' : 'danger'}"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Delete</label>
                                                    <p class="mb-0 fw-medium"><span class="badge ${emp.rbac.can_delete ? 'badge-soft-success' : 'badge-soft-danger'}">${emp.rbac.can_delete ? 'ALLOWED' : 'DENIED'}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            const content = `
                                <div class="row align-items-center mb-4">
                                    <div class="col-12 col-lg-8">
                                        <div class="d-flex align-items-center flex-column flex-md-row">
                                            <div class="flex-shrink-0 mb-3 mb-md-0">
                                                <img src="${emp.profile_photo ? emp.profile_photo : '{{ asset('assets/images/users/user-3.jpg') }}'}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.src='{{ asset('assets/images/users/user-3.jpg') }}'" alt="${emp.full_name}">
                                            </div>
                                        
                                            <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                                <h2 class="mb-1 h3">${emp.full_name}</h2>
                                                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                                    <span class="badge ${statusBadgeClass} p-1 me-2">${emp.status.toUpperCase()}</span>
                                                    <span class="badge badge-soft-primary p-1">${roleDisplay} ${emp.rbac?.role_type ? `- ${emp.rbac.role_type}` : ''}</span>
                                                </div>
                                            
                                                <p class="text-muted mb-0">
                                                    <i class="ti ti-building me-1"></i> ${emp.studio?.name || 'N/A'} | 
                                                    <i class="ti ti-id me-1"></i> UUID: ${emp.uuid.substring(0, 8)}...
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col">
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Personal Information</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-mail fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Email Address</label>
                                                        <p class="mb-0 fw-medium">${emp.email}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-phone fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Contact Number</label>
                                                        <p class="mb-0 fw-medium">${emp.mobile_number}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-user fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Full Name</label>
                                                        <p class="mb-0 fw-medium">${emp.first_name} ${emp.middle_name ? emp.middle_name + ' ' : ''}${emp.last_name}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-hash fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">UUID</label>
                                                        <p class="mb-0 fw-medium"><code>${emp.uuid}</code></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Employment Details</h5>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-building-community fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Studio</label>
                                                        <p class="mb-0 fw-medium">${emp.studio?.name || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-briefcase fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Role</label>
                                                        <p class="mb-0 fw-medium">${roleDisplay} ${emp.rbac?.role_type ? `(${emp.rbac.role_type})` : ''}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-calendar-plus fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Date Created</label>
                                                        <p class="mb-0 fw-medium">${emp.created_at || 'N/A'}</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-light-primary rounded-circle p-2">
                                                            <i class="ti ti-status-change fs-20 text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <label class="text-muted small mb-1">Status</label>
                                                        <p class="mb-0 fw-medium"><span class="badge ${statusBadgeClass}">${emp.status.toUpperCase()}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        ${photographerSection}
                                        ${scheduleSection}
                                        ${permissionsSection}
                                    </div>
                                </div>
                            `;
                            
                            $('#modalContent').html(content).show();
                        } else {
                            $('#modalContent').html(`
                                <div class="text-center py-4">
                                    <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                    <p class="mt-2 text-danger">Failed to load employee details.</p>
                                </div>
                            `).show();
                        }
                    },
                    error: function() {
                        $('#modalLoading').hide();
                        $('#modalContent').html(`
                            <div class="text-center py-4">
                                <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                <p class="mt-2 text-danger">An error occurred while loading employee details.</p>
                            </div>
                        `).show();
                    }
                });
            });

            // ==================== PERMISSION SWITCH TOGGLE ====================
            $(document).on('change', '.permission-switch', function() {
                const employeeId = $(this).data('employee-id');
                const permission = $(this).data('permission');
                const isChecked = $(this).is(':checked');
                
                // Create data object with explicit values
                const data = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };
                
                // Send explicit '1' for true, '0' for false
                data[`can_${permission}`] = isChecked ? '1' : '0';
                
                // Show loading state on the switch
                const $switch = $(this);
                $switch.prop('disabled', true);
                
                // Add a small visual indicator that it's saving
                const $parent = $switch.closest('.d-flex');
                $parent.css('opacity', '0.6');
                
                $.ajax({
                    url: `/owner/employee/${employeeId}/permissions`,
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // DEFAULT SWEETALERT - NOT TOAST
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: `${permission.charAt(0).toUpperCase() + permission.slice(1)} permission updated successfully.`,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });
                            
                            // Visual feedback - briefly highlight
                            $parent.find('.text-muted').addClass('text-success');
                            setTimeout(() => {
                                $parent.find('.text-muted').removeClass('text-success');
                            }, 1000);
                        } else {
                            // Revert switch on error
                            $switch.prop('checked', !isChecked);
                            
                            // DEFAULT SWEETALERT ERROR - NOT TOAST
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Failed to update permission.',
                                showConfirmButton: true,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        // Revert switch on error
                        $switch.prop('checked', !isChecked);
                        
                        let errorMessage = 'Failed to update permission.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            // Get the first error message
                            for (let key in errors) {
                                errorMessage = errors[key][0];
                                break;
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // DEFAULT SWEETALERT ERROR - NOT TOAST
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            showConfirmButton: true,
                            confirmButtonColor: '#3475db'
                        });
                    },
                    complete: function() {
                        $switch.prop('disabled', false);
                        $parent.css('opacity', '1');
                    }
                });
            });

            // ==================== DELETE EMPLOYEE ====================
            $(document).on('click', '.delete-employee', function() {
                const employeeId = $(this).data('id');
                const employeeName = $(this).data('name');
                
                // CONFIRMATION SWEETALERT
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Employee',
                    html: `Are you sure you want to delete <strong>${employeeName}</strong>? This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonColor: '#3475db',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/owner/employee/${employeeId}`,
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    // SUCCESS SWEETALERT
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            loadEmployees(); // Reload table
                                        }
                                    });
                                } else {
                                    // ERROR SWEETALERT
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: response.message,
                                        showConfirmButton: true,
                                        confirmButtonColor: '#3475db'
                                    });
                                }
                            },
                            error: function() {
                                // ERROR SWEETALERT
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to delete employee.',
                                    showConfirmButton: true,
                                    confirmButtonColor: '#3475db'
                                });
                            }
                        });
                    }
                });
            });

            // ==================== FILTER CHANGE HANDLERS ====================
            $('#searchInput, #studioFilter, #roleFilter, #statusFilter').on('input change', function() {
                loadEmployees();
            });

            // Initial load
            loadEmployees();
        });
    </script>
@endsection