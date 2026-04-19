@extends('layouts.admin.app')
@section('title', 'Registered Studios')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">List of Registered Studios</h4>
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
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#studioModal{{ $studio->id }}">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
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
                                                <p class="mb-0 fw-medium">{{ $studio->location->street }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">SERVICE AND BUSINESS INFORMATION</h5>
                                    
                                    {{-- Service Category --}}
                                    <div class="col-12">
                                        <label class="text-muted small mb-1">Photography Category</label>
                                        <div class="list-group">
                                            @if($studio->category)
                                            <li class="list-group-item">
                                                <div class="d-flex align-items-start">
                                                    <i class="ti ti-check text-success me-2 mt-1"></i>
                                                    <div>
                                                        <h5 class="mb-1 fw-semibold">{{ $studio->category->category_name }}</h5>
                                                        <p class="text-muted mb-0">
                                                            {{ $studio->category->description ?: 'No description available' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                            @else
                                            <li class="list-group-item">
                                                <div class="d-flex align-items-start">
                                                    <i class="ti ti-x text-danger me-2 mt-1"></i>
                                                    <div>
                                                        <h5 class="mb-1 fw-semibold">No category selected</h5>
                                                        <p class="text-muted mb-0">
                                                            This studio hasn't selected a service category yet.
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                            @endif
                                        </div>
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
                                                    @foreach(is_array($studio->service_coverage_area) ? $studio->service_coverage_area : (json_decode($studio->service_coverage_area, true) ?? []) as $area)
                                                    <span class="badge badge-soft-secondary fs-6 p-1 fw-medium">{{ $area }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
