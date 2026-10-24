@extends('layouts.owner.app')
@section('title', 'Invite Freelancers')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">                  
            <div class="row mt-3">
                <div class="col-12">
                    @if(!$studio)
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-circle me-2"></i>
                            You need to create a studio first before inviting members.
                            <a href="{{ route('owner.studio.create') }}" class="alert-link">Create Studio</a>
                        </div>
                    @else
                        @include('partials.studio-picker', ['route' => 'owner.members.invite', 'label' => 'Invite freelancers to', 'help' => 'Each studio has its own freelancer members.'])

                        {{-- TABLE --}}
                        <div data-table data-table-rows-per-page="5" class="card">
                            <div class="card-header">
                                <h4 class="card-title">List of Freelancers &middot; {{ $studio->studio_name }}</h4>
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
                                            <th data-table-sort>Fullname</th>
                                            <th data-table-sort>Email Address</th>
                                            <th data-table-sort>Contact Number</th>
                                            <th data-table-sort>Role</th>
                                            <th class="text-center" style="width: 1%;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($freelancers as $freelancer)
                                            @php
                                                $isInvited = $invitedFreelancerIds->contains($freelancer->id);
                                                // Check if freelancer has profile and if it's complete
                                                $hasProfile = $freelancer->freelancerProfile && (
                                                    $freelancer->freelancerProfile->brand_name ||
                                                    $freelancer->freelancerProfile->services->isNotEmpty() ||
                                                    $freelancer->freelancerProfile->starting_price ||
                                                    $freelancer->freelancerProfile->schedule ||
                                                    $freelancer->freelancerProfile->facebook_url ||
                                                    $freelancer->freelancerProfile->portfolio_works
                                                );
                                                $canInvite = !$isInvited && $hasProfile;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-1">
                                                                <a href="javascript:void(0)" class="link-reset">{{ $freelancer->full_name }}</a>
                                                            </h5>
                                                            <p class="mb-0 fs-xxs">
                                                                <span class="fw-medium">UUID:</span>
                                                                <span class="text-muted">{{ $freelancer->uuid }}</span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $freelancer->email }}</td>
                                                <td>{{ $freelancer->mobile_number ?? 'N/A' }}</td>
                                                <td>
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-1">
                                                                <a href="javascript:void(0)" class="link-reset">Freelancer</a>
                                                            </h5>
                                                            <p class="mb-0 fs-xxs">
                                                                @if($freelancer->freelancerProfile && $freelancer->freelancerProfile->categories->isNotEmpty())
                                                                    <span class="text-muted">
                                                                        {{ $freelancer->freelancerProfile->categories->first()->category_name }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-muted">Photographer</span>
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex justify-content-start gap-1">
                                                        <button type="button" class="btn view-freelancer-btn" data-freelancer-id="{{ $freelancer->id }}">
                                                            <i class="ti ti-eye"></i>
                                                        </button>
                                                        @if($isInvited)
                                                            <button type="button" class="w-100 btn btn-sm btn-success" disabled>
                                                                <i class="ti ti-check me-1"></i> Invited
                                                            </button>
                                                        @elseif(!$hasProfile)
                                                            <button type="button" class="w-100 btn btn-sm btn-warning" disabled data-bs-toggle="tooltip" title="Freelancer has not completed their profile setup">
                                                                <i class="ti ti-user-x me-1"></i>Incomplete
                                                            </button>
                                                        @else
                                                            <button type="button" class="w-100 btn btn-sm btn-primary invite-btn" data-freelancer-id="{{ $freelancer->id }}"data-freelancer-name="{{ $freelancer->full_name }}">
                                                                <i class="ti ti-user-plus me-1"></i> Invite
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="ti ti-users-off fs-48 mb-3"></i>
                                                        <h5>No freelancers available</h5>
                                                        <p>There are no freelancers registered in the system yet.</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
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
                    <!-- Content loaded via AJAX -->
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

    {{-- INVITATION MODAL --}}
    <div class="modal fade" id="invitationModal" tabindex="-1" aria-labelledby="invitationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="invitationModalLabel">Send Invitation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="invitationForm">
                    @csrf
                    <input type="hidden" name="studio_id" value="{{ $studio->id ?? '' }}">
                    <input type="hidden" name="freelancer_id" id="inviteFreelancerId">
                    
                    <div class="modal-body">
                        <p class="mb-4">You are about to invite <strong id="inviteFreelancerName">[Freelancer Name]</strong> to collaborate.</p>
                        
                        <div class="mb-4">
                            <label for="invitation_message" class="form-label">Invitation Message</label>
                            <textarea name="invitation_message" 
                                      id="invitation_message" 
                                      class="form-control" 
                                      rows="5" 
                                      placeholder="Hi [Freelancer Name]!&#10;&#10;I really love your photography style and would love to discuss possible collaboration for upcoming projects.&#10;&#10;Best regards,&#10;{{ auth()->user()->full_name }}"
                                      required></textarea>
                            <div class="form-text mt-2">
                                Feel free to include project details, timeline, budget range, or anything that might help.
                            </div>
                            <div class="invalid-feedback" id="invitation_message_error"></div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="sendInvitationBtn">
                            <i class="me-1" data-lucide="send"></i> Send Invitation
                        </button>
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
            let currentFreelancerId = null;
            let currentFreelancerName = null;

            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // View freelancer details
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
                            const profile = freelancer.freelancer_profile; // Note: snake_case in JSON
                            
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
                                                    <div class="card-body text-center">
                                                        <i class="ti ti-user-off fs-48 text-muted mb-3"></i>
                                                        <h5 class="text-muted mb-2">No Profile Information Available</h5>
                                                        <p class="text-muted mb-4">
                                                            The freelancer needs to complete their profile setup in order to display:
                                                        </p>
                                                        <div class="row justify-content-center">
                                                            <div class="col-12">
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
                                    
                                    // Handle services_name - it might be array or JSON string
                                    let serviceItems = service.services_name;
                                    
                                    if (typeof serviceItems === 'string') {
                                        try {
                                            serviceItems = JSON.parse(serviceItems);
                                        } catch (e) {
                                            console.error('Error parsing services_name:', e);
                                            serviceItems = [];
                                        }
                                    }
                                    
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
                            
                            // Handle operating_days - it might be array or JSON string
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
                            
                            // Handle portfolio_works - it might be array or JSON string
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
                            
                            // Check if profile has basic information
                            const hasBasicInfo = profile.brand_name || profile.tagline || profile.bio || profile.years_experience;
                            const hasServices = servicesHtml !== '';
                            const hasPricing = profile.starting_price || profile.deposit_policy;
                            const hasSchedule = profile.schedule;
                            const hasSocialLinks = profile.facebook_url || profile.instagram_url || profile.website_url;
                            const hasPortfolio = portfolioWorks.length > 0;
                            
                            // Check if profile is completely empty (no credentials at all)
                            const isEmptyProfile = !hasBasicInfo && !hasServices && !hasPricing && !hasSchedule && !hasSocialLinks && !hasPortfolio;
                            
                            // Prepare the header section (always shown)
                            const headerHtml = `
                                <div class="row align-items-center mb-4">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center flex-column flex-md-row">
                                            <div class="flex-shrink-0 mb-3 mb-md-0">
                                                ${profile.brand_logo ? 
                                                    `<img src="/storage/${profile.brand_logo}" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;" alt="${freelancer.first_name} ${freelancer.last_name}">` :
                                                    `<div class="rounded-circle d-flex align-items-center justify-content-center ${isEmptyProfile ? 'bg-light-warning' : 'bg-light-primary'}" style="width: 100px; height: 100px;">
                                                        <i data-lucide="${isEmptyProfile ? 'user-circle' : 'user'}" class="fs-32 ${isEmptyProfile ? 'text-warning' : 'text-primary'}"></i>
                                                    </div>`
                                                }
                                            </div>
                                            <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                                <h3 class="mb-1 fw-bold">${freelancer.first_name} ${freelancer.last_name}</h3>
                                                <p class="text-primary fw-semibold mb-1">${profile.brand_name || 'No brand name set'}</p>
                                                <p class="text-muted mb-0">
                                                    <i class="ti ti-map-pin me-1"></i> 
                                                    ${profile.location ? profile.location.municipality + ', ' + profile.location.province : 'Location not specified'}
                                                    ${profile.years_experience ? ' · ' + profile.years_experience + ' years experience' : ''}
                                                </p>
                                                <div class="mt-2">
                                                    ${categoriesHtml}
                                                    ${isEmptyProfile ? '<span class="badge badge-soft-warning fs-6 p-1">Profile Incomplete</span>' : ''}
                                                    ${freelancer.email_verified ? 
                                                        '<span class="badge badge-soft-success fs-6 p-1 ms-1">Verified Email</span>' : 
                                                        '<span class="badge badge-soft-warning fs-6 p-1 ms-1">Email Not Verified</span>'
                                                    }
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            let contentHtml = '';
                            
                            if (isEmptyProfile) {
                                // Profile exists but has no credentials
                                contentHtml = `
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <div class="d-flex align-items-center">
                                                    <i class="ti ti-info-circle fs-20 me-3"></i>
                                                    <div>
                                                        <h5 class="alert-heading mb-1">Profile Setup Started</h5>
                                                        <p class="mb-0">This freelancer has started their profile setup but hasn't added any credentials yet.</p>
                                                        <p class="mb-0">The following information is missing:</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <h5 class="text-primary fw-semibold mb-3">Missing Credentials</h5>
                                                            <div class="list-group list-group-borderless">
                                                                ${!profile.brand_name ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Brand Information</h6>
                                                                            <p class="text-muted mb-0">Brand name, tagline, and about section not provided</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                                
                                                                ${!hasServices ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Services</h6>
                                                                            <p class="text-muted mb-0">No services or categories have been added</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                                
                                                                ${!hasPricing ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Pricing Information</h6>
                                                                            <p class="text-muted mb-0">Starting price and deposit policy not specified</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                                
                                                                ${!hasSchedule ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Availability Schedule</h6>
                                                                            <p class="text-muted mb-0">Operating days, hours, and booking limits not set</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                                
                                                                ${!hasSocialLinks ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Contact Information</h6>
                                                                            <p class="text-muted mb-0">Social media links and website not provided</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                                
                                                                ${!hasPortfolio ? `
                                                                    <div class="list-group-item d-flex align-items-center">
                                                                        <i class="ti ti-circle-dashed text-secondary me-3"></i>
                                                                        <div class="flex-grow-1">
                                                                            <h6 class="mb-1">Portfolio</h6>
                                                                            <p class="text-muted mb-0">No portfolio works or sample projects uploaded</p>
                                                                        </div>
                                                                    </div>
                                                                ` : ''}
                                                            </div>
                                                            <div class="mt-4 text-center">
                                                                <p class="text-muted">
                                                                    You can still invite this freelancer, but they should complete their profile for better collaboration.
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else {
                                // Complete profile - show all sections
                                contentHtml = `
                                    <div class="row g-4">
                                        <!-- Personal Information -->
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
                                        <div class="col-12">
                                            <h5 class="text-primary fw-semibold mb-3">Brand Identity</h5>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-light-primary rounded-circle p-2">
                                                                <i data-lucide="building" class="fs-20 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <label class="text-muted small mb-1">Brand Name</label>
                                                            <p class="mb-0 fw-medium">${profile.brand_name || 'Not specified'}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-light-primary rounded-circle p-2">
                                                                <i data-lucide="info" class="fs-20 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <label class="text-muted small mb-1">Professional Tagline</label>
                                                            <p class="mb-0 fw-medium">${profile.tagline || 'Not specified'}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-light-primary rounded-circle p-2">
                                                                <i data-lucide="file-text" class="fs-20 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <label class="text-muted small mb-1">About Me</label>
                                                            <p class="mb-0">${profile.bio || 'No bio provided'}</p>
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
                                                            <p class="mb-0 fw-medium">${profile.years_experience || '0'} years</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Services -->
                                        <div class="col-12">
                                            <h5 class="text-primary fw-semibold mb-3">Services Offered</h5>
                                            <div class="row g-3">
                                                <div class="col-12 mb-3">
                                                    ${servicesHtml ? 
                                                        `<div class="list-group">
                                                            ${servicesHtml}
                                                        </div>` :
                                                        '<p class="text-muted">No services specified</p>'
                                                    }
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pricing -->
                                        <div class="col-12">
                                            <h5 class="text-primary fw-semibold mb-3">Pricing Information</h5>
                                            <div class="row g-3">
                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-light-primary rounded-circle p-2">
                                                                <i data-lucide="tag" class="fs-20 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <label class="text-muted small mb-1">Starting Price</label>
                                                            <p class="mb-0 fs-5">PHP ${profile.starting_price ? parseFloat(profile.starting_price).toLocaleString('en-PH', {minimumFractionDigits: 2}) : '0.00'}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12 col-md-6">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0">
                                                            <div class="bg-light-primary rounded-circle p-2">
                                                                <i data-lucide="banknote-arrow-down" class="fs-20 text-primary"></i>
                                                            </div>
                                                        </div>
                                                        <div class="flex-grow-1 ms-3">
                                                            <label class="text-muted small mb-1">Deposit Policy</label>
                                                            <p class="mb-0 fw-medium">${profile.deposit_policy || 'Not specified'}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

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

                                                    <div class="col-12 col-md-6">
                                                        <div class="d-flex align-items-start">
                                                            <div class="flex-shrink-0">
                                                                <div class="bg-light-primary rounded-circle p-2">
                                                                    <i data-lucide="users" class="fs-20 text-primary"></i>
                                                                </div>
                                                            </div>
                                                            <div class="flex-grow-1 ms-3">
                                                                <label class="text-muted small mb-1">Max Clients per Day</label>
                                                                <p class="mb-0 fw-medium">${profile.schedule.booking_limit || 'Not specified'}</p>
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
                                                                <p class="mb-0 fw-medium">${profile.schedule.advance_booking ? profile.schedule.advance_booking + ' day(s)' : 'Not specified'}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ` : ''}

                                        <!-- Social Links -->
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
                                `;
                            }
                            
                            // Combine header and content
                            const detailsHtml = `
                                <div class="container-fluid">
                                    ${headerHtml}
                                    ${contentHtml}
                                </div>
                            `;
                            
                            $('#freelancerDetailsContent').html(detailsHtml);
                            
                            // Initialize Lucide icons
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                            
                            // Initialize carousel if present
                            if (portfolioWorks.length > 1 && !isEmptyProfile) {
                                new bootstrap.Carousel(document.getElementById('portfolioCarousel'));
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

            // Open invitation modal
            $(document).on('click', '.invite-btn', function() {
                currentFreelancerId = $(this).data('freelancer-id');
                currentFreelancerName = $(this).data('freelancer-name');
                
                $('#inviteFreelancerId').val(currentFreelancerId);
                $('#inviteFreelancerName').text(currentFreelancerName);
                
                // Set default message
                const defaultMessage = `Hi ${currentFreelancerName}!\n\nI really love your photography style and would love to discuss possible collaboration for upcoming projects.\n\nBest regards,\n${$('meta[name="user-name"]').attr('content') || 'Studio Owner'}`;
                $('#invitation_message').val(defaultMessage);
                
                // Clear previous errors
                $('#invitation_message').removeClass('is-invalid');
                $('#invitation_message_error').text('');
                
                $('#invitationModal').modal('show');
            });

            // Handle invitation form submission
            $('#invitationForm').on('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = $('#sendInvitationBtn');
                const originalText = submitBtn.html();
                
                // Disable button and show loading
                submitBtn.prop('disabled', true);
                submitBtn.html(`
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Sending...
                `);
                
                // Clear previous errors
                $('#invitation_message').removeClass('is-invalid');
                $('#invitation_message_error').text('');
                
                $.ajax({
                    url: '{{ route("owner.members.invite.store") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            $('#invitationModal').modal('hide');
                            
                            // Show success message with SweetAlert
                            Swal.fire({
                                icon: 'success',
                                title: 'Invitation Sent!',
                                html: `<div class="text-center">
                                    <i class="ti ti-check-circle fs-48 text-primary mb-3"></i>
                                    <h5 class="fw-bold">Invitation Sent Successfully</h5>
                                    <p class="text-muted">Your invitation has been sent to <strong>${currentFreelancerName}</strong>.</p>
                                </div>`,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });
                            
                            // Update UI
                            $(`.invite-btn[data-freelancer-id="${currentFreelancerId}"]`)
                                .replaceWith(`
                                    <button type="button" class="btn btn-sm btn-success" disabled>
                                        <i class="ti ti-check fs-lg me-2"></i> Invited
                                    </button>
                                `);
                            
                            // Reset form
                            $('#invitationForm')[0].reset();
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(key => {
                                const input = $(`[name="${key}"]`);
                                const errorDiv = $(`#${key}_error`);
                                
                                if (input.length) {
                                    input.addClass('is-invalid');
                                }
                                if (errorDiv.length) {
                                    errorDiv.text(errors[key][0]);
                                }
                            });
                            
                            // Show error message with SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: `<div class="text-center">
                                    <p class="text-muted">There are errors in the form that need to be corrected.</p>
                                </div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#DC3545',
                            });
                        } else {
                            // Show error message with SweetAlert
                            Swal.fire({
                                icon: 'error',
                                title: 'Operation Failed',
                                html: `<div class="text-center">
                                    <p class="text-muted">${xhr.responseJSON?.message || 'Please try again.'}</p>
                                    </div>`,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#DC3545', // Red color
                            });
                        }
                    },
                    complete: function() {
                        // Re-enable button
                        submitBtn.prop('disabled', false);
                        submitBtn.html(originalText);
                    }
                });
            });

            // Close modal and reset
            $('#invitationModal').on('hidden.bs.modal', function() {
                $('#invitationForm')[0].reset();
                currentFreelancerId = null;
                currentFreelancerName = null;
            });
        });
    </script>
@endsection
