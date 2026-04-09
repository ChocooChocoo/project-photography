@extends('layouts.owner.app')
@section('title', 'Manage Roles')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Roles</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#view-roles" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        View Roles
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#create-roles" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Create Roles
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane show active" id="view-roles">
                                    <div data-table data-table-rows-per-page="10" id="rolesTable">
                                        <div class="card-header border-light justify-content-between">
                                            <div class="d-flex gap-2">
                                                <div class="app-search">
                                                    <form id="filterForm">
                                                        <input type="search" class="form-control" placeholder="Search roles..." id="searchInput">
                                                        <i data-lucide="search" class="app-search-icon text-muted"></i>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">
                                                    <i class="ti ti-filter me-1"></i>Filter By:
                                                </span>

                                                <div class="app-filter">
                                                    <select class="me-0 form-select form-control" id="statusFilter">
                                                        <option value="">All Status</option>
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                                    <tr class="text-uppercase fs-xxs">
                                                        <th data-table-sort="name">Role</th>
                                                        <th data-table-sort="description">Description</th>
                                                        <th data-table-sort="permissions">Permissions</th>
                                                        <th data-table-sort="users">Users</th>
                                                        <th data-table-sort="status">Status</th>
                                                        <th data-table-sort="is_system">Type</th>
                                                        <th data-table-sort="created_at">Created Date</th>
                                                        <th class="text-center" style="width: 1%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="rolesTableBody">
                                                    <tr id="loadingRow">
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <p class="mt-2 text-muted">Loading roles...</p>
                                                        </td>
                                                    </tr>
                                                    <tr id="noResultsRow" style="display: none;">
                                                        <td colspan="8" class="text-center py-4">
                                                            <i class="ti ti-filter-off fs-1 text-muted"></i>
                                                            <p class="mt-2">No roles found.</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="card-footer border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div data-table-pagination-info="roles"></div>
                                                <div data-table-pagination></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="create-roles">
                                    <h4 class="card-title text-primary mb-3">Create New Role</h4>
                                    <form id="createRoleForm" class="needs-validation" novalidate>
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Role Label Preview</label>
                                                <input type="text" class="form-control" id="createRolePreview" value="Role label will appear here" readonly>
                                                <div class="form-text">This is how the role will appear to users.</div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">System Role Key <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" placeholder="e.g., studio-hr-manager" required>
                                                <div class="form-text">Internal identifier only. Keep lowercase words separated by hyphens.</div>
                                                <div class="invalid-feedback">
                                                    Role name is required.
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3" placeholder="Describe the role's responsibilities..."></textarea>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Status <span class="text-danger">*</span></label>
                                                <select class="form-select" name="status" required>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    Please select a status.
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" role="switch" name="is_system" id="createRoleIsSystem" value="1">
                                                    <label class="form-check-label" for="createRoleIsSystem">Protected System Role</label>
                                                </div>
                                                <div class="form-text">Use this for built-in roles that should not be deleted accidentally.</div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col">
                                                <button class="btn btn-primary" type="submit" id="createRoleBtn">
                                                    <span id="createRoleText">Create Role</span>
                                                    <span id="createRoleSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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

    {{-- Edit Role Modal --}}
    <div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading role details...</p>
                    </div>
                    <div id="editModalContent" style="display: none;">
                        <form id="editRoleForm">
                            <input type="hidden" name="role_id" id="editRoleId">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Role Label Preview</label>
                                    <input type="text" class="form-control" id="editRolePreview" readonly>
                                    <div class="form-text">This is the friendly label that users will see.</div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">System Role Key <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" id="editRoleName" required>
                                    <div class="invalid-feedback">
                                        Role name is required.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="editRoleDescription" rows="3"></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="editRoleStatus" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a status.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_system" id="editRoleIsSystem" value="1">
                                        <label class="form-check-label" for="editRoleIsSystem">Protected System Role</label>
                                    </div>
                                    <div class="form-text">Use this for built-in roles that should not be deleted accidentally.</div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Access Items</label>
                                    <div id="permissionsChecklist" class="row g-3"></div>
                                    <div class="form-text">Choose the screens and actions this role can access.</div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveRoleBtn">
                        <span id="saveRoleText">Save Changes</span>
                        <span id="saveRoleSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentPage = 1;
            let perPage = 10;
            let totalPages = 1;
            let rolesData = [];

            const roleLabelMap = {
                'admin': 'Administrator',
                'owner': 'Studio Owner',
                'owner-super-admin': 'Studio Owner',
                'freelancer': 'Freelancer',
                'client': 'Client',
                'studio-hr': 'Human Resources',
                'studio-hr-manager': 'HR Manager',
                'studio-hr-staff': 'HR Staff',
                'studio-finance': 'Finance',
                'studio-finance-manager': 'Finance Manager',
                'studio-finance-staff': 'Finance Staff',
                'studio-photographer': 'Photographer'
            };

            function getFriendlyRoleName(roleName) {
                const normalizedRoleName = String(roleName || '').trim().toLowerCase();

                if (!normalizedRoleName) {
                    return 'Role label will appear here';
                }

                if (roleLabelMap[normalizedRoleName]) {
                    return roleLabelMap[normalizedRoleName];
                }

                return normalizedRoleName
                    .replace(/[-_]+/g, ' ')
                    .replace(/\b\w/g, char => char.toUpperCase());
            }

            function syncRolePreview(inputSelector, previewSelector) {
                $(previewSelector).val(getFriendlyRoleName($(inputSelector).val()));
            }

            // ==================== LOAD ROLES ====================
            function loadRoles() {
                const status = $('#statusFilter').val();
                const search = $('#searchInput').val();

                $('#rolesTableBody tr:not(#noResultsRow)').remove();
                $('#loadingRow').show();

                $.ajax({
                    url: "{{ route('owner.role.data') }}",
                    method: 'GET',
                    data: {
                        page: currentPage,
                        per_page: perPage,
                        status: status,
                        search: search
                    },
                    success: function(response) {
                        $('#loadingRow').hide();
                        
                        if (response.success && response.data.data.length > 0) {
                            rolesData = response.data.data;
                            totalPages = response.data.last_page;
                            renderRolesTable(rolesData);
                            updatePagination(response.data);
                        } else {
                            $('#noResultsRow').show();
                        }
                    },
                    error: function() {
                        $('#loadingRow').hide();
                        $('#noResultsRow').show();
                    }
                });
            }

            function renderRolesTable(roles) {
                const $tbody = $('#rolesTableBody');
                $tbody.find('tr:not(#noResultsRow)').remove();
                
                roles.forEach(role => {
                    const statusBadgeClass = role.status === 'active' ? 'badge-soft-success' : 'badge-soft-danger';
                    const statusText = role.status.toUpperCase();
                    const systemBadgeClass = role.is_system ? 'badge-soft-warning' : 'badge-soft-secondary';
                    const systemBadgeText = role.is_system ? 'Protected' : 'Custom';
                    const deleteButtonHtml = role.is_system
                        ? `<button type="button" class="btn btn-sm" title="System-protected roles cannot be deleted" disabled><i class="ti ti-lock fs-lg"></i></button>`
                        : `<button type="button" class="btn btn-sm delete-role" data-id="${role.id}" data-name="${role.display_name || role.name}" title="Delete"><i class="ti ti-trash fs-lg"></i></button>`;
                    
                    const row = `
                        <tr data-role-id="${role.id}">
                            <td>
                                <h5 class="mb-1">${role.display_name || role.name}</h5>
                                <p class="mb-0 fs-xxs text-muted">System key: ${role.technical_name || role.name}</p>
                            </td>
                            <td>${role.description || '—'}</td>
                            <td><span class="badge badge-soft-info">${role.permissions_count} access items</span></td>
                            <td><span class="badge badge-soft-secondary">${role.users_count} users</span></td>
                            <td><span class="badge ${statusBadgeClass}">${statusText}</span></td>
                            <td><span class="badge ${systemBadgeClass}">${systemBadgeText}</span></td>
                            <td>${role.created_at}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm edit-role" data-id="${role.id}" title="Edit Role">
                                        <i class="ti ti-edit fs-lg"></i>
                                    </button>
                                    ${deleteButtonHtml}
                                </div>
                            </td>
                        </tr>
                    `;
                    $tbody.append(row);
                });
                
                $('#noResultsRow').hide();
            }

            function updatePagination(data) {
                const paginationInfo = $('[data-table-pagination-info="roles"]');
                const paginationContainer = $('[data-table-pagination]');
                
                const start = (data.current_page - 1) * data.per_page + 1;
                const end = Math.min(data.current_page * data.per_page, data.total);
                paginationInfo.html(`Showing ${start} to ${end} of ${data.total} entries`);
                
                let paginationHtml = '<ul class="pagination pagination-sm mb-0">';
                
                if (data.current_page > 1) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;
                } else {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
                }
                
                const maxPages = Math.min(5, data.last_page);
                let startPage = Math.max(1, data.current_page - 2);
                let endPage = Math.min(data.last_page, startPage + maxPages - 1);
                
                if (endPage - startPage + 1 < maxPages) {
                    startPage = Math.max(1, endPage - maxPages + 1);
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    if (i === data.current_page) {
                        paginationHtml += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                    } else {
                        paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
                    }
                }
                
                if (data.current_page < data.last_page) {
                    paginationHtml += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
                } else {
                    paginationHtml += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
                }
                
                paginationHtml += '</ul>';
                paginationContainer.html(paginationHtml);
                
                $('.page-link').click(function(e) {
                    e.preventDefault();
                    const page = $(this).data('page');
                    if (page) {
                        currentPage = page;
                        loadRoles();
                    }
                });
            }

            // ==================== FILTERS ====================
            $('#statusFilter, #searchInput').on('change keyup', function() {
                currentPage = 1;
                loadRoles();
            });

            $('#createRoleForm input[name="name"]').on('input', function() {
                syncRolePreview('#createRoleForm input[name="name"]', '#createRolePreview');
            });

            $('#editRoleName').on('input', function() {
                syncRolePreview('#editRoleName', '#editRolePreview');
            });

            // ==================== CREATE ROLE ====================
            $('#createRoleForm').on('submit', function(e) {
                e.preventDefault();
                
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }
                
                const $btn = $('#createRoleBtn');
                const $text = $('#createRoleText');
                const $spinner = $('#createRoleSpinner');
                
                $btn.prop('disabled', true);
                $text.text('Creating...');
                $spinner.removeClass('d-none');
                
                $.ajax({
                    url: "{{ route('owner.role.store') }}",
                    method: 'POST',
                    data: $(this).serialize(),
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
                                timer: 2000,
                                timerProgressBar: true
                            });
                            
                            $('#createRoleForm')[0].reset();
                            $('#createRoleForm').removeClass('was-validated');
                            loadRoles();
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create role.';
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
                        $btn.prop('disabled', false);
                        $text.text('Create Role');
                        $spinner.addClass('d-none');
                    }
                });
            });

            // ==================== EDIT ROLE ====================
            $(document).on('click', '.edit-role', function() {
                const roleId = $(this).data('id');
                
                $('#editModalLoading').show();
                $('#editModalContent').hide();
                $('#editRoleModal').modal('show');
                
                $.ajax({
                    url: `/owner/roles/${roleId}`,
                    method: 'GET',
                    success: function(response) {
                        $('#editModalLoading').hide();
                        
                        if (response.success) {
                            const role = response.data;
                            $('#editRoleId').val(role.id);
                            $('#editRoleName').val(role.name);
                            $('#editRolePreview').val(role.display_name || getFriendlyRoleName(role.name));
                            $('#editRoleDescription').val(role.description || '');
                            $('#editRoleStatus').val(role.status);
                            $('#editRoleIsSystem').prop('checked', Boolean(role.is_system));
                            
                            loadPermissionsForRole(role.id, role.permissions || []);
                            $('#editModalContent').show();
                        } else {
                            $('#editModalContent').html('<div class="text-center text-danger">Failed to load role details.</div>').show();
                        }
                    },
                    error: function() {
                        $('#editModalLoading').hide();
                        $('#editModalContent').html('<div class="text-center text-danger">An error occurred.</div>').show();
                    }
                });
            });
            
            function loadPermissionsForRole(roleId, selectedPermissions) {
                const selectedIds = selectedPermissions.map(p => p.id);
                
                $.ajax({
                    url: "{{ route('owner.permission.all') }}",
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            const container = $('#permissionsChecklist');
                            container.empty();
                            
                            const permissionsByGroup = groupPermissions(response.data);
                            
                            for (const [group, permissions] of Object.entries(permissionsByGroup)) {
                                container.append(`<div class="col-12"><h6 class="mt-2 mb-2 text-primary">${group}</h6><div class="row g-2 mb-3"></div></div>`);
                                const rowContainer = container.children().last().find('.row');
                                
                                permissions.forEach(permission => {
                                    const isChecked = selectedIds.includes(permission.id);
                                    rowContainer.append(`
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="${permission.id}" id="perm_${permission.id}" ${isChecked ? 'checked' : ''}>
                                                <label class="form-check-label" for="perm_${permission.id}">
                                                    ${permission.display_label || permission.permission_string || permission.name}
                                                    <small class="text-muted d-block">${permission.portal_display || ''}</small>
                                                    <small class="text-muted d-block">${permission.description || ''}</small>
                                                </label>
                                            </div>
                                        </div>
                                    `);
                                });
                            }
                            
                            $('#editModalContent').data('role-id', roleId);
                        }
                    }
                });
            }
            
            function groupPermissions(permissions) {
                const groupedPermissions = {};

                permissions.forEach(permission => {
                    const groupName = permission.resource_display || 'Other';

                    if (!groupedPermissions[groupName]) {
                        groupedPermissions[groupName] = [];
                    }

                    groupedPermissions[groupName].push(permission);
                });

                return groupedPermissions;
            }

            
            $('#saveRoleBtn').on('click', function() {
                const roleId = $('#editRoleId').val();
                const roleName = $('#editRoleName').val();
                const roleDescription = $('#editRoleDescription').val();
                const roleStatus = $('#editRoleStatus').val();
                const roleIsSystem = $('#editRoleIsSystem').is(':checked') ? 1 : 0;
                
                if (!roleName) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: 'Role name is required.' });
                    return;
                }
                
                const permissions = [];
                $('.permission-checkbox:checked').each(function() {
                    permissions.push($(this).val());
                });
                
                const $btn = $('#saveRoleBtn');
                const $text = $('#saveRoleText');
                const $spinner = $('#saveRoleSpinner');
                
                $btn.prop('disabled', true);
                $text.text('Saving...');
                $spinner.removeClass('d-none');
                
                $.ajax({
                    url: `/owner/roles/${roleId}`,
                    method: 'PUT',
                    data: {
                        name: roleName,
                        description: roleDescription,
                        status: roleStatus,
                        is_system: roleIsSystem,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $.ajax({
                                url: `/owner/roles/${roleId}/permissions`,
                                method: 'PUT',
                                data: {
                                    permissions: permissions,
                                    _token: $('meta[name="csrf-token"]').attr('content')
                                },
                                success: function(permResponse) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Role updated successfully.',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            $('#editRoleModal').modal('hide');
                                            loadRoles();
                                        }
                                    });
                                },
                                error: function() {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: 'Role details updated, but permissions may need review.',
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true,
                                        didClose: () => {
                                            $('#editRoleModal').modal('hide');
                                            loadRoles();
                                        }
                                    });
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update role.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error!', text: errorMessage });
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $text.text('Save Changes');
                        $spinner.addClass('d-none');
                    }
                });
            });
            
            // ==================== DELETE ROLE ====================
            $(document).on('click', '.delete-role', function() {
                const roleId = $(this).data('id');
                const roleName = $(this).data('name');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Role',
                    html: `Are you sure you want to delete <strong>${roleName}</strong>? This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonColor: '#3475db',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/owner/roles/${roleId}`,
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
                                            loadRoles();
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
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Failed to delete role.'
                                });
                            }
                        });
                    }
                });
            });
            
            // Initial load
            syncRolePreview('#createRoleForm input[name="name"]', '#createRolePreview');
            loadRoles();
        });
    </script>
@endsection
