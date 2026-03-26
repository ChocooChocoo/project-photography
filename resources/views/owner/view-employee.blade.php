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
                                    {{-- FIX: Form no longer submits to server. JS intercepts and runs applyFilters() instead. --}}
                                    <form id="filterForm">
                                        <input type="search" class="form-control" placeholder="Search employees..." id="searchInput">
                                        <i data-lucide="search" class="app-search-icon text-muted"></i>
                                    </form>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>

                                {{-- Studio Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="studioFilter">
                                        <option value="">All Studios</option>
                                        @foreach($studios as $studio)
                                            <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Role Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="roleFilter">
                                        <option value="">All Roles</option>
                                        <option value="studio-hr">Human Resource</option>
                                        <option value="studio-finance">Finance</option>
                                        <option value="studio-photographer">Photographer</option>
                                    </select>
                                </div>

                                {{-- Status Filter --}}
                                <div class="app-filter">
                                    <select class="me-0 form-select form-control" id="statusFilter">
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
                                        <th data-table-sort="status">Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="employeesTableBody">
                                    @forelse($employees as $employee)
                                        @php
                                            $statusBadgeClass = [
                                                'active' => 'badge-soft-success',
                                                'inactive' => 'badge-soft-secondary',
                                                'suspended' => 'badge-soft-danger'
                                            ][$employee->status] ?? 'badge-soft-secondary';
                                            
                                            $roleDisplay = [
                                                'studio-hr' => 'Human Resource',
                                                'studio-finance' => 'Finance',
                                                'studio-photographer' => 'Photographer'
                                            ][$employee->role] ?? $employee->role;
                                            
                                            // Build the full role name correctly for all employee types
                                            $fullRoleName = '';
                                            
                                            if ($employee->role === 'studio-photographer') {
                                                $fullRoleName = 'Photographer';
                                                // Add position if available
                                                if ($employee->photographer_details && $employee->photographer_details->position) {
                                                    $fullRoleName .= ' - ' . $employee->photographer_details->position;
                                                }
                                            } elseif ($employee->role === 'studio-hr') {
                                                // For HR employees, user_type should be 'Manager' or 'Staff'
                                                $roleType = ucfirst($employee->user_type ?? 'Staff');
                                                $fullRoleName = 'Human Resource ' . $roleType;
                                            } elseif ($employee->role === 'studio-finance') {
                                                // For Finance employees, user_type should be 'Manager' or 'Staff'
                                                $roleType = ucfirst($employee->user_type ?? 'Staff');
                                                $fullRoleName = 'Finance ' . $roleType;
                                            } else {
                                                $fullRoleName = $roleDisplay;
                                            }
                                        @endphp

                                        <tr data-employee-id="{{ $employee->id }}">
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="#" class="link-reset">{{ $employee->studio_data->studio_name ?? 'N/A' }}</a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">Studio ID:</span>
                                                            <span class="text-muted">{{ $employee->studio_data->id ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="javascript:void(0)" class="link-reset view-employee" data-id="{{ $employee->id }}">
                                                                {{ $employee->full_name }}
                                                            </a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">UUID:</span>
                                                            <span class="text-muted">{{ substr($employee->uuid, 0, 8) }}...</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $employee->email }}</td>
                                            <td>{{ $employee->mobile_number }}</td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="badge badge-soft-primary mb-1">
                                                        {{ $fullRoleName }}
                                                    </span>
                                                    @if($employee->role === 'studio-photographer' && $employee->photographer_details)
                                                        <small class="text-muted">{{ $employee->photographer_details->position }}</small>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $statusBadgeClass }} fs-8 px-1 w-100">{{ strtoupper($employee->status) }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="javascript:void(0)" class="btn btn-sm view-employee" data-id="{{ $employee->id }}" title="View Details">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="btn btn-sm edit-schedule" data-id="{{ $employee->id }}" title="Edit Schedule">
                                                        <i class="ti ti-calendar-time fs-lg"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm delete-employee" data-id="{{ $employee->id }}" data-name="{{ $employee->full_name }}" title="Delete">
                                                        <i class="ti ti-trash fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="dbEmptyRow">
                                            <td colspan="7" class="text-center py-4">
                                                <i class="ti ti-users fs-1 text-muted"></i>
                                                <p class="mt-2">No employees found.</p>
                                            </td>
                                        </tr>
                                    @endforelse

                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="7" class="text-center py-4">
                                            <i class="ti ti-filter-off fs-1 text-muted"></i>
                                            <p class="mt-2">No employees match the selected filters.</p>
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
        const allEmployees = {!! $employeesJson !!};
    </script>

    <script>
        $(document).ready(function() {

            // ==================== CLIENT-SIDE FILTERING ====================

            function applyFilters() {
                const selectedStudio = $('#studioFilter').val();
                const selectedRole   = $('#roleFilter').val();
                const selectedStatus = $('#statusFilter').val();
                const searchTerm     = $('#searchInput').val().toLowerCase().trim();

                const filtered = allEmployees.filter(function(emp) {
                    const matchesStudio = !selectedStudio || String(emp.studio_id) === String(selectedStudio);
                    
                    // Role filter now checks both role and user_type for proper matching
                    let matchesRole = !selectedRole;
                    if (selectedRole === 'studio-hr') {
                        matchesRole = emp.role === 'studio-hr';
                    } else if (selectedRole === 'studio-finance') {
                        matchesRole = emp.role === 'studio-finance';
                    } else if (selectedRole === 'studio-photographer') {
                        matchesRole = emp.role === 'studio-photographer';
                    }
                    
                    const matchesStatus = !selectedStatus || emp.status === selectedStatus;
                    const matchesSearch = !searchTerm ||
                        emp.full_name.toLowerCase().includes(searchTerm) ||
                        emp.email.toLowerCase().includes(searchTerm) ||
                        (emp.mobile_number && emp.mobile_number.toLowerCase().includes(searchTerm));

                    return matchesStudio && matchesRole && matchesStatus && matchesSearch;
                });

                const matchedIds = new Set(filtered.map(function(emp) { return emp.id; }));
                let visibleCount = 0;

                $('#employeesTableBody tr[data-employee-id]').each(function() {
                    const rowId = parseInt($(this).data('employee-id'));
                    if (matchedIds.has(rowId)) {
                        $(this).show();
                        visibleCount++;
                    } else {
                        $(this).hide();
                    }
                });

                $('#noResultsRow').toggle(visibleCount === 0);
            }

            // Bind filter dropdowns — each change triggers an instant re-filter
            $('#studioFilter, #roleFilter, #statusFilter').on('change', applyFilters);

            // Bind search input — filters on every keystroke
            $('#searchInput').on('input', applyFilters);

            // Prevent the search form from triggering a full page reload
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();
                applyFilters();
            });

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

                            const content = `
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="flex-shrink-0">
                                                ${emp.profile_photo
                                                    ? `<img src="${emp.profile_photo}" alt="${emp.full_name}" class="rounded-circle border" width="72" height="72" style="object-fit: cover;">`
                                                    : `<div class="rounded-circle border d-flex align-items-center justify-content-center bg-light text-primary fw-semibold" style="width: 72px; height: 72px;">${(emp.first_name || 'E').charAt(0)}${(emp.last_name || '').charAt(0)}</div>`
                                                }
                                            </div>
                                            <div>
                                                <h4 class="mb-1">${emp.full_name || 'N/A'}</h4>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <span class="badge badge-soft-primary">${emp.role_display || roleDisplay}</span>
                                                    <span class="badge ${statusBadgeClass}">${(emp.status || 'unknown').toUpperCase()}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Basic Information</h5>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Email Address</label>
                                                <p class="mb-0 fw-medium">${emp.email || 'N/A'}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Contact Number</label>
                                                <p class="mb-0 fw-medium">${emp.mobile_number || 'N/A'}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Role</label>
                                                <p class="mb-0 fw-medium">${emp.role_display || roleDisplay}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">User Type</label>
                                                <p class="mb-0 fw-medium">${emp.user_type || 'N/A'}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">UUID</label>
                                                <p class="mb-0 fw-medium">${emp.uuid || 'N/A'}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Created At</label>
                                                <p class="mb-0 fw-medium">${emp.created_at || 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="row g-2 mb-3">
                                            <h5 class="card-title text-primary">Studio Assignment</h5>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Studio Name</label>
                                                <p class="mb-0 fw-medium">${emp.studio && emp.studio.name ? emp.studio.name : 'Not assigned'}</p>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="text-muted small mb-1">Studio ID</label>
                                                <p class="mb-0 fw-medium">${emp.studio && emp.studio.id ? emp.studio.id : 'N/A'}</p>
                                            </div>
                                        </div>
                                    </div>

                                    ${photographerSection}
                                    ${scheduleSection}
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

            // ==================== DELETE EMPLOYEE ====================
            $(document).on('click', '.delete-employee', function() {
                const employeeId = $(this).data('id');
                const employeeName = $(this).data('name');
                
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
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            window.location.reload();
                                        }
                                    });
                                } else {
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
        });
    </script>
@endsection
