@extends('layouts.owner.app')
@section('title', 'Edit Studio')

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/leaflet/leaflet.css') }}">
    <style>
        #attendanceGeofenceMap {
            min-height: 320px;
            border-radius: 0.75rem;
        }

        .attendance-map-marker {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border: 3px solid #fff;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
        }

        .attendance-map-marker i {
            font-size: 20px;
            line-height: 1;
        }

        .attendance-map-marker.studio-marker {
            background: #dc3545;
        }

        .attendance-map-marker.employee-marker {
            background: #0d6efd;
        }
    </style>
@endsection

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Edit Studio: {{ $studio->studio_name }}</h4>
                        </div>
                        <div class="card-body">
                            <form id="studioEditForm" action="{{ route('owner.studio.update', $studio->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                @csrf
                                @method('PUT')
                                
                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Studio Identification Information</h4>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Studio Name</label>
                                        <input type="text" class="form-control" placeholder="Enter your studio name" name="studio_name" value="{{ $studio->studio_name }}" required>
                                        <div class="invalid-feedback">
                                            Please enter your studio name.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Studio Type</label>
                                        <select class="form-select" name="studio_type" required>
                                            <option value="" disabled>Choose a studio type</option>
                                            <option value="photography_studio" {{ $studio->studio_type == 'photography_studio' ? 'selected' : '' }}>Photography Studio</option>
                                            <option value="video_production" {{ $studio->studio_type == 'video_production' ? 'selected' : '' }}>Video Production</option>
                                            <option value="mixed_media" {{ $studio->studio_type == 'mixed_media' ? 'selected' : '' }}>Mixed Media</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please choose a studio type.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Year Established</label>
                                        <input type="number" class="form-control" name="year_established" placeholder="Enter your year established" value="{{ $studio->year_established }}" required>
                                        <div class="invalid-feedback">
                                            Please enter your year established.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Studio Description</label>
                                        <textarea class="form-control" name="studio_description" rows="5" placeholder="Enter your studio description" required>{{ $studio->studio_description }}</textarea>
                                        <div class="invalid-feedback">
                                            Please enter your studio description.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label fw-semibold">Studio Logo</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="studioLogo" name="studio_logo" accept=".jpg,.jpeg,.png">
                                        </div>
                                        <div class="form-text">Leave empty to keep current logo. Max size: 3MB</div>
                                        @if($studio->studio_logo)
                                            <div class="mt-2">
                                                <img src="{{ asset('storage/' . $studio->studio_logo) }}" alt="Current Logo" style="max-height: 80px;" class="rounded">
                                                <small class="text-muted d-block">Current logo</small>
                                            </div>
                                        @endif
                                        <div class="invalid-feedback">
                                            Please upload a valid studio logo.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Studio Contact Information</h4>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" placeholder="Enter studio contact number" name="contact_number" value="{{ $studio->contact_number }}" required>
                                        <div class="invalid-feedback">
                                            Please enter studio contact number.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Studio Email</label>
                                        <input type="email" class="form-control" placeholder="Enter studio email address" name="studio_email" value="{{ $studio->studio_email }}" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid studio email.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Facebook URL <span class="text-muted">(Optional)</span></label>
                                        <input type="url" class="form-control" placeholder="https://facebook.com/yourpage" name="facebook_url" value="{{ $studio->facebook_url }}">
                                        <div class="invalid-feedback">
                                            Please enter a valid Facebook URL.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Instagram URL <span class="text-muted">(Optional)</span></label>
                                        <input type="url" class="form-control" placeholder="https://instagram.com/yourprofile" name="instagram_url" value="{{ $studio->instagram_url }}">
                                        <div class="invalid-feedback">
                                            Please enter a valid Instagram URL.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Website URL <span class="text-muted">(Optional)</span></label>
                                        <input type="url" class="form-control" placeholder="https://yourwebsite.com" name="website_url" value="{{ $studio->website_url }}">
                                        <div class="invalid-feedback">
                                            Please enter a valid website URL.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Location Information</h4>
                                    
                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Province</label>
                                        <input type="text" class="form-control" value="Cavite" readonly disabled required>
                                        <input type="hidden" name="province" value="Cavite">
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Municipality</label>
                                        <select class="form-control" id="municipalitySelect" name="municipality" required>
                                            <option value="">Select your municipality</option>
                                            @foreach($municipalities as $municipality)
                                                <option value="{{ $municipality }}" {{ $studio->location && $studio->location->municipality == $municipality ? 'selected' : '' }}>
                                                    {{ $municipality }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select your municipality.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Barangay</label>
                                        <select class="form-control" id="barangaySelect" name="barangay" required>
                                            <option value="">Select municipality first</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            Please select your barangay.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">ZIP Code</label>
                                        <input type="text" class="form-control" id="zipCodeInput" placeholder="ZIP code will auto-fill" name="zip_code_display" readonly required>
                                        <div class="invalid-feedback">
                                            Please wait for the ZIP code to load.
                                        </div>
                                    </div>

                                    <div class="col-12 mb-3">
                                        <label class="form-label">Street Address</label>
                                        <input type="text" class="form-control" placeholder="Enter your street address" name="street" value="{{ $studio->street }}" required>
                                        <div class="invalid-feedback">
                                            Please enter your street address.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Attendance Geofence</h4>
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            Update the official attendance pin here. Employees will only be able to submit attendance while inside this saved radius.
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <div id="attendanceGeofenceMap"></div>
                                    </div>
                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Attendance Latitude</label>
                                        <input type="number" class="form-control" id="attendanceLatitude" name="attendance_latitude" step="0.0000001" value="{{ $studio->attendance_latitude }}" required>
                                        <div class="invalid-feedback">
                                            Please set the attendance latitude.
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Attendance Longitude</label>
                                        <input type="number" class="form-control" id="attendanceLongitude" name="attendance_longitude" step="0.0000001" value="{{ $studio->attendance_longitude }}" required>
                                        <div class="invalid-feedback">
                                            Please set the attendance longitude.
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-4 mb-3">
                                        <label class="form-label">Attendance Radius (meters)</label>
                                        <input type="number" class="form-control" id="attendanceRadius" name="attendance_radius_meters" min="1" max="1000" value="{{ $studio->attendance_radius_meters ?? 100 }}" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid attendance radius.
                                        </div>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-2 mb-3">
                                        <button type="button" class="btn btn-soft-primary" id="useCurrentLocationBtn">
                                            <i class="ti ti-current-location me-1"></i> Use My Current Location
                                        </button>
                                        <span class="text-muted align-self-center">Click the map or drag the marker to move the studio attendance pin.</span>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Service Information</h4>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Service Categories</label>
                                        <select class="form-control" name="service_categories[]" multiple required>
                                            <option value="" disabled>Select service categories</option>
                                            @foreach($categories as $category)
                                                @php
                                                    $selected = $studio->categories && $studio->categories->contains('id', $category->id);
                                                @endphp
                                                <option value="{{ $category->id }}" {{ $selected ? 'selected' : '' }}>
                                                    {{ $category->category_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="form-text text-muted">Hold Ctrl/Cmd to select multiple categories</small>
                                        <div class="invalid-feedback">
                                            Please select at least one service category.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Starting Price (PHP)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" placeholder="Enter your starting price" name="starting_price" step="0.01" min="0" value="{{ $studio->starting_price }}" required>
                                            <div class="invalid-feedback">
                                                Please enter your starting price.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label">Downpayment Percentage (%)</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" placeholder="Enter downpayment percentage" name="downpayment_percentage" step="0.01" min="0" max="100" value="{{ $studio->downpayment_percentage }}">
                                            <span class="input-group-text">%</span>
                                            <div class="invalid-feedback">
                                                Please enter a valid percentage between 0 and 100.
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">This will be required as downpayment for bookings.</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-3">Operating Schedule</h4>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Operating Days</label>
                                        <div class="btn-group w-100 mb-1" role="group" aria-label="Weekday toggle button group" id="operatingDaysGroup">
                                            @php
                                                $operatingDays = is_array($studio->operating_days) ? $studio->operating_days : json_decode($studio->operating_days, true) ?? [];
                                            @endphp
                                            <input type="checkbox" class="btn-check" id="btnMonday" name="operating_days[]" value="monday" autocomplete="off" {{ in_array('monday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnMonday">Monday</label>

                                            <input type="checkbox" class="btn-check" id="btnTuesday" name="operating_days[]" value="tuesday" autocomplete="off" {{ in_array('tuesday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnTuesday">Tuesday</label>

                                            <input type="checkbox" class="btn-check" id="btnWednesday" name="operating_days[]" value="wednesday" autocomplete="off" {{ in_array('wednesday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnWednesday">Wednesday</label>

                                            <input type="checkbox" class="btn-check" id="btnThursday" name="operating_days[]" value="thursday" autocomplete="off" {{ in_array('thursday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnThursday">Thursday</label>

                                            <input type="checkbox" class="btn-check" id="btnFriday" name="operating_days[]" value="friday" autocomplete="off" {{ in_array('friday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnFriday">Friday</label>

                                            <input type="checkbox" class="btn-check" id="btnSaturday" name="operating_days[]" value="saturday" autocomplete="off" {{ in_array('saturday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnSaturday">Saturday</label>

                                            <input type="checkbox" class="btn-check" id="btnSunday" name="operating_days[]" value="sunday" autocomplete="off" {{ in_array('sunday', $operatingDays) ? 'checked' : '' }}>
                                            <label class="btn btn-outline-primary" for="btnSunday">Sunday</label>
                                        </div>
                                        <div class="invalid-feedback operating-days-error" style="display: none;">
                                            Please select at least one operating day.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label" for="startTime">Start Time</label>
                                        <input type="time" class="form-control" id="startTime" name="start_time" value="{{ $studio->start_time ? date('H:i', strtotime($studio->start_time)) : '' }}" required>
                                        <div class="invalid-feedback">
                                            Please enter the start time.
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-6 mb-3">
                                        <label class="form-label" for="endTime">End Time</label>
                                        <input type="time" class="form-control" id="endTime" name="end_time" value="{{ $studio->end_time ? date('H:i', strtotime($studio->end_time)) : '' }}" required>
                                        <div class="invalid-feedback">
                                            Please enter the end time.
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label">Maximum Client per Day</label>
                                        <div class="input-group" data-touchspin="">
                                            <button type="button" class="btn btn-light floating" data-minus=""><i class="ti ti-minus"></i></button>
                                            <input type="number" class="form-control form-control-sm border-0" value="{{ $studio->max_clients_per_day }}" max="100" name="max_clients_per_day" required>
                                            <button type="button" class="btn btn-light floating" data-plus=""><i class="ti ti-plus"></i></button>
                                        </div>
                                        <div class="invalid-feedback">
                                            Please enter the maximum client per day.
                                        </div>
                                    </div>

                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label">Advance Booking</label>
                                        <input type="number" class="form-control" placeholder="Enter the advance booking days" max="30" name="advance_booking_days" value="{{ $studio->advance_booking_days }}" required>
                                        <small class="form-text text-muted">The minimum number of days before the studio can be reserved</small>
                                        <div class="invalid-feedback">
                                            Please enter the advance booking days.
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <h4 class="card-title text-primary mb-1">Verification Documents</h4>
                                    <p class="text-muted mb-3">Upload new documents only if you need to update them. Leave empty to keep current files.</p>
                                    
                                    <div class="col-12 mb-3">                                            
                                        <!-- Business Permit -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Business Permit/DTI/SEC Registration</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="businessPermit" name="business_permit" accept=".pdf,.jpg,.jpeg,.png">
                                            </div>
                                            <div class="form-text">Leave empty to keep current file. Max size: 3MB</div>
                                            @if($studio->business_permit)
                                                <div class="mt-2">
                                                    <small class="text-muted">Current file: {{ basename($studio->business_permit) }}</small>
                                                </div>
                                            @endif
                                            <div class="invalid-feedback">
                                                Please upload a valid file.
                                            </div>
                                        </div>
                                        
                                        <!-- Valid ID -->
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Valid Government ID (Owner)</label>
                                            <div class="input-group">
                                                <input type="file" class="form-control" id="ownerId" name="owner_id_document" accept=".pdf,.jpg,.jpeg,.png">
                                            </div>
                                            <div class="form-text">Leave empty to keep current file. Max size: 3MB</div>
                                            @if($studio->owner_id_document)
                                                <div class="mt-2">
                                                    <small class="text-muted">Current file: {{ basename($studio->owner_id_document) }}</small>
                                                </div>
                                            @endif
                                            <div class="invalid-feedback">
                                                Please upload a valid file.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 text-end">
                                        <a href="{{ route('owner.studio.index') }}" class="btn btn-soft-primary me-2">Back</a>
                                        <button type="submit" class="btn btn-primary">Update Studio</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script src="{{ asset('assets/plugins/leaflet/leaflet.js') }}"></script>
    <script>
        $(document).ready(function() {
            let geofenceMap = null;
            let geofenceMarker = null;
            let geofenceCircle = null;
            let currentLocationMarker = null;

            // Initialize Choices for service categories
            function initializeChoices() {
                if (typeof Choices !== 'undefined') {
                    const serviceCategoriesSelect = document.querySelector('select[name="service_categories[]"]');
                    if (serviceCategoriesSelect) {
                        new Choices(serviceCategoriesSelect, {
                            removeItemButton: true,
                            searchEnabled: true,
                            placeholder: true,
                            placeholderValue: 'Select service categories',
                            shouldSort: false
                        });
                    }
                }
            }
            
            initializeChoices();
            initializeGeofenceMap();

            function createAttendanceMapIcon(type) {
                const iconClass = type === 'studio' ? 'ti ti-map-pin' : 'ti ti-current-location';
                const markerClass = type === 'studio' ? 'studio-marker' : 'employee-marker';

                return L.divIcon({
                    className: 'attendance-map-icon-wrapper',
                    html: `<div class="attendance-map-marker ${markerClass}"><i class="${iconClass}"></i></div>`,
                    iconSize: [42, 42],
                    iconAnchor: [21, 21],
                    popupAnchor: [0, -18]
                });
            }

            function initializeGeofenceMap() {
                const initialLatitude = parseFloat('{{ $studio->attendance_latitude ?? 14.2820 }}');
                const initialLongitude = parseFloat('{{ $studio->attendance_longitude ?? 120.8660 }}');
                const initialCoordinates = [initialLatitude, initialLongitude];

                geofenceMap = L.map('attendanceGeofenceMap').setView(initialCoordinates, 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19
                }).addTo(geofenceMap);

                geofenceMarker = L.marker(initialCoordinates, {
                    draggable: true,
                    icon: createAttendanceMapIcon('studio')
                }).addTo(geofenceMap);
                geofenceCircle = L.circle(initialCoordinates, {
                    radius: Number($('#attendanceRadius').val() || 100),
                    color: '#3475db',
                    fillColor: '#3475db',
                    fillOpacity: 0.15
                }).addTo(geofenceMap);

                geofenceMap.on('click', function(event) {
                    setGeofenceLocation(event.latlng.lat, event.latlng.lng);
                });

                geofenceMarker.on('dragend', function(event) {
                    const markerLocation = event.target.getLatLng();
                    setGeofenceLocation(markerLocation.lat, markerLocation.lng);
                });
            }

            function setGeofenceLocation(latitude, longitude) {
                $('#attendanceLatitude').val(Number(latitude).toFixed(7));
                $('#attendanceLongitude').val(Number(longitude).toFixed(7));
                geofenceMarker.setLatLng([latitude, longitude]);
                geofenceCircle.setLatLng([latitude, longitude]);
                geofenceMap.panTo([latitude, longitude]);
            }

            $('#attendanceRadius').on('input change', function() {
                if (geofenceCircle) {
                    geofenceCircle.setRadius(Number($(this).val() || 100));
                }
            });

            $('#attendanceLatitude, #attendanceLongitude').on('change', function() {
                const latitude = parseFloat($('#attendanceLatitude').val());
                const longitude = parseFloat($('#attendanceLongitude').val());

                if (!Number.isNaN(latitude) && !Number.isNaN(longitude)) {
                    setGeofenceLocation(latitude, longitude);
                }
            });

            $('#useCurrentLocationBtn').on('click', function() {
                if (!navigator.geolocation) {
                    Swal.fire({
                        title: 'Geolocation Unavailable',
                        text: 'Your browser does not support geolocation.',
                        icon: 'error',
                        confirmButtonColor: '#3475db'
                    });
                    return;
                }

                navigator.geolocation.getCurrentPosition(function(position) {
                    const currentLatLng = [position.coords.latitude, position.coords.longitude];

                    if (currentLocationMarker) {
                        currentLocationMarker.setLatLng(currentLatLng);
                    } else {
                        currentLocationMarker = L.marker(currentLatLng, {
                            icon: createAttendanceMapIcon('employee')
                        }).addTo(geofenceMap).bindPopup('My current location');
                    }

                    setGeofenceLocation(position.coords.latitude, position.coords.longitude);
                    geofenceMap.setZoom(18);
                }, function() {
                    Swal.fire({
                        title: 'Location Error',
                        text: 'Unable to get your current location. Please place the pin manually on the map.',
                        icon: 'error',
                        confirmButtonColor: '#3475db'
                    });
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            });

            // Store current values for dynamic loading
            const currentMunicipality = $('#municipalitySelect').val();
            const currentBarangay = '{{ $studio->barangay }}';
            
            // If there's a current municipality, load barangays
            if (currentMunicipality) {
                loadBarangays(currentMunicipality, currentBarangay);
            }

            // Dynamic location handling
            $('#municipalitySelect').on('change', function() {
                const municipality = $(this).val();
                loadBarangays(municipality, null);
            });

            function loadBarangays(municipality, selectedBarangay = null) {
                const barangaySelect = $('#barangaySelect');
                const zipCodeInput = $('#zipCodeInput');
                
                if (!municipality) {
                    barangaySelect.prop('disabled', true).html('<option value="">Select municipality first</option>');
                    zipCodeInput.val('');
                    return;
                }
                
                // Show loading
                barangaySelect.prop('disabled', true).html('<option value="">Loading barangays...</option>');
                zipCodeInput.val('Loading...');
                
                // Fetch barangays and zip code
                $.ajax({
                    url: '{{ route("owner.studio.get-barangays", ["municipality" => "__MUNICIPALITY__"]) }}'.replace('__MUNICIPALITY__', municipality),
                    type: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        // Populate barangay dropdown
                        let barangayOptions = '<option value="">Select barangay</option>';
                        if (response.barangays && response.barangays.length > 0) {
                            response.barangays.forEach(barangay => {
                                const selected = (selectedBarangay && barangay === selectedBarangay) ? 'selected' : '';
                                barangayOptions += `<option value="${barangay}" ${selected}>${barangay}</option>`;
                            });
                            barangaySelect.prop('disabled', false);
                        } else {
                            barangayOptions = '<option value="">No barangays found for this municipality</option>';
                            barangaySelect.prop('disabled', true);
                        }
                        
                        barangaySelect.html(barangayOptions);
                        
                        // Set zip code
                        if (response.zip_code) {
                            zipCodeInput.val(response.zip_code);
                            // Update hidden input
                            if (!$('#hiddenZipCode').length) {
                                $('#zipCodeInput').after(`<input type="hidden" id="hiddenZipCode" name="zip_code" value="${response.zip_code}">`);
                            } else {
                                $('#hiddenZipCode').val(response.zip_code);
                            }
                        } else {
                            zipCodeInput.val('');
                            $('#hiddenZipCode').remove();
                        }
                    },
                    error: function() {
                        barangaySelect.html('<option value="">Error loading barangays</option>').prop('disabled', true);
                        zipCodeInput.val('');
                        $('#hiddenZipCode').remove();
                    }
                });
            }

            // Create hidden input for zip_code if exists
            const initialZipCode = $('#zipCodeInput').val();
            if (initialZipCode && initialZipCode !== '' && initialZipCode !== 'Loading...') {
                $('#zipCodeInput').after(`<input type="hidden" id="hiddenZipCode" name="zip_code" value="${initialZipCode}">`);
            }

            // Function to validate operating days
            function validateOperatingDays() {
                const checkedDays = $('#operatingDaysGroup input[type="checkbox"]:checked');
                const errorElement = $('.operating-days-error');
                const operatingDaysGroup = $('#operatingDaysGroup');
                
                if (checkedDays.length === 0) {
                    operatingDaysGroup.addClass('border border-danger rounded');
                    errorElement.show();
                    return false;
                } else {
                    operatingDaysGroup.removeClass('border border-danger rounded');
                    errorElement.hide();
                    return true;
                }
            }

            // Validate operating days when checkboxes change
            $('#operatingDaysGroup input[type="checkbox"]').on('change', function() {
                validateOperatingDays();
            });

            // AJAX Form Submission
            $('#studioEditForm').on('submit', function(e) {
                e.preventDefault();
                
                // Validate operating days before submission
                if (!validateOperatingDays()) {
                    $('html, body').animate({
                        scrollTop: $('#operatingDaysGroup').offset().top - 100
                    }, 500);
                    return;
                }
                
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Show loading state
                submitBtn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...'
                );
                
                // Prepare form data
                const formData = new FormData(this);
                
                // Get selected service categories
                const serviceCategoriesSelect = document.querySelector('select[name="service_categories[]"]');
                if (serviceCategoriesSelect && serviceCategoriesSelect.choices) {
                    const selectedCategories = serviceCategoriesSelect.choices.getValue(true);
                    formData.delete('service_categories[]');
                    selectedCategories.forEach(value => {
                        formData.append('service_categories[]', value);
                    });
                }
                
                // Get selected operating days
                const selectedOperatingDays = [];
                $('#operatingDaysGroup input[type="checkbox"]:checked').each(function() {
                    selectedOperatingDays.push($(this).val());
                });
                formData.delete('operating_days[]');
                selectedOperatingDays.forEach(value => {
                    formData.append('operating_days[]', value);
                });
                
                // Ensure barangay and zip code are included
                const barangayValue = $('#barangaySelect').val();
                if (barangayValue) {
                    formData.set('barangay', barangayValue);
                }
                
                const zipCodeValue = $('#hiddenZipCode').val() || $('#zipCodeInput').val();
                if (zipCodeValue && zipCodeValue !== 'Loading...') {
                    formData.set('zip_code', zipCodeValue);
                }
                
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
                            errors = xhr.responseJSON.errors;
                            errorMessage = 'Please fix the following errors:';
                            
                            // Clear previous error messages
                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').hide();
                            $('.border-danger').removeClass('border border-danger rounded');
                            
                            // Show field errors
                            $.each(errors, function(field, messages) {
                                const fieldName = field.replace(/\.\d+/, '').replace('[]', '');
                                
                                if (fieldName === 'operating_days') {
                                    $('#operatingDaysGroup').addClass('border border-danger rounded');
                                    $('.operating-days-error').text(messages.join(', ')).show();
                                } else {
                                    const input = $(`[name="${fieldName}"], [name="${fieldName}[]"]`);
                                    if (input.length) {
                                        input.addClass('is-invalid');
                                        const feedback = input.closest('.mb-3').find('.invalid-feedback');
                                        if (feedback.length) {
                                            feedback.text(messages.join(', ')).show();
                                        }
                                    }
                                }
                            });
                            
                            // Scroll to first error
                            const firstError = $('.is-invalid, .border-danger').first();
                            if (firstError.length) {
                                $('html, body').animate({
                                    scrollTop: firstError.offset().top - 100
                                }, 500);
                            }
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        
                        // Show error SweetAlert only if not field validation errors
                        if (Object.keys(errors).length === 0) {
                            Swal.fire({
                                title: 'Error!',
                                html: errorMessage,
                                icon: 'error',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                timerProgressBar: true,
                                timer: 1500
                            });
                        }
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false).html(originalText);
                    }
                });
            });
            
            // Remove invalid class on input change
            $('input, select, textarea').on('input change', function() {
                $(this).removeClass('is-invalid');
                $(this).closest('.mb-3').find('.invalid-feedback').hide();
            });
            
            // Remove border on operating days checkbox change
            $('#operatingDaysGroup input[type="checkbox"]').on('change', function() {
                $('#operatingDaysGroup').removeClass('border border-danger rounded');
                $('.operating-days-error').hide();
            });
        });
    </script>
@endsection
