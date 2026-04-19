@extends('layouts.owner.app')
@section('title', 'View Registered Studios')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h5 class="card-title">List of Studios</h5>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search schedules...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">
                                    <i class="ti ti-filter me-1"></i>Filter By:
                                </span>
                                <div class="app-filter">
                                    <select data-table-filter="status" class="me-0 form-select form-control">
                                        <option value="">All Status</option>
                                        <option value="Verified">Verified</option>
                                        <option value="Pending">Pending</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Studio Name</th>
                                        <th data-table-sort>Studio Type</th>
                                        <th data-table-sort>Role</th>
                                        <th data-table-sort data-column="status">Status</th>
                                        <th data-table-sort>Registration Date</th>
                                        <th data-table-sort>Approved Date</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($studios as $studio)
                                    <tr>
                                        <td>
                                            <div class="d-flex">
                                                <div class="avatar-lg me-1">
                                                    @if($studio->studio_logo)
                                                        <img src="{{ asset('storage/' . $studio->studio_logo) }}" alt="{{ $studio->studio_name }}" class="img-fluid rounded">
                                                    @else
                                                        <img src="{{ asset('assets/uploads/profile_placeholder.jpg') }}" alt="Studio Logo" class="img-fluid rounded">
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="#" class="link-reset">{{ $studio->studio_name }}</a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">Studio Owner:</span>
                                                        <span class="text-muted">{{ $studio->user->first_name }} {{ $studio->user->last_name }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $studio->studio_type)) }}</td>
                                        <td>Studio Owner</td>
                                        <td>
                                            @switch($studio->status)
                                                @case('pending')
                                                    <span class="badge badge-soft-warning fs-8 px-1 w-100">PENDING</span>
                                                    @break
                                                @case('verified')
                                                    <span class="badge badge-soft-success fs-8 px-1 w-100">VERIFIED</span>
                                                    @break
                                                @case('active')
                                                    <span class="badge badge-soft-success fs-8 px-1 w-100">ACTIVE</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge badge-soft-danger fs-8 px-1 w-100">REJECTED</span>
                                                    @break
                                                @case('inactive')
                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100">INACTIVE</span>
                                                    @break
                                                @default
                                                    <span class="badge badge-soft-secondary fs-8 px-1 w-100">{{ strtoupper($studio->status) }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $studio->created_at->format('F d, Y') }}</td>
                                        <td>
                                            @if($studio->status === 'verified' || $studio->status === 'active')
                                                {{ $studio->updated_at->format('F d, Y') }}
                                            @else
                                                PENDING
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#studioModal{{ $studio->id }}">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
                                                
                                                {{-- Show Cancel button only for pending studios --}}
                                                @if($studio->status === 'pending')
                                                    <button type="button" class="btn btn-sm delete-studio" data-id="{{ $studio->id }}" data-name="{{ $studio->studio_name }}" title="Cancel Registration">
                                                        <i class="ti ti-cancel fs-lg"></i>
                                                    </button>
                                                @endif
                                                
                                                {{-- You can add an Edit button here in the future if needed --}}
                                                @if($studio->status === 'pending' || $studio->status === 'verified')
                                                    <a href="{{ route('owner.studio.edit', $studio->id) }}" class="btn btn-sm" title="Edit Studio">
                                                        <i class="ti ti-edit fs-lg"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="card-footer border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="studios"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    @foreach($studios as $studio)
        <div class="modal fade" id="studioModal{{ $studio->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold" id="studioModalLabel">
                            Studio Information - {{ $studio->studio_name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        @if($studio->studio_logo)
                                            <img src="{{ asset('storage/' . $studio->studio_logo) }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Studio Logo">
                                        @else
                                            <img src="{{ asset('assets/uploads/profile_placeholder.jpg') }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Studio Logo">
                                        @endif
                                    </div>
                                    
                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3 h3-md">{{ $studio->studio_name }}</h2>
                                        <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                            @switch($studio->status)
                                                @case('pending')
                                                    <span class="badge badge-soft-warning p-1">Pending</span>
                                                    @break
                                                @case('verified')
                                                    <span class="badge badge-soft-success p-1">Verified</span>
                                                    @break
                                                @case('active')
                                                    <span class="badge badge-soft-success p-1">Active</span>
                                                    @break
                                                @case('rejected')
                                                    <span class="badge badge-soft-danger p-1">Rejected</span>
                                                    @break
                                                @case('inactive')
                                                    <span class="badge badge-soft-secondary p-1">Inactive</span>
                                                    @break
                                            @endswitch
                                        </div>
                                        
                                        <p class="text-muted mb-0">
                                            <i class="ti ti-map-pin me-1"></i>
                                            @if($studio->location)
                                                {{ $studio->location->municipality }}, {{ $studio->location->province }}
                                            @endif
                                            | Established: {{ $studio->year_established }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">STUDIO IDENTIFICATION INFORMATION</h5>
                                    {{-- Studio Name --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="building" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Studio Name</label>
                                                <p class="mb-0 fw-medium">{{ $studio->studio_name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Studio Type --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="briefcase" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Studio Type</label>
                                                <p class="mb-0 fw-medium">{{ ucfirst(str_replace('_', ' ', $studio->studio_type)) }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Year Established --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="calendar" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Year Established</label>
                                                <p class="mb-0 fw-medium">{{ $studio->year_established }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Studio Description --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Studio Description</label>
                                                <p class="mb-0 fw-medium">{{ $studio->studio_description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">STUDIO OWNER INFORMATION</h5>
                                    {{-- Owner Name --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="user" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Owner Name</label>
                                                <p class="mb-0 fw-medium">{{ $studio->user->first_name }} {{ $studio->user->last_name }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Email Address --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="mail" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Email Address</label>
                                                <p class="mb-0 fw-medium">{{ $studio->user->email }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Mobile Number --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="phone" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Mobile Number</label>
                                                <p class="mb-0 fw-medium">{{ $studio->user->mobile_number }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- User Role --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="user-star" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">User Role</label>
                                                <p class="mb-0 fw-medium">Studio Owner</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">STUDIO CONTACT INFORMATION</h5>
                                    
                                    {{-- Contact Number --}}
                                    @if($studio->contact_number)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="phone" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Contact Number</label>
                                                <p class="mb-0 fw-medium">{{ $studio->contact_number }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Studio Email --}}
                                    @if($studio->studio_email)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="mail" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Studio Email</label>
                                                <p class="mb-0 fw-medium">{{ $studio->studio_email }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Facebook URL --}}
                                    @if($studio->facebook_url)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="facebook" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Facebook</label>
                                                <p class="mb-0 fw-medium">
                                                    <a href="{{ $studio->facebook_url }}" target="_blank" class="text-primary text-decoration-none">
                                                        View Facebook Page
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Instagram URL --}}
                                    @if($studio->instagram_url)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="instagram" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Instagram</label>
                                                <p class="mb-0 fw-medium">
                                                    <a href="{{ $studio->instagram_url }}" target="_blank" class="text-primary text-decoration-none">
                                                        View Instagram Profile
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    {{-- Website URL --}}
                                    @if($studio->website_url)
                                    <div class="col-12 col-md-4">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="globe" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Website</label>
                                                <p class="mb-0 fw-medium">
                                                    <a href="{{ $studio->website_url }}" target="_blank" class="text-primary text-decoration-none">
                                                        Visit Website
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                @if($studio->location)
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">STUDIO LOCATION INFORMATION</h5>
                                    {{-- Province --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="map" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Province</label>
                                                <p class="mb-0 fw-medium">{{ $studio->location->province }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Municipality --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Municipality</label>
                                                <p class="mb-0 fw-medium">{{ $studio->location->municipality }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Barangay --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="navigation" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Barangay</label>
                                                <p class="mb-0 fw-medium">{{ $studio->barangay ?? 'Not specified' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ZIP Code --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="hash" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">ZIP Code</label>
                                                <p class="mb-0 fw-medium">{{ $studio->location->zip_code }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Street Address --}}
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="home" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Street Address</label>
                                                <p class="mb-0 fw-medium">{{ $studio->street ?? 'Not specified' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">SERVICE AND BUSINESS INFORMATION</h5>                                    
                                    <div class="row g-2 mb-3">
                                        {{-- Service Categories --}}
                                        <div class="col-12 mb-3">
                                            <label class="text-muted small mb-1">Service Categories</label>
                                            <div class="list-group">
                                                @if($studio->categories && $studio->categories->count() > 0)
                                                    @foreach($studio->categories as $category)
                                                    <li class="list-group-item">
                                                        <div class="d-flex align-items-start">
                                                            <i class="ti ti-check text-success me-2 mt-1"></i>
                                                            <div>
                                                                <h5 class="mb-1 fw-semibold">{{ $category->category_name }}</h5>
                                                                <p class="text-muted mb-0">
                                                                    {{ $category->description ?: 'No description available' }}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </li>
                                                    @endforeach
                                                @else
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-x text-danger me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">No categories selected</h5>
                                                            <p class="text-muted mb-0">
                                                                This studio hasn't selected service categories yet.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </li>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Starting Price --}}
                                        @if($studio->starting_price)
                                        <div class="col-12 col-md-6 mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Starting Price</label>
                                                    <p class="mb-0 fw-medium">PHP {{ number_format($studio->starting_price, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if($studio->downpayment_percentage)
                                        <div class="col-12 col-md-6 mb-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="percent" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Downpayment Requirement</label>
                                                    <p class="mb-0 fw-medium">{{ number_format($studio->downpayment_percentage, 2) }}% of total booking amount</p>
                                                    <small class="text-muted">Clients must pay this percentage as downpayment when booking</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>

                                    {{-- Service Coverage Area --}}
                                    @if($studio->service_coverage_area)
                                    <div class="col-12 mb-3">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Service Coverage Area</label>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @php
                                                        $coverageAreas = $studio->service_coverage_area;

                                                        if (is_string($coverageAreas)) {
                                                            $decodedCoverageAreas = json_decode($coverageAreas, true);
                                                            $coverageAreas = is_array($decodedCoverageAreas) ? $decodedCoverageAreas : [$coverageAreas];
                                                        }

                                                        $coverageAreas = is_array($coverageAreas) ? $coverageAreas : [];
                                                    @endphp

                                                    @foreach($coverageAreas as $area)
                                                    <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">{{ $area }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">OPERATING INFORMATION</h5>
                                    
                                    {{-- Operating Days --}}
                                    @if($studio->operating_days)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="calendar-days" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Operating Days</label>
                                                <p class="mb-0 fw-medium">
                                                    @php
                                                        $days = $studio->operating_days;

                                                        if (is_string($days)) {
                                                            $decodedDays = json_decode($days, true);
                                                            $days = is_array($decodedDays) ? $decodedDays : [$days];
                                                        }

                                                        $days = is_array($days) ? $days : [];
                                                        $dayNames = array_map('ucfirst', $days);
                                                    @endphp
                                                    {{ implode(', ', $dayNames) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Operating Hours --}}
                                    @if($studio->start_time && $studio->end_time)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="clock" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Operating Hours</label>
                                                <p class="mb-0 fw-medium">
                                                    {{ date('g:i A', strtotime($studio->start_time)) }} – {{ date('g:i A', strtotime($studio->end_time)) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Maximum Clients Per Day --}}
                                    @if($studio->max_clients_per_day)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="users" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Maximum Clients Per Day</label>
                                                <p class="mb-0 fw-medium">{{ $studio->max_clients_per_day }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Advance Booking Days --}}
                                    @if($studio->advance_booking_days)
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Advance Booking Requirement</label>
                                                <p class="mb-0 fw-medium">At least {{ $studio->advance_booking_days }} day(s) before event</p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">VERIFICATION DOCUMENTS</h5>
                                    
                                    {{-- Business Permit --}}
                                    @if($studio->business_permit)
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="file-check" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Business Permit</label>
                                                <p class="mb-0 fw-medium">
                                                    <a href="{{ asset($studio->business_permit) }}" target="_blank" class="text-primary text-decoration-none">
                                                        View Business Permit
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    {{-- Owner ID Document --}}
                                    @if($studio->owner_id_document)
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="id-card" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Valid ID (Owner)</label>
                                                <p class="mb-0 fw-medium">
                                                    <a href="{{ asset('storage/' . $studio->owner_id_document) }}" target="_blank" class="text-primary text-decoration-none">
                                                        View ID Document
                                                    </a>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize Choices for multi-select
            function initializeChoices() {
                if (typeof Choices !== 'undefined') {
                    // Service coverage area
                    const coverageAreaSelect = document.querySelector('select[name="service_coverage_area[]"]');
                    if (coverageAreaSelect) {
                        new Choices(coverageAreaSelect, {
                            removeItemButton: true,
                            searchEnabled: true,
                            placeholder: true,
                            placeholderValue: 'Select coverage areas',
                            shouldSort: false
                        });
                    }

                    // Operating days
                    const operatingDaysSelect = document.querySelector('select[name="operating_days[]"]');
                    if (operatingDaysSelect) {
                        new Choices(operatingDaysSelect, {
                            removeItemButton: true,
                            searchEnabled: true,
                            placeholder: true,
                            placeholderValue: 'Select operating days',
                            shouldSort: false
                        });
                    }
                }
            }
            
            initializeChoices();

            // AJAX Form Submission
            $('#studioRegistrationForm').on('submit', function(e) {
                e.preventDefault();
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing...'
                );
                
                // Prepare form data - use the actual form data
                const formData = new FormData(this);
                
                // AJAX request
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            // Success SweetAlert
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                allowOutsideClick: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred. Please try again.';
                        let errors = {};
                        
                        if (xhr.status === 422) {
                            // Validation errors
                            errors = xhr.responseJSON.errors;
                            errorMessage = 'Please fix the following errors:';
                            
                            // Clear previous error messages
                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').hide();
                            
                            // Show field errors
                            $.each(errors, function(field, messages) {
                                // Handle array fields
                                const fieldName = field.replace(/\.\d+/, '');
                                const input = $('[name="' + fieldName + '"], [name="' + fieldName + '[]"]');
                                if (input.length) {
                                    input.addClass('is-invalid');
                                    const feedback = input.closest('.mb-3').find('.invalid-feedback');
                                    if (feedback.length) {
                                        feedback.text(messages.join(', ')).show();
                                    }
                                }
                            });
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Show error SweetAlert only if not field validation errors
                        if (Object.keys(errors).length === 0) {
                            Swal.fire({
                                title: 'Error!',
                                html: errorMessage,
                                icon: 'error',
                                confirmButtonColor: '#DC3545',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    complete: function() {
                        // Restore button state
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Remove invalid class on input change
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).closest('.mb-3').find('.invalid-feedback').hide();
            });
            
            // Bootstrap validation
            (function() {
                'use strict';
                window.addEventListener('load', function() {
                    var forms = document.getElementsByClassName('needs-validation');
                    var validation = Array.prototype.filter.call(forms, function(form) {
                        form.addEventListener('submit', function(event) {
                            if (form.checkValidity() === false) {
                                event.preventDefault();
                                event.stopPropagation();
                            }
                            form.classList.add('was-validated');
                        }, false);
                    });
                }, false);
            })();
        });

        // Delete studio functionality
        $(document).on('click', '.delete-studio', function(e) {
            e.preventDefault();
            
            const studioId = $(this).data('id');
            const studioName = $(this).data('name');
            
            Swal.fire({
                title: 'Cancel Studio Registration?',
                html: `Are you sure you want to cancel the registration of <strong>${studioName}</strong>? This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC3545',
                cancelButtonColor: '#6C757D',
                confirmButtonText: 'Yes, Cancel Registration',
                cancelButtonText: 'No, Keep It'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Canceling...',
                        text: 'Please wait while we cancel the studio registration.',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // AJAX request to delete studio
                    $.ajax({
                        url: '/owner/studio/' + studioId,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Success!',
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonColor: response.alert_color,
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true,
                                    allowOutsideClick: false
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        },
                        error: function(xhr) {
                            let errorMessage = 'Failed to cancel the studio. Please try again.';
                            
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }
                            
                            Swal.fire({
                                title: 'Error!',
                                text: errorMessage,
                                icon: 'error',
                                confirmButtonColor: '#DC3545'
                            });
                        }
                    });
                }
            });
        });
    </script>
@endsection
