@extends('layouts.owner.app')
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
                            <div class="row g-3 mb-4" id="packagesContainer">
                                @forelse($packages as $package)
                                <div class="col-md-4 package-item" data-category="{{ $package->category_id ?? '' }}">
                                    <div class="card shadow-none h-100 my-4 my-lg-0">
                                        <div class="card-body p-lg-4 pb-0 text-center">
                                            <h3 class="fw-bold mb-1">{{ $package->package_name }}</h3>
                                            <p class="text-muted mb-0">{{ $package->studio->studio_name ?? 'N/A' }} - {{ $package->category->category_name ?? 'N/A' }}</p>
                                            <div class="mt-2">
                                                @php
                                                    $locations = is_array($package->package_location) ? $package->package_location : (array)json_decode($package->package_location ?? '[]', true);
                                                @endphp
                                                
                                                @if(in_array('In-Studio', $locations))
                                                    <span class="badge badge-soft-primary d-inline-block mb-1">
                                                        <i class="ti ti-building me-1"></i> In-Studio
                                                    </span>
                                                @endif
                                                
                                                @if(in_array('On-Location', $locations))
                                                    <span class="badge badge-soft-info d-inline-block {{ in_array('In-Studio', $locations) ? 'ms-1' : '' }}">
                                                        <i class="ti ti-map-pin me-1"></i> On-Location
                                                    </span>
                                                @endif
                                                
                                                @if(empty($locations))
                                                    <span class="badge badge-soft-secondary">
                                                        <i class="ti ti-minus me-1"></i> Not specified
                                                    </span>
                                                @endif
                                            </div>
                                            <!-- ==== Start: Time Customization Badge ==== -->
                                            <div class="mt-1">
                                                @if($package->allow_time_customization)
                                                    <span class="badge badge-soft-success" title="Clients can customize duration">
                                                        <i class="ti ti-clock-edit me-1"></i> Flexible Duration
                                                    </span>
                                                @else
                                                    <span class="badge badge-soft-secondary" title="Fixed duration only">
                                                        <i class="ti ti-clock me-1"></i> Fixed Duration: {{ $package->duration }} hours
                                                    </span>
                                                @endif
                                            </div>
                                            <!-- ==== End: Time Customization Badge ==== -->
                                            <div class="my-4">
                                                <h1 class="display-6 fw-bold mb-0">PHP {{ number_format($package->package_price, 2) }}</h1>
                                                <!-- ==== Start: Update duration display ==== -->
                                                @if(!$package->allow_time_customization && $package->duration)
                                                    <small class="d-block text-muted fs-base">{{ $package->duration }} Hours (Fixed)</small>
                                                @elseif($package->allow_time_customization)
                                                    <small class="d-block text-muted fs-base">Flexible Duration</small>
                                                @endif
                                                <!-- ==== End: Update duration display ==== -->
                                                <small class="d-block text-muted">{{ $package->maximum_edited_photos }} Edited Photos</small>
                                                <div class="d-flex justify-content-center gap-3 mt-2">
                                                    <div class="text-center">
                                                        <span class="badge {{ $package->online_gallery ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                                            <i class="ti ti-photo {{ $package->online_gallery ? '' : 'ti-photo-off' }} me-1"></i>
                                                            Gallery: {{ $package->online_gallery ? 'Yes' : 'No' }}
                                                        </span>
                                                    </div>
                                                    <div class="text-center">
                                                        <span class="badge badge-soft-primary">
                                                            <i class="ti ti-users me-1"></i>
                                                            {{ $package->photographer_count ?? 0 }} Photographer(s)
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- INCLUSIONS ON CARD --}}
                                            <div class="text-start mb-3">
                                                <small class="text-muted fw-semibold d-block mb-2">INCLUSIONS:</small>
                                                <ul class="list-unstyled fs-sm mb-0">
                                                    @php
                                                        $inclusions = [];
                                                        $coverageAreas = [];

                                                        if (is_array($package->package_inclusions)) {
                                                            $inclusions = $package->package_inclusions;
                                                        } elseif (is_string($package->package_inclusions) && $package->package_inclusions !== '') {
                                                            $decodedInclusions = json_decode($package->package_inclusions, true);
                                                            $inclusions = is_array($decodedInclusions)
                                                                ? $decodedInclusions
                                                                : array_map('trim', explode(',', $package->package_inclusions));
                                                        }

                                                        $inclusions = array_values(array_filter(array_map(function ($item) {
                                                            if (is_scalar($item) || $item === null) {
                                                                $value = trim((string) $item);
                                                                return $value !== '' ? $value : null;
                                                            }

                                                            return null;
                                                        }, $inclusions)));

                                                        if (is_array($package->coverage_scope)) {
                                                            $coverageAreas = $package->coverage_scope;
                                                        } elseif (is_string($package->coverage_scope) && $package->coverage_scope !== '') {
                                                            $decodedCoverage = json_decode($package->coverage_scope, true);
                                                            $coverageAreas = is_array($decodedCoverage)
                                                                ? $decodedCoverage
                                                                : [$package->coverage_scope];
                                                        }

                                                        $coverageAreas = array_values(array_filter(array_map(function ($item) {
                                                            if (is_scalar($item) || $item === null) {
                                                                $value = trim((string) $item);
                                                                return $value !== '' ? $value : null;
                                                            }

                                                            return null;
                                                        }, $coverageAreas)));

                                                        $displayInclusions = array_slice($inclusions, 0, 3);
                                                        $hasMore = count($inclusions) > 3;
                                                        $coverageText = implode(', ', $coverageAreas);
                                                    @endphp

                                                    @if(!empty($inclusions))
                                                        
                                                        @foreach($displayInclusions as $inclusion)
                                                        <li class="mb-2">
                                                            <i class="ti ti-check text-success me-2"></i> 
                                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $inclusion }}">
                                                                {{ $inclusion }}
                                                            </span>
                                                        </li>
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
                                                    
                                                    @if(!empty($coverageAreas))
                                                    <li class="mb-2">
                                                        <i class="ti ti-map-pin text-primary me-2"></i> 
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $coverageText }}">
                                                            {{ $coverageText }}
                                                        </span>
                                                    </li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent px-5 pb-4">
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-outline-primary w-100 py-2 fw-semibold rounded-pill view-package-btn" data-package-id="{{ $package->id }}">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <i class="ti ti-package-off fs-1 text-muted"></i>
                                        <h4 class="mt-3">No Packages Created Yet</h4>
                                        <p class="text-muted mb-4">Start by creating your first package</p>
                                    </div>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="viewPackageModal" tabindex="-1" aria-labelledby="viewPackageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPackageModalLabel">Package Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="packageLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading package details...</p>
                    </div>
                    <div id="packageDetailsContent" style="display: none;">
                        {{-- Content will be loaded here via AJAX --}}
                    </div>
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
            // Category filter functionality
            $('#categoryFilter').change(function() {
                const selectedCategory = $(this).val();
                
                // Remove any existing "no packages" message
                $('.no-packages-message').remove();
                
                if (selectedCategory === '') {
                    // Show all packages
                    $('.package-item').fadeIn();
                } else {
                    // Hide all packages first
                    $('.package-item').hide();
                    
                    // Show only packages with matching category
                    $(`.package-item[data-category="${selectedCategory}"]`).fadeIn();
                    
                    // Show message if no packages found
                    if ($(`.package-item[data-category="${selectedCategory}"]:visible`).length === 0) {
                        $('#packagesContainer').append(`
                            <div class="col-12 no-packages-message">
                                <div class="text-center py-5">
                                    <i class="ti ti-package-off fs-1 text-muted"></i>
                                    <h4 class="mt-3">No Packages Found</h4>
                                    <p class="text-muted mb-4">No packages found for this category.</p>
                                </div>
                            </div>
                        `);
                    }
                }
            });

            // Function to generate modal HTML from package data - UPDATED with time customization
            function generatePackageModalHtml(package) {
                // Debug: Log the package data to see what we're getting
                console.log('Package data:', package);
                
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
                
                // Format inclusions as HTML list
                let inclusionsHtml = '';
                if (inclusions.length > 0) {
                    inclusions.forEach(function(inclusion) {
                        if (inclusion && inclusion.trim()) {
                            inclusionsHtml += '<li class="mb-2"><i class="ti ti-check text-success me-2"></i> ' + inclusion.trim() + '</li>';
                        }
                    });
                }
                
                // If still no inclusions, show a default message
                if (!inclusionsHtml) {
                    inclusionsHtml = '<li class="mb-2 text-muted"><i class="ti ti-minus me-2"></i> No inclusions specified</li>';
                }
                
                // Format coverage scope
                let coverageBadges = '';
                if (package.coverage_scope) {
                    let coverageAreas = [];
                    if (Array.isArray(package.coverage_scope)) {
                        coverageAreas = package.coverage_scope;
                    } else if (typeof package.coverage_scope === 'string') {
                        try {
                            const parsed = JSON.parse(package.coverage_scope);
                            coverageAreas = Array.isArray(parsed) ? parsed : [package.coverage_scope];
                        } catch (e) {
                            coverageAreas = package.coverage_scope.split(',').map(item => item.trim());
                        }
                    } else {
                        coverageAreas = [String(package.coverage_scope)];
                    }
                    
                    coverageAreas.forEach(function(area) {
                        if (area && area.trim()) {
                            coverageBadges += '<span class="badge badge-soft-secondary fs-6 p-1 fw-medium me-1 mb-1">' + area.trim() + '</span>';
                        }
                    });
                }
                
                // Format status badge
                let statusBadge = package.status === 'active' 
                    ? '<span class="badge badge-soft-success px-2 fw-medium">Active</span>'
                    : '<span class="badge badge-soft-danger px-2 fw-medium">Inactive</span>';
                
                // Format online gallery badge
                let galleryBadge = package.online_gallery 
                    ? '<span class="badge badge-soft-success px-2 fw-medium"><i class="ti ti-check me-1"></i> Included</span>'
                    : '<span class="badge badge-soft-secondary px-2 fw-medium"><i class="ti ti-x me-1"></i> Not Included</span>';
                
                // Format time customization badge
                let timeCustomizationBadge = package.allow_time_customization 
                    ? '<span class="badge badge-soft-success px-2 fw-medium"><i class="ti ti-clock-edit me-1"></i> Flexible (Client can choose)</span>'
                    : '<span class="badge badge-soft-secondary px-2 fw-medium"><i class="ti ti-clock me-1"></i> Fixed Duration: ' + (package.duration || 0) + ' hours</span>';
                
                // ==== Start: Handle multiple locations ====
                let locations = [];
                if (package.package_location) {
                    if (Array.isArray(package.package_location)) {
                        locations = package.package_location;
                    } else if (typeof package.package_location === 'string') {
                        try {
                            const parsed = JSON.parse(package.package_location);
                            locations = Array.isArray(parsed) ? parsed : [package.package_location];
                        } catch (e) {
                            locations = [package.package_location];
                        }
                    } else {
                        locations = [String(package.package_location)];
                    }
                }
                
                let locationBadges = '';
                let locationDescriptions = [];
                
                if (locations.includes('In-Studio')) {
                    locationBadges += '<span class="badge badge-soft-primary px-2 fw-medium d-inline-block mb-1"><i class="ti ti-building me-1"></i> In-Studio</span> ';
                    locationDescriptions.push('Session takes place at the studio');
                }
                
                if (locations.includes('On-Location')) {
                    locationBadges += '<span class="badge badge-soft-info px-2 fw-medium d-inline-block mb-1 ms-1"><i class="ti ti-map-pin me-1"></i> On-Location</span> ';
                    locationDescriptions.push('Session takes place at client\'s location');
                }
                
                if (locations.length === 0) {
                    locationBadges = '<span class="badge badge-soft-secondary px-2 fw-medium"><i class="ti ti-minus me-1"></i> Not specified</span>';
                    locationDescriptions = ['Location not specified'];
                }
                // ==== End: Handle multiple locations ====
                
                // Format created date
                let createdDate = 'N/A';
                if (package.created_at) {
                    try {
                        createdDate = new Date(package.created_at).toLocaleDateString('en-US', { 
                            year: 'numeric', 
                            month: 'long', 
                            day: 'numeric' 
                        });
                    } catch (e) {
                        createdDate = package.created_at;
                    }
                }
                
                // Studio and category info
                let studioName = package.studio ? package.studio.studio_name : 'N/A';
                let categoryName = package.category ? package.category.category_name : 'N/A';
                
                // Generate modal HTML with updated location display
                return `
                <div class="row align-items-center mb-4">
                    <div class="col-12 col-lg-8">
                        <div class="text-center text-md-start">
                            <div>
                                <h2 class="mb-1 h3 h3-md">${package.package_name || 'Unnamed Package'}</h2>
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                    ${statusBadge}
                                    <span class="ms-2 text-muted small">Created: ${createdDate}</span>
                                </div>
                                
                                <p class="text-muted mb-0">
                                    <i class="ti ti-building me-1"></i> ${studioName} | 
                                    <i class="ti ti-category me-1 ms-2"></i> ${categoryName}
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
                                        <p class="mb-0 fw-medium">PHP ${package.package_price ? parseFloat(package.package_price).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0.00'}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light-primary rounded-circle p-2">
                                            <i class="ti ti-map-pin fs-20 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="text-muted small mb-1">Location Type</label>
                                        <div class="mb-0">
                                            ${locationBadges}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            ${locationDescriptions.join(' • ')}
                                        </small>
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
                                        <label class="text-muted small mb-1">Time Customization</label>
                                        <div class="mb-0 fw-medium">
                                            ${timeCustomizationBadge}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            ${package.allow_time_customization 
                                                ? 'Clients can choose their preferred duration during booking' 
                                                : 'Fixed duration package - clients must book for exactly ' + (package.duration || 0) + ' hours'}
                                        </small>
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
                                            ${galleryBadge}
                                        </div>
                                        <small class="text-muted d-block mt-1">Client access to digital gallery</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <div class="d-flex align-items-start">
                                    <div class="flex-shrink-0">
                                        <div class="bg-light-primary rounded-circle p-2">
                                            <i class="ti ti-users fs-20 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <label class="text-muted small mb-1">Assigned Photographers</label>
                                        <p class="mb-0 fw-medium">${package.photographer_count || 0} Photographer(s)</p>
                                        <small class="text-muted d-block mt-1">Studio photographers assigned to this package</small>
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

                        ${coverageBadges ? `
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
                                        <div class="mb-0">
                                            ${coverageBadges}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>`;
            }

            // View Package Details
            $(document).on('click', '.view-package-btn', function() {
                const packageId = $(this).data('package-id');
                const modal = new bootstrap.Modal(document.getElementById('viewPackageModal'));
                
                // Show modal
                modal.show();
                
                // Show loading spinner, hide content
                $('#packageLoading').show();
                $('#packageDetailsContent').hide().empty();
                
                // Build the URL correctly
                const url = "{{ route('owner.packages.show', ['package' => '__PACKAGE_ID__']) }}".replace('__PACKAGE_ID__', packageId);
                
                // Fetch package details via AJAX
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        if (response.success && response.data) {
                            // Hide loading spinner
                            $('#packageLoading').hide();
                            
                            // Generate HTML using JavaScript function
                            const packageHtml = generatePackageModalHtml(response.data);
                            
                            // Populate modal with package details
                            $('#packageDetailsContent').html(packageHtml).show();
                        } else {
                            throw new Error(response.message || 'Invalid response format');
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#packageLoading').hide();
                        
                        let errorMessage = 'Failed to load package details. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage,
                            confirmButtonColor: '#3475db',
                            confirmButtonText: 'OK'
                        });
                        modal.hide();
                    }
                });
            });
        });
    </script>
@endsection
