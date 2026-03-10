@extends('layouts.studio-hr.app')
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
                                    <form id="filterForm">
                                        <input type="search" class="form-control" placeholder="Search employees..."
                                            id="searchInput">
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
                                        @foreach ($studios as $studio)
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
                                        <th>Access</th>
                                        <th data-table-sort="status">Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="employeesTableBody">
                                    @forelse($employees as $employee)
                                        @php
                                            $statusBadgeClass =
                                                [
                                                    'active' => 'badge-soft-success',
                                                    'inactive' => 'badge-soft-secondary',
                                                    'suspended' => 'badge-soft-danger',
                                                ][$employee->status] ?? 'badge-soft-secondary';

                                            $roleDisplay =
                                                [
                                                    'studio-hr' => 'Human Resource',
                                                    'studio-finance' => 'Finance',
                                                    'studio-photographer' => 'Photographer',
                                                ][$employee->role] ?? $employee->role;

                                            $roleTypeDisplay =
                                                $employee->rbac_data && $employee->rbac_data->role_type
                                                    ? " - {$employee->rbac_data->role_type}"
                                                    : '';
                                        @endphp

                                        <tr data-employee-id="{{ $employee->id }}">
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="#"
                                                                class="link-reset">{{ $employee->studio_data->studio_name ?? 'N/A' }}</a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">Studio ID:</span>
                                                            <span
                                                                class="text-muted">{{ $employee->studio_data->id ?? 'N/A' }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h5 class="mb-1">
                                                            <a href="javascript:void(0)" class="link-reset view-employee"
                                                                data-id="{{ $employee->id }}">
                                                                {{ $employee->full_name }}
                                                            </a>
                                                        </h5>
                                                        <p class="mb-0 fs-xxs">
                                                            <span class="fw-medium">UUID:</span>
                                                            <span
                                                                class="text-muted">{{ substr($employee->uuid, 0, 8) }}...</span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $employee->email }}</td>
                                            <td>{{ $employee->mobile_number }}</td>
                                            <td>
                                                {{ $roleDisplay }}{{ $roleTypeDisplay }}
                                                @if ($employee->photographer_details)
                                                    <br><small
                                                        class="text-muted">{{ $employee->photographer_details->position }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-4 align-items-center">
                                                    {{-- Create Permission --}}
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="fs-xxs text-muted mb-1">CREATE</span>
                                                        <div class="form-check form-check-success form-switch">
                                                            <input class="form-check-input permission-switch"
                                                                type="checkbox" role="switch" data-permission="create"
                                                                data-employee-id="{{ $employee->id }}"
                                                                {{ $employee->rbac_data->can_create ?? false ? 'checked' : '' }}
                                                                {{ !$canUpdate ? 'disabled' : '' }}
                                                                style="width: 2.5em; height: 1.3em;">
                                                        </div>
                                                    </div>

                                                    {{-- Read Permission --}}
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="fs-xxs text-muted mb-1">READ</span>
                                                        <div class="form-check form-check-info form-switch">
                                                            <input class="form-check-input permission-switch"
                                                                type="checkbox" role="switch" data-permission="read"
                                                                data-employee-id="{{ $employee->id }}"
                                                                {{ $employee->rbac_data->can_read ?? false ? 'checked' : '' }}
                                                                {{ !$canUpdate ? 'disabled' : '' }}
                                                                style="width: 2.5em; height: 1.3em;">
                                                        </div>
                                                    </div>

                                                    {{-- Update Permission --}}
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="fs-xxs text-muted mb-1">UPDATE</span>
                                                        <div class="form-check form-check-warning form-switch">
                                                            <input class="form-check-input permission-switch"
                                                                type="checkbox" role="switch" data-permission="update"
                                                                data-employee-id="{{ $employee->id }}"
                                                                {{ $employee->rbac_data->can_update ?? false ? 'checked' : '' }}
                                                                {{ !$canUpdate ? 'disabled' : '' }}
                                                                style="width: 2.5em; height: 1.3em;">
                                                        </div>
                                                    </div>

                                                    {{-- Delete Permission --}}
                                                    <div class="d-flex flex-column align-items-center">
                                                        <span class="fs-xxs text-muted mb-1">DELETE</span>
                                                        <div class="form-check form-check-danger form-switch">
                                                            <input class="form-check-input permission-switch"
                                                                type="checkbox" role="switch" data-permission="delete"
                                                                data-employee-id="{{ $employee->id }}"
                                                                {{ $employee->rbac_data->can_delete ?? false ? 'checked' : '' }}
                                                                {{ !$canUpdate ? 'disabled' : '' }}
                                                                style="width: 2.5em; height: 1.3em;">
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge {{ $statusBadgeClass }} fs-8 px-1 w-100">{{ strtoupper($employee->status) }}</span>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="javascript:void(0)" class="btn btn-sm view-employee"
                                                        data-id="{{ $employee->id }}" title="View Details">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" class="btn btn-sm edit-schedule"
                                                        data-id="{{ $employee->id }}" title="Edit Schedule">
                                                        <i class="ti ti-calendar-time fs-lg"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm delete-employee"
                                                        data-id="{{ $employee->id }}"
                                                        data-name="{{ $employee->full_name }}" title="Delete">
                                                        <i class="ti ti-trash fs-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr id="dbEmptyRow">
                                            <td colspan="8" class="text-center py-4">
                                                <i class="ti ti-users fs-1 text-muted"></i>
                                                <p class="mt-2">No employees found.</p>
                                            </td>
                                        </tr>
                                    @endforelse

                                    <tr id="noResultsRow" style="display: none;">
                                        <td colspan="8" class="text-center py-4">
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

    {{-- Edit Schedule Modal --}}
    <div class="modal fade" id="editScheduleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Employee Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editScheduleForm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="employee_id" id="edit_employee_id">

                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="start_time" id="edit_start_time"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <label class="form-label mb-2">Select Operating Days <span
                                    class="text-danger">*</span></label>
                            <div class="mb-2">
                                <div class="btn-group w-100 mb-1" role="group" id="editOperatingDaysGroup">
                                    <input type="checkbox" class="btn-check" id="editBtnMonday" name="operating_days[]"
                                        value="monday">
                                    <label class="btn btn-outline-primary" for="editBtnMonday">Mon</label>

                                    <input type="checkbox" class="btn-check" id="editBtnTuesday" name="operating_days[]"
                                        value="tuesday">
                                    <label class="btn btn-outline-primary" for="editBtnTuesday">Tue</label>

                                    <input type="checkbox" class="btn-check" id="editBtnWednesday"
                                        name="operating_days[]" value="wednesday">
                                    <label class="btn btn-outline-primary" for="editBtnWednesday">Wed</label>

                                    <input type="checkbox" class="btn-check" id="editBtnThursday"
                                        name="operating_days[]" value="thursday">
                                    <label class="btn btn-outline-primary" for="editBtnThursday">Thu</label>

                                    <input type="checkbox" class="btn-check" id="editBtnFriday" name="operating_days[]"
                                        value="friday">
                                    <label class="btn btn-outline-primary" for="editBtnFriday">Fri</label>

                                    <input type="checkbox" class="btn-check" id="editBtnSaturday"
                                        name="operating_days[]" value="saturday">
                                    <label class="btn btn-outline-primary" for="editBtnSaturday">Sat</label>

                                    <input type="checkbox" class="btn-check" id="editBtnSunday" name="operating_days[]"
                                        value="sunday">
                                    <label class="btn btn-outline-primary" for="editBtnSunday">Sun</label>
                                </div>
                                <div class="invalid-feedback" id="edit_operating_days_error">Please select at least one
                                    day.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="saveScheduleBtn">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        const allEmployees = {!! $employeesJson !!};

        $(document).ready(function() {
            // ==================== CLIENT-SIDE FILTERING ====================
            function applyFilters() {
                const selectedStudio = $('#studioFilter').val();
                const selectedRole = $('#roleFilter').val();
                const selectedStatus = $('#statusFilter').val();
                const searchTerm = $('#searchInput').val().toLowerCase().trim();

                const filtered = allEmployees.filter(function(emp) {
                    const matchesStudio = !selectedStudio || String(emp.studio_id) === String(
                        selectedStudio);
                    const matchesRole = !selectedRole || emp.role === selectedRole;
                    const matchesStatus = !selectedStatus || emp.status === selectedStatus;
                    const matchesSearch = !searchTerm ||
                        emp.full_name.toLowerCase().includes(searchTerm) ||
                        emp.email.toLowerCase().includes(searchTerm) ||
                        (emp.mobile_number && emp.mobile_number.toLowerCase().includes(searchTerm));

                    return matchesStudio && matchesRole && matchesStatus && matchesSearch;
                });

                const matchedIds = new Set(filtered.map(function(emp) {
                    return emp.id;
                }));

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

            $('#studioFilter, #roleFilter, #statusFilter').on('change', applyFilters);
            $('#searchInput').on('input', applyFilters);

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
                    url: `/studio-hr/employee/${employeeId}`,
                    method: 'GET',
                    success: function(response) {
                        $('#modalLoading').hide();

                        if (response.success) {
                            const emp = response.data;

                            const roleDisplay = {
                                'studio-hr': 'Human Resource',
                                'studio-finance': 'Finance',
                                'studio-photographer': 'Photographer'
                            } [emp.role] || emp.role;

                            const statusBadgeClass = {
                                'active': 'badge-soft-success',
                                'inactive': 'badge-soft-secondary',
                                'suspended': 'badge-soft-danger'
                            } [emp.status] || 'badge-soft-secondary';

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
                                                    <img src="${emp.profile_photo ? emp.profile_photo : '{{ asset('assets/images/users / user - 3.jpg') }}'
                        } " class="rounded - circle" style="width: 80px; height: 80px; object - fit: cover; " onerror="this.src = '{{ asset('assets / images / users / user - 3.jpg') }}'" alt="${ emp.full_name } ">
                                                </div >

                            <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                <h2 class="mb-1 h3">${emp.full_name}</h2>
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                    <span class="badge ${statusBadgeClass} p-1 me-2">${emp.status.toUpperCase()}</span>
                                    <span class="badge badge-soft-primary p-1">${roleDisplay} ${emp.rbac?.role_type ? `(${emp.rbac.role_type})` : ''}</span>
                                </div>

                                <p class="text-muted mb-0">
                                    <i class="ti ti-building me-1"></i> ${emp.studio?.name || 'N/A'} |
                                    <i class="ti ti-id me-1"></i> UUID: ${emp.uuid.substring(0, 8)}...
                                </p>
                            </div>
                                            </div >
                                        </div >
                                    </div >

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
                            < div class="text-center py-4" >
                                        <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                        <p class="mt-2 text-danger">Failed to load employee details.</p>
                                    </div >
                            `).show();
                        }
                    },
                    error: function() {
                        $('#modalLoading').hide();
                        $('#modalContent').html(`
                            < div class="text-center py-4" >
                                    <i class="ti ti-alert-circle fs-1 text-danger"></i>
                                    <p class="mt-2 text-danger">An error occurred while loading employee details.</p>
                                </div >
                            `).show();
                    }
                });
            });

            // ==================== PERMISSION SWITCH TOGGLE ====================
            $(document).on('change', '.permission-switch', function() {
                @if(!$canUpdate)
                    Swal.fire({
                        icon: 'error',
                        title: 'Permission Denied',
                        text: 'You do not have permission to update employee access.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                @endif

                const employeeId = $(this).data('employee-id');
                const permission = $(this).data('permission');
                const isChecked = $(this).is(':checked');

                const data = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT'
                };

                data[`can_${permission}`] = isChecked ? '1' : '0';

                const $switch = $(this);
                $switch.prop('disabled', true);
                const $parent = $switch.closest('.d-flex');
                $parent.css('opacity', '0.6');

                $.ajax({
                    url: `/studio-hr/employee/${employeeId}/permissions`,
                    method: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: `${permission.charAt(0).toUpperCase() + permission.slice(1)} permission updated successfully.`,
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            });
                        } else {
                            $switch.prop('checked', !isChecked);
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
                        $switch.prop('checked', !isChecked);

                        let errorMessage = 'Failed to update permission.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

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
        });

        // ==================== EDIT SCHEDULE ====================
        $(document).on('click', '.edit-schedule', function() {
            const employeeId = $(this).data('id');

            // First get employee details to populate schedule
            $.ajax({
                url: `/studio-hr/employee/${employeeId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data.schedule) {
                        const schedule = response.data.schedule;

                        $('#edit_employee_id').val(employeeId);
                        $('#edit_start_time').val(schedule.start_time);
                        $('#edit_end_time').val(schedule.end_time);

                        // Uncheck all days first
                        $('#editOperatingDaysGroup input[type="checkbox"]').prop('checked', false);
                        // Check the operating days
                        if (schedule.operating_days && Array.isArray(schedule.operating_days)) {
                            schedule.operating_days.forEach(day => {
                                $(`#editBtn${day.charAt(0).toUpperCase() + day.slice(1)}`).prop(
                                    'checked', true);
                            });
                        }

                        $('#editScheduleModal').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Could not load schedule data.'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load employee schedule.'
                    });
                }
            });
        });

        // ==================== EDIT SCHEDULE FORM SUBMIT ====================
        $('#editScheduleForm').on('submit', function(e) {
            e.preventDefault();

            const employeeId = $('#edit_employee_id').val();
            const $submitBtn = $('#saveScheduleBtn');

            // Validate operating days
            const operatingDays = $('#editOperatingDaysGroup input[type="checkbox"]:checked').length;
            if (operatingDays === 0) {
                $('#edit_operating_days_error').show();
                return;
            } else {
                $('#edit_operating_days_error').hide();
            }

            // Validate times
            const startTime = $('#edit_start_time').val();
            const endTime = $('#edit_end_time').val();

            if (endTime <= startTime) {
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error',
                    text: 'End time must be after start time.'
                });
                return;
            }

            const formData = new FormData(this);
            formData.append('_method', 'PUT');

            $submitBtn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: `/studio-hr/employee/${employeeId}/schedule`,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                            didClose: () => {
                                $('#editScheduleModal').modal('hide');
                                window.location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to update schedule.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text('Save Changes');
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
                        url: `/studio-hr/employee/${employeeId}`,
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
                        error: function(xhr) {
                            let errorMessage = 'Failed to delete employee.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: errorMessage,
                                showConfirmButton: true,
                                confirmButtonColor: '#3475db'
                            });
                        }
                    });
                }
            });
        });

        // ==================== MODAL RESET ON HIDE ====================
        $('#editScheduleModal').on('hidden.bs.modal', function() {
            $('#editScheduleForm')[0].reset();
            $('#edit_operating_days_error').hide();
        });
    </script>
@endsection
