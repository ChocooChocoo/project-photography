@extends('layouts.client.app')
@section('title', 'Client Home')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="page-title-head d-flex align-items-center">
                <div class="flex-grow-1">
                    <h3 class="fw-bold m-0">Welcome to Lumora</h3>
                    <p>Providing Reliable and Continuous Photography Services for Clients</p>
                </div>
            </div>

            {{-- FILTER TOGGLE - MOBILE VIEW --}}
            <div class="row mb-2">
                <div class="col-lg-12">
                    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                        <div class="d-lg-none d-flex gap-2">
                            <button class="btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#productFillterOffcanvas" aria-controls="productFillterOffcanvas">
                                <i data-lucide="sliders-horizontal"></i>
                                <span class="d-lg-none ms-2">Filter</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-3">
                    <div class="offcanvas-lg offcanvas-start" tabindex="-1" id="productFillterOffcanvas">
                        <div class="card h-100" data-simplebar>
                            <div class="card-body p-0">

                                {{-- PHOTOGRAPHER TYPE --}}
                                <div class="p-3 border-bottom border-dashed">
                                    <div class="d-flex mb-2 justify-content-between align-items-center">
                                        <h5 class="mb-0">Photographer Type:</h5>
                                        <a href="javascript: void(0);" class="btn btn-link btn-sm px-0 fw-semibold text-primary view-all-type">View All</a>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 text-muted py-1">
                                        <div class="form-check flex-grow-1">
                                            <input type="checkbox" id="type-studio-type-photographer" class="form-check-input filter-type" value="studio">
                                            <label for="type-studio-type-photographer" class="form-check-label mb-0">Studio Photographer</label>
                                        </div>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 text-muted py-1">
                                        <div class="form-check flex-grow-1">
                                            <input type="checkbox" id="type-freelancer-type-photographer" class="form-check-input filter-type" value="freelancer">
                                            <label for="type-freelancer-type-photographer" class="form-check-label mb-0">Freelancer Photographer</label>
                                        </div>
                                    </div>
                                </div>

                                {{-- CATEGORY --}}
                                <div class="p-3 border-bottom border-dashed">
                                    <div class="d-flex mb-2 justify-content-between align-items-center">
                                        <h5 class="mb-0">Category:</h5>
                                        <a href="javascript: void(0);" class="btn btn-link btn-sm px-0 fw-semibold text-primary view-all-category">View All</a>
                                    </div>

                                    <div id="category-filter-container">
                                        @foreach($categories as $category)
                                        <div class="d-flex align-items-center gap-2 text-muted py-1">
                                            <div class="form-check flex-grow-1">
                                                <input type="checkbox" id="cat-{{ $category->id }}" class="form-check-input filter-category" value="{{ $category->id }}">
                                                <label for="cat-{{ $category->id }}" class="form-check-label mb-0">{{ $category->category_name }}</label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- LOCATION --}}
                                <div class="p-3 border-bottom border-dashed">
                                    <div class="d-flex mb-2 justify-content-between align-items-center">
                                        <h5 class="mb-0">Locations:</h5>
                                        <a href="javascript: void(0);" class="btn btn-link btn-sm px-0 fw-semibold text-primary view-all-location">View All</a>
                                    </div>

                                    <div id="location-filter-container">
                                        @foreach($locations as $location)
                                        <div class="d-flex align-items-center gap-2 text-muted py-1">
                                            <div class="form-check flex-grow-1">
                                                <input type="checkbox" id="loc-{{ $location->id }}" class="form-check-input filter-location" value="{{ $location->id }}">
                                                <label for="loc-{{ $location->id }}" class="form-check-label mb-0">{{ $location->municipality }}</label>
                                            </div>
                                            <div class="flex-shrink-0"><span class="badge text-bg-light location-count" data-location="{{ $location->id }}">0</span></div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- PRICE --}}
                                <div class="p-3 border-bottom border-dashed">
                                    <h5 class="mb-3">Price Range:</h5>

                                    <div class="d-flex gap-2 align-items-center mt-3">
                                        <input type="number" id="min-price" class="form-control form-control-sm text-center" placeholder="PHP 00.00" min="0">
                                        <span class="fw-semibold text-muted">to</span>
                                        <input type="number" id="max-price" class="form-control form-control-sm text-center" placeholder="PHP 00.00" min="0">
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" id="apply-price-filter" class="btn btn-sm btn-primary w-100">Apply Price</button>
                                    </div>
                                </div>

                                {{-- RATING FILTER --}}
                                <div class="p-3 border-bottom border-dashed">
                                    <div class="d-flex mb-2 justify-content-between align-items-center">
                                        <h5 class="mb-0">Minimum Rating:</h5>
                                        <a href="javascript: void(0);" class="btn btn-link btn-sm px-0 fw-semibold text-primary view-all-rating">View All</a>
                                    </div>

                                    <div id="rating-filter-container">
                                        <div class="d-flex align-items-center gap-2 text-muted py-1">
                                            <div class="form-check flex-grow-1">
                                                <input type="checkbox" id="rating-3" class="form-check-input filter-rating" value="3">
                                                <label for="rating-3" class="form-check-label mb-0">3+ Stars <small class="text-muted">(Good)</small></label>
                                            </div>
                                            <div class="flex-shrink-0"><span class="badge text-bg-light rating-count" data-rating="3">0</span></div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center gap-2 text-muted py-1">
                                            <div class="form-check flex-grow-1">
                                                <input type="checkbox" id="rating-4" class="form-check-input filter-rating" value="4">
                                                <label for="rating-4" class="form-check-label mb-0">4+ Stars <small class="text-muted">(Very Good)</small></label>
                                            </div>
                                            <div class="flex-shrink-0"><span class="badge text-bg-light rating-count" data-rating="4">0</span></div>
                                        </div>
                                        
                                        <div class="d-flex align-items-center gap-2 text-muted py-1">
                                            <div class="form-check flex-grow-1">
                                                <input type="checkbox" id="rating-45" class="form-check-input filter-rating" value="4.5">
                                                <label for="rating-45" class="form-check-label mb-0">4.5+ Stars <small class="text-muted">(Excellent)</small></label>
                                            </div>
                                            <div class="flex-shrink-0"><span class="badge text-bg-light rating-count" data-rating="4.5">0</span></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <button type="button" id="apply-filters" class="btn btn-primary w-100">Apply Filters</button>
                                    <button type="button" id="clear-filters" class="btn btn-soft-primary w-100 mt-2">Clear All</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-9">
                    <div class="row g-2" id="photographer-cards-container">
                        {{-- STUDIOS --}}
                        @foreach($studios as $studio)
                        <div class="col">
                            <div class="card h-100 mb-2 border-1 shadow-sm">
                                <div class="card-body pb-2">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $studio->studio_logo ? asset('storage/' . $studio->studio_logo) : asset('assets/images/sellers/7.png') }}" 
                                                class="rounded" alt="{{ $studio->studio_name }}" 
                                                style="width: 100px; height: 100px; object-fit: cover;">
                                        </div>                                            
                                        <div class="flex-grow-1 ms-3" style="min-width: 0;">
                                            <h4 class="card-title mb-1 text-truncate" title="{{ $studio->studio_name }}">{{ $studio->studio_name }}</h4>
                                            <p class="text-muted mb-1">Studio</p>
                                            <div class="mb-2">
                                                <span class="text-muted small">
                                                    <i class="ti ti-map-pin me-1"></i>
                                                    <span>{{ $studio->location ? $studio->location->municipality . ', Cavite' : 'Location not specified' }}</span>
                                                </span>
                                            </div>                                                
                                            <div class="d-flex align-items-center">
                                                <span class="text-warning">
                                                    @php
                                                        $avgRating = $studio->average_rating ?? 0;
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
                                                <span class="ms-2 fw-medium">
                                                    ({{ number_format($avgRating, 1) }}) 
                                                    <small class="text-muted">{{ $studio->ratings_count ?? 0 }} {{ $studio->ratings_count == 1 ? 'review' : 'reviews' }}</small>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted small mb-1">Starting Price</p>
                                            <h5 class="text-success d-flex align-items-center gap-2 mb-0">PHP {{ number_format($studio->starting_price, 2) }}</h5>
                                        </div>                                            
                                        <a class="btn btn-primary w-10" href="{{ route('client.booking-details', ['type' => 'studio', 'id' => $studio->id]) }}">Book Service</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach

                        {{-- FREELANCER --}}
                        @foreach($freelancers as $freelancer)
                        <div class="col">
                            <div class="card h-100 mb-2 border-1 shadow-sm">
                                <div class="card-body pb-2">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <img src="{{ $freelancer->brand_logo ? asset('storage/' . $freelancer->brand_logo) : asset('assets/images/sellers/3.png') }}" 
                                                class="rounded" alt="{{ $freelancer->brand_name }}" 
                                                style="width: 100px; height: 100px; object-fit: cover;">
                                        </div>                                            
                                        <div class="flex-grow-1 ms-3" style="min-width: 0;">
                                            <h4 class="card-title mb-1 text-truncate" title="{{ $freelancer->brand_name }}">{{ $freelancer->brand_name }}</h4>
                                            <p class="text-muted mb-1">Freelancer</p>
                                            <div class="mb-2">
                                                <span class="text-muted small">
                                                    <i class="ti ti-map-pin me-1"></i>
                                                    <span>{{ $freelancer->location ? $freelancer->location->municipality . ', Cavite' : 'Location not specified' }}</span>
                                                </span>
                                            </div>                                                
                                            <div class="d-flex align-items-center">
                                                <span class="text-warning">
                                                    @php
                                                        $avgRating = $freelancer->average_rating ?? 0;
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
                                                <span class="ms-2 fw-medium">
                                                    ({{ number_format($avgRating, 1) }}) 
                                                    <small class="text-muted">{{ $freelancer->ratings_count ?? 0 }} {{ $freelancer->ratings_count == 1 ? 'review' : 'reviews' }}</small>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted small mb-1">Starting Price</p>
                                            <h5 class="text-success d-flex align-items-center gap-2 mb-0">PHP {{ number_format($freelancer->starting_price, 2) }}</h5>
                                        </div>                                            
                                        <a class="btn btn-primary w-10" href="{{ route('client.booking-details', ['type' => 'freelancer', 'id' => $freelancer->user_id]) }}">Book Service</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Loading Spinner --}}
                    <div id="loading-spinner" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading photographers...</p>
                    </div>

                    {{-- No Results Message --}}
                    <div id="no-results" class="text-center py-5" style="display: none;">
                        <i class="ti ti-search-off fs-1 text-muted"></i>
                        <h5 class="mt-3">No photographers found</h5>
                        <p class="text-muted">Try adjusting your filters</p>
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
            // CSRF Token for AJAX
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Filter variables
            let activeFilters = {
                type: [],
                category: [],
                location: [],
                rating: [],
                min_price: null,
                max_price: null
            };

            // Update photographer cards
            function updatePhotographerCards(results) {
                const container = $('#photographer-cards-container');
                container.empty();

                if (results.length === 0) {
                    $('#no-results').show();
                    return;
                }

                $('#no-results').hide();

                $.each(results, function(index, photographer) {
                    const cardHtml = `
                        <div class="col-xxl-4 col-lg-4 col-sm-6 col-12 mb-3">
                            <div class="card h-100 border-1 shadow-sm photographer-card" data-type="${photographer.type}" data-id="${photographer.id}" data-location-id="${photographer.location_id || ''}" data-rating="${photographer.rating}">
                                <div class="card-body pb-2">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-shrink-0">
                                            <img src="${photographer.logo}" 
                                                class="rounded" alt="${photographer.name}" 
                                                style="width: 100px; height: 100px; object-fit: cover;">
                                        </div>                                            
                                        <div class="flex-grow-1 ms-3">
                                            <h4 class="card-title mb-1">${photographer.name}</h4>
                                            <p class="text-muted mb-1">${photographer.type_label}</p>
                                            <div class="mb-2">
                                                <span class="text-muted small">
                                                    <i class="ti ti-map-pin me-1"></i>
                                                    <span>${photographer.location}</span>
                                                </span>
                                            </div>                                                
                                            <div class="d-flex align-items-center">
                                                <span class="text-warning">
                                                    ${photographer.rating_display.stars}
                                                </span>
                                                <span class="ms-2 fw-medium">
                                                    <span>${photographer.rating_display.display}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-footer bg-transparent border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted small mb-1">Starting Price</p>
                                            <h5 class="text-success d-flex align-items-center gap-2 mb-0">PHP ${photographer.starting_price}</h5>
                                        </div>                                            
                                        <a class="btn btn-primary w-10" href="/client/booking-details/${photographer.type}/${photographer.id}">Book Service</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.append(cardHtml);
                });

                container.show();
            }

            // Update the applyFilters function to include rating
            function applyFilters() {
                // Show loading spinner
                $('#loading-spinner').show();
                $('#no-results').hide();
                $('#photographer-cards-container').hide();

                // Get selected types
                activeFilters.type = [];
                $('.filter-type:checked').each(function() {
                    activeFilters.type.push($(this).val());
                });

                // Get selected categories
                activeFilters.category = [];
                $('.filter-category:checked').each(function() {
                    activeFilters.category.push($(this).val());
                });

                // Get selected locations
                activeFilters.location = [];
                $('.filter-location:checked').each(function() {
                    activeFilters.location.push($(this).val());
                });

                // Get selected ratings
                activeFilters.rating = [];
                $('.filter-rating:checked').each(function() {
                    activeFilters.rating.push($(this).val());
                });

                // Get price range
                activeFilters.min_price = $('#min-price').val() || null;
                activeFilters.max_price = $('#max-price').val() || null;

                // Determine which rating to use (if multiple selected, use the highest)
                let minRating = null;
                if (activeFilters.rating.length > 0) {
                    // Convert to numbers and get the maximum (most restrictive) rating
                    minRating = Math.max(...activeFilters.rating.map(Number));
                }

                // AJAX request
                $.ajax({
                    url: '{{ route("client.dashboard.filter") }}',
                    type: 'POST',
                    data: {
                        photographer_type: activeFilters.type.length > 0 ? activeFilters.type.join(',') : '',
                        category_id: activeFilters.category.length > 0 ? activeFilters.category[0] : '',
                        location_id: activeFilters.location.length > 0 ? activeFilters.location[0] : '',
                        min_price: activeFilters.min_price,
                        max_price: activeFilters.max_price,
                        min_rating: minRating
                    },
                    success: function(response) {
                        // Hide loading spinner
                        $('#loading-spinner').hide();

                        if (response.success) {
                            updatePhotographerCards(response.results);
                            
                            // Update rating counts based on results
                            updateRatingCounts(response.results);
                            
                            // Update location counts
                            updateLocationCounts(response.results);
                        }
                    },
                    error: function(xhr) {
                        $('#loading-spinner').hide();
                        $('#photographer-cards-container').show();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to apply filters. Please try again.',
                            confirmButtonColor: '#3475db',
                            showConfirmButton: true
                        });
                    }
                });
            }

            // Function to update rating counts
            function updateRatingCounts(results) {
                // Initialize counts
                let counts = {
                    '3': 0,
                    '4': 0,
                    '4.5': 0
                };
                
                // Count photographers that meet each rating threshold
                $.each(results, function(index, photographer) {
                    const rating = parseFloat(photographer.rating);
                    
                    if (rating >= 4.5) {
                        counts['4.5']++;
                        counts['4']++;
                        counts['3']++;
                    } else if (rating >= 4) {
                        counts['4']++;
                        counts['3']++;
                    } else if (rating >= 3) {
                        counts['3']++;
                    }
                });
                
                // Update the badges
                $('.rating-count[data-rating="3"]').text(counts['3']);
                $('.rating-count[data-rating="4"]').text(counts['4']);
                $('.rating-count[data-rating="4.5"]').text(counts['4.5']);
            }

            // Update location counts function
            function updateLocationCounts(results) {
                // Initialize counts object
                let locationCounts = {};
                
                // Count by location_id
                $.each(results, function(index, photographer) {
                    const locationId = photographer.location_id;
                    if (locationId) {
                        locationCounts[locationId] = (locationCounts[locationId] || 0) + 1;
                    }
                });
                
                // Update the badges
                $('.location-count').each(function() {
                    const locationId = $(this).data('location');
                    const count = locationCounts[locationId] || 0;
                    $(this).text(count);
                });
            }

            // Initialize counts on page load
            function initializeCounts() {
                // Get all photographer cards
                const cards = $('.photographer-card');
                
                if (cards.length === 0) {
                    // If no cards (AJAX not used on initial load), set all counts to 0
                    $('.rating-count[data-rating="3"]').text('0');
                    $('.rating-count[data-rating="4"]').text('0');
                    $('.rating-count[data-rating="4.5"]').text('0');
                    
                    $('.location-count').each(function() {
                        $(this).text('0');
                    });
                    return;
                }
                
                // Calculate rating counts
                let ratingCounts = {
                    '3': 0,
                    '4': 0,
                    '4.5': 0
                };
                
                // Calculate location counts
                let locationCounts = {};
                
                cards.each(function() {
                    const card = $(this);
                    
                    // Get rating from the displayed text
                    const ratingText = card.find('.fw-medium').text().trim();
                    const ratingMatch = ratingText.match(/\((\d+\.?\d*)\)/);
                    
                    if (ratingMatch) {
                        const rating = parseFloat(ratingMatch[1]);
                        
                        if (rating >= 4.5) {
                            ratingCounts['4.5']++;
                            ratingCounts['4']++;
                            ratingCounts['3']++;
                        } else if (rating >= 4) {
                            ratingCounts['4']++;
                            ratingCounts['3']++;
                        } else if (rating >= 3) {
                            ratingCounts['3']++;
                        }
                    }
                    
                    // Get location from the card data (we need to add data-location-id in the static cards)
                    // For static cards, we need a different approach
                    const locationElement = card.find('.ti-map-pin').parent().find('span').last();
                    const locationText = locationElement.text().trim();
                    
                    // Find location ID by matching text with filter labels
                    $('.filter-location').each(function() {
                        const label = $(this).closest('.d-flex').find('.form-check-label').text().trim();
                        if (locationText.includes(label) && label !== '') {
                            const locId = $(this).val();
                            locationCounts[locId] = (locationCounts[locId] || 0) + 1;
                        }
                    });
                });
                
                // Update rating badges
                $('.rating-count[data-rating="3"]').text(ratingCounts['3']);
                $('.rating-count[data-rating="4"]').text(ratingCounts['4']);
                $('.rating-count[data-rating="4.5"]').text(ratingCounts['4.5']);
                
                // Update location badges
                $('.location-count').each(function() {
                    const locationId = $(this).data('location');
                    $(this).text(locationCounts[locationId] || 0);
                });
            }

            // Add event listener for rating checkboxes
            $('.filter-rating').on('change', function() {
                applyFilters();
            });

            // View All rating button
            $('.view-all-rating').on('click', function() {
                $('.filter-rating').prop('checked', true);
                applyFilters();
            });

            // Event Listeners
            $('#apply-filters').on('click', applyFilters);

            $('#apply-price-filter').on('click', function() {
                applyFilters();
            });

            // Clear all filters
            $('#clear-filters').on('click', function() {
                // Uncheck all checkboxes
                $('.filter-type, .filter-category, .filter-location, .filter-rating').prop('checked', false);
                
                // Clear price inputs
                $('#min-price').val('');
                $('#max-price').val('');
                
                // Reset active filters
                activeFilters = {
                    type: [],
                    category: [],
                    location: [],
                    rating: [],
                    min_price: null,
                    max_price: null
                };
                
                // Apply filters to refresh results
                applyFilters();
            });

            // View All buttons
            $('.view-all-type').on('click', function() {
                $('.filter-type').prop('checked', true);
                applyFilters();
            });

            $('.view-all-category').on('click', function() {
                $('.filter-category').prop('checked', true);
                applyFilters();
            });

            $('.view-all-location').on('click', function() {
                $('.filter-location').prop('checked', true);
                applyFilters();
            });

            // Apply filters on checkbox change
            $('.filter-type, .filter-category, .filter-location').on('change', function() {
                applyFilters();
            });

            // Initialize counts on page load (only call this once)
            initializeCounts();

            // Add CSS for rating badges
            $('<style>')
                .prop('type', 'text/css')
                .html(`
                    .rating-count, .location-count {
                        background-color: #f0f0f0;
                        color: #666;
                        font-weight: normal;
                    }
                `)
                .appendTo('head');
        });
    </script>
@endsection