@extends('layouts.owner.app')
@section('title', 'Invited Freelancers')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    {{-- ALERT MESSAGES --}}
                    <div id="alertContainer"></div>
                    
                    @if(!$studio)
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-circle me-2"></i>
                            You need to create a studio first before viewing members.
                            <a href="{{ route('owner.studio.create') }}" class="alert-link">Create Studio</a>
                        </div>
                    @else
                        @include('partials.studio-picker', ['route' => 'owner.members.index', 'label' => 'Members of', 'help' => 'Each studio has its own freelancer members.'])

                        {{-- TABLE --}}
                        <div data-table data-table-rows-per-page="5" class="card">
                            <div class="card-header">
                                <h4 class="card-title">Invited Members &middot; {{ $studio->studio_name }}</h4>
                            </div>

                            <div class="card-header border-light justify-content-between">
                                <div class="d-flex gap-2">
                                    <div class="app-search">
                                        <input data-table-search type="search" class="form-control" placeholder="Search...">
                                        <i data-lucide="search" class="app-search-icon text-muted"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                        <tr class="text-uppercase fs-xxs">
                                            <th data-table-sort>Freelancer</th>
                                            <th data-table-sort>Invitation Date</th>
                                            <th data-table-sort>Invitation Message</th>
                                            <th data-table-sort>Status</th>
                                            <th class="text-center" style="width: 1%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($members as $member)
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'cancelled' => 'secondary'
                                                ];
                                                
                                                $statusTexts = [
                                                    'pending' => 'Waiting for freelancer approval',
                                                    'approved' => 'Freelancer accepted the invitation',
                                                    'rejected' => 'Freelancer declined the invitation',
                                                    'cancelled' => 'Invitation cancelled by you'
                                                ];
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($member->freelancer && $member->freelancer->profile && $member->freelancer->profile->brand_logo)
                                                            <img src="/storage/{{ $member->freelancer->profile->brand_logo }}" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="{{ $member->freelancer->full_name }}">
                                                        @endif
                                                        <div>
                                                            <h5 class="mb-1">
                                                                <a href="javascript:void(0)" class="link-reset view-freelancer-btn" data-freelancer-id="{{ $member->freelancer_id }}">
                                                                    {{ $member->freelancer->full_name ?? 'Unknown' }}
                                                                </a>
                                                            </h5>
                                                            <p class="mb-0 fs-xxs">
                                                                <span class="fw-medium">Email:</span>
                                                                <span class="text-muted">{{ $member->freelancer->email ?? 'N/A' }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-1">
                                                                {{ $member->invited_at->format('M d, Y') }}
                                                            </h5>
                                                            <p class="mb-0 fs-xxs">
                                                                <span class="text-muted">{{ $member->invited_at->format('h:i A') }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="text-truncate" style="max-width: 200px;" 
                                                         data-bs-toggle="tooltip" 
                                                         title="{{ $member->invitation_message }}">
                                                        {{ Str::limit($member->invitation_message, 50) }}
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div>
                                                            <h5 class="mb-1">
                                                                <span class="badge badge-soft-{{ $statusColors[$member->status] }} p-1">
                                                                    {{ strtoupper($member->status) }}
                                                                </span>
                                                            </h5>
                                                            <p class="mb-0 fs-xxs">
                                                                <span class="text-muted">{{ $statusTexts[$member->status] }}</span>
                                                                @if($member->responded_at)
                                                                    <br>
                                                                    <small>Responded: {{ $member->responded_at->format('M d, Y') }}</small>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button type="button" class="btn btn-sm view-freelancer-btn" data-freelancer-id="{{ $member->freelancer_id }}">
                                                            <i class="ti ti-eye fs-lg"></i>
                                                        </button>
                                                        
                                                        @if($member->status === 'pending')
                                                            <button type="button" class="btn btn-sm cancel-invitation-btn" data-invitation-id="{{ $member->id }}"data-freelancer-name="{{ $member->freelancer->full_name ?? 'Unknown' }}">
                                                                <i class="ti ti-cancel fs-lg"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="card-footer border-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div data-table-pagination-info="users"></div>
                                    <div data-table-pagination></div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    {{-- FREELANCER DETAILS MODAL --}}
    <div class="modal fade" id="freelancerDetailsModal" tabindex="-1" aria-labelledby="freelancerDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="freelancerDetailsModalLabel">Freelancer Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="freelancerDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading freelancer details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- CANCEL INVITATION MODAL --}}
    <div class="modal fade" id="cancelInvitationModal" tabindex="-1" aria-labelledby="cancelInvitationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="cancelInvitationModalLabel">Cancel Invitation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="cancelInvitationForm">
                    @csrf
                    <input type="hidden" name="invitation_id" id="cancelInvitationId">
                    
                    <div class="modal-body">
                        <p class="mb-4">You are about to cancel the invitation sent to <strong id="cancelFreelancerName">[Freelancer Name]</strong>.</p>
                        
                        <div class="mb-4">
                            <label for="cancellation_reason" class="form-label">Cancellation Reason (Optional)</label>
                            <textarea name="cancellation_reason" 
                                      id="cancellation_reason" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Optional: Please provide a reason for cancelling this invitation..."></textarea>
                            <div class="form-text mt-2">
                                This will be recorded but not sent to the freelancer.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Keep Invitation</button>
                        <button type="submit" class="btn btn-danger" id="cancelInvitationBtn">Cancel Invitation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let csrfToken = $('meta[name="csrf-token"]').attr('content');
            let currentInvitationId = null;
            let currentFreelancerName = null;

            // Function to show alert
            function showAlert(type, message) {
                const alertHtml = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
                $('#alertContainer').html(alertHtml);
                
                // Auto dismiss after 5 seconds
                setTimeout(() => {
                    $('.alert').alert('close');
                }, 5000);
            }

            // View freelancer details (same as invite page)
            $(document).on('click', '.view-freelancer-btn', function() {
                const freelancerId = $(this).data('freelancer-id');
                
                // Show loading state
                $('#freelancerDetailsContent').html(`
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading freelancer details...</p>
                    </div>
                `);
                
                $('#freelancerDetailsModal').modal('show');
                
                // Load freelancer details via AJAX
                $.ajax({
                    url: '{{ route("owner.members.freelancer.details", ":id") }}'.replace(':id', freelancerId),
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const freelancer = response.data;
                            const profile = freelancer.freelancer_profile; // Changed from profile to freelancer_profile
                            
                            // Check if freelancer has a profile
                            if (!profile) {
                                // Show no profile message
                                const noProfileHtml = `
                                    <div class="container-fluid">
                                        <div class="row align-items-center mb-4">
                                            <div class="col-12">
                                                <div class="d-flex align-items-center flex-column flex-md-row">
                                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-light-secondary" style="width: 100px; height: 100px;">
                                                            <i data-lucide="user-x" class="fs-32 text-secondary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                                        <h3 class="mb-1 fw-bold">${freelancer.first_name} ${freelancer.last_name}</h3>
                                                        <p class="text-muted mb-0">
                                                            <i class="ti ti-mail me-1"></i> 
                                                            ${freelancer.email}
                                                        </p>
                                                        <div class="mt-2">
                                                            <span class="badge badge-soft-warning fs-6 p-1">Profile Not Setup</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert alert-warning">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-alert-circle fs-20 me-3"></i>
                                                        <div>
                                                            <h5 class="alert-heading mb-1">Profile Not Complete</h5>
                                                            <p class="mb-0">This freelancer has not set up their professional profile yet.</p>
                                                            <p class="mb-0">They need to complete their profile setup before you can view their credentials and services.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card">
                                                    <div class="card-body text-center py-5">
                                                        <i class="ti ti-user-off fs-48 text-muted mb-3"></i>
                                                        <h5 class="text-muted mb-2">No Profile Information Available</h5>
                                                        <p class="text-muted mb-4">
                                                            The freelancer needs to complete their profile setup in order to display:
                                                        </p>
                                                        <div class="row justify-content-center">
                                                            <div class="col-12 col-md-8">
                                                                <div class="list-group list-group-borderless">
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-x text-danger me-3"></i>
                                                                        <span>Brand information and logo</span>
                                                                    </div>
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-x text-danger me-3"></i>
                                                                        <span>Services and pricing</span>
                                                                    </div>
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-x text-danger me-3"></i>
                                                                        <span>Portfolio and work samples</span>
                                                                    </div>
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-x text-danger me-3"></i>
                                                                        <span>Availability schedule</span>
                                                                    </div>
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-x text-danger me-3"></i>
                                                                        <span>Contact and social media links</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-4">
                                                            <p class="text-muted">
                                                                You can still invite this freelancer, but they will need to complete their profile before collaborating.
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                                
                                $('#freelancerDetailsContent').html(noProfileHtml);
                                
                                // Initialize Lucide icons
                                if (typeof lucide !== 'undefined') {
                                    lucide.createIcons();
                                }
                                return;
                            }
                            
                            let categoriesHtml = '';
                            if (profile.categories && profile.categories.length > 0) {
                                categoriesHtml = profile.categories.map(cat => 
                                    `<span class="badge badge-soft-primary me-1">${cat.category_name}</span>`
                                ).join('');
                            }
                            
                            let servicesHtml = '';
                            if (profile.services && profile.services.length > 0) {
                                servicesHtml = profile.services.map(service => {
                                    // Get category name if available
                                    let categoryName = 'Services';
                                    if (service.category && service.category.category_name) {
                                        categoryName = service.category.category_name;
                                    } else if (service.category_id) {
                                        // If category is not loaded but we have ID, we could fetch it
                                        categoryName = 'Category #' + service.category_id;
                                    }
                                    
                                    // Handle services_name - it's already an array in the response
                                    let serviceItems = service.services_name;
                                    
                                    if (Array.isArray(serviceItems) && serviceItems.length > 0) {
                                        const itemsHtml = serviceItems.map(s => `<li>${s}</li>`).join('');
                                        return `<div class="list-group-item">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-check text-success me-2 mt-1"></i>
                                                        <div class="flex-grow-1">
                                                            <h5 class="mb-1 fw-semibold">${categoryName}</h5>
                                                            <ul class="mb-2 ps-3 text-muted">
                                                                ${itemsHtml}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>`;
                                    }
                                    
                                    return '';
                                }).join('');
                            }
                            
                            // Handle operating_days - it's a JSON string
                            let operatingDays = 'Not specified';
                            if (profile.schedule && profile.schedule.operating_days) {
                                let days = profile.schedule.operating_days;
                                
                                if (typeof days === 'string') {
                                    try {
                                        days = JSON.parse(days);
                                    } catch (e) {
                                        console.error('Error parsing operating_days:', e);
                                        days = [];
                                    }
                                }
                                
                                if (Array.isArray(days) && days.length > 0) {
                                    // Capitalize first letter of each day
                                    operatingDays = days.map(day => 
                                        day.charAt(0).toUpperCase() + day.slice(1)
                                    ).join(', ');
                                }
                            }
                            
                            // Handle portfolio_works - it's a JSON string
                            let portfolioWorks = [];
                            if (profile.portfolio_works) {
                                if (typeof profile.portfolio_works === 'string') {
                                    try {
                                        portfolioWorks = JSON.parse(profile.portfolio_works);
                                    } catch (e) {
                                        console.error('Error parsing portfolio_works:', e);
                                        portfolioWorks = [];
                                    }
                                } else if (Array.isArray(profile.portfolio_works)) {
                                    portfolioWorks = profile.portfolio_works;
                                }
                            }
                            
                            // Build the full details HTML
                                                        // Build the full details HTML
                            const detailsHtml = `
                                <div class="container-fluid">
                                    <div class="row align-items-center mb-4">
                                        <div class="col-12">
                                            <div class="d-flex align-items-center flex-column flex-md-row">
                                                <div class="flex-shrink-0 mb-3 mb-md-0">
                                                    ${profile.brand_logo ? 
                                                        `<img src="/storage/${profile.brand_logo}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;" alt="${freelancer.first_name} ${freelancer.last_name}">` :
                                                        `<div class="rounded-circle d-flex align-items-center justify-content-center bg-light-primary" style="width: 100px; height: 100px;">
                                                            <i data-lucide="user" class="fs-32 text-primary"></i>
                                                        </div>`
                                                    }
                                                </div>
                                                <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                                    <h3 class="mb-1 fw-bold">${freelancer.first_name} ${freelancer.last_name}</h3>
                                                    <p class="text-primary fw-semibold mb-1">${profile.brand_name || 'No brand name'}</p>
                                                    <p class="text-muted mb-0">
                                                        <i class="ti ti-map-pin me-1"></i> 
                                                        ${profile.location ? profile.location.municipality + ', ' + profile.location.province : 'Location not specified'}
                                                        ${profile.years_experience ? ' · ' + profile.years_experience + ' years experience' : ''}
                                                    </p>
                                                    <div class="mt-2">
                                                        ${categoriesHtml}
                                                        ${freelancer.email_verified ? 
                                                            '<span class="badge badge-soft-success fs-6 p-1 ms-1">Verified Email</span>' : 
                                                            '<span class="badge badge-soft-warning fs-6 p-1 ms-1">Email Not Verified</span>'
                                                        }
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Check if profile is completely empty -->
                                    ${!profile.brand_name && !profile.tagline && !profile.bio && !profile.years_experience && !profile.services && !profile.starting_price && !profile.schedule && !profile.facebook_url && !profile.instagram_url && !profile.website_url && (!profile.portfolio_works || profile.portfolio_works === '[]') ? `
                                        <div class="row">
                                            <div class="col-12">
                                                <div class="alert alert-info">
                                                    <div class="d-flex align-items-center">
                                                        <i class="ti ti-info-circle fs-20 me-3"></i>
                                                        <div>
                                                            <h5 class="alert-heading mb-1">Profile Setup Started</h5>
                                                            <p class="mb-0">This freelancer has started their profile setup but hasn't added any credentials yet.</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ` : `
                                        <!-- Personal Information -->
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
                                                                <p class="mb-0 fw-medium">${freelancer.first_name} ${freelancer.last_name}</p>
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
                                                                <p class="mb-0 fw-medium">${freelancer.email}</p>
                                                                <small class="text-muted">Status: ${freelancer.email_verified ? 'Verified' : 'Not Verified'}</small>
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
                                                                <p class="mb-0 fw-medium">${freelancer.mobile_number || 'N/A'}</p>
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
                                                                <label class="text-muted small mb-1">Registered Since</label>
                                                                <p class="mb-0 fw-medium">${new Date(freelancer.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6">
                                                        <div class="d-flex align-items-start">
                                                            <div class="flex-shrink-0">
                                                                <div class="bg-light-primary rounded-circle p-2">
                                                                    <i data-lucide="shield" class="fs-20 text-primary"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <label class="text-muted small mb-1">Account Status</label>
                                                                <p class="mb-0 fw-medium">
                                                                    <span class="badge badge-soft-${freelancer.status === 'active' ? 'success' : 'danger'}">${freelancer.status ? freelancer.status.toUpperCase() : 'UNKNOWN'}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-12 col-md-6">
                                                        <div class="d-flex align-items-start">
                                                            <div class="flex-shrink-0">
                                                                <div class="bg-light-primary rounded-circle p-2">
                                                                    <i data-lucide="user-check" class="fs-20 text-primary"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <label class="text-muted small mb-1">User Type</label>
                                                                <p class="mb-0 fw-medium">${freelancer.user_type || 'Freelancer Individual'}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Brand Identity -->
                                            ${profile.brand_name || profile.tagline || profile.bio || profile.years_experience ? `
                                                <div class="col-12">
                                                    <h5 class="text-primary fw-semibold mb-3">Brand Identity</h5>
                                                    <div class="row g-3">
                                                        ${profile.brand_name ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="building" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Brand Name</label>
                                                                        <p class="mb-0 fw-medium">${profile.brand_name}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.tagline ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="info" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Professional Tagline</label>
                                                                        <p class="mb-0 fw-medium">${profile.tagline}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.bio ? `
                                                            <div class="col-12">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">About Me</label>
                                                                        <p class="mb-0">${profile.bio}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.years_experience ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="calendar" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Years of Experience</label>
                                                                        <p class="mb-0 fw-medium">${profile.years_experience} years</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}

                                            <!-- Services -->
                                            ${servicesHtml ? `
                                                <div class="col-12">
                                                    <h5 class="text-primary fw-semibold mb-3">Services Offered</h5>
                                                    <div class="row g-3">
                                                        <div class="col-12 mb-3">
                                                            <div class="list-group">
                                                                ${servicesHtml}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            ` : ''}

                                            <!-- Pricing -->
                                            ${profile.starting_price || profile.deposit_policy ? `
                                                <div class="col-12">
                                                    <h5 class="text-primary fw-semibold mb-3">Pricing Information</h5>
                                                    <div class="row g-3">
                                                        ${profile.starting_price ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Starting Price</label>
                                                                        <p class="mb-0 fs-5">PHP ${parseFloat(profile.starting_price).toLocaleString('en-PH', {minimumFractionDigits: 2})}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.deposit_policy ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="banknote-arrow-down" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Deposit Policy</label>
                                                                        <p class="mb-0 fw-medium">${profile.deposit_policy}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}

                                            <!-- Schedule -->
                                            ${profile.schedule ? `
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
                                                                    <p class="mb-0 fw-medium">${operatingDays}</p>
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
                                                                    <p class="mb-0 fw-medium">${profile.schedule.start_time || 'N/A'} – ${profile.schedule.end_time || 'N/A'}</p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        ${profile.schedule.booking_limit ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="users" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Max Clients per Day</label>
                                                                        <p class="mb-0 fw-medium">${profile.schedule.booking_limit}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.schedule.advance_booking ? `
                                                            <div class="col-12 col-md-6">
                                                                <div class="d-flex align-items-start">
                                                                    <div class="flex-shrink-0">
                                                                        <div class="bg-light-primary rounded-circle p-2">
                                                                            <i data-lucide="alert-circle" class="fs-20 text-primary"></i>
                                                                        </div>
                                                                    </div>
                                                                    <div class="flex-grow-1 ms-3">
                                                                        <label class="text-muted small mb-1">Advance Booking</label>
                                                                        <p class="mb-0 fw-medium">${profile.schedule.advance_booking} day(s)</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}

                                            <!-- Social Links -->
                                            ${profile.facebook_url || profile.instagram_url || profile.website_url ? `
                                                <div class="col-12">
                                                    <h5 class="text-primary fw-semibold mb-3">Social Media Links</h5>
                                                    <div class="row g-3">
                                                        ${profile.facebook_url ? `
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
                                                                            <a href="${profile.facebook_url}" target="_blank" class="text-decoration-none">
                                                                                ${profile.facebook_url}
                                                                            </a>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.instagram_url ? `
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
                                                                            <a href="${profile.instagram_url}" target="_blank" class="text-decoration-none">
                                                                                ${profile.instagram_url}
                                                                            </a>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}

                                                        ${profile.website_url ? `
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
                                                                            <a href="${profile.website_url}" target="_blank" class="text-decoration-none">
                                                                                ${profile.website_url}
                                                                            </a>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}

                                            <!-- Portfolio -->
                                            ${portfolioWorks.length > 0 ? `
                                                <div class="col-12">
                                                    <h5 class="text-primary fw-semibold mb-3">Portfolio Works</h5>
                                                    <p class="text-muted">Sample works and recent projects</p>

                                                    <div id="portfolioCarousel" class="carousel slide" data-bs-ride="carousel">
                                                        <div class="carousel-inner">
                                                            ${portfolioWorks.map((work, index) => `
                                                                <div class="carousel-item ${index === 0 ? 'active' : ''}">
                                                                    <img src="/storage/${work}" 
                                                                        class="d-block w-100 rounded shadow" 
                                                                        alt="Portfolio ${index + 1}" 
                                                                        style="height: 300px; object-fit: cover;">
                                                                </div>
                                                            `).join('')}
                                                        </div>
                                                        ${portfolioWorks.length > 1 ? `
                                                            <button class="carousel-control-prev" type="button" data-bs-target="#portfolioCarousel" data-bs-slide="prev">
                                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Previous</span>
                                                            </button>
                                                            <button class="carousel-control-next" type="button" data-bs-target="#portfolioCarousel" data-bs-slide="next">
                                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                                <span class="visually-hidden">Next</span>
                                                            </button>
                                                        ` : ''}
                                                    </div>
                                                </div>
                                            ` : ''}
                                        </div>
                                    `}
                                </div>
                            `;
                            
                            $('#freelancerDetailsContent').html(detailsHtml);
                            
                            // Initialize Lucide icons
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        } else {
                            $('#freelancerDetailsContent').html(`
                                <div class="text-center py-5">
                                    <i class="ti ti-alert-circle fs-48 text-danger mb-3"></i>
                                    <h5>Error Loading Details</h5>
                                    <p class="text-muted">${response.message}</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr) {
                        $('#freelancerDetailsContent').html(`
                            <div class="text-center py-5">
                                <i class="ti ti-alert-circle fs-48 text-danger mb-3"></i>
                                <h5>Error Loading Details</h5>
                                <p class="text-muted">Unable to load freelancer details. Please try again.</p>
                            </div>
                        `);
                    }
                });
            });

            // Open cancel invitation modal
            $(document).on('click', '.cancel-invitation-btn', function() {
                currentInvitationId = $(this).data('invitation-id');
                currentFreelancerName = $(this).data('freelancer-name');
                
                $('#cancelInvitationId').val(currentInvitationId);
                $('#cancelFreelancerName').text(currentFreelancerName);
                
                // Clear previous input
                $('#cancellation_reason').val('');
                
                $('#cancelInvitationModal').modal('show');
            });

            // Handle cancel invitation form submission
            $('#cancelInvitationForm').on('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = $('#cancelInvitationBtn');
                const originalText = submitBtn.html();
                
                // Disable button and show loading
                submitBtn.prop('disabled', true);
                submitBtn.html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Cancelling...
                `);
                
                $.ajax({
                    url: '{{ route("owner.members.cancel", ":id") }}'.replace(':id', currentInvitationId),
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            $('#cancelInvitationModal').modal('hide');
                            
                            // Show success message with SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Invitation Cancelled',
                                html: `<div class="text-center">
                                    <i class="ti ti-check-circle fs-48 text-primary mb-3"></i>
                                    <p class="text-muted">Invitation to <strong>${currentFreelancerName}</strong> has been cancelled.</p>
                                </div>`,
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true
                            }).then((result) => {
                                if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                                    // Reload page to update status
                                    window.location.reload();
                                }
                            });
                            
                            // Reload page to update status
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 403) {
                            showAlert('danger', 
                                `<i class="ti ti-alert-circle me-2"></i> 
                                You are not authorized to cancel this invitation.`
                            );
                        } else if (xhr.status === 400) {
                            showAlert('danger', 
                                `<i class="ti ti-alert-circle me-2"></i> 
                                Only pending invitations can be cancelled.`
                            );
                        } else {
                            showAlert('danger', 
                                `<i class="ti ti-alert-circle me-2"></i> 
                                Failed to cancel invitation. Please try again.`
                            );
                        }
                    },
                    complete: function() {
                        // Re-enable button
                        submitBtn.prop('disabled', false);
                        submitBtn.html(originalText);
                    }
                });
            });

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
@endsection
