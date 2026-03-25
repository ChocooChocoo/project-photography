@extends('layouts.owner.app')
@section('title', 'Manage Permissions')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Permissions</h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs mb-3">
                                <li class="nav-item">
                                    <a href="#view-permissions" data-bs-toggle="tab" aria-expanded="true" class="nav-link active">
                                        View Permissions
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#create-permissions" data-bs-toggle="tab" aria-expanded="false" class="nav-link">
                                        Create Permissions
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane show active" id="view-permissions">
                                    <div data-table data-table-rows-per-page="10" id="permissionsTable">
                                        <div class="card-header border-light justify-content-between">
                                            <div class="d-flex gap-2">
                                                <div class="app-search">
                                                    <form id="filterForm">
                                                        <input type="search" class="form-control" placeholder="Search permissions..." id="searchInput">
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
                                                        <th data-table-sort="permission_string">Permission String</th>
                                                        <th data-table-sort="resource">Resource</th>
                                                        <th data-table-sort="action">Action</th>
                                                        <th data-table-sort="description">Description</th>
                                                        <th data-table-sort="roles">Assigned Roles</th>
                                                        <th data-table-sort="status">Status</th>
                                                        <th data-table-sort="created_at">Created Date</th>
                                                        <th class="text-center" style="width: 1%;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="permissionsTableBody">
                                                    <tr id="loadingRow">
                                                        <td colspan="8" class="text-center py-4">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="visually-hidden">Loading...</span>
                                                            </div>
                                                            <p class="mt-2 text-muted">Loading permissions...</p>
                                                        </td>
                                                    </tr>
                                                    <tr id="noResultsRow" style="display: none;">
                                                        <td colspan="8" class="text-center py-4">
                                                            <i class="ti ti-filter-off fs-1 text-muted"></i>
                                                            <p class="mt-2">No permissions found.</p>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="card-footer border-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div data-table-pagination-info="permissions"></div>
                                                <div data-table-pagination></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane" id="create-permissions">
                                    <h4 class="card-title text-primary mb-3">Create New Permission</h4>
                                    <form id="createPermissionForm" class="needs-validation" novalidate>
                                        @csrf
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Resource <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="resource" id="createPermissionResource" placeholder="e.g., user, invoice" required>
                                                <div class="invalid-feedback">
                                                    Resource is required.
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Action <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="action" id="createPermissionAction" placeholder="e.g., create, read" required>
                                                <div class="invalid-feedback">
                                                    Action is required.
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Permission String <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="permission_string" id="createPermissionString" readonly required>
                                                <div class="form-text">This is automatically generated using the format resource:action.</div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea class="form-control" name="description" rows="3" placeholder="Describe what this permission allows..."></textarea>
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
                                        </div>

                                        <div class="row">
                                            <div class="col">
                                                <button class="btn btn-primary" type="submit" id="createPermissionBtn">
                                                    <span id="createPermissionText">Create Permission</span>
                                                    <span id="createPermissionSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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

    {{-- Edit Permission Modal --}}
    <div class="modal fade" id="editPermissionModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold">Edit Permission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editModalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading permission details...</p>
                    </div>
                    <div id="editModalContent" style="display: none;">
                        <form id="editPermissionForm">
                            <input type="hidden" name="permission_id" id="editPermissionId">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Resource <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="resource" id="editPermissionResource" required>
                                    <div class="invalid-feedback">
                                        Resource is required.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Action <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="action" id="editPermissionAction" required>
                                    <div class="invalid-feedback">
                                        Action is required.
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Permission String <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="permission_string" id="editPermissionString" readonly required>
                                    <div class="form-text">This is automatically generated using the format resource:action.</div>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" id="editPermissionDescription" rows="3"></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" name="status" id="editPermissionStatus" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a status.
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePermissionBtn">
                        <span id="savePermissionText">Save Changes</span>
                        <span id="savePermissionSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
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

            // ==================== PERMISSION STRING HELPERS ====================
            function normalizePermissionSegment(value) {
                return value
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
            }

            function buildPermissionString(resourceSelector, actionSelector, permissionStringSelector) {
                const resource = normalizePermissionSegment($(resourceSelector).val() || '');
                const action = normalizePermissionSegment($(actionSelector).val() || '');
                const permissionString = resource && action ? `${resource}:${action}` : '';

                $(resourceSelector).val(resource);
                $(actionSelector).val(action);
                $(permissionStringSelector).val(permissionString);
            }

            // ==================== LOAD PERMISSIONS ====================
            function loadPermissions() {
                const status = $('#statusFilter').val();
                const search = $('#searchInput').val();

                $('#permissionsTableBody tr:not(#noResultsRow)').remove();
                $('#loadingRow').show();

                $.ajax({
                    url: "{{ route('owner.permission.data') }}",
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
                            renderPermissionsTable(response.data.data);
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

            function renderPermissionsTable(permissions) {
                const $tbody = $('#permissionsTableBody');
                $tbody.find('tr:not(#noResultsRow)').remove();
                
                permissions.forEach(permission => {
                    const statusBadgeClass = permission.status === 'active' ? 'badge-soft-success' : 'badge-soft-danger';
                    const statusText = permission.status.toUpperCase();
                    
                    const row = `
                        <tr data-permission-id="${permission.id}">
                            <td>
                                <h5 class="mb-1">${permission.permission_string}</h5>
                                <p class="mb-0 fs-xxs text-muted">${permission.name}</p>
                            </td>
                            <td>${permission.resource || '-'}</td>
                            <td>${permission.action || '-'}</td>
                            <td>${permission.description || '—'}</td>
                            <td><span class="badge badge-soft-info">${permission.roles_count} roles</span></td>
                            <td><span class="badge ${statusBadgeClass}">${statusText}</span></td>
                            <td>${permission.created_at}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <button type="button" class="btn btn-sm edit-permission" data-id="${permission.id}" title="Edit Permission">
                                        <i class="ti ti-edit fs-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm delete-permission" data-id="${permission.id}" data-name="${permission.permission_string}" title="Delete">
                                        <i class="ti ti-trash fs-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    $tbody.append(row);
                });
                
                $('#noResultsRow').hide();
            }

            function updatePagination(data) {
                const paginationInfo = $('[data-table-pagination-info="permissions"]');
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
                        loadPermissions();
                    }
                });
            }

            // ==================== FILTERS ====================
            $('#statusFilter, #searchInput').on('change keyup', function() {
                currentPage = 1;
                loadPermissions();
            });

            // ==================== PERMISSION STRING GENERATION ====================
            $('#createPermissionResource, #createPermissionAction').on('input', function() {
                buildPermissionString('#createPermissionResource', '#createPermissionAction', '#createPermissionString');
            });

            $('#editPermissionResource, #editPermissionAction').on('input', function() {
                buildPermissionString('#editPermissionResource', '#editPermissionAction', '#editPermissionString');
            });

            // ==================== CREATE PERMISSION ====================
            $('#createPermissionForm').on('submit', function(e) {
                e.preventDefault();
                buildPermissionString('#createPermissionResource', '#createPermissionAction', '#createPermissionString');
                
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    $(this).addClass('was-validated');
                    return;
                }
                
                const $btn = $('#createPermissionBtn');
                const $text = $('#createPermissionText');
                const $spinner = $('#createPermissionSpinner');
                
                $btn.prop('disabled', true);
                $text.text('Creating...');
                $spinner.removeClass('d-none');
                
                $.ajax({
                    url: "{{ route('owner.permission.store') }}",
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
                            
                            $('#createPermissionForm')[0].reset();
                            $('#createPermissionString').val('');
                            $('#createPermissionForm').removeClass('was-validated');
                            loadPermissions();
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to create permission.';
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
                        $text.text('Create Permission');
                        $spinner.addClass('d-none');
                    }
                });
            });

            // ==================== EDIT PERMISSION ====================
            $(document).on('click', '.edit-permission', function() {
                const permissionId = $(this).data('id');
                
                $('#editModalLoading').show();
                $('#editModalContent').hide();
                $('#editPermissionModal').modal('show');
                
                $.ajax({
                    url: `/owner/permissions/${permissionId}`,
                    method: 'GET',
                    success: function(response) {
                        $('#editModalLoading').hide();
                        
                        if (response.success) {
                            const permission = response.data;
                            $('#editPermissionId').val(permission.id);
                            $('#editPermissionResource').val(permission.resource || '');
                            $('#editPermissionAction').val(permission.action || '');
                            $('#editPermissionString').val(permission.permission_string || '');
                            $('#editPermissionDescription').val(permission.description || '');
                            $('#editPermissionStatus').val(permission.status);
                            $('#editModalContent').show();
                        } else {
                            $('#editModalContent').html('<div class="text-center text-danger">Failed to load permission details.</div>').show();
                        }
                    },
                    error: function() {
                        $('#editModalLoading').hide();
                        $('#editModalContent').html('<div class="text-center text-danger">An error occurred.</div>').show();
                    }
                });
            });
            
            $('#savePermissionBtn').on('click', function() {
                const permissionId = $('#editPermissionId').val();
                buildPermissionString('#editPermissionResource', '#editPermissionAction', '#editPermissionString');
                const permissionResource = $('#editPermissionResource').val();
                const permissionAction = $('#editPermissionAction').val();
                const permissionString = $('#editPermissionString').val();
                const permissionDescription = $('#editPermissionDescription').val();
                const permissionStatus = $('#editPermissionStatus').val();
                
                if (!permissionResource || !permissionAction || !permissionString) {
                    Swal.fire({ icon: 'error', title: 'Error!', text: 'Resource, action, and permission string are required.' });
                    return;
                }
                
                const $btn = $('#savePermissionBtn');
                const $text = $('#savePermissionText');
                const $spinner = $('#savePermissionSpinner');
                
                $btn.prop('disabled', true);
                $text.text('Saving...');
                $spinner.removeClass('d-none');
                
                $.ajax({
                    url: `/owner/permissions/${permissionId}`,
                    method: 'PUT',
                    data: {
                        resource: permissionResource,
                        action: permissionAction,
                        permission_string: permissionString,
                        description: permissionDescription,
                        status: permissionStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                                didClose: () => {
                                    $('#editPermissionModal').modal('hide');
                                    loadPermissions();
                                }
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error!', text: response.message });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update permission.';
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
            
            // ==================== DELETE PERMISSION ====================
            $(document).on('click', '.delete-permission', function() {
                const permissionId = $(this).data('id');
                const permissionName = $(this).data('name');
                
                Swal.fire({
                    icon: 'warning',
                    title: 'Delete Permission',
                    html: `Are you sure you want to delete <strong>${permissionName}</strong>? This action cannot be undone.`,
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete',
                    cancelButtonColor: '#3475db',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `/owner/permissions/${permissionId}`,
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
                                            loadPermissions();
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
                                    text: 'Failed to delete permission.'
                                });
                            }
                        });
                    }
                });
            });
            
            // Initial load
            loadPermissions();
        });
    </script>
@endsection
