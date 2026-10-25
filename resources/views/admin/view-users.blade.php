@extends('layouts.admin.app')
@section('title', 'Admin Panel')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Registered Users</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input type="search" class="form-control" id="searchInput" placeholder="Search...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>
                        </div>

                        <div data-table class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0" id="usersTable">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Fullname</th>
                                        <th data-table-sort>Email Address</th>
                                        <th data-table-sort>Contact Number</th>
                                        <th data-table-sort>Role</th>
                                        <th data-table-sort>Location</th>
                                        <th data-table-sort>Status</th>
                                        <th data-table-sort>Date Created</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTableBody">
                                    @forelse($users as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="javascript:void(0)" class="link-reset view-user" data-id="{{ $user->id }}"
                                                           data-bs-toggle="modal" data-bs-target="#userModal">
                                                            {{ $user->full_name }}
                                                        </a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">UUID:</span>
                                                        <span class="text-muted">{{ $user->uuid }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->mobile_number }}</td>
                                        <td>
                                            <div class="d-flex">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="javascript:void(0)" class="link-reset">
                                                            {{ $user->formatted_role }}
                                                        </a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="text-muted">{{ $user->formatted_user_type }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <span class="text-dark">{{ $user->location ? $user->location->municipality : '—' }}</span>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">Province:</span>
                                                        <span class="text-muted">{{ $user->location ? $user->location->province : '—' }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-soft-{{ $user->formatted_status['class'] }} fs-8 px-1 w-100">
                                                {{ $user->formatted_status['label'] }}
                                            </span>
                                        </td>
                                        <td>{{ $user->created_at->format('F d, Y') }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="javascript:void(0)" class="btn btn-sm view-user" data-id="{{ $user->id }}" data-bs-toggle="modal" data-bs-target="#userModal">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
                                                <a href="javascript:void(0)" class="btn btn-sm edit-user" data-id="{{ $user->id }}">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-5">No users found</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div id="paginationInfo"></div>
                                <div id="pagination"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- MODAL --}}
    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="myuserModalLabel">
                        User Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row align-items-center mb-5">
                        <div class="col-12">
                            <div class="d-flex align-items-center flex-column flex-md-row gap-4 gap-md-5">
                                <div class="flex-shrink-0 position-relative">
                                    <img src="{{ asset('assets/uploads/profile_placeholder.jpg') }}" alt="avatar" class="rounded-circle border border-4 border-white shadow" id="modalProfilePhoto" style="width: 110px; height: 110px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1 text-center text-md-start">
                                    <h2 class="mb-2 h3 fw-bold" id="modalFullName">John Alexander</h2>
                                    
                                    <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap gap-2">
                                        <span class="badge badge-soft-primary fw-semibold fs-6" id="modalUserTypeRole">
                                            UserType | Role
                                        </span>
                                        <span class="badge badge-soft-success fw-semibold" id="modalStatus">Verified</span>
                                    </div>
                                    
                                    <p class="text-muted mb-1 small" id="modalMemberSince">
                                        Member since: January 15, 2024
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="mb-2">
                                <h5 class="text-primary fw-semibold text-uppercase small">Personal Information</h5>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="circle-user-round" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">Full Name</label>
                                                <p class="mb-0 fw-medium" id="modalName">John Alexander</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="key-round" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">UUID</label>
                                                <p class="mb-0 fw-medium text-truncate" id="modalUuid">d3e26d71-ffb1-4f29-b3b7-b480f1e55c82</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <h5 class="text-primary fw-semibold text-uppercase small">Contact Information</h5>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="mail" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">Email Address</label>
                                                <p class="mb-0 fw-medium" id="modalEmail">john.alexander@example.com</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="phone" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">Mobile Number</label>
                                                <p class="mb-0 fw-medium" id="modalMobile">+(63) 912 345 6789</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <h5 class="text-primary fw-semibold text-uppercase small">Location Information</h5>
                                <div class="row">
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="map" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">Province</label>
                                                <p class="mb-0 fw-medium" id="modalProvince">—</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-3">
                                                    <i data-lucide="map-pin" class="fs-24 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1 d-block">Municipality / City</label>
                                                <p class="mb-0 fw-medium" id="modalMunicipality">—</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <h5 class="text-primary fw-semibold text-uppercase small">Account Status</h5>
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light-primary rounded-circle p-3">
                                            <i data-lucide="shield-check" class="fs-24 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="text-muted small mb-1 d-block">Status & Verification</label>
                                        <div class="d-flex align-items-center gap-1 flex-wrap">
                                            <span class="badge badge-soft-success fw-medium" id="modalStatus">Verified</span>
                                            <span class="text-muted small" id="modalVerificationDate">Email verified on Jan 15, 2024</span>
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
            // Search functionality
            $('#searchInput').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('tbody tr').each(function() {
                    const rowText = $(this).text().toLowerCase();
                    $(this).toggle(rowText.includes(searchTerm));
                });
            });

            // View user details
            $(document).on('click', '.view-user', function() {
                const userId = $(this).data('id');
                const url = "{{ route('admin.user.details', ':id') }}".replace(':id', userId);
                
                $.ajax({
                    url: url,
                    type: "GET",
                    success: function(response) {
                        if (response.success) {
                            const user = response.user;
                            
                            // Update modal content
                            $('#modalProfilePhoto').attr('src', user.profile_photo);
                            $('#modalFullName').text(user.full_name);
                            $('#modalUserTypeRole').text(user.user_type + ' | ' + user.role);
                            
                            // Update details
                            $('#modalName').text(user.full_name);
                            $('#modalEmail').text(user.email);
                            $('#modalUuid').text(user.uuid);
                            $('#modalMobile').text(user.mobile_number);
                            
                            // Update location information
                            if (user.location) {
                                $('#modalProvince').text(user.location.province || '—');
                                $('#modalMunicipality').text(user.location.municipality || '—');
                            } else {
                                $('#modalProvince').text('—');
                                $('#modalMunicipality').text('—');
                            }
                            
                            // Update status
                            $('#modalStatus').removeClass('badge-soft-success badge-soft-warning badge-soft-danger')
                                            .addClass('badge-soft-' + user.status.class)
                                            .text(user.status.label);
                            
                            // Update verification
                            $('#modalVerificationDate').text(
                                user.email_verified ? 'Email verified on ' + user.email_verified_at : 'Email not verified'
                            );
                            
                            // Update dates
                            $('#modalMemberSince').text('Member since: ' + user.created_at);
                        } else {
                            showAlert('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        showAlert('Error', 'Failed to load user details', 'error');
                    }
                });
            });

            // SweetAlert2 helper function
            function showAlert(title, text, icon) {
                const colors = {
                    success: '#007BFF',
                    error: '#DC3545',
                    warning: '#6C757D',
                    info: '#6C757D'
                };
                
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    confirmButtonColor: colors[icon] || '#007BFF',
                    timer: 3000,
                    timerProgressBar: true
                });
            }

            // Reinitialize Lucide icons when modal is shown
            $('#userModal').on('shown.bs.modal', function() {
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        });
    </script>
@endsection