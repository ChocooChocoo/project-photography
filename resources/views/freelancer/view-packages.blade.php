@extends('layouts.freelancer.app')
@section('title', 'View Packages')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="10" class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">List of Packages</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search services...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="categories" class="me-0 form-select form-control">
                                        <option value="">All Categories</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->category_name }}">{{ $category->category_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control">
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
                                        <th data-table-sort data-column="categories">Category</th>
                                        <th data-table-sort>Package Name</th>
                                        <th data-table-sort>Price</th>
                                        <th data-table-sort>Online Gallery</th>
                                        <!-- ==== Start: Add Time Customization Columns ==== -->
                                        <th data-table-sort>Time Customization</th>
                                        <th data-table-sort>Duration</th>
                                        <!-- ==== End: Add Time Customization Columns ==== -->
                                        <th data-table-sort>Max Photos</th>
                                        <th data-table-sort data-column="status">Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="packagesTableBody">
                                    @forelse($packages as $package)
                                    <tr class="package-row" data-status="{{ $package->status }}">
                                        <td>{{ $package->category->category_name ?? 'N/A' }}</td>
                                        <td>{{ $package->package_name }}</td>
                                        <td>PHP {{ number_format($package->package_price, 2) }}</td>
                                        <td>
                                            @if($package->online_gallery)
                                                <span class="badge badge-soft-success fs-8 px-1 w-100">
                                                    <i class="ti ti-check me-1"></i> Yes
                                                </span>
                                            @else
                                                <span class="badge badge-soft-secondary fs-8 px-1 w-100">
                                                    <i class="ti ti-x me-1"></i> No
                                                </span>
                                            @endif
                                        </td>
                                        <!-- ==== Start: Time Customization Display ==== -->
                                        <td>
                                            @if($package->allow_time_customization)
                                                <span class="badge badge-soft-success fs-8 px-1 w-100" title="Clients can customize duration">
                                                    <i class="ti ti-clock-edit me-1"></i> Flexible
                                                </span>
                                            @else
                                                <span class="badge badge-soft-secondary fs-8 px-1 w-100" title="Fixed duration only">
                                                    <i class="ti ti-clock me-1"></i> Fixed
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($package->allow_time_customization)
                                                <span class="text-muted">—</span>
                                            @else
                                                {{ $package->duration }} hours
                                            @endif
                                        </td>
                                        <!-- ==== End: Time Customization Display ==== -->
                                        <td>{{ $package->maximum_edited_photos }}</td>
                                        <td>
                                            @if($package->status == 'active')
                                                <span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>
                                            @else
                                                <span class="badge badge-soft-danger fs-8 px-1 w-100">INACTIVE</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <button class="btn btn-sm btn-edit" data-id="{{ $package->id }}">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </button>
                                                <button class="btn btn-sm btn-delete" data-id="{{ $package->id }}">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ti ti-package-off fs-1 text-muted mb-2"></i>
                                                <p class="text-muted mb-0">No packages found.</p>
                                                <a href="{{ route('freelancer.packages.create') }}" class="btn btn-primary btn-sm mt-2">
                                                    <i class="ti ti-plus me-1"></i> Create Your First Package
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="packages"></div>
                                <div data-table-pagination></div>
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
            // Edit package
            $('.btn-edit').on('click', function() {
                const packageId = $(this).data('id');
                window.location.href = "{{ url('freelancer/packages') }}/" + packageId + "/edit";
            });
            
            // Delete package
            $('.btn-delete').on('click', function() {
                const packageId = $(this).data('id');
                const button = $(this);
                
                Swal.fire({
                    title: 'Delete Package?',
                    text: 'Are you sure you want to delete this package? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show loading
                        button.prop('disabled', true).html('<i class="ti ti-loader spinner"></i>');
                        
                        // AJAX delete request
                        $.ajax({
                            url: `/freelancer/packages/${packageId}`,
                            type: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Deleted!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonColor: '#007BFF',
                                        timer: 1500
                                    }).then(() => {
                                        // Remove row from table
                                        button.closest('tr').fadeOut(300, function() {
                                            $(this).remove();
                                            // Check if table is empty
                                            if ($('.package-row').length === 0) {
                                                location.reload();
                                            }
                                        });
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message || 'Failed to delete package.',
                                        icon: 'error',
                                        confirmButtonColor: '#DC3545'
                                    });
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred while deleting the package.';
                                
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                
                                Swal.fire({
                                    title: 'Error!',
                                    text: errorMessage,
                                    icon: 'error',
                                    confirmButtonColor: '#DC3545'
                                });
                            },
                            complete: function() {
                                button.prop('disabled', false).html('<i class="ti ti-trash fs-lg"></i>');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection