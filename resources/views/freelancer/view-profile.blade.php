@extends('layouts.freelancer.app')
@section('title', 'My Profile')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Your Freelance Profile</h4>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center mb-4">
                                <div class="col-12">
                                    <div class="d-flex align-items-center flex-column flex-md-row">
                                        <div class="flex-shrink-0 mb-3 mb-md-0">
                                            <img src="{{ $profile->user->profile_photo ? asset('storage/' . $profile->user->profile_photo) : asset('assets/images/stock/gallery-2.jpg') }}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;" alt="{{ $profile->user->first_name . ' ' . $profile->user->last_name }}">
                                        </div>

                                        <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                            <h3 class="mb-1 fw-bold">{{ $profile->user->first_name . ' ' . $profile->user->last_name }}</h3>
                                            <p class="text-primary fw-semibold mb-1">{{ $profile->brand_name }}</p>
                                            <p class="text-muted mb-0">
                                                <i class="ti ti-map-pin me-1"></i> 
                                                {{ $profile->location->municipality ?? 'N/A' }}, {{ $profile->location->province ?? 'N/A' }} 
                                                · Since {{ $profile->created_at->format('Y') }}
                                            </p>
                                            <div class="mt-2">
                                                <span class="badge badge-soft-success fs-6">
                                                    <i data-lucide="badge-check" class="me-1"></i>Verified Freelancer
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Personal Information</h5>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="user" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Full Name</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->user->first_name . ' ' . $profile->user->last_name }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="mail" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Email Address</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->user->email }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="phone" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Mobile Number</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->user->mobile_number }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Brand Identity</h5>
                                    <div class="row g-3">
                                        @if($profile->brand_logo)
                                        <div class="col-12 mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ asset('storage/' . $profile->brand_logo) }}" class="rounded" style="width: 100px; height: 100px; object-fit: cover;" alt="{{ $profile->brand_name }}">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Brand Logo</label>
                                                    <h3 class="mb-0 fw-bold">{{ $profile->brand_name }}</h3>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="building" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Brand Name</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->brand_name }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        @if($profile->tagline)
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="info" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Professional Tagline</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->tagline }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">About Me</label>
                                                    <p class="mb-0">{{ $profile->bio }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Years of Experience</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->years_experience }} {{ Str::plural('year', $profile->years_experience) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Location & Service Coverage</h5>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="map" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Province</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->location->province ?? 'N/A' }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="map-pin" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Municipality / Barangay</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->location->municipality ?? 'N/A' }} · {{ $profile->barangay }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="home" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Street Address</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->street }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="navigation" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Service Coverage</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->service_area }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Services & Pricing</h5>
                                    <div class="row g-3">
                                        {{-- Display Services from tbl_freelancer_services --}}
                                        @if($profile->services->count() > 0)
                                        <div class="col-12 mb-3">
                                            <label class="text-muted small mb-1">My Services</label>
                                            <div class="list-group">
                                                @foreach($profile->services as $service)
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h5 class="mb-1 fw-semibold">
                                                                {{ $service->category->category_name ?? 'Services' }}
                                                            </h5>
                                                            
                                                            @if($service->services_name)
                                                                <ul class="mb-2 ps-3">
                                                                    @php
                                                                        // Handle both array and JSON string formats
                                                                        $serviceNames = is_array($service->services_name) 
                                                                            ? $service->services_name 
                                                                            : json_decode($service->services_name, true);
                                                                        
                                                                        // Ensure we have an array
                                                                        $serviceNames = is_array($serviceNames) ? $serviceNames : [$service->services_name];
                                                                    @endphp
                                                                    
                                                                    @foreach($serviceNames as $serviceName)
                                                                        <li class="mb-1 text-muted">{{ $serviceName }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </div>
                                        </div>
                                        @else
                                        @if($profile->categories->count() > 0)
                                        <div class="col-12 mb-3">
                                            <label class="text-muted small mb-1">Service Categories</label>
                                            <div class="list-group">
                                                @foreach($profile->categories as $category)
                                                <li class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div>
                                                            <h5 class="mb-1 fw-semibold">{{ $category->category_name }}</h5>
                                                            @if($category->description)
                                                            <p class="text-muted mb-0">{{ $category->description }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </li>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                        @endif

                                        {{-- Starting Price --}}
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Starting Price</label>
                                                    <p class="mb-0 fs-5">PHP {{ number_format($profile->starting_price, 2) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                @if($profile->schedule)
                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Availability and Schedule</h5>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="calendar-days" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Operating Days</label>
                                                    @php
                                                        $days = is_array($profile->schedule->operating_days)
                                                            ? $profile->schedule->operating_days
                                                            : json_decode($profile->schedule->operating_days, true);
                                                        $dayNames = [
                                                            'monday' => 'Monday',
                                                            'tuesday' => 'Tuesday',
                                                            'wednesday' => 'Wednesday',
                                                            'thursday' => 'Thursday',
                                                            'friday' => 'Friday',
                                                            'saturday' => 'Saturday',
                                                            'sunday' => 'Sunday'
                                                        ];
                                                        $displayDays = [];
                                                        if (is_array($days)) {
                                                            foreach ($days as $day) {
                                                                if (isset($dayNames[$day])) {
                                                                    $displayDays[] = $dayNames[$day];
                                                                }
                                                            }
                                                        }
                                                    @endphp
                                                    <p class="mb-0 fw-medium">{{ implode(', ', $displayDays) }}</p>
                                                </div>
                                            </div>
                                        </div>

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
                                                        {{ date('h:i A', strtotime($profile->schedule->start_time)) }} – 
                                                        {{ date('h:i A', strtotime($profile->schedule->end_time)) }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="users" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Max Clients per Day</label>
                                                    <p class="mb-0 fw-medium">{{ $profile->schedule->booking_limit }} {{ Str::plural('client', $profile->schedule->booking_limit) }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Advance Booking</label>
                                                    <p class="mb-0 fw-medium">Minimum {{ $profile->schedule->advance_booking }} {{ Str::plural('day', $profile->schedule->advance_booking) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @php
                                    $portfolioWorks = is_array($profile->portfolio_works) ? $profile->portfolio_works : json_decode($profile->portfolio_works, true);
                                @endphp

                                @if(!empty($portfolioWorks) && is_array($portfolioWorks) && count($portfolioWorks) > 0)
                                <div class="row g-4 mb-4">
                                    <div class="col-12">
                                        <h5 class="text-primary fw-semibold mb-3">Personal Portfolio</h5>
                                        <p class="text-muted">Sample works and recent projects</p>

                                        <div id="portfolioCarousel" class="carousel slide" data-bs-ride="carousel">
                                            <div class="carousel-inner">
                                                @foreach($portfolioWorks as $index => $portfolio)
                                                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                                                    <img src="{{ asset('storage/' . $portfolio) }}" 
                                                        class="d-block w-100 rounded shadow" 
                                                        alt="Portfolio {{ $index + 1 }}" 
                                                        style="height: 400px; object-fit: cover;">
                                                </div>
                                                @endforeach
                                            </div>
                                            @if(count($portfolioWorks) > 1)
                                            <button class="carousel-control-prev" type="button" data-bs-target="#portfolioCarousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#portfolioCarousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if($profile->facebook_url || $profile->instagram_url || $profile->website_url)
                                <div class="col-12">
                                    <h5 class="text-primary fw-semibold mb-3">Social Media and Portfolio Links</h5>
                                    <div class="row g-3">
                                        @if($profile->facebook_url)
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="facebook" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Facebook Link</label>
                                                    <p class="mb-0 fw-medium">
                                                        <a href="{{ $profile->facebook_url }}" target="_blank" class="text-decoration-none">
                                                            {{ $profile->facebook_url }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if($profile->instagram_url)
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="instagram" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Instagram Link</label>
                                                    <p class="mb-0 fw-medium">
                                                        <a href="{{ $profile->instagram_url }}" target="_blank" class="text-decoration-none">
                                                            {{ $profile->instagram_url }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if($profile->website_url)
                                        <div class="col-12">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0">
                                                    <div class="bg-light-primary rounded-circle p-2">
                                                        <i data-lucide="globe" class="fs-20 text-primary"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <label class="text-muted small mb-1">Website Link</label>
                                                    <p class="mb-0 fw-medium">
                                                        <a href="{{ $profile->website_url }}" target="_blank" class="text-decoration-none">
                                                            {{ $profile->website_url }}
                                                        </a>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
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
@endsection