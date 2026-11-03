@extends('layouts.freelancer.app')
@section('title', 'View Packages')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Package Lists</h4>
                        </div>
                        <div class="card-body">
                            <div class="card-header border-0 justify-content-end">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold">
                                        <i class="ti ti-filter me-1"></i>Filter Categories:
                                    </span>
                                    <div class="app-filter">
                                        <select id="categoryFilter" class="me-0 form-select form-control">
                                            <option value="">All Categories</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            @if($packages->isEmpty())
                                <div class="text-center py-5">
                                    <i class="ti ti-package-off fs-1 text-muted mb-3"></i>
                                    <h5 class="text-muted">No packages found</h5>
                                    <p class="text-muted mb-4">You haven't created any packages yet.</p>
                                    <a href="{{ route('freelancer.packages.create') }}" class="btn btn-primary">
                                        <i class="ti ti-plus me-1"></i> Create Your First Package
                                    </a>
                                </div>
                            @else
                                <div class="row g-3 mb-4">
                                    @foreach($packages as $package)
                                    <div class="col-md-4">
                                        <div class="card shadow-none border h-100 my-4 my-lg-0">
                                            <div class="card-body p-lg-4 pb-0 text-center">
                                                <h3 class="fw-bold mb-1">{{ $package->package_name }}</h3>
                                                <p class="text-muted mb-0">{{ $package->category->category_name ?? 'Uncategorized' }}</p>

                                                <div class="my-4">
                                                    <h1 class="display-6 fw-bold mb-0">₱{{ number_format($package->package_price, 2) }}</h1>
                                                    
                                                    <!-- ==== Start: Time Customization in Price Card ==== -->
                                                    @if($package->allow_time_customization)
                                                        <small class="d-block text-muted fs-base">
                                                            <i class="ti ti-clock-edit me-1"></i> Flexible duration
                                                        </small>
                                                    @else
                                                        <small class="d-block text-muted fs-base">{{ $package->duration }} Hours</small>
                                                    @endif
                                                    <!-- ==== End: Time Customization in Price Card ==== -->
                                                    
                                                    <small class="d-block text-muted">{{ $package->maximum_edited_photos }} Edited Photos</small>
                                                    <div class="d-flex justify-content-center mt-2">
                                                        <span class="badge {{ $package->online_gallery ? 'badge-soft-success' : 'badge-soft-secondary' }} px-2 py-1">
                                                            <i class="ti ti-photo {{ $package->online_gallery ? '' : 'ti-photo-off' }} me-1"></i>
                                                            Online Gallery: {{ $package->online_gallery ? 'Yes' : 'No' }}
                                                        </span>
                                                    </div>

                                                    <!-- ==== Start: Add Time Customization Badge ==== -->
                                                    @if($package->allow_time_customization)
                                                        <div class="d-flex justify-content-center mt-2">
                                                            <span class="badge badge-soft-success px-2 py-1">
                                                                <i class="ti ti-clock-edit me-1"></i> Flexible Duration
                                                            </span>
                                                        </div>
                                                    @endif
                                                    <!-- ==== End: Add Time Customization Badge ==== -->
                                                </div>

                                                {{-- INCLUSIONS ON CARD - Display first 3 inclusions --}}
                                                <div class="text-start mb-3">
                                                    <small class="text-muted fw-semibold d-block mb-2">INCLUSIONS:</small>
                                                    <ul class="list-unstyled fs-sm mb-0">
                                                        @if($package->package_inclusions && 
                                                            (is_array($package->package_inclusions) || is_string($package->package_inclusions)))
                                                            
                                                            @php
                                                                // Handle different data types
                                                                $inclusions = [];
                                                                if (is_array($package->package_inclusions)) {
                                                                    $inclusions = $package->package_inclusions;
                                                                } elseif (is_string($package->package_inclusions)) {
                                                                    // Try to decode JSON
                                                                    $decoded = json_decode($package->package_inclusions, true);
                                                                    if (is_array($decoded)) {
                                                                        $inclusions = $decoded;
                                                                    } else {
                                                                        // Split by commas if it's a comma-separated string
                                                                        $inclusions = array_map('trim', explode(',', $package->package_inclusions));
                                                                    }
                                                                }
                                                                
                                                                // Filter out empty values
                                                                $inclusions = array_filter($inclusions);
                                                                
                                                                // Take first 3 inclusions
                                                                $displayInclusions = array_slice($inclusions, 0, 3);
                                                                $hasMore = count($inclusions) > 3;
                                                            @endphp
                                                            
                                                            @foreach($displayInclusions as $inclusion)
                                                                @if(!empty($inclusion))
                                                                <li class="mb-2">
                                                                    <i class="ti ti-check text-success me-2"></i> 
                                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $inclusion }}">
                                                                        {{ $inclusion }}
                                                                    </span>
                                                                </li>
                                                                @endif
                                                            @endforeach
                                                            
                                                            @if($hasMore)
                                                                <li class="mb-2 text-muted">
                                                                    <i class="ti ti-dots me-2"></i>
                                                                    <small>+{{ count($inclusions) - 3 }} more inclusions</small>
                                                                </li>
                                                            @endif
                                                        @else
                                                            <li class="mb-2 text-muted">
                                                                <i class="ti ti-minus me-2"></i>
                                                                No inclusions specified
                                                            </li>
                                                        @endif
                                                        
                                                        @if($package->coverage_scope)
                                                        <li class="mb-2">
                                                            <i class="ti ti-map-pin text-primary me-2"></i> 
                                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $package->coverage_scope }}">
                                                                {{ $package->coverage_scope }}
                                                            </span>
                                                        </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent px-5 pb-4">
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-pill view-details-btn" 
                                                            data-package-id="{{ $package->id }}"
                                                            data-category-id="{{ $package->category_id }}">
                                                        View Details
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                
                                @if($packages->count() > 3)
                                <div class="text-center mt-4">
                                    <nav aria-label="Package navigation">
                                        <ul class="pagination justify-content-center mb-0">
                                            <li class="page-item disabled">
                                                <span class="page-link">Showing {{ $packages->count() }} packages</span>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="viewPackageModal" tabindex="-1" aria-labelledby="viewPackageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPackageModalLabel">Package Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="packageLoading" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading package details...</p>
                    </div>
                    <div id="packageDetailsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // View Package Details
            $(document).on('click', '.view-details-btn', function() {
                const packageId = $(this).data('package-id');
                loadPackageDetails(packageId);
            });

            // Category filter change event
            $('#categoryFilter').on('change', function() {
                filterPackagesByCategory($(this).val());
            });

            /**
             * Filter packages by category
             */
            function filterPackagesByCategory(categoryId) {
                // Remove any existing "no packages" message
                $('#noPackagesMessage').remove();
                
                if (!categoryId) {
                    // Show all packages when "All Categories" is selected
                    $('.col-md-4').show();
                    return;
                }

                // Hide all packages first
                $('.col-md-4').hide();
                
                // Show only packages with matching category
                $(`.col-md-4 .view-details-btn[data-category-id="${categoryId}"]`).closest('.col-md-4').show();
                
                // Check if any packages are visible
                const visiblePackages = $('.col-md-4:visible').length;
                
                if (visiblePackages === 0) {
                    // Show "no packages" message
                    const messageHtml = `
                        <div id="noPackagesMessage" class="text-center py-5 col-12">
                            <i class="ti ti-package-off fs-1 text-muted mb-3"></i>
                            <h5 class="text-muted">No packages found in this category</h5>
                            <p class="text-muted mb-4">Try selecting a different category or create a package in this category.</p>
                            <a href="{{ route('freelancer.packages.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i> Create Package
                            </a>
                        </div>
                    `;
                    $('.row.g-3.mb-4').append(messageHtml);
                }
            }

            /**
             * Load package details via AJAX
             */
            function loadPackageDetails(packageId) {
                // Show loading
                $('#packageLoading').show();
                $('#packageDetailsContent').empty().hide();
                
                // Reset modal title
                $('#viewPackageModalLabel').text('Package Details');
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('viewPackageModal'));
                modal.show();

                $.ajax({
                    url: '{{ url("freelancer/packages") }}/' + packageId,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        if (response.success && response.data) {
                            renderPackageDetails(response.data);
                        } else {
                            showError(response.message || 'Failed to load package details');
                        }
                    },
                    error: function(xhr, status, error) {
                        let errorMessage = 'Error loading package details. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        showError(errorMessage);
                        console.error('Error:', error);
                    },
                    complete: function() {
                        $('#packageLoading').hide();
                        $('#packageDetailsContent').show();
                    }
                });
            }

            /**
             * Render package details in modal
             */
            function renderPackageDetails(package) {
                // Debug: Log the package data
                console.log('Freelancer package data:', package);
                
                // Set modal title
                $('#viewPackageModalLabel').text((package.package_name || 'Package') + ' Details');
                
                // Safely handle package_inclusions
                let inclusions = [];
                if (package.package_inclusions) {
                    if (Array.isArray(package.package_inclusions)) {
                        inclusions = package.package_inclusions;
                    } else if (typeof package.package_inclusions === 'string') {
                        try {
                            const parsed = JSON.parse(package.package_inclusions);
                            inclusions = Array.isArray(parsed) ? parsed : [package.package_inclusions];
                        } catch (e) {
                            inclusions = package.package_inclusions.split(',').map(item => item.trim());
                        }
                    } else {
                        inclusions = [String(package.package_inclusions)];
                    }
                }
                
                // Format inclusions as list
                let inclusionsHtml = '';
                if (inclusions.length > 0) {
                    inclusions.forEach(inclusion => {
                        if (inclusion && inclusion.trim()) {
                            inclusionsHtml += `<li class="mb-1"><i class="ti ti-check text-success me-2"></i>${inclusion.trim()}</li>`;
                        }
                    });
                }
                
                // If still no inclusions, show a default message
                if (!inclusionsHtml) {
                    inclusionsHtml = '<li class="mb-1 text-muted"><i class="ti ti-minus me-2"></i>No inclusions specified</li>';
                }
                
                // Format date
                let formattedDate = 'N/A';
                if (package.created_at) {
                    try {
                        const createdAt = new Date(package.created_at);
                        formattedDate = createdAt.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    } catch (e) {
                        formattedDate = package.created_at;
                    }
                }

                // Build HTML
                const html = `
                    <div class="row align-items-center mb-4">
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-1">${package.package_name || 'Unnamed Package'}</h3>
                                    <div class="d-flex align-items-center mb-2 flex-wrap">
                                        <span class="badge ${package.status === 'active' ? 'badge-soft-success' : 'badge-soft-danger'} px-2 fw-medium">
                                            ${package.status ? package.status.toUpperCase() : 'INACTIVE'}
                                        </span>
                                        <span class="ms-2 text-muted small">Created: ${formattedDate}</span>
                                    </div>
                                    
                                    <p class="text-muted mb-0">
                                        <i class="ti ti-category me-1"></i> ${package.category?.category_name || 'Uncategorized'}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">PACKAGE INFORMATION</h5>
                                
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-package fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Package Name</label>
                                            <p class="mb-0 fw-medium">${package.package_name || 'N/A'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-tag fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Package Price</label>
                                            <p class="mb-0 fw-medium">₱${package.package_price ? parseFloat(package.package_price).toFixed(2) : '0.00'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-photo fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Online Gallery</label>
                                            <div class="mb-0 fw-medium">
                                                ${package.online_gallery 
                                                    ? '<span class="badge badge-soft-success px-2 fw-medium"><i class="ti ti-check me-1"></i> Included</span>' 
                                                    : '<span class="badge badge-soft-secondary px-2 fw-medium"><i class="ti ti-x me-1"></i> Not Included</span>'}
                                            </div>
                                            <small class="text-muted d-block mt-1">Client access to digital gallery</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- ==== Start: Time Customization Display in Modal ==== -->
                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-clock fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Time Customization</label>
                                            <div class="mb-0 fw-medium">
                                                ${package.allow_time_customization 
                                                    ? '<span class="badge badge-soft-success px-2 fw-medium"><i class="ti ti-clock-edit me-1"></i> Flexible Duration</span>' 
                                                    : '<span class="badge badge-soft-secondary px-2 fw-medium"><i class="ti ti-clock me-1"></i> Fixed Duration</span>'}
                                            </div>
                                            <small class="text-muted d-block mt-1">
                                                ${package.allow_time_customization 
                                                    ? 'Clients can choose their preferred duration' 
                                                    : 'Fixed duration package'}
                                            </small>
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
                                            <label class="text-muted small mb-1">Duration</label>
                                            <p class="mb-0 fw-medium">
                                                ${package.allow_time_customization 
                                                    ? '<span class="text-muted">Client can choose</span>' 
                                                    : (package.duration || 0) + ' Hours'}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- ==== End: Time Customization Display in Modal ==== -->

                                <div class="col-12 col-md-6">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-camera fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Maximum Edited Photos</label>
                                            <p class="mb-0 fw-medium">${package.maximum_edited_photos || 0} Photos</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">PACKAGE DETAILS</h5>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-file-text fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Package Description</label>
                                            <p class="mb-0 fw-medium">${package.package_description || 'No description provided'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-check-circle fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Package Inclusions</label>
                                            <ul class="list-unstyled mb-0">
                                                ${inclusionsHtml}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            ${package.coverage_scope ? `
                            <div class="row g-2 mb-3">
                                <h5 class="card-title text-primary">COVERAGE SCOPE</h5>
                                
                                <div class="col-12">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <div class="bg-light-primary rounded-circle p-2">
                                                <i class="ti ti-map-pin fs-20 text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <label class="text-muted small mb-1">Service Coverage Area</label>
                                            <p class="mb-0 fw-medium">${package.coverage_scope}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                $('#packageDetailsContent').html(html);
            }

            /**
             * Show error message
             */
            function showError(message) {
                const errorHtml = `
                    <div class="alert alert-danger" role="alert">
                        <i class="ti ti-alert-circle me-2"></i>
                        ${message}
                    </div>
                `;
                $('#packageDetailsContent').html(errorHtml);
            }
        });
    </script>
@endsection