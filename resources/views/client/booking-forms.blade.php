@extends('layouts.client.app')
@section('title', 'Booking Form')

{{-- STYLES --}}
@section('styles')
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
        }
        
        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .calendar-day {
            position: relative;
            text-align: center;
            padding: 10px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .calendar-day:hover:not(.past):not(.unavailable) {
            background: #e7f1ff;
            border-color: #3475db;
        }
        
        .calendar-day.today {
            background: #3475db;
            color: white;
            border-color: #3475db;
        }
        
        .calendar-day.past {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: not-allowed;
        }
        
        .calendar-day.unavailable {
            background: #fee;
            color: #dc3545;
            cursor: not-allowed;
        }
        
        .calendar-day.empty {
            background: transparent;
            border: none;
        }
        
        .availability-dot {
            position: absolute;
            bottom: 2px;
            left: 50%;
            transform: translateX(-50%);
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        
        .availability-dot.available {
            background: #28a745;
        }
        
        .availability-dot.unavailable {
            background: #dc3545;
        }

        #locationType:disabled {
            background-color: #f8f9fa;
            opacity: 0.8;
            cursor: not-allowed;
        }

        .location-type-note {
            color: #6c757d;
            font-style: italic;
        }

        .flexible-location-ui .btn-check:checked + .btn-outline-primary {
            background-color: #3475db;
            color: white;
            border-color: #3475db;
        }

        .flexible-location-ui .btn-outline-primary {
            transition: all 0.3s ease;
        }

        .flexible-location-ui .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 117, 219, 0.2);
        }

        .flexible-location-ui .btn-check:checked + .btn-outline-primary i {
            color: white !important;
        }

        .location-auto-set-badge {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Booking Form</h4>
                            <p class="text-muted mb-0">Booking for
                                {{ $type === 'studio' ? $provider->studio_name : $provider->brand_name }}</p>
                        </div>
                        <div class="card-body">
                            <form id="bookingForm" class="needs-validation" novalidate>
                                @csrf
                                <input type="hidden" id="bookingType" value="{{ $type }}">
                                <input type="hidden" id="providerId" value="{{ $id }}">
                                <input type="hidden" id="operatingDays" value="{{ json_encode($operatingDays) }}">
                                @if($type === 'studio')
                                <input type="hidden" id="downpaymentPercentage" value="{{ $downpaymentPercentage }}">
                                @endif

                                {{-- CLIENT INFORMATION --}}
                                <h4 class="card-title text-primary mb-3">Client Information</h4>
                                <div class="mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="fullName"
                                                value="{{ $user->first_name . ' ' . $user->last_name }}"
                                                placeholder="Enter your full name" required>
                                            <div class="invalid-feedback">
                                                Please enter your full name.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="contactNumber"
                                                value="{{ $user->mobile_number }}" placeholder="Enter your contact number"
                                                required>
                                            <div class="invalid-feedback">
                                                Please enter a valid contact number.
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email"
                                                value="{{ $user->email }}" placeholder="Enter your email address" required>
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- SERVICE AND PACKAGES SELECTION --}}
                                <h4 class="card-title text-primary mb-3">Service and Packages Selection</h4>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Service Category</label>
                                    <div class="form-select-wrapper">
                                        <select class="form-select" id="serviceCategory" name="category_id" required>
                                            <option value="">Select Category</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please select a service category.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-medium">Select Package</label>
                                    <div id="packagesContainer">
                                        <div class="alert alert-warning">
                                            <i class="ti ti-warning-circle me-2"></i> Please select a category first to view available packages.
                                        </div>
                                    </div>
                                    <div class="invalid-feedback mt-2">
                                        Please select a package.
                                    </div>
                                </div>

                                {{-- EVENT DATE & TIME --}}
                                <h4 class="card-title text-primary mb-3">Event Date & Time</h4>
                                <div class="mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label">Event Date</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="eventDate" name="event_date"
                                                    min="{{ date('Y-m-d') }}"
                                                    max="{{ date('Y-m-d', strtotime('+60 days')) }}"
                                                    placeholder="Select event date" required>
                                                <button class="btn btn-outline-primary" type="button" id="checkDateBtn">
                                                    <i class="ti ti-calendar me-1"></i> Check Availability
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                Please select a valid event date.
                                            </div>
                                            <small class="text-muted mt-1 d-block" id="closedDayNote" style="display:none !important;"></small>
                                            <small class="text-muted mt-1" id="dateAvailabilityStatus">
                                                <span id="dateStatusIcon" class="me-1"></span>
                                                <span id="dateStatusText">Select a date to check availability</span>
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="startTime" name="start_time"
                                                value="08:00" placeholder="Enter start time" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="endTime" name="end_time"
                                                value="18:00" placeholder="Enter end time" required>
                                        </div>
                                    </div>

                                    {{-- Calendar Modal --}}
                                    <div class="modal fade" id="calendarModal" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Select Available Date</h5>
                                                    <button type="button" class="btn btn-default btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div id="availabilityCalendar"></div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-default btn-secondary"
                                                        data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- PAYMENT TYPE --}}
                                <h4 class="card-title text-primary mb-3">Payment Details</h4>
                                <div class="mb-4">
                                    @if($type === 'studio')
                                        {{-- Studio always shows payment options --}}
                                        <div class="btn-group w-100" role="group" aria-label="Payment type selection">
                                            <input class="btn-check" type="radio" name="payment_type" id="payment_type_downpayment" value="downpayment" checked>
                                            <label class="btn btn-outline-primary" for="payment_type_downpayment">
                                                <i class="ti ti-percentage me-1"></i> {{ $downpaymentPercentage }}% Downpayment
                                            </label>
                                            
                                            <input class="btn-check" type="radio" name="payment_type" id="payment_type_full" value="full_payment">
                                            <label class="btn btn-outline-primary" for="payment_type_full">
                                                <i class="ti ti-discount-2 me-1"></i> Full Payment (5% OFF)
                                            </label>
                                        </div>
                                    @else
                                        {{-- Freelancer dynamic display based on deposit policy --}}
                                        @if($depositPolicy === 'required')
                                            <div class="alert alert-info">
                                                <i class="ti ti-info-circle me-2"></i>
                                                <strong>Payment Required:</strong> {{ $depositDisplay }}
                                            </div>
                                            
                                            @if($depositType === 'percentage')
                                                {{-- For percentage deposits, show the percentage clearly --}}
                                                <div class="bg-light p-3 rounded text-center">
                                                    <span class="text-muted d-block mb-1">Required Deposit</span>
                                                    <span class="display-6 fw-bold text-primary">{{ $depositAmount }}%</span>
                                                    <span class="text-muted d-block mt-1">of total package price</span>
                                                </div>
                                                <input type="hidden" name="payment_type" value="downpayment">
                                            @elseif($depositType === 'fixed')
                                                {{-- For fixed deposits, show the fixed amount --}}
                                                <div class="bg-light p-3 rounded text-center">
                                                    <span class="text-muted d-block mb-1">Required Fixed Deposit</span>
                                                    <span class="display-6 fw-bold text-primary">₱{{ number_format($depositAmount, 2) }}</span>
                                                    <span class="text-muted d-block mt-1">fixed amount regardless of package price</span>
                                                </div>
                                                <input type="hidden" name="payment_type" value="downpayment">
                                            @endif
                                        @else
                                            {{-- No deposit required --}}
                                            <div class="alert alert-success">
                                                <i class="ti ti-check-circle me-2"></i>
                                                <strong>No Deposit Required:</strong> Full payment will be collected upon booking.
                                            </div>
                                            <div class="bg-light p-3 rounded text-center">
                                                <span class="text-muted d-block mb-1">Payment Type</span>
                                                <span class="display-6 fw-bold text-success">Full Payment</span>
                                            </div>
                                            <input type="hidden" name="payment_type" value="full_payment">
                                        @endif
                                    @endif
                                </div>

                                {{-- EVENT LOCATION --}}
                                <h4 class="card-title text-primary mb-3">Event Location</h4>
                                <div class="mb-3">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Location Type</label>
                                            <select class="form-select" id="locationType" name="location_type" required>
                                                <option value="">Select Location Type</option>
                                                @if ($type === 'studio')
                                                    <option value="in-studio">In-Studio</option>
                                                @endif
                                                <option value="on-location">On-Location</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a valid location type.
                                            </div>
                                            <small class="text-muted location-type-note">
                                                <i class="ti ti-info-circle me-1"></i> Location type is automatically determined by your selected package.
                                            </small>
                                        </div>

                                        {{-- SINGLE LOCATION DETAILS (Default) --}}
                                        <div id="singleLocationDetails" style="display: none;">
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Venue Name</label>
                                                <input type="text" class="form-control" id="venueName" name="venue_name" 
                                                    placeholder="Enter venue name (e.g., Hotel, Resort, Event Hall)">
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label">City/Municipality</label>
                                                <select class="form-select" id="city" name="city" required>
                                                    <option value="">Select City/Municipality</option>
                                                    @foreach($municipalities as $municipality)
                                                        <option value="{{ $municipality }}">{{ $municipality }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Barangay</label>
                                                <select class="form-select" id="barangay" name="barangay" required disabled>
                                                    <option value="">Select Barangay</option>
                                                </select>
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Street / Building / Unit No.</label>
                                                <input type="text" class="form-control" id="street" name="street" 
                                                    placeholder="Enter street name, building, unit number (optional)">
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Province</label>
                                                <input type="text" class="form-control" id="province" name="province" 
                                                    value="Cavite" readonly>
                                            </div>
                                        </div>

                                        {{-- MULTIPLE LOCATIONS CONTAINER --}}
                                        <div id="multipleLocationsContainer" style="display: none;">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0 text-primary">Event Locations</h6>
                                                <span class="badge badge-soft-info" id="locationCounter">0/0 locations</span>
                                            </div>
                                            
                                            <div id="locationsList">
                                                {{-- Dynamic locations will be added here --}}
                                            </div>
                                            
                                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addLocationBtn" style="display: none;">
                                                <i class="ti ti-plus me-1"></i> Add Another Location
                                            </button>
                                            
                                            <small class="text-muted d-block mt-2" id="multipleLocationsNote"></small>
                                        </div>
                                    </div>
                                </div>

                                {{-- SPECIAL REQUESTS --}}
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Special Requests / Notes</label>
                                    <textarea class="form-control" rows="3" id="specialRequests" name="special_requests"
                                        placeholder="Enter special requests or notes..."></textarea>
                                </div>

                                {{-- TERMS & CONDITIONS --}}
                                <div class="mb-3">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="termsCheck"
                                            name="terms_agree" required>
                                        <label class="form-check-label" for="termsCheck">
                                            I agree to the <a href="#" class="text-primary">Booking Terms and
                                                Conditions</a>
                                        </label>
                                        <div class="invalid-feedback">
                                            You must agree to the terms and conditions.
                                        </div>
                                    </div>
                                </div>

                                {{-- SUBMIT BUTTON --}}
                                <div class="row">
                                    <div class="col">
                                        <button type="button" class="btn btn-primary w-100" id="submitBookingBtn">
                                            <span id="submitText">Proceed to Summary</span>
                                            <span id="loadingSpinner" class="spinner-border spinner-border-sm d-none"
                                                role="status" aria-hidden="true"></span>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BOOKING SUMMARY MODAL --}}
    <div class="modal fade" id="bookingSummaryModal" tabindex="-1" aria-labelledby="bookingSummaryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="bookingSummaryModalLabel">Booking Summary</h4>
                    <button type="button" class="btn btn-default btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- BOOKING SUMMARY --}}
                    <div class="mb-4">
                        <div class="mb-3">
                            <h5 class="text-primary mb-2">Client Information</h5>
                            <p class="text-muted small mb-1">Full Name:</p>
                            <p class="fw-medium mb-2" id="summaryFullName"></p>

                            <p class="text-muted small mb-1">Contact Number:</p>
                            <p class="fw-medium mb-2" id="summaryContactNumber"></p>

                            <p class="text-muted small mb-1">Email Address:</p>
                            <p class="fw-medium mb-2" id="summaryEmailAddress"></p>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <h5 class="text-primary mb-2">Booking Details</h5>
                            <p class="text-muted small mb-1">Selected Package:</p>
                            <p class="fw-medium mb-2" id="summaryPackage"></p>

                            <p class="text-muted small mb-1">Package Inclusions:</p>
                            <ul class="mb-2" id="summaryInclusions"></ul>

                            <p class="text-muted small mb-1">Event Date:</p>
                            <p class="fw-medium mb-2" id="summaryDate"></p>

                            <p class="text-muted small mb-1">Event Time:</p>
                            <p class="fw-medium mb-2" id="summaryTime"></p>

                            <p class="text-muted small mb-1">Location Type:</p>
                            <p class="fw-medium mb-2" id="summaryLocationType"></p>

                            <div id="summaryLocationDetails"></div>
                        </div>
                    </div>

                    <hr>

                    {{-- PRICE BREAKDOWN --}}
                    <div class="mb-3">
                        <h5 class="text-primary mb-2">Price Breakdown</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Package Price:</span>
                            <span class="fw-medium" id="packagePrice">₱0</span>
                        </div>

                        <div class="d-flex justify-content-between mb-2" id="downPaymentRow">
                            <span id="downPaymentLabel">Down Payment (30%):</span>
                            <span class="fw-medium" id="downPayment">₱0</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" id="remainingBalanceRow">
                            <span>Remaining Balance:</span>
                            <span class="fw-medium" id="remainingBalance">₱0</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-semibold">Total Amount:</span>
                            <span class="fw-semibold h5 text-success" id="totalAmount">₱0</span>
                        </div>
                    </div>

                    {{-- NEXT STEP --}}
                    <div class="d-grid">
                        <button type="button" class="btn btn-primary btn-lg" id="proceedToPaymentBtn">
                            <i class="ti ti-credit-card me-2"></i>Proceed to Payment
                        </button>
                    </div>

                    <p class="text-muted small text-center mt-3">
                        <i class="ti ti-info-circle me-1"></i>You'll review all details before payment
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- PAYMENT MODAL --}}
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Complete Payment</h5>
                    <button type="button" class="btn btn-default btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentContainer">
                        {{-- Payment form will be loaded here --}}
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
            // Initialize variables
            let selectedPackageId = null;
            let bookingData = null;
            let bookingId = null;
            let selectedPackageFlexibility = null;
            let selectedPackageDuration = null;
            let currentPackageLocationFlexibility = null;
            
            // ========== MULTIPLE LOCATION VARIABLES ==========
            let currentMaxLocations = 1;
            let allowMultipleLocations = false;
            let locationCount = 0;

            // ========== Operating Days Enforcement ==========
            const operatingDays = JSON.parse($('#operatingDays').val() || '[]');

            /**
             * Map day name to JS getDay() index (0 = Sunday, 6 = Saturday)
             */
            const dayNameToIndex = {
                'sunday': 0, 'monday': 1, 'tuesday': 2, 'wednesday': 3,
                'thursday': 4, 'friday': 5, 'saturday': 6
            };

            /**
             * Get array of operating day indices from operating days array
             */
            const operatingDayIndices = operatingDays
                .map(d => dayNameToIndex[d.toLowerCase()])
                .filter(d => d !== undefined);

            /**
             * Check if a date string falls on an operating day
             */
            function isOperatingDay(dateString) {
                if (!dateString) return false;
                const parts = dateString.split('-');
                // Use explicit constructor to avoid timezone shift
                const date = new Date(parts[0], parts[1] - 1, parts[2]);
                return operatingDayIndices.includes(date.getDay());
            }

            /**
             * Format operating days for readable display
             */
            function formatOperatingDays() {
                if (!operatingDays.length) return 'No operating schedule set';
                return operatingDays.map(d => d.charAt(0).toUpperCase() + d.slice(1)).join(', ');
            }

            // ========== EVENT HANDLERS ==========

            // Handle payment option selection
            $('input[name="payment_type"]').on('change', function() {
                const paymentType = $(this).val();
                
                if (selectedPackageId) {
                    const packageRadio = $(`.package-radio[value="${selectedPackageId}"]`);
                    if (packageRadio.length) {
                        const packageData = packageRadio.data('package');
                        
                        // For freelancer, we still pass payment_type but server will override based on deposit policy
                        // For studio, this works normally
                        getBookingSummaryWithPaymentType(packageData, paymentType);
                    }
                }
            });

            // Initialize the checked state on page load
            $('input[name="payment_type"]:checked').trigger('change');

            // Load packages when category is selected
            $('#serviceCategory').on('change', function() {
                // Reset location type when category changes
                $('#locationType').val('').prop('disabled', false);
                $('#locationType').closest('.col-12').find('.badge.badge-soft-info').remove();
                
                const categoryId = $(this).val();
                const type = $('#bookingType').val();
                const providerId = $('#providerId').val();
                
                if (!categoryId) {
                    $('#packagesContainer').html(`
                        <div class="alert alert-warning">
                            <i class="ti ti-warning-circle me-2"></i> Please select a category first to view available packages.
                        </div>
                    `);
                    return;
                }
                
                // Show loading
                $('#packagesContainer').html(`
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2 text-muted">Loading packages...</span>
                    </div>
                `);
                
                $.ajax({
                    url: '{{ route("client.bookings.packages") }}',
                    type: 'POST',
                    data: {
                        type: type,
                        provider_id: providerId,
                        category_id: categoryId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('Packages response:', response);
                        
                        if (response.success && response.packages && response.packages.length > 0) {
                            let packagesHtml = '<div class="row g-3">';
                            
                            response.packages.forEach(function(package, index) {
                                // FIXED: Ensure package data is properly stringified for the data-package attribute
                                const packageJson = JSON.stringify(package)
                                    .replace(/"/g, '&quot;') // Escape quotes for HTML attribute
                                    .replace(/'/g, "&#39;"); // Escape single quotes
                                
                                const priceText = `₱${parseFloat(package.package_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                                
                                // Parse inclusions safely
                                let inclusions = [];
                                if (package.package_inclusions) {
                                    try {
                                        if (typeof package.package_inclusions === 'string') {
                                            // Handle the specific format from your database
                                            let cleanedStr = package.package_inclusions;
                                            // Remove outer quotes if they exist
                                            if (cleanedStr.startsWith('"') && cleanedStr.endsWith('"')) {
                                                cleanedStr = cleanedStr.slice(1, -1);
                                            }
                                            // Split by comma if it's a comma-separated string
                                            if (cleanedStr.includes(',')) {
                                                inclusions = cleanedStr.split(',').map(item => item.trim());
                                            } else {
                                                // Try JSON parse
                                                try {
                                                    const parsed = JSON.parse(cleanedStr);
                                                    if (Array.isArray(parsed)) {
                                                        inclusions = parsed;
                                                    } else {
                                                        inclusions = [parsed];
                                                    }
                                                } catch (e) {
                                                    // If all else fails, use as single item
                                                    inclusions = [cleanedStr];
                                                }
                                            }
                                        } else if (Array.isArray(package.package_inclusions)) {
                                            inclusions = package.package_inclusions;
                                        }
                                    } catch (e) {
                                        console.warn('Error parsing inclusions:', e);
                                    }
                                }
                                
                                const isStudio = $('#bookingType').val() === 'studio';
                                
                                // === START: Updated location badge generation ===
                                // Parse package location properly
                                let packageLocations = package.package_location;
                                let locationBadges = '';

                                // Ensure we're working with an array
                                if (typeof packageLocations === 'string') {
                                    try {
                                        packageLocations = JSON.parse(packageLocations);
                                    } catch (e) {
                                        // If parsing fails, treat as single value
                                        packageLocations = [packageLocations];
                                    }
                                } else if (!Array.isArray(packageLocations)) {
                                    packageLocations = packageLocations ? [packageLocations] : ['In-Studio'];
                                }

                                // Generate badges for each location
                                if (Array.isArray(packageLocations)) {
                                    packageLocations.forEach(function(location) {
                                        // Clean up the location string (remove any quotes or extra spaces)
                                        location = location.replace(/["']/g, '').trim();
                                        
                                        if (location === 'On-Location') {
                                            locationBadges += '<span class="badge badge-soft-info me-1 mb-1"><i class="ti ti-map-pin me-1"></i> On-Location</span>';
                                        } else if (location === 'In-Studio') {
                                            locationBadges += '<span class="badge badge-soft-primary me-1 mb-1"><i class="ti ti-building me-1"></i> In-Studio</span>';
                                        } else {
                                            locationBadges += '<span class="badge badge-soft-secondary me-1 mb-1">' + location + '</span>';
                                        }
                                    });
                                }

                                // If multiple locations, add a "Flexible" indicator
                                if (Array.isArray(packageLocations) && packageLocations.length > 1) {
                                    locationBadges += '<span class="badge badge-soft-success me-1 mb-1"><i class="ti ti-arrows-maximize me-1"></i> Flexible</span>';
                                }
                                // === END: Updated location badge generation ===
                                
                                // ==== Start: Display package flexibility status ====
                                console.log('Package ID:', package.id, 'allow_time_customization:', package.allow_time_customization, 'type:', typeof package.allow_time_customization);

                                let flexibilityBadge = '';
                                // Check for truthy values in various formats
                                const isFlexible = package.allow_time_customization == 1 || 
                                                package.allow_time_customization === true || 
                                                package.allow_time_customization === '1' || 
                                                package.allow_time_customization === 'true';

                                if (isFlexible) {
                                    flexibilityBadge = '<span class="badge badge-soft-success"><i class="ti ti-clock-edit me-1"></i> Flexible Time</span>';
                                } else {
                                    flexibilityBadge = '<span class="badge badge-soft-secondary"><i class="ti ti-clock me-1"></i> Fixed Duration</span>';
                                }
                                // ==== End: Display package flexibility status ====
                                
                                packagesHtml += `
                                    <div class="col-md-6 col-xl-4">
                                        <input type="radio" class="btn-check package-radio" 
                                            name="package" value="${package.id}" 
                                            id="package${package.id}" 
                                            data-package='${packageJson}'
                                            data-allow-time-customization="${package.allow_time_customization ? '1' : '0'}"
                                            data-duration="${package.duration || 0}"
                                            style="display: none;">
                                        
                                        <label class="card border h-100 package-card" for="package${package.id}" style="cursor: pointer;">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="card-title fw-bold mb-0">${package.package_name}</h6>
                                                    <span class="text-success fw-bold">${priceText}</span>
                                                </div>
                                                
                                                <p class="text-muted small mb-3">${package.package_description ? package.package_description.substring(0, 80) + (package.package_description.length > 80 ? '...' : '') : 'No description available.'}</p>
                                                
                                                <!-- Location badges display -->
                                                <div class="d-flex align-items-center flex-wrap mb-2">
                                                    ${locationBadges}
                                                </div>
                                                
                                                <!-- ==== Start: Flexibility status display ==== -->
                                                <div class="d-flex align-items-center mb-2">
                                                    ${flexibilityBadge}
                                                </div>
                                                <!-- ==== End: Flexibility status display ==== -->
                                                
                                                <div class="d-flex align-items-center mb-2">
                                                    ${package.online_gallery ? 
                                                        `<span class="p-1 badge badge-soft-success">
                                                            <i class="ti ti-photo me-1"></i> Online Gallery: Included
                                                        </span>` : 
                                                        `<span class="p-1 badge badge-soft-warning">
                                                            <i class="ti ti-photo-off me-1"></i> Online Gallery: Not Included
                                                        </span>`
                                                    }
                                                </div>
                                                
                                                ${isStudio ? `
                                                    <div class="d-flex align-items-center mb-3">
                                                        <span class="p-1 badge badge-soft-primary">
                                                            <i class="ti ti-users me-1"></i>
                                                            Photographers: ${package.photographer_count || 1}
                                                            ${(package.photographer_count || 1) > 1 ? 'photographers' : 'photographer'}
                                                        </span>
                                                    </div>
                                                ` : ''}
                                                
                                                <div class="col">
                                                    <small class="text-muted d-block mb-2"><i class="ti ti-checklist me-1"></i> Package Includes:</small>
                                                    <ul class="list-unstyled small mb-0">
                                                        ${!package.allow_time_customization ? `
                                                            <li class="mb-1">
                                                                <i class="ti ti-clock text-primary me-2"></i> 
                                                                ${package.duration ? 
                                                                    'Fixed Duration: ' + package.duration + (package.duration > 1 ? ' hours' : ' hour') + ' (client must book exactly this duration)' : 
                                                                    'Fixed Duration Package'}
                                                            </li>
                                                        ` : `
                                                            <li class="mb-1">
                                                                <i class="ti ti-clock-edit text-success me-2"></i> 
                                                                Flexible Time: Client can choose any duration
                                                            </li>
                                                        `}
                                                        
                                                        ${package.maximum_edited_photos ? `
                                                            <li class="mb-1">
                                                                <i class="ti ti-camera text-primary me-2"></i> 
                                                                ${package.maximum_edited_photos} edited photos
                                                            </li>
                                                        ` : ''}
                                                        
                                                        ${inclusions.map(inclusion => `
                                                            <li class="mb-1">
                                                                <i class="ti ti-check text-success me-2"></i> 
                                                                ${inclusion}
                                                            </li>
                                                        `).join('')}
                                                        
                                                        ${package.coverage_scope ? `
                                                            <li class="mb-1">
                                                                <i class="ti ti-map-pin text-primary me-2"></i> 
                                                                Coverage: ${package.coverage_scope}
                                                            </li>
                                                        ` : ''}
                                                    </ul>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                `;
                            });
                            
                            packagesHtml += '</div>';
                            $('#packagesContainer').html(packagesHtml);
                            
                            selectedPackageId = null;
                            
                            $('<style>')
                                .prop('type', 'text/css')
                                .html(`
                                    .btn-check:checked + .package-card {
                                        border-color: #3475db !important;
                                    }
                                `)
                                .appendTo('head');
                            
                        } else {
                            let message = 'No packages available for this service/category.';
                            if (response.message) {
                                message = response.message;
                            }
                            
                            $('#packagesContainer').html(`
                                <div class="alert alert-warning">
                                    <i class="ti ti-package-off me-2"></i> ${message}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        console.error('Packages AJAX error:', xhr);
                        
                        $('#packagesContainer').html(`
                            <div class="alert alert-danger">
                                <i class="ti ti-alert-circle me-2"></i> Failed to load packages. Please try again.
                            </div>
                        `);
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Loading Error',
                            text: 'Failed to load packages. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            });
            
            // ========== PACKAGE SELECTION HANDLER ==========
            $(document).on('change', '.package-radio', function() {
                selectedPackageId = $(this).val();
                selectedPackageFlexibility = $(this).data('allow-time-customization') === '1';
                selectedPackageDuration = parseInt($(this).data('duration')) || 0;
                
                console.log('Package selected:', {
                    id: selectedPackageId,
                    flexible: selectedPackageFlexibility,
                    duration: selectedPackageDuration
                });
                
                // SAFELY get package data with error handling
                let packageData;
                try {
                    const dataAttr = $(this).attr('data-package');
                    if (!dataAttr || dataAttr === 'undefined' || dataAttr === 'null') {
                        console.error('Package data attribute is missing or invalid');
                        Swal.fire({
                            icon: 'error',
                            title: 'Package Data Error',
                            text: 'Unable to load package details. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                        return;
                    }
                    
                    packageData = JSON.parse(dataAttr);
                    console.log('Selected package data:', packageData);
                    
                } catch (e) {
                    console.error('Error parsing package data:', e);
                    console.log('Raw data attribute:', $(this).attr('data-package'));
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Package Data Error',
                        text: 'Unable to parse package details. Please refresh and try again.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                // ==== START: Reset and prepare location UI based on booking type ====
                resetLocationUI();
                
                // Get booking type
                const bookingType = $('#bookingType').val();
                
                // ==== FIXED: Store freelancer policy info for later use ====
                if (bookingType === 'freelancer') {
                    // Initialize freelancer settings storage if not exists
                    if (!window.currentFreelancerSettings) {
                        window.currentFreelancerSettings = {};
                    }
                    
                    // Check if package data contains freelancer policy information
                    // The freelancer_has_policy field should be sent from the server
                    const hasDepositPolicy = packageData.freelancer_has_policy === true;
                    const depositPolicy = packageData.freelancer_deposit_policy;
                    const depositType = packageData.freelancer_deposit_type;
                    const depositAmount = packageData.freelancer_deposit_amount;
                    
                    window.currentFreelancerSettings = {
                        hasDepositPolicy: hasDepositPolicy,
                        depositPolicy: depositPolicy,
                        depositType: depositType,
                        depositAmount: depositAmount
                    };
                    
                    console.log('Freelancer settings stored:', window.currentFreelancerSettings);
                }
                // ==== END: Store freelancer policy info ====
                
                // ==== NEW: Special handling for freelancer bookings ====
                if (bookingType === 'freelancer') {
                    console.log('Freelancer package selected - multiple location check:', {
                        allowMultipleLocations: packageData.allow_multiple_locations,
                        maxLocations: packageData.max_locations
                    });
                    
                    // For freelancers, location type is always on-location
                    $('#locationType').val('on-location');
                    $('#locationType').prop('disabled', true);
                    
                    // Add visual indicator
                    $('#locationType').closest('.col-12').find('.form-label').append(
                        '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                        '<i class="ti ti-info-circle me-1"></i>On-Location only for freelancers</span>'
                    );
                    
                    // Check if multiple locations are allowed
                    const allowMultiple = packageData.allow_multiple_locations === true || 
                                        packageData.allow_multiple_locations === '1' || 
                                        packageData.allow_multiple_locations === 1;
                    const maxLocations = parseInt(packageData.max_locations) || 1;
                    
                    // Store these for later use
                    window.currentPackageSettings = {
                        allowMultipleLocations: allowMultiple,
                        maxLocations: maxLocations,
                        locationType: 'on-location',
                        bookingType: 'freelancer'
                    };
                    
                    // Initialize multiple location UI if allowed and max > 1
                    if (allowMultiple && maxLocations > 1) {
                        console.log('Initializing multiple location UI for freelancer with max:', maxLocations);
                        initMultipleLocationUI(packageData);
                    } else {
                        // Show single location UI
                        showSingleLocationUI();
                    }
                    
                } else {
                    // ==== Original studio logic (unchanged) ====
                    if (packageData.location_flexibility) {
                        handlePackageLocationFlexibility(packageData);
                    } else {
                        console.warn('No location flexibility data, using fallback');
                        if (packageData.package_location) {
                            const packageLocation = packageData.package_location;
                            
                            if (Array.isArray(packageLocation)) {
                                if (packageLocation.length === 1) {
                                    const location = packageLocation[0];
                                    if (location === 'In-Studio') {
                                        $('#locationType').val('in-studio');
                                        $('#locationType').prop('disabled', true);
                                        $('#locationType').closest('.col-12').find('.form-label').append(
                                            '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                                            '<i class="ti ti-info-circle me-1"></i>Auto-set by package</span>'
                                        );
                                    } else if (location === 'On-Location') {
                                        $('#locationType').val('on-location');
                                        $('#locationType').prop('disabled', true);
                                        $('#locationType').closest('.col-12').find('.form-label').append(
                                            '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                                            '<i class="ti ti-info-circle me-1"></i>Auto-set by package</span>'
                                        );
                                    }
                                } else if (packageLocation.length > 1) {
                                    $('#locationType').prop('disabled', false);
                                    $('#locationType').val('');
                                }
                            } else if (typeof packageLocation === 'string') {
                                if (packageLocation === 'In-Studio') {
                                    $('#locationType').val('in-studio');
                                    $('#locationType').prop('disabled', true);
                                    $('#locationType').closest('.col-12').find('.form-label').append(
                                        '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                                        '<i class="ti ti-info-circle me-1"></i>Auto-set by package</span>'
                                    );
                                } else if (packageLocation === 'On-Location') {
                                    $('#locationType').val('on-location');
                                    $('#locationType').prop('disabled', true);
                                    $('#locationType').closest('.col-12').find('.form-label').append(
                                        '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                                        '<i class="ti ti-info-circle me-1"></i>Auto-set by package</span>'
                                    );
                                }
                            }
                            
                            $('#locationType').trigger('change');
                        }
                    }
                    
                    // Check if multiple locations are allowed (for studios)
                    const allowMultiple = packageData.allow_multiple_locations === true || 
                                        packageData.allow_multiple_locations === '1' || 
                                        packageData.allow_multiple_locations === 1;
                    const maxLocations = parseInt(packageData.max_locations) || 1;
                    
                    // Store these for later use
                    window.currentPackageSettings = {
                        allowMultipleLocations: allowMultiple,
                        maxLocations: maxLocations,
                        locationType: $('#locationType').val(),
                        bookingType: 'studio'
                    };
                    
                    // Only show multiple location UI for studios if location type is on-location
                    if (allowMultiple && maxLocations > 1 && $('#locationType').val() === 'on-location') {
                        initMultipleLocationUI(packageData);
                    }
                }
                // ==== END: Special handling for freelancer bookings ====
                
                const paymentType = $('input[name="payment_type"]:checked').val();
                getBookingSummaryWithPaymentType(packageData, paymentType);
                
                // Show/hide duration info based on package flexibility
                updateTimeRestrictionInfo();
            });

            // ========== ADD LOCATION BUTTON HANDLER ==========
            $(document).on('click', '#addLocationBtn', function() {
                addLocation();
            });

            // ========== REMOVE LOCATION BUTTON HANDLER ==========
            $(document).on('click', '.remove-location-btn', function() {
                removeLocation($(this));
            });
            
            // ========== LOCATION TYPE CHANGE HANDLER ==========
            $('#locationType').on('change', function() {
                const locationValue = $(this).val();
                
                // Check if this is from flexible selection or direct
                const isFlexible = currentPackageLocationFlexibility && 
                                currentPackageLocationFlexibility.is_flexible;
                
                // Remove any existing auto-set badges when user changes location
                $('.location-auto-set-badge').remove();
                
                if (locationValue === 'on-location') {
                    // Check if we have package settings and if multiple locations are allowed
                    if (window.currentPackageSettings && 
                        window.currentPackageSettings.allowMultipleLocations && 
                        window.currentPackageSettings.maxLocations > 1) {
                        // Show multiple location UI
                        initMultipleLocationUI(window.currentPackageSettings);
                    } else {
                        // Show single location UI
                        showSingleLocationUI();
                    }
                    
                    // If from flexible selection, add visual indicator
                    if (isFlexible) {
                        $('.flexible-location-ui .btn-outline-primary').removeClass('active');
                        $('input[value="on-location"]').next('label').addClass('active');
                    }
                } else if (locationValue === 'in-studio') {
                    // Hide all location details for in-studio
                    $('#singleLocationDetails').hide();
                    $('#multipleLocationsContainer').hide();
                    
                    // Clear any location data
                    $('#venueName').val('');
                    $('#city').val('').trigger('change');
                    $('#barangay').prop('disabled', true).html('<option value="">Select Barangay</option>');
                    $('#street').val('');
                    
                    // If from flexible selection, add visual indicator
                    if (isFlexible) {
                        $('.flexible-location-ui .btn-outline-primary').removeClass('active');
                        $('input[value="in-studio"]').next('label').addClass('active');
                    }
                } else {
                    // No location type selected
                    $('#singleLocationDetails').hide();
                    $('#multipleLocationsContainer').hide();
                }
            });
            
            // ========== CHECK DATE AVAILABILITY ==========
            $('#checkDateBtn').on('click', function() {
                const selectedDate = $('#eventDate').val();
                const startTime    = $('#startTime').val();
                const endTime      = $('#endTime').val();
                const type         = $('#bookingType').val();
                const providerId   = $('#providerId').val();

                if (!selectedDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Date Selected',
                        text: 'Please select a date first.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                if (!startTime || !endTime) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Time Required',
                        text: 'Please enter both start time and end time before checking availability.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                // Show checking status
                $('#dateStatusIcon').html('<i class="ti ti-clock text-info"></i>');
                $('#dateStatusText').text('Checking availability...');

                $.ajax({
                    url: '{{ route("client.bookings.check-availability") }}',
                    type: 'POST',
                    data: {
                        type:        type,
                        provider_id: providerId,
                        date:        selectedDate,
                        start_time:  startTime,
                        end_time:    endTime,
                        _token:      '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.available) {
                            $('#dateStatusIcon').html('<i class="ti ti-circle-check text-success"></i>');
                            $('#dateStatusText').html(`
                                <span class="text-success fw-medium">Available</span> 
                                <span class="text-muted">(${response.existing_bookings}/${response.max_bookings} bookings)</span>
                            `);
                            $('#submitBookingBtn').prop('disabled', false);
                        } else {
                            $('#dateStatusIcon').html('<i class="ti ti-circle-x text-danger"></i>');
                            $('#dateStatusText').html(`
                                <span class="text-danger fw-medium">${response.message || 'Not Available'}</span>
                            `);
                            $('#submitBookingBtn').prop('disabled', true);

                            Swal.fire({
                                icon: 'warning',
                                title: 'Not Available',
                                text: response.message || 'This time slot is not available.',
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#dateStatusIcon').html('<i class="ti ti-alert-circle text-danger"></i>');
                        $('#dateStatusText').html('<span class="text-danger fw-medium">Error checking availability</span>');
                        $('#submitBookingBtn').prop('disabled', true);

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to check availability. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            });

            // ========== AUTO-CHECK DATE/TIME CHANGES ==========
            $('#eventDate, #startTime, #endTime').on('change', function() {
                const selectedDate = $('#eventDate').val();
                const startTime    = $('#startTime').val();
                const endTime      = $('#endTime').val();

                if (!selectedDate) {
                    $('#dateStatusIcon').empty();
                    $('#dateStatusText').text('Select a date to check availability');
                    $('#closedDayNote').hide();
                    $('#submitBookingBtn').prop('disabled', false);
                    return;
                }

                // Block non-operating days immediately without server call
                if (!isOperatingDay(selectedDate)) {
                    const parts   = selectedDate.split('-');
                    const dayName = new Date(parts[0], parts[1] - 1, parts[2])
                        .toLocaleDateString('en-US', { weekday: 'long' });

                    $('#dateStatusIcon').html('<i class="ti ti-circle-x text-danger"></i>');
                    $('#dateStatusText').html(
                        `<span class="text-danger fw-medium">${dayName} is not an operating day</span>`
                    );
                    $('#closedDayNote').text('Operating days: ' + formatOperatingDays()).show();
                    $('#submitBookingBtn').prop('disabled', true);
                    return;
                }

                // Only auto-check if all three fields are filled
                if (selectedDate && startTime && endTime) {
                    $('#closedDayNote').hide();
                    $('#checkDateBtn').trigger('click');
                } else {
                    $('#dateStatusIcon').html('<i class="ti ti-info-circle text-info"></i>');
                    $('#dateStatusText').html('<span class="text-muted">Please fill in start and end time to check availability</span>');
                }
            });
            
            // ========== SUBMIT BOOKING BUTTON ==========
            $('#submitBookingBtn').on('click', function() {
                console.log('Submit button clicked');
                console.log('Current state:', {
                    allowMultipleLocations: allowMultipleLocations,
                    currentMaxLocations: currentMaxLocations,
                    locationCount: locationCount,
                    locationType: $('#locationType').val()
                });
                
                if (!validateBookingForm()) {
                    console.log('Form validation failed');
                    return;
                }
                
                console.log('Form validation passed, preparing booking data');
                
                // Get booking type
                const bookingType = $('#bookingType').val();
                
                // Prepare booking data base
                bookingData = {
                    type: bookingType,
                    provider_id: $('#providerId').val(),
                    category_id: $('#serviceCategory').val(),
                    package_id: selectedPackageId,
                    event_date: $('#eventDate').val(),
                    start_time: $('#startTime').val(),
                    end_time: $('#endTime').val(),
                    location_type: $('#locationType').val(),
                    special_requests: $('#specialRequests').val(),
                    full_name: $('#fullName').val(),
                    contact_number: $('#contactNumber').val(),
                    email: $('#email').val(),
                    _token: '{{ csrf_token() }}'
                };
                
                // ==== FIXED: Handle payment type based on booking type and policy ====
                if (bookingType === 'freelancer') {
                    // Check if freelancer has a deposit policy
                    const hasPolicy = window.currentFreelancerSettings?.hasDepositPolicy === true;
                    
                    if (hasPolicy) {
                        // Freelancer has policy - don't send payment_type, server will determine
                        console.log('Freelancer (has policy) - skipping payment_type');
                    } else {
                        // Freelancer has no policy - send the hidden payment_type value
                        // Get the hidden input value
                        const hiddenPaymentType = $('input[name="payment_type"][type="hidden"]').val();
                        if (hiddenPaymentType) {
                            bookingData.payment_type = hiddenPaymentType;
                            console.log('Freelancer (no policy) - sending payment_type from hidden:', hiddenPaymentType);
                        }
                    }
                } else {
                    // Studio always sends payment_type from radio selection
                    bookingData.payment_type = $('input[name="payment_type"]:checked').val();
                    console.log('Studio booking - sending payment_type:', bookingData.payment_type);
                }
                // ==== END: Handle payment type ====
                
                // Handle multiple locations
                if (allowMultipleLocations && currentMaxLocations > 1 && $('#locationType').val() === 'on-location') {
                    const locations = getMultipleLocationsData();
                    console.log('Multiple locations data:', locations);
                    
                    if (locations && locations.length > 0) {
                        bookingData.locations = locations;
                        // Clear single location fields
                        bookingData.venue_name = null;
                        bookingData.street = null;
                        bookingData.barangay = null;
                        bookingData.city = null;
                    } else {
                        console.error('No valid location data found');
                        Swal.fire({
                            icon: 'error',
                            title: 'Location Error',
                            text: 'Please fill in all required location fields.',
                            confirmButtonColor: '#3475db'
                        });
                        return;
                    }
                } else {
                    // Single location
                    bookingData.venue_name = $('#venueName').val();
                    bookingData.street = $('#street').val();
                    bookingData.barangay = $('#barangay').val();
                    bookingData.city = $('#city').val();
                }
                
                console.log('Final booking data:', bookingData);
                showBookingSummary();
            });
            
            // ========== PROCEED TO PAYMENT ==========
            $('#proceedToPaymentBtn').on('click', function() {
                processBooking();
            });

            // ========== CITY CHANGE HANDLER (for single location) ==========
            $(document).on('change', '#city', function() {
                const municipality = $(this).val();
                const barangaySelect = $('#barangay');
                
                if (!municipality) {
                    barangaySelect.prop('disabled', true).html('<option value="">Select Barangay</option>');
                    return;
                }
                
                barangaySelect.prop('disabled', true).html('<option value="">Loading barangays...</option>');
                
                $.ajax({
                    url: '{{ route("client.locations.barangays") }}',
                    type: 'POST',
                    data: {
                        municipality: municipality,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success && response.barangays && response.barangays.length > 0) {
                            let options = '<option value="">Select Barangay</option>';
                            const sortedBarangays = response.barangays.sort();
                            sortedBarangays.forEach(function(barangay) {
                                options += `<option value="${barangay}">${barangay}</option>`;
                            });
                            barangaySelect.html(options).prop('disabled', false);
                        } else {
                            barangaySelect.html('<option value="">No barangays available</option>').prop('disabled', true);
                            Swal.fire({
                                icon: 'warning',
                                title: 'No Barangays Found',
                                text: 'No barangay data available for this municipality.',
                                confirmButtonColor: '#3475db'
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Barangay load error:', xhr);
                        barangaySelect.html('<option value="">Error loading barangays</option>').prop('disabled', true);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load barangays. Please try again.',
                            confirmButtonColor: '#3475db'
                        });
                    }
                });
            });
            
            // ========== FUNCTIONS ==========

            /**
             * Update time restriction info based on package flexibility
             */
            function updateTimeRestrictionInfo() {
                const timeHelpText = $('#dateStatusText').parent().find('.time-restriction-info');
                timeHelpText.remove();
                
                if (selectedPackageFlexibility === false && selectedPackageDuration > 0) {
                    // Fixed duration package - show restriction info
                    const restrictionHtml = `
                        <div class="alert alert-info mt-2 mb-0 py-2 time-restriction-info" style="font-size: 0.85rem;">
                            <i class="ti ti-info-circle me-1"></i>
                            <strong>Fixed Duration Package:</strong> You must select a time range of exactly 
                            <strong>${selectedPackageDuration} ${selectedPackageDuration > 1 ? 'hours' : 'hour'}</strong>.
                            The system will automatically calculate and validate the end time.
                        </div>
                    `;
                    
                    // Insert after the date status text
                    $('#dateStatusText').parent().append(restrictionHtml);
                    
                    // Add auto-calculation for end time when start time changes
                    $('#startTime').off('change.calcEndTime').on('change.calcEndTime', function() {
                        const startTime = $(this).val();
                        if (startTime && selectedPackageFlexibility === false && selectedPackageDuration > 0) {
                            calculateEndTime(startTime, selectedPackageDuration);
                        }
                    });
                    
                    // Trigger calculation if start time already has a value
                    const currentStartTime = $('#startTime').val();
                    if (currentStartTime) {
                        calculateEndTime(currentStartTime, selectedPackageDuration);
                    }
                    
                } else if (selectedPackageFlexibility === true) {
                    // Flexible package - show info
                    const flexibleHtml = `
                        <div class="alert alert-success mt-2 mb-0 py-2 time-restriction-info" style="font-size: 0.85rem;">
                            <i class="ti ti-clock-edit me-1"></i>
                            <strong>Flexible Time Package:</strong> You can select any start and end time that works for you.
                        </div>
                    `;
                    
                    $('#dateStatusText').parent().append(flexibleHtml);
                    
                    // Remove auto-calculation
                    $('#startTime').off('change.calcEndTime');
                }
            }

            /**
             * Calculate end time based on start time and duration
             */
            function calculateEndTime(startTime, durationHours) {
                if (!startTime || !durationHours) return;
                
                const [hours, minutes] = startTime.split(':').map(Number);
                const startDate = new Date();
                startDate.setHours(hours, minutes, 0);
                
                const endDate = new Date(startDate.getTime() + (durationHours * 60 * 60 * 1000));
                
                const endHours = endDate.getHours().toString().padStart(2, '0');
                const endMinutes = endDate.getMinutes().toString().padStart(2, '0');
                const calculatedEndTime = `${endHours}:${endMinutes}`;
                
                $('#endTime').val(calculatedEndTime);
                console.log(`Auto-calculated end time: ${calculatedEndTime} for start time ${startTime} + ${durationHours} hours`);
                
                // Trigger change event to check availability
                $('#endTime').trigger('change');
            }

            /**
             * Get booking summary with payment type
             */
            function getBookingSummaryWithPaymentType(packageData, paymentType) {
                if (!packageData) {
                    console.error('Package data is null or undefined');
                    return;
                }
                
                if (!packageData.id) {
                    console.error('Package ID is missing from package data:', packageData);
                    return;
                }
                
                console.log('Getting summary for package:', packageData.id, 'Payment type:', paymentType);
                
                $.ajax({
                    url: '{{ route("client.bookings.summary") }}',
                    type: 'POST',
                    data: {
                        package_id: packageData.id,
                        type: $('#bookingType').val(),
                        payment_type: paymentType,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        console.log('Summary response:', response);
                        
                        if (response.success) {
                            window.bookingSummary = response.summary;
                            
                            if ($('#bookingSummaryModal').hasClass('show')) {
                                updateSummaryPriceDisplay(response.summary);
                            }
                            
                            // Handle fixed deposit display for freelancer
                            @if($type === 'freelancer')
                                if (response.summary.deposit_type === 'fixed') {
                                    $('#downPaymentLabel').text('Fixed Deposit:');
                                    $('#downPayment').text('₱' + response.summary.down_payment);
                                    
                                    const depositInfo = `
                                        <div class="alert alert-info mt-2 py-2 small">
                                            <i class="ti ti-info-circle me-1"></i>
                                            This freelancer requires a fixed deposit of ₱${response.summary.deposit_amount}.
                                            ${parseFloat(response.summary.down_payment) < parseFloat(response.summary.total_amount.replace(/,/g, '')) ? 
                                                'The remaining balance will be paid after the event.' : 
                                                'This covers the full amount.'}
                                        </div>
                                    `;
                                    
                                    $('.fixed-deposit-info').remove();
                                    $('#downPaymentRow').after(depositInfo);
                                } else {
                                    const percentage = response.summary.downpayment_percentage || 30;
                                    $('#downPaymentLabel').text(`Down Payment (${percentage}%):`);
                                    $('.fixed-deposit-info').remove();
                                }
                            @else
                                const percentage = response.summary.downpayment_percentage || 30;
                                $('#downPaymentLabel').text(`Down Payment (${percentage}%):`);
                            @endif
                        } else {
                            console.error('Summary response error:', response.message);
                        }
                    },
                    error: function(xhr) {
                        console.error('Summary AJAX error:', xhr);
                        console.error('Summary error response:', xhr.responseJSON);
                    }
                });
            }

            /**
             * Validate booking form
             */
            function validateBookingForm() {
                console.log('========== VALIDATION START ==========');
                
                // Manual validation for required fields (skip HTML5 checkValidity)
                const fullName = $('#fullName').val()?.trim();
                const contactNumber = $('#contactNumber').val()?.trim();
                const email = $('#email').val()?.trim();
                const serviceCategory = $('#serviceCategory').val();
                const eventDate = $('#eventDate').val();
                const startTime = $('#startTime').val();
                const endTime = $('#endTime').val();

                console.log('Validating required fields:', {
                    fullName, contactNumber, email, serviceCategory, eventDate, startTime, endTime
                });

                if (!fullName) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Full Name Required',
                        text: 'Please enter your full name.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                if (!contactNumber) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Contact Number Required',
                        text: 'Please enter your contact number.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                if (!email) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Email Required',
                        text: 'Please enter your email address.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                // Basic email format validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Invalid Email',
                        text: 'Please enter a valid email address.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                if (!serviceCategory) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Service Category Required',
                        text: 'Please select a service category.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                if (!eventDate) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Event Date Required',
                        text: 'Please select an event date.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }

                if (!startTime || !endTime) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Time Required',
                        text: 'Please select both start and end time.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                // Check date availability status
                const dateStatusText = $('#dateStatusText').text().toLowerCase();
                console.log('Date status text:', dateStatusText);
                
                if (dateStatusText.includes('fully booked') || 
                    dateStatusText.includes('not available') || 
                    dateStatusText.includes('error') ||
                    dateStatusText.includes('not an operating day') ||
                    dateStatusText.includes('duration mismatch')) {
                    console.log('Date availability check failed');
                    Swal.fire({
                        icon: 'error',
                        title: 'Date/Time Not Available',
                        text: 'Please select an available date and valid time before proceeding.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                // Check if date has been checked
                if ($('#dateStatusText').text() === 'Select a date to check availability') {
                    console.log('Date not checked');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Date Not Checked',
                        text: 'Please check the availability of your selected date first.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                // Check if package is selected
                if (!selectedPackageId) {
                    console.log('No package selected');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Package Required',
                        text: 'Please select a package.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                // Get location type and booking type
                const locationType = $('#locationType').val();
                const bookingType = $('#bookingType').val();
                
                console.log('Location type:', locationType, 'Booking type:', bookingType);
                
                // ========== LOCATION VALIDATION ==========
                if (bookingType === 'freelancer') {
                    console.log('Validating freelancer booking - location must be on-location');
                    
                    // Location type should already be set to on-location, but double-check
                    if (locationType !== 'on-location') {
                        console.error('Freelancer booking with invalid location type:', locationType);
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Location',
                            text: 'Freelancer bookings must be On-Location. Please refresh and try again.',
                            confirmButtonColor: '#3475db'
                        });
                        return false;
                    }
                    
                    // Check if multiple locations are enabled and validate accordingly
                    if (allowMultipleLocations && currentMaxLocations > 1) {
                        console.log('Validating multiple locations for freelancer with max:', currentMaxLocations);
                        
                        const locationEntries = $('.location-entry');
                        console.log('Location entries found:', locationEntries.length);
                        
                        if (locationEntries.length === 0) {
                            console.log('No location entries found');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Location Required',
                                text: 'Please add at least one location.',
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                        
                        let allLocationsValid = true;
                        let firstInvalidIndex = -1;
                        
                        $('.location-entry').each(function(index) {
                            const elementIndex = $(this).data('index');
                            const city = $(`select[name="locations[${elementIndex}][city]"]`).val();
                            const barangay = $(`select[name="locations[${elementIndex}][barangay]"]`).val();
                            
                            if (!city || !barangay) {
                                allLocationsValid = false;
                                if (firstInvalidIndex === -1) {
                                    firstInvalidIndex = index;
                                }
                            }
                        });
                        
                        if (!allLocationsValid) {
                            console.log('Some locations are incomplete');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Incomplete Locations',
                                text: `Location #${firstInvalidIndex + 1} is missing required fields (City/Municipality and Barangay).`,
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                        
                        const validLocations = getMultipleLocationsData();
                        if (validLocations.length > currentMaxLocations) {
                            console.log('Too many locations:', validLocations.length, 'max:', currentMaxLocations);
                            Swal.fire({
                                icon: 'warning',
                                title: 'Too Many Locations',
                                text: `Maximum of ${currentMaxLocations} location${currentMaxLocations > 1 ? 's' : ''} allowed.`,
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                        
                        console.log('All multiple locations validated successfully for freelancer');
                        
                    } else {
                        console.log('Validating single location for freelancer');
                        const city = $('#city').val();
                        const barangay = $('#barangay').val();
                        
                        if (!city) {
                            console.log('City missing');
                            Swal.fire({
                                icon: 'warning',
                                title: 'City/Municipality Required',
                                text: 'Please select a city/municipality.',
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                        
                        if (!barangay) {
                            console.log('Barangay missing');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Barangay Required',
                                text: 'Please select a barangay.',
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                    }
                } else {
                    // Studio location validation
                    if (!locationType) {
                        console.log('No location type selected');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Location Type Required',
                            text: 'Please select a location type.',
                            confirmButtonColor: '#3475db'
                        });
                        return false;
                    }
                    
                    if (locationType === 'on-location') {
                        if (allowMultipleLocations && currentMaxLocations > 1) {
                            const multipleLocationsValid = validateMultipleLocations();
                            if (!multipleLocationsValid) {
                                return false;
                            }
                        } else {
                            const city = $('#city').val();
                            const barangay = $('#barangay').val();
                            
                            if (!city) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'City/Municipality Required',
                                    text: 'Please select a city/municipality.',
                                    confirmButtonColor: '#3475db'
                                });
                                return false;
                            }
                            
                            if (!barangay) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Barangay Required',
                                    text: 'Please select a barangay.',
                                    confirmButtonColor: '#3475db'
                                });
                                return false;
                            }
                        }
                    }
                }
                // ========== END LOCATION VALIDATION ==========
                
                // Duration validation for fixed packages
                if (selectedPackageFlexibility === false && selectedPackageDuration > 0) {
                    console.log('Validating fixed duration package');
                    const startTime = $('#startTime').val();
                    const endTime = $('#endTime').val();
                    
                    if (startTime && endTime) {
                        const [startHours, startMinutes] = startTime.split(':').map(Number);
                        const [endHours, endMinutes] = endTime.split(':').map(Number);
                        
                        const startDate = new Date();
                        startDate.setHours(startHours, startMinutes, 0);
                        
                        const endDate = new Date();
                        endDate.setHours(endHours, endMinutes, 0);
                        
                        if (endDate < startDate) {
                            endDate.setDate(endDate.getDate() + 1);
                        }
                        
                        const durationInHours = (endDate - startDate) / (1000 * 60 * 60);
                        
                        console.log('Duration check:', {
                            selectedDuration: selectedPackageDuration,
                            calculatedDuration: durationInHours
                        });
                        
                        if (Math.abs(durationInHours - selectedPackageDuration) > 0.1) {
                            console.log('Duration mismatch');
                            Swal.fire({
                                icon: 'error',
                                title: 'Duration Mismatch',
                                text: `This package requires exactly ${selectedPackageDuration} hours. Please adjust your time selection.`,
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                    }
                }
                
                // ========== FIXED: Payment type validation with freelancer policy check ==========
                // Get payment type - handle both radio buttons and hidden inputs
                let paymentType = null;
                const paymentTypeRadio = $('input[name="payment_type"]:checked').val();
                const paymentTypeHidden = $('input[name="payment_type"][type="hidden"]').val();
                
                // Use radio value if available, otherwise use hidden value
                paymentType = paymentTypeRadio || paymentTypeHidden;
                
                console.log('Payment type found:', paymentType);
                
                // For freelancer bookings, check if they have a deposit policy
                if (bookingType === 'freelancer') {
                    // Check if we have freelancer settings stored
                    const hasPolicy = window.currentFreelancerSettings?.hasDepositPolicy === true;
                    
                    console.log('Freelancer payment validation:', {
                        hasPolicy: hasPolicy,
                        paymentType: paymentType,
                        settings: window.currentFreelancerSettings
                    });
                    
                    if (hasPolicy) {
                        // Freelancer has a policy - payment type is automatically determined by the server
                        // We don't need to validate it here
                        console.log('Freelancer has deposit policy - skipping payment type validation');
                    } else {
                        // Freelancer has no policy - require payment type (should be set by hidden input)
                        if (!paymentType) {
                            console.log('No payment type found for freelancer without policy');
                            Swal.fire({
                                icon: 'warning',
                                title: 'Payment Type Required',
                                text: 'Please select a payment type.',
                                confirmButtonColor: '#3475db'
                            });
                            return false;
                        }
                    }
                } else {
                    // Studio always requires payment type
                    if (!paymentType) {
                        console.log('No payment type selected for studio');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Payment Type Required',
                            text: 'Please select a payment type.',
                            confirmButtonColor: '#3475db'
                        });
                        return false;
                    }
                }
                // ========== END PAYMENT TYPE VALIDATION ==========
                
                console.log('========== VALIDATION PASSED ==========');
                return true;
            }
            
            /**
             * Get booking summary
             */
            function getBookingSummary(packageData) {
                $.ajax({
                    url: '{{ route("client.bookings.summary") }}',
                    type: 'POST',
                    data: {
                        package_id: packageData.id,
                        type: $('#bookingType').val(),
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            window.bookingSummary = response.summary;
                        }
                    },
                    error: function(xhr) {
                        console.error('Summary error:', xhr);
                    }
                });
            }
            
            /**
             * Show booking summary modal
             */
            function showBookingSummary() {
                // Populate client information
                $('#summaryFullName').text(bookingData.full_name);
                $('#summaryContactNumber').text(bookingData.contact_number);
                $('#summaryEmailAddress').text(bookingData.email);

                // Get package details
                const packageRadio = $(`.package-radio[value="${selectedPackageId}"]`);
                const packageData = packageRadio.data('package');
                const packageName = packageData.package_name;

                // Clear existing dynamic content to prevent duplicates
                $('#summaryPackage').siblings('.package-type-badge, .package-location-badge, .gallery-info, .photographer-info, .fixed-deposit-info, .multiple-locations-info').remove();

                // Package name
                $('#summaryPackage').text(packageName);

                // Show package flexibility in summary
                const flexibilityHtml = selectedPackageFlexibility
                    ? '<span class="badge badge-soft-success package-type-badge"><i class="ti ti-clock-edit me-1"></i> Flexible Time Package</span>'
                    : '<span class="badge badge-soft-secondary package-type-badge"><i class="ti ti-clock me-1"></i> Fixed Duration: ' + selectedPackageDuration + ' hours</span>';

                $('#summaryPackage').after(`
                    <p class="text-muted small mb-1">Package Type:</p>
                    <p class="fw-medium mb-2 package-type-badge">${flexibilityHtml}</p>
                `);

                // Show package location badge
                const locationBadge = packageData.package_location === 'On-Location'
                    ? '<span class="badge badge-soft-info package-location-badge"><i class="ti ti-map-pin me-1"></i> On-Location Package</span>'
                    : '<span class="badge badge-soft-primary package-location-badge"><i class="ti ti-building me-1"></i> In-Studio Package</span>';

                $('#summaryPackage').after(`
                    <p class="text-muted small mb-1">Package Location:</p>
                    <p class="fw-medium mb-2 package-location-badge">${locationBadge}</p>
                `);

                // Event date
                const eventDate = new Date(bookingData.event_date);
                $('#summaryDate').text(eventDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                }));

                // Event time
                $('#summaryTime').text(
                    formatTime(bookingData.start_time) + ' - ' + formatTime(bookingData.end_time)
                );

                // Show duration info in summary for fixed packages
                if (!selectedPackageFlexibility && selectedPackageDuration > 0) {
                    const startTime = bookingData.start_time;
                    const endTime = bookingData.end_time;

                    const [startHours, startMinutes] = startTime.split(':').map(Number);
                    const [endHours, endMinutes] = endTime.split(':').map(Number);

                    const startDate = new Date();
                    startDate.setHours(startHours, startMinutes, 0);

                    const endDate = new Date();
                    endDate.setHours(endHours, endMinutes, 0);

                    if (endDate < startDate) {
                        endDate.setDate(endDate.getDate() + 1);
                    }

                    const durationMs = endDate - startDate;
                    const durationHours = durationMs / (1000 * 60 * 60);

                    $('#summaryTime').after(`
                        <p class="text-muted small mb-1 mt-2">Duration:</p>
                        <p class="fw-medium mb-2">${durationHours.toFixed(1)} hours (matches package fixed duration)</p>
                    `);
                }

                // Location type
                let locationTypeDisplay = bookingData.location_type === 'in-studio' ? 'In-Studio' : 'On-Location';
                $('#summaryLocationType').text(locationTypeDisplay);

                // Location details
                $('#summaryLocationDetails').empty();
                
                if (allowMultipleLocations && currentMaxLocations > 1 && bookingData.locations && bookingData.locations.length > 0) {
                    // Multiple locations
                    let locationsHtml = '<p class="text-muted small mb-1 mt-2">Event Locations:</p>';
                    
                    bookingData.locations.forEach(function(loc, index) {
                        let locationText = '';
                        if (loc.venue_name) locationText += `<strong>${loc.venue_name}</strong><br>`;
                        if (loc.street) locationText += loc.street + ', ';
                        if (loc.barangay) locationText += 'Brgy. ' + loc.barangay + ', ';
                        if (loc.city) locationText += loc.city + ', ';
                        locationText += 'Cavite';
                        
                        locationsHtml += `
                            <div class="mb-2 p-2 border rounded multiple-locations-info">
                                <span class="badge badge-soft-primary mb-1">Location #${index + 1}</span>
                                <p class="mb-0 small">${locationText}</p>
                            </div>
                        `;
                    });
                    
                    $('#summaryLocationDetails').html(locationsHtml).show();
                } else if (bookingData.location_type === 'on-location') {
                    // Single on-location
                    let locationText = '';
                    if (bookingData.venue_name) locationText += `<strong>${bookingData.venue_name}</strong><br>`;
                    if (bookingData.street) locationText += bookingData.street + ', ';
                    if (bookingData.barangay) locationText += 'Brgy. ' + bookingData.barangay + ', ';
                    if (bookingData.city) locationText += bookingData.city + ', ';
                    locationText += 'Cavite';

                    $('#summaryLocationDetails').html(`
                        <p class="text-muted small mb-1 mt-2">Location Details:</p>
                        <p class="fw-medium mb-2">${locationText}</p>
                    `).show();
                } else {
                    $('#summaryLocationDetails').hide();
                }

                // Display price breakdown with proper formatting
                if (window.bookingSummary) {
                    $('#packagePrice').text('₱' + window.bookingSummary.package_price);
                    $('#downPayment').text('₱' + window.bookingSummary.down_payment);
                    $('#remainingBalance').text('₱' + window.bookingSummary.remaining_balance);
                    $('#totalAmount').text('₱' + window.bookingSummary.total_amount);

                    // Handle different deposit types for freelancer
                    @if($type === 'freelancer')
                        if (window.bookingSummary.deposit_type === 'fixed') {
                            $('#downPaymentLabel').text('Fixed Deposit:');
                            $('#downPayment').text('₱' + window.bookingSummary.down_payment);

                            const depositInfo = `
                                <div class="alert alert-info mt-2 py-2 small fixed-deposit-info">
                                    <i class="ti ti-info-circle me-1"></i>
                                    Fixed deposit of ₱${window.bookingSummary.deposit_amount}.
                                    ${parseFloat(window.bookingSummary.remaining_balance.replace(/,/g, '')) > 0 ?
                                        'Balance of ₱' + window.bookingSummary.remaining_balance + ' payable after event.' :
                                        'This is the full amount.'}
                                </div>
                            `;
                            $('#downPaymentRow').after(depositInfo);
                        } else {
                            const downpaymentPercentage = window.bookingSummary.downpayment_percentage || 30;
                            $('#downPaymentLabel').text(`Down Payment (${downpaymentPercentage}%):`);
                            $('.fixed-deposit-info').remove();
                        }
                    @else
                        const downpaymentPercentage = window.bookingSummary.downpayment_percentage || 30;
                        $('#downPaymentLabel').text(`Down Payment (${downpaymentPercentage}%):`);
                    @endif

                    // Show/hide rows based on payment type
                    if (window.bookingSummary.payment_type === 'full_payment') {
                        $('#downPaymentRow').hide();
                        $('#remainingBalanceRow').hide();
                    } else {
                        $('#downPaymentRow').show();
                        $('#remainingBalanceRow').show();
                    }

                    // Display gallery info
                    const galleryHtml = `
                        <p class="text-muted small mb-1 mt-2">Online Gallery:</p>
                        <p class="fw-medium mb-2 gallery-info">
                            <span class="badge badge-soft-${window.bookingSummary.online_gallery ? 'success' : 'warning'}">
                                <i class="${window.bookingSummary.online_gallery ? 'ti ti-photo' : 'ti ti-photo-off'} me-1"></i>
                                ${window.bookingSummary.gallery_status || (window.bookingSummary.online_gallery ? 'Included' : 'Not Included')}
                            </span>
                        </p>
                    `;
                    $('#summaryPackage').after(galleryHtml);

                    // Display photographer info for studio
                    @if($type === 'studio')
                        if (window.bookingSummary.photographer_count !== undefined) {
                            const photographerHtml = `
                                <p class="text-muted small mb-1">Assigned Photographers:</p>
                                <p class="fw-medium mb-2 photographer-info">
                                    <span class="badge badge-soft-primary">
                                        <i class="ti ti-users me-1"></i>
                                        ${window.bookingSummary.photographer_text || (window.bookingSummary.photographer_count + ' photographer' + (window.bookingSummary.photographer_count > 1 ? 's' : ''))}
                                    </span>
                                </p>
                            `;
                            $('#summaryPackage').after(photographerHtml);
                        }
                    @endif

                    // Display package inclusions
                    let inclusionsHtml = '';
                    if (window.bookingSummary.inclusions && Array.isArray(window.bookingSummary.inclusions)) {
                        window.bookingSummary.inclusions.forEach(function(inclusion) {
                            inclusionsHtml += `<li><i class="ti ti-check text-success me-2"></i>${inclusion}</li>`;
                        });
                    }
                    $('#summaryInclusions').html(inclusionsHtml);
                }

                $('#bookingSummaryModal').modal('show');
            }
            
            /**
             * Process booking
             */
            function processBooking() {
                $('#proceedToPaymentBtn').prop('disabled', true);
                $('#proceedToPaymentBtn').html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Processing...
                `);
                
                if (!bookingData.payment_type) {
                    bookingData.payment_type = $('input[name="payment_type"]:checked').val();
                }
                
                console.log('Sending booking data to server:', bookingData);
                
                $.ajax({
                    url: '{{ route("client.bookings.store") }}',
                    type: 'POST',
                    data: bookingData,
                    success: function(response) {
                        console.log('Booking store response:', response);
                        if (response.success) {
                            bookingId = response.booking.id;
                            initializePayment();
                        } else {
                            showError('Failed to create booking: ' + response.message);
                            resetPaymentButton();
                        }
                    },
                    error: function(xhr) {
                        console.error('Booking store error:', xhr);
                        console.error('Error response:', xhr.responseJSON);
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            let errorMessages = [];
                            $.each(xhr.responseJSON.errors, function(field, messages) {
                                errorMessages.push(messages.join(', '));
                            });
                            showError('Validation errors: ' + errorMessages.join('; '));
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            showError('Booking creation failed: ' + xhr.responseJSON.message);
                        } else {
                            showError('Booking creation failed. Please try again.');
                        }
                        resetPaymentButton();
                    }
                });
            }
            
            /**
             * Initialize payment
             */
            function initializePayment() {
                Swal.fire({
                    title: 'Confirm Payment',
                    text: 'Are you sure you want to proceed to payment?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Proceed to Payment',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3475db',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        proceedWithPayment();
                    } else {
                        resetPaymentButton();
                    }
                });
            }

            /**
             * Proceed with payment
             */
            function proceedWithPayment() {
                $('#proceedToPaymentBtn').prop('disabled', true);
                $('#proceedToPaymentBtn').html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Creating payment link...
                `);
                
                $.ajax({
                    url: '{{ route("client.payments.initialize") }}',
                    type: 'POST',
                    data: {
                        booking_id: bookingId,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.type === 'payment_intent') {
                                window.location.href = response.redirect_url;
                            } else if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            } else {
                                showError('No redirect URL provided');
                                resetPaymentButton();
                            }
                        } else {
                            showError('Payment initialization failed: ' + (response.message || 'Unknown error'));
                            resetPaymentButton();
                        }
                    },
                    error: function(xhr) {
                        console.error('Payment init error:', xhr);
                        showError('Payment initialization failed. Please try again.');
                        resetPaymentButton();
                    }
                });
            }
            
            /**
             * Generate availability calendar
             */
            function generateAvailabilityCalendar() {
                const calendarEl = document.getElementById('availabilityCalendar');
                const today = new Date();
                
                getCalendarAvailability(today.getFullYear(), today.getMonth() + 1).then(availabilityData => {
                    let calendarHtml = `
                        <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                            <button class="btn btn-sm btn-outline-secondary" id="prevMonth"><i class="ti ti-chevron-left"></i></button>
                            <h6 class="mb-0" id="currentMonth">${today.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</h6>
                            <button class="btn btn-sm btn-outline-secondary" id="nextMonth"><i class="ti ti-chevron-right"></i></button>
                        </div>
                        <div class="calendar-grid" id="calendarGrid">
                    `;
                    
                    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                    days.forEach(day => {
                        calendarHtml += `<div class="calendar-day-header">${day}</div>`;
                    });
                    
                    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                    
                    for (let i = 0; i < firstDay.getDay(); i++) {
                        calendarHtml += `<div class="calendar-day empty"></div>`;
                    }
                    
                    for (let day = 1; day <= lastDay.getDate(); day++) {
                        const date = new Date(today.getFullYear(), today.getMonth(), day);
                        const dateString = date.toISOString().split('T')[0];
                        const isToday = date.toDateString() === today.toDateString();
                        const isPast = date < today;
                        
                        const dateAvailability = availabilityData[dateString];
                        const isAvailable = dateAvailability ? dateAvailability.available : true;
                        const isFullyBooked = dateAvailability ? dateAvailability.fully_booked : false;
                        const isNotOperating = dateAvailability ? dateAvailability.not_operating : !isOperatingDay(dateString);
                        
                        let dateClass = 'calendar-day';
                        if (isToday) dateClass += ' today';
                        if (isPast) dateClass += ' past';
                        if (!isAvailable || isFullyBooked || isNotOperating) dateClass += ' unavailable';
                        if (isFullyBooked) dateClass += ' fully-booked';
                        
                        calendarHtml += `
                            <div class="${dateClass}" data-date="${dateString}" 
                                title="${isNotOperating ? 'Closed' : (isFullyBooked ? 'Fully Booked' : (isAvailable ? 'Available' : 'Not Available'))}">
                                ${day}
                                ${isFullyBooked ? '<div class="availability-dot unavailable"></div>' : (isAvailable && !isNotOperating ? '<div class="availability-dot available"></div>' : '')}
                            </div>
                        `;
                    }
                    
                    calendarHtml += '</div>';
                    calendarEl.innerHTML = calendarHtml;
                    
                    $('.calendar-day:not(.past):not(.unavailable):not(.fully-booked)').on('click', function() {
                        const selectedDate = $(this).data('date');
                        $('#eventDate').val(selectedDate);
                        $('#calendarModal').modal('hide');
                        $('#eventDate').trigger('change');
                    });
                    
                    $('<style>')
                        .prop('type', 'text/css')
                        .html('.calendar-day.fully-booked { background: #fee; border-color: #dc3545; color: #dc3545; cursor: not-allowed; }')
                        .appendTo('head');
                });
            }

            /**
             * Get calendar availability
             */
            function getCalendarAvailability(year, month) {
                const type = $('#bookingType').val();
                const providerId = $('#providerId').val();
                
                return $.ajax({
                    url: '{{ route("client.bookings.calendar-availability") }}',
                    type: 'POST',
                    data: {
                        type: type,
                        provider_id: providerId,
                        year: year,
                        month: month,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        return response.availability || {};
                    },
                    error: function() {
                        return {};
                    }
                });
            }
            
            /**
             * Format time
             */
            function formatTime(timeString) {
                const [hours, minutes] = timeString.split(':');
                const hour = parseInt(hours);
                const ampm = hour >= 12 ? 'PM' : 'AM';
                const formattedHour = hour % 12 || 12;
                return `${formattedHour}:${minutes} ${ampm}`;
            }
            
            /**
             * Show error message
             */
            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#3475db'
                });
            }
            
            /**
             * Reset payment button
             */
            function resetPaymentButton() {
                $('#proceedToPaymentBtn').prop('disabled', false);
                $('#proceedToPaymentBtn').html(`
                    <i class="ti ti-credit-card me-2"></i>Proceed to Payment
                `);
            }

            /**
             * Update summary price display
             */
            function updateSummaryPriceDisplay(summary) {
                $('#packagePrice').text('₱' + summary.package_price);
                $('#downPayment').text('₱' + summary.down_payment);
                $('#remainingBalance').text('₱' + summary.remaining_balance);
                $('#totalAmount').text('₱' + summary.total_amount);
                
                // Update down payment label with dynamic percentage or fixed amount
                @if($type === 'freelancer')
                    if (summary.deposit_type === 'fixed') {
                        $('#downPaymentLabel').text('Fixed Deposit:');
                        $('.fixed-deposit-info').remove();
                        
                        const depositInfo = `
                            <div class="alert alert-info mt-2 py-2 small fixed-deposit-info">
                                <i class="ti ti-info-circle me-1"></i>
                                Fixed deposit of ₱${summary.deposit_amount}. 
                                ${parseFloat(summary.remaining_balance.replace(/,/g, '')) > 0 ? 
                                    'Balance of ₱' + summary.remaining_balance + ' payable after event.' : 
                                    'This is the full amount.'}
                            </div>
                        `;
                        $('#downPaymentRow').after(depositInfo);
                    } else {
                        const downpaymentPercentage = summary.downpayment_percentage || 30;
                        $('#downPaymentLabel').text(`Down Payment (${downpaymentPercentage}%):`);
                        $('.fixed-deposit-info').remove();
                    }
                @else
                    const downpaymentPercentage = summary.downpayment_percentage || 30;
                    $('#downPaymentLabel').text(`Down Payment (${downpaymentPercentage}%):`);
                @endif
                
                // Show/hide rows based on payment type
                if (summary.payment_type === 'full_payment') {
                    $('#downPaymentRow').hide();
                    $('#remainingBalanceRow').hide();
                } else {
                    $('#downPaymentRow').show();
                    $('#remainingBalanceRow').show();
                }
            }

            /**
             * Handle package location flexibility
             */
            function handlePackageLocationFlexibility(packageData) {
                // Store current package flexibility data
                currentPackageLocationFlexibility = packageData.location_flexibility;
                
                // Get the location container elements
                const locationTypeSelect = $('#locationType');
                
                // Remove any existing custom UI first
                $('.flexible-location-ui').remove();
                $('.location-auto-set-badge').remove();
                
                // Check if package has location flexibility data
                if (!currentPackageLocationFlexibility) {
                    console.warn('No location flexibility data available');
                    return;
                }
                
                const options = currentPackageLocationFlexibility.options || [];
                const isFlexible = currentPackageLocationFlexibility.is_flexible;
                const singleOption = currentPackageLocationFlexibility.single_option;
                
                console.log('Package location flexibility:', {
                    options: options,
                    isFlexible: isFlexible,
                    singleOption: singleOption
                });
                
                if (isFlexible) {
                    // === CASE: Both options available ["In-Studio", "On-Location"] - Show selection UI ===
                    
                    // Show the original select but don't disable it
                    locationTypeSelect.show();
                    locationTypeSelect.prop('disabled', false);
                    locationTypeSelect.val(''); // Clear any previous value
                    
                    // Add info badge
                    locationTypeSelect.closest('.col-12').find('.form-label').append(
                        '<span class="badge badge-soft-success ms-2 flexible-location-badge" style="font-size: 0.65rem;">' +
                        '<i class="ti ti-arrows-maximize me-1"></i>Flexible - Choose location</span>'
                    );
                    
                    // Hide location details until selection is made
                    $('#singleLocationDetails').hide();
                    $('#multipleLocationsContainer').hide();
                    
                } else if (singleOption) {
                    // === CASE: Single option only ["In-Studio"] or ["On-Location"] - Auto-set ===
                    
                    // Show and set the original select
                    locationTypeSelect.show();
                    locationTypeSelect.prop('disabled', true); // Disable since it's auto-set
                    
                    // Map package location to form value
                    let formValue = '';
                    let displayText = '';
                    
                    if (singleOption === 'In-Studio') {
                        formValue = 'in-studio';
                        displayText = 'In-Studio';
                    } else if (singleOption === 'On-Location') {
                        formValue = 'on-location';
                        displayText = 'On-Location';
                    }
                    
                    // Set the value
                    locationTypeSelect.val(formValue);
                    
                    // Add visual indicator
                    locationTypeSelect.closest('.col-12').find('.form-label').append(
                        '<span class="badge badge-soft-info ms-2 location-auto-set-badge" style="font-size: 0.65rem;">' +
                        '<i class="ti ti-info-circle me-1"></i>Auto-set by package</span>'
                    );
                    
                    // Trigger change to show/hide location details
                    locationTypeSelect.trigger('change');
                }
            }

            /**
             * Reset location UI
             */
            function resetLocationUI() {
                const locationTypeSelect = $('#locationType');
                const singleLocationDiv = $('#singleLocationDetails');
                const multipleLocationsDiv = $('#multipleLocationsContainer');
                const locationsList = $('#locationsList');
                const addLocationBtn = $('#addLocationBtn');
                
                // Reset values
                locationCount = 0;
                currentMaxLocations = 1;
                allowMultipleLocations = false;
                
                // Clear locations list
                locationsList.empty();
                
                // Hide both containers initially
                singleLocationDiv.hide();
                multipleLocationsDiv.hide();
                addLocationBtn.hide();
                
                // Remove any badges
                $('.flexible-location-badge').remove();
                $('.location-auto-set-badge').remove();
                
                // Reset location type select
                locationTypeSelect.prop('disabled', false);
                $('.flexible-location-ui').remove();
            }

            /**
             * Initialize multiple location UI
             */
            function initMultipleLocationUI(packageData) {
                resetLocationUI();
                
                // Get package location settings
                allowMultipleLocations = packageData.allow_multiple_locations === true || packageData.allow_multiple_locations === '1' || packageData.allow_multiple_locations === 1;
                currentMaxLocations = parseInt(packageData.max_locations) || 1;
                
                console.log('Initializing multiple location UI:', {
                    allowMultiple: allowMultipleLocations,
                    maxLocations: currentMaxLocations
                });
                
                // Update location counter
                $('#locationCounter').text(`0/${currentMaxLocations} locations`);
                $('#multipleLocationsNote').text(`You can add up to ${currentMaxLocations} location${currentMaxLocations > 1 ? 's' : ''} for this package.`);
                
                // Hide single location UI
                $('#singleLocationDetails').hide();
                
                // Show multiple location container
                $('#multipleLocationsContainer').show();
                
                // Add first location by default
                addLocation();
                
                // ==== NEW: Customize UI based on booking type ====
                const bookingType = $('#bookingType').val();
                
                if (bookingType === 'freelancer') {
                    // For freelancers, we already set location type to on-location and disabled it
                    // No additional changes needed
                    console.log('Multiple location UI initialized for freelancer');
                } else {
                    // For studios, show add button based on max locations
                    if (currentMaxLocations > 1) {
                        $('#addLocationBtn').show();
                    } else {
                        $('#addLocationBtn').hide();
                    }
                }
                // ==== END: Customize UI based on booking type ====
                
                // Show add button if max locations > 1 (for all types)
                if (currentMaxLocations > 1) {
                    $('#addLocationBtn').show();
                } else {
                    $('#addLocationBtn').hide();
                }
                
                // Update location counter
                updateLocationCounter();
            }

            /**
             * Show single location UI
             */
            function showSingleLocationUI() {
                const singleLocationDiv = $('#singleLocationDetails');
                const multipleLocationsDiv = $('#multipleLocationsContainer');
                
                singleLocationDiv.show();
                multipleLocationsDiv.hide();
                
                // Reset single location fields
                $('#venueName').val('');
                $('#street').val('');
                $('#barangay').val('').prop('disabled', true);
                $('#city').val('').trigger('change');
            }

            /**
             * Add a new location entry
             */
            function addLocation() {
                if (locationCount >= currentMaxLocations) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Maximum Locations Reached',
                        text: `You can only add up to ${currentMaxLocations} location${currentMaxLocations > 1 ? 's' : ''}.`,
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                locationCount++;
                const locationIndex = locationCount - 1;
                
                const locationHtml = `
                    <div class="location-entry card mb-3" data-index="${locationIndex}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="card-subtitle text-muted">Location #${locationCount}</h6>
                                ${locationCount > 1 ? `
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-location-btn">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                ` : ''}
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">Venue Name</label>
                                <input type="text" class="form-control form-control-sm" 
                                    name="locations[${locationIndex}][venue_name]" 
                                    placeholder="Enter venue name (optional)">
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">City/Municipality <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm location-city" 
                                    name="locations[${locationIndex}][city]" required>
                                    <option value="">Select City/Municipality</option>
                                    @foreach($municipalities as $municipality)
                                        <option value="{{ $municipality }}">{{ $municipality }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">Barangay <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm location-barangay" 
                                    name="locations[${locationIndex}][barangay]" required disabled>
                                    <option value="">Select Barangay</option>
                                </select>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label small">Street / Building / Unit No.</label>
                                <input type="text" class="form-control form-control-sm" 
                                    name="locations[${locationIndex}][street]" 
                                    placeholder="Enter street name, building, unit number (optional)">
                            </div>
                            
                            <input type="hidden" name="locations[${locationIndex}][province]" value="Cavite">
                        </div>
                    </div>
                `;
                
                $('#locationsList').append(locationHtml);
                updateLocationCounter();
                
                // Attach city change handler for this location
                attachLocationCityHandler(locationIndex);
            }

            /**
             * Attach city change handler for a specific location
             */
            function attachLocationCityHandler(index) {
                $(document).off('change', `select[name="locations[${index}][city]"]`).on('change', `select[name="locations[${index}][city]"]`, function() {
                    const municipality = $(this).val();
                    const barangaySelect = $(`select[name="locations[${index}][barangay]"]`);
                    
                    if (!municipality) {
                        barangaySelect.prop('disabled', true).html('<option value="">Select Barangay</option>');
                        return;
                    }
                    
                    barangaySelect.prop('disabled', true).html('<option value="">Loading barangays...</option>');
                    
                    $.ajax({
                        url: '{{ route("client.locations.barangays") }}',
                        type: 'POST',
                        data: {
                            municipality: municipality,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success && response.barangays && response.barangays.length > 0) {
                                let options = '<option value="">Select Barangay</option>';
                                const sortedBarangays = response.barangays.sort();
                                sortedBarangays.forEach(function(barangay) {
                                    options += `<option value="${barangay}">${barangay}</option>`;
                                });
                                barangaySelect.html(options).prop('disabled', false);
                            } else {
                                barangaySelect.html('<option value="">No barangays available</option>').prop('disabled', true);
                            }
                        },
                        error: function() {
                            barangaySelect.html('<option value="">Error loading barangays</option>').prop('disabled', true);
                        }
                    });
                });
            }

            /**
             * Remove a location entry
             */
            function removeLocation(entry) {
                if (locationCount <= 1) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Cannot Remove',
                        text: 'At least one location is required.',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }
                
                Swal.fire({
                    title: 'Remove Location',
                    text: 'Are you sure you want to remove this location?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, remove it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        entry.closest('.location-entry').remove();
                        locationCount--;
                        
                        // Re-index remaining locations
                        $('#locationsList .location-entry').each(function(newIndex) {
                            $(this).attr('data-index', newIndex);
                            $(this).find('input, select').each(function() {
                                const name = $(this).attr('name');
                                if (name) {
                                    $(this).attr('name', name.replace(/\[\d+\]/, `[${newIndex}]`));
                                }
                            });
                        });
                        
                        updateLocationCounter();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed',
                            text: 'Location has been removed.',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    }
                });
            }

            /**
             * Update location counter display
             */
            function updateLocationCounter() {
                $('#locationCounter').text(`${locationCount}/${currentMaxLocations} locations`);
                
                // Enable/disable add button
                if (locationCount >= currentMaxLocations) {
                    $('#addLocationBtn').prop('disabled', true);
                } else {
                    $('#addLocationBtn').prop('disabled', false);
                }
            }

            /**
             * Get multiple locations data for form submission
             */
            function getMultipleLocationsData() {
                console.log('Collecting multiple locations data');
                const locations = [];
                
                $('.location-entry').each(function(index) {
                    const elementIndex = $(this).data('index');
                    console.log(`Processing location entry #${index + 1}, stored index: ${elementIndex}`);
                    
                    const location = {
                        venue_name: $(`input[name="locations[${elementIndex}][venue_name]"]`).val() || '',
                        city: $(`select[name="locations[${elementIndex}][city]"]`).val(),
                        barangay: $(`select[name="locations[${elementIndex}][barangay]"]`).val(),
                        street: $(`input[name="locations[${elementIndex}][street]"]`).val() || '',
                        province: 'Cavite'
                    };
                    
                    console.log(`Location #${index + 1} data:`, location);
                    
                    // Only add if required fields are filled
                    if (location.city && location.barangay) {
                        locations.push(location);
                        console.log(`Location #${index + 1} is valid, added to submission`);
                    } else {
                        console.warn(`Location #${index + 1} is incomplete, skipping:`, {
                            hasCity: !!location.city,
                            hasBarangay: !!location.barangay
                        });
                    }
                });
                
                console.log('Total valid locations collected:', locations.length);
                return locations;
            }

            /**
             * Validate multiple locations
             */
            function validateMultipleLocations() {
                console.log('Validating multiple locations');
                
                if (!allowMultipleLocations || currentMaxLocations <= 1) {
                    console.log('Multiple locations not enabled, skipping validation');
                    return true;
                }
                
                // First, check if there are any location entries
                const locationEntries = $('.location-entry');
                console.log('Location entries found:', locationEntries.length);
                
                if (locationEntries.length === 0) {
                    console.log('No location entries found');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Required',
                        text: 'Please add at least one location.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                const locations = getMultipleLocationsData();
                console.log('Collected locations data:', locations);
                
                if (locations.length === 0) {
                    console.log('No valid locations found (all incomplete)');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Required',
                        text: 'Please fill in at least one complete location with City/Municipality and Barangay.',
                        confirmButtonColor: '#3475db'
                    });
                    return false;
                }
                
                // Check if all locations have required fields
                for (let i = 0; i < locations.length; i++) {
                    const loc = locations[i];
                    console.log(`Location #${i + 1}:`, loc);
                    
                    if (!loc.city || !loc.barangay) {
                        console.log(`Location #${i + 1} missing required fields`);
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Location',
                            text: `Location #${i + 1} is missing required fields (City/Municipality and Barangay).`,
                            confirmButtonColor: '#3475db'
                        });
                        return false;
                    }
                }
                
                console.log('All locations validated successfully');
                return true;
            }
        });
    </script>
@endsection