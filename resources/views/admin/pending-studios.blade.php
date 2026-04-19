@extends('layouts.admin.app')
@section('title', 'Pending Studios')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Pending Studios</h4>
                        </div>

                        <div class="card-header border-light justify-content-between">
                            <div class="d-flex gap-2">
                                <div class="app-search">
                                    <input data-table-search type="search" class="form-control" placeholder="Search...">
                                    <i data-lucide="search" class="app-search-icon text-muted"></i>
                                </div>
                                <button class="btn btn-danger d-none">Delete</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Studio Name</th>
                                        <th data-table-sort>Studio Type</th>
                                        <th data-table-sort>Role</th>
                                        <th data-table-sort>Status</th>
                                        <th data-table-sort>Registration Date</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($studios as $studio)
                                    <tr>
                                        <td>
                                            <div class="d-flex">
                                                <div class="avatar-lg me-1">
                                                    @if($studio->studio_logo)
                                                        <img src="{{ asset('storage/' . $studio->studio_logo) }}" alt="{{ $studio->studio_name }}" class="img-fluid rounded">
                                                    @else
                                                        <img src="{{ asset('assets/images/products/1.png') }}" alt="Studio Logo" class="img-fluid rounded">
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">
                                                        <a href="#" class="link-reset">{{ $studio->studio_name }}</a>
                                                    </h5>
                                                    <p class="mb-0 fs-xxs">
                                                        <span class="fw-medium">ID:</span>
                                                        <span class="text-muted">{{ $studio->id }}</span>
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
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#studioModal{{ $studio->id }}">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
                                                {{-- <button type="button" class="btn btn-sm delete-studio" data-id="{{ $studio->id }}">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button> --}}
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
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
                                            <img src="{{ asset('assets/images/sellers/7.png') }}" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Studio Logo">
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
                                                    <span class="badge badge-success-info p-1">Verified</span>
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

                                @if($studio->contact_number || $studio->studio_email || $studio->facebook_url || $studio->instagram_url || $studio->website_url)
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
                                    <div class="col-12 col-md-6">
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
                                @endif

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

                                    {{-- Owner UUID --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="key-round" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">UUID</label>
                                                <p class="mb-0 fw-medium text-truncate">{{ $studio->user->uuid }}</p>
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

                                    {{-- Account Status --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i data-lucide="shield-check" class="fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Account Status</label>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    @if($studio->user->email_verified)
                                                        <span class="badge badge-soft-success px-2 fw-medium">Verified</span>
                                                        <span class="text-muted small">Email verified</span>
                                                    @else
                                                        <span class="badge badge-soft-warning px-2 fw-medium">Unverified</span>
                                                    @endif
                                                </div>
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
                                                <p class="mb-0 fw-medium">{{ ucfirst($studio->user->role) }}</p>
                                            </div>
                                        </div>
                                    </div>
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
                                    <h5 class="card-title text-primary">SERVICE INFORMATION</h5>
                                    
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
                                    <div class="col-12 mb-3">
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
                                                        $days = is_array($studio->operating_days)
                                                            ? $studio->operating_days
                                                            : (json_decode($studio->operating_days, true) ?? []);
                                                        $dayNames = array_map('ucfirst', $days);
                                                        echo implode(', ', $dayNames);
                                                    @endphp
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
                                                    <a href="{{ asset('storage/' . $studio->business_permit) }}" target="_blank" class="text-primary text-decoration-none">
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
                                
                                @if($studio->status === 'pending')
                                <div class="row mt-4">
                                    <div class="col text-end">
                                        <button type="button" class="btn btn-soft-danger me-2" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $studio->id }}">
                                            Reject Studio
                                        </button>
                                        <button type="button" class="btn btn-primary approve-studio" data-id="{{ $studio->id }}">
                                            Approve Studio
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rejection Modal --}}
        <div class="modal fade" id="rejectModal{{ $studio->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-semibold">Reject Studio</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="rejectForm{{ $studio->id }}" class="reject-form">
                        @csrf
                        <div class="modal-body">
                            <p class="mb-3">You are about to reject <strong>{{ $studio->studio_name }}</strong>. Please provide a reason for rejection.</p>
                            
                            <div class="mb-3">
                                <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="rejection_note" rows="4" placeholder="Please explain why this studio registration is being rejected..." required minlength="10"></textarea>
                                <div class="form-text">Minimum 10 characters required.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-soft-danger" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger reject-studio" data-id="{{ $studio->id }}">Confirm Rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Approve studio
            $(document).on('click', '.approve-studio', function(e) {
                e.preventDefault();
                const studioId = $(this).data('id');
                const approveButton = $(this); // Store reference to the button
                
                Swal.fire({
                    title: 'Approve Studio?',
                    text: "Are you sure you want to approve this studio registration?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#007BFF',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, approve it!',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true, // Show loader in SweetAlert
                    preConfirm: () => {
                        // Add spinner to the button
                        approveButton.html(`
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Approving...
                        `);
                        approveButton.prop('disabled', true);
                        
                        return $.ajax({
                            url: "{{ route('admin.studio.approve', ':id') }}".replace(':id', studioId),
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).then(response => {
                            if (response.success) {
                                return response;
                            } else {
                                throw new Error(response.message || 'Failed to approve studio.');
                            }
                        }).catch(error => {
                            // Reset button on error
                            approveButton.html('Approve Studio');
                            approveButton.prop('disabled', false);
                            Swal.showValidationMessage(`Request failed: ${error.responseJSON?.message || error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value && result.value.success) {
                            Swal.fire({
                                title: 'Approved!',
                                text: result.value.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true
                            }).then(() => {
                                location.reload();
                            });
                        }
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        // Reset button if user cancels
                        approveButton.html('Approve Studio');
                        approveButton.prop('disabled', false);
                    }
                }).catch(error => {
                    // Reset button on any error
                    approveButton.html('Approve Studio');
                    approveButton.prop('disabled', false);
                });
            });

            // Reject studio
            $(document).on('submit', '.reject-form', function(e) {
                e.preventDefault();
                const form = $(this);
                const studioId = form.find('.reject-studio').data('id');
                const formData = form.serialize();
                
                Swal.fire({
                    title: 'Reject Studio?',
                    text: "Are you sure you want to reject this studio registration?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#DC3545',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, reject it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('admin.studio.reject', ':id') }}".replace(':id', studioId),
                            type: 'POST',
                            data: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Close modals
                                    $('#rejectModal' + studioId).modal('hide');
                                    $('#studioModal' + studioId).modal('hide');
                                    
                                    Swal.fire({
                                        title: 'Rejected!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonColor: response.alert_color,
                                        confirmButtonText: 'OK',
                                        allowOutsideClick: false
                                    }).then(() => {
                                        location.reload();
                                    });
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred. Please try again.';
                                
                                if (xhr.status === 422) {
                                    const errors = xhr.responseJSON.errors;
                                    if (errors.rejection_note) {
                                        errorMessage = errors.rejection_note[0];
                                    }
                                } else if (xhr.responseJSON?.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                
                                Swal.fire({
                                    title: 'Error!',
                                    text: errorMessage,
                                    icon: 'error',
                                    confirmButtonColor: '#DC3545',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    }
                });
            });

            // Delete studio
            // $(document).on('click', '.delete-studio', function(e) {
            //     e.preventDefault();
            //     const studioId = $(this).data('id');
                
            //     Swal.fire({
            //         title: 'Delete Studio?',
            //         text: "Are you sure you want to delete this studio? This action cannot be undone.",
            //         icon: 'warning',
            //         showCancelButton: true,
            //         confirmButtonColor: '#DC3545',
            //         cancelButtonColor: '#6C757D',
            //         confirmButtonText: 'Yes, delete it!',
            //         cancelButtonText: 'Cancel'
            //     }).then((result) => {
            //         if (result.isConfirmed) {
            //             $.ajax({
            //                 url: "{{ route('admin.studio.destroy', ':id') }}".replace(':id', studioId),
            //                 type: 'DELETE',
            //                 headers: {
            //                     'X-CSRF-TOKEN': '{{ csrf_token() }}'
            //                 },
            //                 success: function(response) {
            //                     if (response.success) {
            //                         Swal.fire({
            //                             title: 'Deleted!',
            //                             text: response.message,
            //                             icon: 'success',
            //                             confirmButtonColor: response.alert_color,
            //                             confirmButtonText: 'OK',
            //                             allowOutsideClick: false
            //                         }).then(() => {
            //                             location.reload();
            //                         });
            //                     }
            //                 },
            //                 error: function(xhr) {
            //                     Swal.fire({
            //                         title: 'Error!',
            //                         text: xhr.responseJSON?.message || 'Failed to delete studio.',
            //                         icon: 'error',
            //                         confirmButtonColor: '#DC3545',
            //                         confirmButtonText: 'OK'
            //                     });
            //                 }
            //             });
            //         }
            //     });
            // });
        });
    </script>
@endsection
