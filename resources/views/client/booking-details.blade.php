@extends('layouts.client.app')
@section('title', 'Booking Details')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-lg-8">
                                    <div class="d-flex align-items-center flex-column flex-md-row">
                                        <div class="flex-shrink-0 mb-3 mb-md-0">
                                            @if($type === 'studio')
                                            <img src="{{ $studio->studio_logo ? asset('storage/' . $studio->studio_logo) : asset('assets/images/sellers/7.png') }}"  
                                                 class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Studio Logo">
                                            @else
                                            <img src="{{ $freelancer->brand_logo ? asset('storage/' . $freelancer->brand_logo) : asset('assets/images/sellers/3.png') }}"  
                                                 class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Freelancer Logo">
                                            @endif
                                        </div>
                                        
                                        <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                            <h2 class="mb-1 h4 h3-md">
                                                @if($type === 'studio')
                                                    {{ $studio->studio_name }}
                                                @else
                                                    {{ $freelancer->brand_name }}
                                                @endif
                                            </h2>
                                            <div class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                                <span class="text-warning me-2">
                                                    @php
                                                        $avgRating = $type === 'studio' ? ($studio->average_rating ?? 0) : ($freelancer->average_rating ?? 0);
                                                        $fullStars = floor($avgRating);
                                                        $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                                                        $emptyStars = 5 - $fullStars - $halfStar;
                                                    @endphp
                                                    
                                                    @for($i = 0; $i < $fullStars; $i++)
                                                        <i class="ti ti-star-filled fs-6"></i>
                                                    @endfor
                                                    
                                                    @if($halfStar)
                                                        <i class="ti ti-star-half-filled fs-6"></i>
                                                    @endif
                                                    
                                                    @for($i = 0; $i < $emptyStars; $i++)
                                                        <i class="ti ti-star fs-6"></i>
                                                    @endfor
                                                </span>
                                                <span class="text-muted me-2">
                                                    {{ number_format($avgRating, 1) }} 
                                                    ({{ $type === 'studio' ? ($studio->ratings_count ?? 0) : ($freelancer->ratings_count ?? 0) }} 
                                                    {{ ($type === 'studio' ? ($studio->ratings_count ?? 0) : ($freelancer->ratings_count ?? 0)) == 1 ? 'review' : 'reviews' }})
                                                </span>
                                                <span class="badge badge-soft-success p-1">
                                                    {{ $type === 'studio' ? 'Verified Studio' : 'Verified Freelancer' }}
                                                </span>
                                            </div>
                                            
                                            <p class="text-muted mb-0 small">
                                                <i class="ti ti-map-pin me-1"></i> 
                                                @if($type === 'studio')
                                                    {{ $studio->location ? $studio->location->municipality . ', Cavite' : 'Location not specified' }} | 
                                                    Established: {{ $studio->year_established }}
                                                @else
                                                    {{ $freelancer->location ? $freelancer->location->municipality . ', Cavite' : 'Location not specified' }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-12 col-lg-4 mt-3 mt-lg-0">
                                    <div class="d-flex flex-column gap-2 align-items-center align-items-lg-end">
                                        <div class="text-center text-lg-end">
                                            <span class="text-muted d-block">Starting Price at</span>
                                            <h3 class="text-success mb-0 h4">
                                                PHP 
                                                @if($type === 'studio')
                                                    {{ number_format($studio->starting_price, 2) }}
                                                @else
                                                    {{ number_format($freelancer->starting_price, 2) }}
                                                @endif
                                            </h3>
                                        </div>
                                        <a href="{{ route('client.booking-forms', ['type' => $type, 'id' => $type === 'studio' ? $studio->id : $freelancer->user_id]) }}" class="btn btn-primary w-md-auto">
                                            <i class="ti ti-calendar-check me-2"></i> Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        {{-- LEFT SIDE --}}
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-body">
                                    {{-- ABOUT --}}
                                    <div class="row mb-3">
                                        <h5 class="card-title mb-2 text-primary">
                                            {{ $type === 'studio' ? 'About Our Studio' : 'About Me' }}
                                        </h5>
                                        <p class="mb-0">
                                            @if($type === 'studio')
                                                {{ $studio->studio_description ?: 'No description available.' }}
                                            @else
                                                {{ $freelancer->bio ?: 'No bio available.' }}
                                            @endif
                                        </p>
                                    </div>

                                    {{-- PACKAGES --}}
                                    <div class="mb-4">
                                        <h5 class="card-title text-primary mb-3">List of Packages</h5>

                                        <div class="row">
                                            <div class="col">
                                                <div class="form-group mb-3">
                                                    <label class="form-label">Select Service Package</label>
                                                    <select class="form-select" id="packageCategory" aria-label="Select service category">
                                                        <option value="">All Categories</option>
                                                        @foreach($categories as $category)
                                                        <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="packages-container">
                                            @php
                                                $packagesData = $type === 'studio' ? $studioPackages : $freelancerPackages;
                                            @endphp
                                            
                                            @foreach($packagesData as $categoryId => $packages)
                                            <div class="package-category mb-4" data-category="{{ $categoryId }}">
                                                <h4 class="mb-2 text-primary">{{ $packages->first()->category->category_name ?? 'Packages' }}</h4>
                                                <div class="row g-3">
                                                    @foreach($packages as $package)
                                                        <div class="col-md-6 col-xl-4">
                                                            <div class="card border h-100 package-card">
                                                                <div class="card-body">
                                                                    <!-- Package Name & Price -->
                                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                                        <h6 class="card-title fw-bold mb-0">{{ $package->package_name }}</h6>
                                                                        <span class="text-success fw-bold">₱{{ number_format($package->package_price, 2) }}</span>
                                                                    </div>
                                                                    
                                                                    <!-- Package Description -->
                                                                    <p class="text-muted small mb-3">{{ $package->package_description ?: 'No description available.' }}</p>
                                                                    
                                                                    <!-- ==== START: Display package locations correctly ==== -->
                                                                    <div class="d-flex align-items-center flex-wrap mb-2">
                                                                        @php
                                                                            $locations = $package->package_location ?? null;
                                                                            
                                                                            // Decode JSON if it's a string
                                                                            if (is_string($locations)) {
                                                                                $locations = json_decode($locations, true);
                                                                            }
                                                                            
                                                                            // Ensure it's an array
                                                                            if (!is_array($locations)) {
                                                                                $locations = $locations ? [$locations] : ['In-Studio'];
                                                                            }
                                                                        @endphp
                                                                        
                                                                        @foreach($locations as $location)
                                                                            @php $location = trim($location, '"\' '); @endphp
                                                                            @if($location === 'On-Location')
                                                                                <span class="badge badge-soft-info me-1 mb-1">
                                                                                    <i class="ti ti-map-pin me-1"></i> On-Location
                                                                                </span>
                                                                            @elseif($location === 'In-Studio')
                                                                                <span class="badge badge-soft-primary me-1 mb-1">
                                                                                    <i class="ti ti-building me-1"></i> In-Studio
                                                                                </span>
                                                                            @else
                                                                                <span class="badge badge-soft-secondary me-1 mb-1">{{ $location }}</span>
                                                                            @endif
                                                                        @endforeach
                                                                        
                                                                        @if(count($locations) > 1)
                                                                            <span class="badge badge-soft-success me-1 mb-1">
                                                                                <i class="ti ti-arrows-maximize me-1"></i> Flexible
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <!-- ==== END: Display package locations correctly ==== -->
                                                                    
                                                                    <!-- ==== Start: Display package flexibility ==== -->
                                                                    <div class="d-flex align-items-center mb-2">
                                                                        @if($package->allow_time_customization)
                                                                            <span class="badge badge-soft-success">
                                                                                <i class="ti ti-clock-edit me-1"></i> Flexible Time
                                                                            </span>
                                                                        @else
                                                                            @php
                                                                                $durationDisplay = $package->duration ? $package->duration . ($package->duration > 1 ? ' hours' : ' hour') : 'Fixed Duration';
                                                                            @endphp
                                                                            <span class="badge badge-soft-secondary">
                                                                                <i class="ti ti-clock me-1"></i> {{ $durationDisplay }}
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                    <!-- ==== End: Display package flexibility ==== -->
                                                                    
                                                                    @if($type === 'studio')
                                                                        <div class="d-flex align-items-center mb-2">
                                                                            @if($package->online_gallery)
                                                                                <span class="p-1 badge badge-soft-success">
                                                                                    <i class="ti ti-photo me-1"></i> Online Gallery: Included
                                                                                </span>
                                                                            @else
                                                                                <span class="p-1 badge badge-soft-warning">
                                                                                    <i class="ti ti-photo-off me-1"></i> Online Gallery: Not Included
                                                                                </span>
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    @if($type === 'studio')
                                                                        <div class="d-flex align-items-center mb-3">
                                                                            <span class="p-1 badge badge-soft-primary">
                                                                                <i class="ti ti-users me-1"></i> 
                                                                                Photographers: {{ $package->photographer_count ?? 1 }} 
                                                                                @if(($package->photographer_count ?? 1) > 1) photographers @else photographer @endif
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <!-- Package Features -->
                                                                    <div class="col">
                                                                        <small class="text-muted d-block mb-2"><i class="ti ti-checklist me-1"></i> Package Includes:</small>
                                                                        <ul class="list-unstyled small mb-0">
                                                                            @if(!$package->allow_time_customization)
                                                                                <li class="mb-1">
                                                                                    <i class="ti ti-clock text-primary me-2"></i> 
                                                                                    @if($package->duration)
                                                                                        Fixed Duration: {{ $package->duration }} {{ $package->duration > 1 ? 'hours' : 'hour' }}
                                                                                    @else
                                                                                        Fixed Duration Package
                                                                                    @endif
                                                                                </li>
                                                                            @else
                                                                                <li class="mb-1">
                                                                                    <i class="ti ti-clock-edit text-success me-2"></i> 
                                                                                    Flexible Time: Client can choose any duration
                                                                                </li>
                                                                            @endif
                                                                            
                                                                            @if($package->maximum_edited_photos)
                                                                                <li class="mb-1">
                                                                                    <i class="ti ti-camera text-primary me-2"></i> 
                                                                                    {{ $package->maximum_edited_photos }} edited photos
                                                                                </li>
                                                                            @endif
                                                                            
                                                                            @if($package->package_inclusions && is_array($package->package_inclusions))
                                                                                @foreach($package->package_inclusions as $inclusion)
                                                                                    <li class="mb-1">
                                                                                        <i class="ti ti-check text-success me-2"></i> 
                                                                                        {{ $inclusion }}
                                                                                    </li>
                                                                                @endforeach
                                                                            @endif
                                                                            
                                                                            @if($package->coverage_scope)
                                                                                <li class="mb-1">
                                                                                    <i class="ti ti-map-pin text-primary me-2"></i> 
                                                                                    Coverage: {{ $package->coverage_scope }}
                                                                                </li>
                                                                            @endif
                                                                        </ul>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        @endforeach
                                                </div>
                                            </div>
                                            @endforeach
                                        </div>

                                        @if($packagesData->isEmpty())
                                        <div class="alert alert-info">
                                            <i class="ti ti-info-circle me-2"></i> No packages available.
                                        </div>
                                        @endif
                                    </div>

                                    {{-- OPERATING HOURS / AVAILABILITY --}}
                                    <div class="row mb-3">
                                        <h5 class="card-title mb-3 text-primary">
                                            {{ $type === 'studio' ? 'Operating Hours' : 'Availability' }}
                                        </h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Day</th>
                                                        <th>Opening Time</th>
                                                        <th>Closing Time</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if($type === 'studio')
                                                        @if($studio->schedules && $studio->schedules->isNotEmpty())
                                                            @php
                                                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                                $schedule = $studio->schedules->first();
                                                                
                                                                // Ensure operating_days is always an array
                                                                $operatingDays = $schedule->operating_days ?? [];
                                                                
                                                                // If it's a string, try to decode it
                                                                if (is_string($operatingDays)) {
                                                                    $decoded = json_decode($operatingDays, true);
                                                                    $operatingDays = is_array($decoded) ? $decoded : [];
                                                                }
                                                                
                                                                // If it's not an array, make it an empty array
                                                                if (!is_array($operatingDays)) {
                                                                    $operatingDays = [];
                                                                }
                                                            @endphp
                                                            
                                                            @foreach($days as $day)
                                                            <tr>
                                                                <td>{{ ucfirst($day) }}</td>
                                                                @if(in_array($day, $operatingDays))
                                                                <td>{{ $schedule->opening_time ? \Carbon\Carbon::parse($schedule->opening_time)->format('h:i A') : 'N/A' }}</td>
                                                                <td>{{ $schedule->closing_time ? \Carbon\Carbon::parse($schedule->closing_time)->format('h:i A') : 'N/A' }}</td>
                                                                <td><span class="badge badge-soft-primary w-100">AVAILABLE</span></td>
                                                                @else
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td><span class="badge badge-soft-danger w-100">UNAVAILABLE</span></td>
                                                                @endif
                                                            </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">
                                                                    No operating schedule available.
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @else
                                                        {{-- Freelancer Availability --}}
                                                        @if($freelancer->schedule)
                                                            @php
                                                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                                                $schedule = $freelancer->schedule;
                                                                $operatingDays = $schedule->operating_days ?? [];
                                                                
                                                                // Ensure operating_days is always an array
                                                                if (is_string($operatingDays)) {
                                                                    $decoded = json_decode($operatingDays, true);
                                                                    $operatingDays = is_array($decoded) ? $decoded : [];
                                                                }
                                                                
                                                                if (!is_array($operatingDays)) {
                                                                    $operatingDays = [];
                                                                }
                                                            @endphp
                                                            
                                                            @foreach($days as $day)
                                                            <tr>
                                                                <td>{{ ucfirst($day) }}</td>
                                                                @if(in_array($day, $operatingDays))
                                                                <td>{{ $schedule->start_time ? \Carbon\Carbon::parse($schedule->start_time)->format('h:i A') : 'N/A' }}</td>
                                                                <td>{{ $schedule->end_time ? \Carbon\Carbon::parse($schedule->end_time)->format('h:i A') : 'N/A' }}</td>
                                                                <td><span class="badge badge-soft-primary w-100">AVAILABLE</span></td>
                                                                @else
                                                                <td>-</td>
                                                                <td>-</td>
                                                                <td><span class="badge badge-soft-danger w-100">UNAVAILABLE</span></td>
                                                                @endif
                                                            </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="4" class="text-center text-muted">
                                                                    No schedule available.
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{-- LOCATION --}}
                                    <div class="row mb-3">
                                        <h5 class="card-title mb-3 text-primary">
                                            {{ $type === 'studio' ? 'Studio Location' : 'Location' }}
                                        </h5>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label text-muted mb-1">Province</label>
                                                    <p class="mb-0 fw-medium">
                                                        @if($type === 'studio')
                                                            {{ $studio->location ? $studio->location->province : 'Not specified' }}
                                                        @else
                                                            {{ $freelancer->location ? $freelancer->location->province : 'Not specified' }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted mb-1">Municipality</label>
                                                    <p class="mb-0 fw-medium">
                                                        @if($type === 'studio')
                                                            {{ $studio->location ? $studio->location->municipality : 'Not specified' }}
                                                        @else
                                                            {{ $freelancer->location ? $freelancer->location->municipality : 'Not specified' }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted mb-1">Barangay</label>
                                                    <p class="mb-0 fw-medium">
                                                        @if($type === 'studio')
                                                            {{ $studio->barangay ?: 'Not specified' }}
                                                        @else
                                                            {{ $freelancer->barangay ?: 'Not specified' }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-muted mb-1">Address</label>
                                                    <p class="mb-0 fw-medium">
                                                        @if($type === 'studio')
                                                            {{ $studio->street ? $studio->street . ', ' : '' }}
                                                            {{ $studio->barangay ? 'Brgy. ' . $studio->barangay . ', ' : '' }}
                                                            {{ $studio->location ? $studio->location->municipality . ', ' : '' }}
                                                            {{ $studio->location ? $studio->location->province : '' }}
                                                        @else
                                                            {{ $freelancer->street ? $freelancer->street . ', ' : '' }}
                                                            {{ $freelancer->barangay ? 'Brgy. ' . $freelancer->barangay . ', ' : '' }}
                                                            {{ $freelancer->location ? $freelancer->location->municipality . ', ' : '' }}
                                                            {{ $freelancer->location ? $freelancer->location->province : '' }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- RATINGS SECTION --}}
                                    @php
                                        $totalRatings = $type === 'studio' ? ($studio->ratings_count ?? 0) : ($freelancer->ratings_count ?? 0);
                                        $avgRating = $type === 'studio' ? ($studio->average_rating ?? 0) : ($freelancer->average_rating ?? 0);
                                    @endphp

                                    @if($totalRatings > 0)
                                    <div class="row mb-4">
                                        <h5 class="card-title mb-3 text-primary">
                                            <i class="ti ti-star me-2"></i>Client Reviews ({{ $totalRatings }})
                                        </h5>
                                        
                                        <div class="row">
                                            {{-- Rating Summary --}}
                                            <div class="col-md-4 mb-3 mb-md-0">
                                                <div class="text-center p-3 bg-light rounded">
                                                    <h2 class="display-4 fw-bold text-primary mb-2">{{ number_format($avgRating, 1) }}</h2>
                                                    <div class="text-warning mb-2">
                                                        @php
                                                            $fullStars = floor($avgRating);
                                                            $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                                                            $emptyStars = 5 - $fullStars - $halfStar;
                                                        @endphp
                                                        
                                                        @for($i = 0; $i < $fullStars; $i++)
                                                            <i class="ti ti-star-filled fs-5"></i>
                                                        @endfor
                                                        
                                                        @if($halfStar)
                                                            <i class="ti ti-star-half-filled fs-5"></i>
                                                        @endif
                                                        
                                                        @for($i = 0; $i < $emptyStars; $i++)
                                                            <i class="ti ti-star fs-5"></i>
                                                        @endfor
                                                    </div>
                                                    <p class="text-muted mb-0">Based on {{ $totalRatings }} {{ $totalRatings == 1 ? 'review' : 'reviews' }}</p>
                                                </div>
                                            </div>
                                            
                                            {{-- Rating Distribution --}}
                                            <div class="col-md-8">
                                                @for($star = 5; $star >= 1; $star--)
                                                    @php
                                                        $count = $ratingDistribution[$star] ?? 0;
                                                        $percentage = $totalRatings > 0 ? round(($count / $totalRatings) * 100) : 0;
                                                    @endphp
                                                    <div class="d-flex align-items-center mb-2">
                                                        <span class="text-muted small me-2" style="width: 30px;">{{ $star }} ★</span>
                                                        <div class="progress flex-grow-1" style="height: 8px;">
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                 style="width: {{ $percentage }}%;" 
                                                                 aria-valuenow="{{ $percentage }}" 
                                                                 aria-valuemin="0" 
                                                                 aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                        <span class="text-muted small ms-2" style="width: 40px;">{{ $count }}</span>
                                                    </div>
                                                @endfor
                                            </div>
                                        </div>
                                        
                                        {{-- Recent Reviews --}}
                                        @if($recentRatings->isNotEmpty())
                                        <div class="mt-4">
                                            <h6 class="mb-3">Recent Reviews</h6>
                                            <div class="row g-3">
                                                @foreach($recentRatings as $rating)
                                                <div class="col-md-6">
                                                    <div class="card border">
                                                        <div class="card-body">
                                                            <div class="d-flex align-items-center mb-2">
                                                                <img src="{{ $rating->client->profile_photo ? asset('storage/' . $rating->client->profile_photo) : asset('assets/images/avatars/default.png') }}" 
                                                                     class="rounded-circle me-2" 
                                                                     style="width: 30px; height: 30px; object-fit: cover;" 
                                                                     alt="{{ $rating->client->full_name }}">
                                                                <div>
                                                                    <h6 class="mb-0">{{ $rating->client->full_name }}</h6>
                                                                    <small class="text-muted">{{ $rating->created_at->diffForHumans() }}</small>
                                                                </div>
                                                            </div>
                                                            
                                                            <div class="text-warning mb-2">
                                                                @for($i = 1; $i <= 5; $i++)
                                                                    @if($i <= $rating->rating)
                                                                        <i class="ti ti-star-filled fs-6"></i>
                                                                    @else
                                                                        <i class="ti ti-star fs-6"></i>
                                                                    @endif
                                                                @endfor
                                                            </div>
                                                            
                                                            @if($rating->title)
                                                                <h6 class="fw-bold mb-1">{{ $rating->title }}</h6>
                                                            @endif
                                                            
                                                            <p class="small text-muted mb-0">{{ $rating->review_text }}</p>
                                                            
                                                            @if($rating->is_recommend)
                                                                <span class="badge badge-soft-success mt-2">
                                                                    <i class="ti ti-thumb-up me-1"></i> Recommends
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                            
                                            @if(($type === 'studio' ? $studio->ratings_count : $freelancer->ratings_count) > 5)
                                            <div class="text-center mt-3">
                                                <a href="#" class="btn btn-link text-primary" data-bs-toggle="modal" data-bs-target="#allReviewsModal">
                                                    View All {{ $totalRatings }} Reviews
                                                </a>
                                            </div>
                                            @endif
                                        </div>
                                        @endif
                                    </div>
                                    @else
                                    <div class="row mb-4">
                                        <h5 class="card-title mb-3 text-primary">
                                            <i class="ti ti-star me-2"></i>Client Reviews
                                        </h5>
                                        <div class="alert alert-info">
                                            <i class="ti ti-info-circle me-2"></i> No reviews yet. Be the first to leave a review after booking!
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT SIDE --}}
                        <div class="col-lg-4">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3">Contact Information</h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label text-muted mb-1">
                                            {{ $type === 'studio' ? 'Studio Owner' : 'Name' }}
                                        </label>
                                        <p class="mb-0 fw-medium">
                                            @if($type === 'studio')
                                                {{ $studio->user ? $studio->user->full_name : 'Not available' }}
                                            @else
                                                {{ $freelancer->user ? $freelancer->user->full_name : 'Not available' }}
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label text-muted mb-1">
                                            {{ $type === 'studio' ? 'Studio Email' : 'Email' }}
                                        </label>
                                        <p class="mb-0 fw-medium">
                                            @if($type === 'studio')
                                                {{ $studio->studio_email ?: 'Not available' }}
                                            @else
                                                {{ $freelancer->user ? $freelancer->user->email : 'Not available' }}
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label text-muted mb-1">Contact Number</label>
                                        <p class="mb-0 fw-medium">
                                            @if($type === 'studio')
                                                {{ $studio->contact_number ?: 'Not available' }}
                                            @else
                                                {{ $freelancer->user ? $freelancer->user->mobile_number : 'Not available' }}
                                            @endif
                                        </p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label text-muted mb-1">Response Time</label>
                                        <p class="mb-0 fw-medium">Usually responds within 2 hours</p>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary">
                                            <i class="ti ti-phone me-2"></i>
                                            {{ $type === 'studio' ? 'Contact Studio' : 'Contact' }}
                                        </button>
                                        <button class="btn btn-soft-primary">
                                            <i class="ti ti-message-circle me-2"></i>
                                            Send Message
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title text-primary mb-3">Booking Terms</h5>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex align-items-start mb-3">
                                            <i class="ti ti-calendar text-primary me-2 mt-1"></i>
                                            <div>
                                                <h5 class="mb-1">Advance Booking</h5>
                                                <p class="text-muted mb-0">
                                                    @if($type === 'studio')
                                                        Book at least {{ $studio->advance_booking_days ?? 3 }} days in advance
                                                    @else
                                                        Book at least {{ $freelancer->schedule->advance_booking ?? 3 }} days in advance
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-start mb-3">
                                            <i class="ti ti-coin text-primary me-2 mt-1"></i>
                                            <div>
                                                <h5 class="mb-1">Payment Terms</h5>
                                                <p class="text-muted mb-0">
                                                    @if($type === 'studio')
                                                        {{ $studio->deposit_policy ?? '30%' }} downpayment to confirm booking
                                                    @else
                                                        {{ $freelancer->deposit_policy ?? '30%' }} downpayment to confirm booking
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex align-items-start mb-3">
                                            <i class="ti ti-users text-primary me-2 mt-1"></i>
                                            <div>
                                                <h5 class="mb-1">Capacity</h5>
                                                <p class="text-muted mb-0">
                                                    @if($type === 'studio')
                                                        Maximum {{ $studio->max_clients_per_day ?? 3 }} clients per day
                                                    @else
                                                        Maximum {{ $freelancer->schedule->booking_limit ?? 3 }} clients per day
                                                    @endif
                                                </p>
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
    </div>

    {{-- All Reviews Modal --}}
    @if(($type === 'studio' ? ($studio->ratings_count ?? 0) : ($freelancer->ratings_count ?? 0)) > 0)
    <div class="modal fade" id="allReviewsModal" tabindex="-1" aria-labelledby="allReviewsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allReviewsModalLabel">
                        <i class="ti ti-star me-2"></i>All Reviews
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- This will be loaded via AJAX --}}
                    <div class="text-center py-4" id="reviews-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading reviews...</p>
                    </div>
                    <div id="reviews-container" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            // Function to filter packages
            function filterPackages(categoryId) {
                // Show all packages if no category selected
                if (!categoryId) {
                    $('.package-category').show();
                    return;
                }
                
                // Hide all packages first
                $('.package-category').hide();
                
                // Show packages for selected category
                $(`.package-category[data-category="${categoryId}"]`).show();
            }
            
            // Initial state - show all packages
            filterPackages('');
            
            // Filter on dropdown change
            $('#packageCategory').on('change', function() {
                const categoryId = $(this).val();
                filterPackages(categoryId);
            });

            // Load all reviews when modal is opened
            $('#allReviewsModal').on('show.bs.modal', function() {
                const modal = $(this);
                const loadingEl = $('#reviews-loading');
                const containerEl = $('#reviews-container');
                
                // Show loading, hide container
                loadingEl.show();
                containerEl.hide().empty();
                
                // Get the appropriate URL based on type
                @if($type === 'studio')
                    const url = '{{ route("client.studio-reviews", $studio->id) }}';
                @else
                    const url = '{{ route("client.freelancer-reviews", $freelancer->user_id) }}';
                @endif
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        loadingEl.hide();
                        
                        if (response.success) {
                            let html = '';
                            
                            // Summary stats
                            html += `
                                <div class="bg-light p-3 rounded mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center">
                                            <h3 class="display-5 fw-bold text-primary mb-1">${response.average_rating}</h3>
                                            <div class="text-warning mb-1">
                                                ${generateRatingStars(response.average_rating)}
                                            </div>
                                            <small class="text-muted">${response.total_reviews} reviews</small>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="row g-2">
                            `;
                            
                            // Distribution
                            for (let star = 5; star >= 1; star--) {
                                const count = response.distribution[star] || 0;
                                const percentage = response.total_reviews > 0 ? (count / response.total_reviews * 100).toFixed(0) : 0;
                                
                                html += `
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted small me-2" style="width: 40px;">${star} ★</span>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-warning" style="width: ${percentage}%"></div>
                                            </div>
                                            <span class="text-muted small ms-2" style="width: 40px;">${count}</span>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            html += `</div></div></div>`;
                            
                            // Reviews list
                            if (response.reviews.data.length > 0) {
                                $.each(response.reviews.data, function(index, review) {
                                    const profilePhoto = review.client.profile_photo ? 
                                        '{{ asset("storage") }}/' + review.client.profile_photo : 
                                        '{{ asset("assets/images/avatars/default.png") }}';
                                    
                                    html += `
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <img src="${profilePhoto}" 
                                                         class="rounded-circle me-2" 
                                                         style="width: 40px; height: 40px; object-fit: cover;" 
                                                         alt="${review.client.first_name}">
                                                    <div>
                                                        <h6 class="mb-0">${review.client.first_name} ${review.client.last_name}</h6>
                                                        <small class="text-muted">${new Date(review.created_at).toLocaleDateString()}</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-warning mb-2">
                                                    ${generateRatingStars(review.rating)}
                                                </div>
                                                
                                                ${review.title ? `<h6 class="fw-bold mb-1">${review.title}</h6>` : ''}
                                                
                                                <p class="small text-muted mb-0">${review.review_text}</p>
                                                
                                                ${review.is_recommend ? `
                                                    <span class="badge badge-soft-success mt-2">
                                                        <i class="ti ti-thumb-up me-1"></i> Recommends
                                                    </span>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                // Pagination
                                if (response.reviews.last_page > 1) {
                                    html += '<div class="pagination-container mt-3">';
                                    html += '<nav><ul class="pagination justify-content-center">';
                                    
                                    for (let i = 1; i <= response.reviews.last_page; i++) {
                                        html += `<li class="page-item ${i === response.reviews.current_page ? 'active' : ''}">
                                            <a class="page-link review-page-link" href="#" data-page="${i}">${i}</a>
                                        </li>`;
                                    }
                                    
                                    html += '</ul></nav></div>';
                                }
                            } else {
                                html += '<div class="alert alert-info">No reviews found.</div>';
                            }
                            
                            containerEl.html(html).show();
                        }
                    },
                    error: function() {
                        loadingEl.hide();
                        containerEl.html('<div class="alert alert-danger">Failed to load reviews. Please try again.</div>').show();
                    }
                });
            });

            // Helper function to generate rating stars
            function generateRatingStars(rating) {
                let stars = '';
                const fullStars = Math.floor(rating);
                const halfStar = (rating - fullStars) >= 0.5 ? 1 : 0;
                const emptyStars = 5 - fullStars - halfStar;
                
                for (let i = 0; i < fullStars; i++) {
                    stars += '<i class="ti ti-star-filled fs-6"></i>';
                }
                
                if (halfStar) {
                    stars += '<i class="ti ti-star-half-filled fs-6"></i>';
                }
                
                for (let i = 0; i < emptyStars; i++) {
                    stars += '<i class="ti ti-star fs-6"></i>';
                }
                
                return stars;
            }

            // Handle pagination clicks
            $(document).on('click', '.review-page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                const loadingEl = $('#reviews-loading');
                const containerEl = $('#reviews-container');
                
                loadingEl.show();
                containerEl.hide();
                
                // Get the appropriate URL based on type
                @if($type === 'studio')
                    const url = '{{ route("client.studio-reviews", $studio->id) }}?page=' + page;
                @else
                    const url = '{{ route("client.freelancer-reviews", $freelancer->user_id) }}?page=' + page;
                @endif
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        loadingEl.hide();
                        
                        if (response.success) {
                            let html = '';
                            
                            // Summary stats (keep the same as above)
                            html += `
                                <div class="bg-light p-3 rounded mb-4">
                                    <div class="row align-items-center">
                                        <div class="col-md-3 text-center">
                                            <h3 class="display-5 fw-bold text-primary mb-1">${response.average_rating}</h3>
                                            <div class="text-warning mb-1">
                                                ${generateRatingStars(response.average_rating)}
                                            </div>
                                            <small class="text-muted">${response.total_reviews} reviews</small>
                                        </div>
                                        <div class="col-md-9">
                                            <div class="row g-2">
                            `;
                            
                            // Distribution
                            for (let star = 5; star >= 1; star--) {
                                const count = response.distribution[star] || 0;
                                const percentage = response.total_reviews > 0 ? (count / response.total_reviews * 100).toFixed(0) : 0;
                                
                                html += `
                                    <div class="col-12">
                                        <div class="d-flex align-items-center">
                                            <span class="text-muted small me-2" style="width: 40px;">${star} ★</span>
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-warning" style="width: ${percentage}%"></div>
                                            </div>
                                            <span class="text-muted small ms-2" style="width: 40px;">${count}</span>
                                        </div>
                                    </div>
                                `;
                            }
                            
                            html += `</div></div></div>`;
                            
                            // Reviews list for current page
                            if (response.reviews.data.length > 0) {
                                $.each(response.reviews.data, function(index, review) {
                                    const profilePhoto = review.client.profile_photo ? 
                                        '{{ asset("storage") }}/' + review.client.profile_photo : 
                                        '{{ asset("assets/images/avatars/default.png") }}';
                                    
                                    html += `
                                        <div class="card border mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-2">
                                                    <img src="${profilePhoto}" 
                                                         class="rounded-circle me-2" 
                                                         style="width: 40px; height: 40px; object-fit: cover;" 
                                                         alt="${review.client.first_name}">
                                                    <div>
                                                        <h6 class="mb-0">${review.client.first_name} ${review.client.last_name}</h6>
                                                        <small class="text-muted">${new Date(review.created_at).toLocaleDateString()}</small>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-warning mb-2">
                                                    ${generateRatingStars(review.rating)}
                                                </div>
                                                
                                                ${review.title ? `<h6 class="fw-bold mb-1">${review.title}</h6>` : ''}
                                                
                                                <p class="small text-muted mb-0">${review.review_text}</p>
                                                
                                                ${review.is_recommend ? `
                                                    <span class="badge badge-soft-success mt-2">
                                                        <i class="ti ti-thumb-up me-1"></i> Recommends
                                                    </span>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `;
                                });
                                
                                // Pagination
                                if (response.reviews.last_page > 1) {
                                    html += '<div class="pagination-container mt-3">';
                                    html += '<nav><ul class="pagination justify-content-center">';
                                    
                                    for (let i = 1; i <= response.reviews.last_page; i++) {
                                        html += `<li class="page-item ${i === response.reviews.current_page ? 'active' : ''}">
                                            <a class="page-link review-page-link" href="#" data-page="${i}">${i}</a>
                                        </li>`;
                                    }
                                    
                                    html += '</ul></nav></div>';
                                }
                            } else {
                                html += '<div class="alert alert-info">No reviews found.</div>';
                            }
                            
                            containerEl.html(html).show();
                        }
                    },
                    error: function() {
                        loadingEl.hide();
                        containerEl.html('<div class="alert alert-danger">Failed to load reviews. Please try again.</div>').show();
                    }
                });
            });
        });
    </script>
@endsection