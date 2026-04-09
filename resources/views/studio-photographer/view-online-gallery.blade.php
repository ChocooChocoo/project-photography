@extends('layouts.studio-photographer.app')
@section('title', 'Clients Online Gallery')

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    {{-- TABLE --}}
                    <div data-table data-table-rows-per-page="5" class="card">
                        <div class="card-header">
                            <h4 class="card-title">Online Gallery</h4>
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
                            <table
                                class="table table-custom table-centered table-select table-hover table-bordered w-100 mb-0">
                                <thead class=" align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>Booking Ref</th>
                                        <th>Client</th>
                                        <th>Event Date</th>
                                        <th>Category</th>
                                        <th>Package</th>
                                        <th class="text-center">Gallery Status</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bookings as $booking)
                                        <tr>
                                            <td>{{ $booking->booking_reference }}</td>
                                            <td>{{ $booking->client->first_name ?? '' }}
                                                {{ $booking->client->last_name ?? '' }}</td>
                                            <td>{{ $booking->formatted_event_date ?? 'N/A' }}</td>
                                            <td>{{ $booking->category->category_name ?? 'N/A' }}</td>
                                            <td>
                                                @if ($booking->packages->isNotEmpty())
                                                    @php
                                                        $package = $booking->packages->first();
                                                        $packageName = $package->studioPackage->package_name ?? 'N/A';
                                                    @endphp
                                                    {{ $packageName }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($booking->has_gallery)
                                                    <span class="badge badge-soft-success w-100">
                                                        <i class="ti ti-photo-check me-1"></i> Gallery Created
                                                    </span>
                                                @else
                                                    <span class="badge badge-soft-warning w-100">
                                                        <i class="ti ti-photo-off me-1"></i> No Gallery
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($booking->has_gallery)
                                                    <button class="btn btn-sm manage-gallery"
                                                        data-booking-id="{{ $booking->id }}" title="Manage Gallery">
                                                        <i class="ti ti-library-photo fs-5" aria-hidden="true"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-sm create-gallery"
                                                        data-booking-id="{{ $booking->id }}" title="Create Gallery">
                                                        <i class="ti ti-photo-up fs-5" aria-hidden="true"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <i class="ti ti-photo-off fs-3 text-muted mb-2 d-block"></i>
                                                No in-progress or completed bookings assigned to you with online gallery found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer ">
                            <div class="d-flex justify-content-between align-items-center">
                                <div data-table-pagination-info="gallery"></div>
                                <div data-table-pagination></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL --}}
    <div class="modal fade" id="galleryModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="galleryModalLabel">
                        Manage Online Gallery
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Loading Spinner --}}
                    <div id="modalLoadingSpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading gallery details...</p>
                    </div>

                    {{-- Main Content --}}
                    <div id="galleryContent" style="display: none;">
                        {{-- Booking & Gallery Info Header --}}
                        <div class="row align-items-center mb-4">
                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center flex-column flex-md-row">
                                    <div class="flex-shrink-0 mb-3 mb-md-0">
                                        <div class="bg-light-primary rounded-circle p-3">
                                            <i class="ti ti-library-photo fs-1 text-primary"></i>
                                        </div>
                                    </div>

                                    <div class="flex-grow-1 ms-md-4 text-center text-md-start">
                                        <h2 class="mb-1 h3 h3-md" id="galleryNameDisplay">Loading...</h2>
                                        <div
                                            class="d-flex align-items-center justify-content-center justify-content-md-start mb-2 flex-wrap">
                                            <span id="galleryStatusBadge">Loading...</span>
                                        </div>

                                        <p class="text-muted mb-0" id="bookingReferenceDisplay">
                                            <i class="ti ti-hash me-1"></i> Loading...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                {{-- Booking Information Section --}}
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">BOOKING INFORMATION</h5>

                                    {{-- Client Name --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-user fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Client Name</label>
                                                <p class="mb-0 fw-medium" id="clientName">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Client Email --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-mail fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Email Address</label>
                                                <p class="mb-0 fw-medium" id="clientEmail">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Booking Reference --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-hash fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Booking Reference</label>
                                                <p class="mb-0 fw-medium" id="bookingReference">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Event Date --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-calendar fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Event Date</label>
                                                <p class="mb-0 fw-medium" id="eventDate">Loading...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Gallery Information Section --}}
                                <div class="row g-2 mb-3" id="galleryInfo">
                                    <h5 class="card-title text-primary">GALLERY INFORMATION</h5>

                                    {{-- Gallery Reference --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-hash fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Gallery Reference</label>
                                                <p class="mb-0 fw-medium" id="galleryReference">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Gallery Name --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-photo-edit fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Gallery Name</label>
                                                <p class="mb-0 fw-medium" id="galleryName">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Total Photos --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-photo fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Total Photos</label>
                                                <p class="mb-0 fw-medium"><span id="totalPhotos">0</span> images</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Published Date --}}
                                    <div class="col-12 col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-calendar-time fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Published Date</label>
                                                <p class="mb-0 fw-medium" id="publishedDate">Loading...</p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Gallery Description --}}
                                    <div class="col-12">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light-primary rounded-circle p-2">
                                                    <i class="ti ti-notes fs-20 text-primary"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <label class="text-muted small mb-1">Description</label>
                                                <p class="mb-0 fw-medium" id="galleryDescription">No description provided
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- No Gallery Message --}}
                                <div id="noGalleryMessage" class="text-center py-4 border rounded mb-3"
                                    style="display: none;">
                                    <i class="ti ti-photo-off fs-1 text-muted mb-2 d-block"></i>
                                    <p class="text-muted mb-0">No gallery created yet for this booking.</p>
                                </div>

                                {{-- Upload Section --}}
                                <div class="row g-2 mb-3">
                                    <h5 class="card-title text-primary">UPLOAD IMAGES</h5>

                                    <div class="col-12">
                                        <div
                                            class="d-flex justify-content-between align-items-center p-3 border rounded mb-3">
                                            <div>
                                                <p class="mb-1 fw-medium">Upload New Images</p>
                                                <small class="text-muted">
                                                    <i class="ti ti-info-circle me-1"></i>Select up to 50 images. Max 5MB
                                                    per image.
                                                </small>
                                            </div>
                                            <button type="button" class="btn btn-primary" id="showUploadFormBtn"
                                                style="background-color: #3475db; border-color: #3475db;">
                                                <i class="ti ti-cloud-upload me-2"></i>Upload Images
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Upload Form (Hidden by default) --}}
                                    <div class="col-12" id="uploadFormContainer" style="display: none;">
                                        <div class="card border shadow-sm">
                                            <div class="card-body">
                                                <form id="uploadGalleryForm" enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" id="bookingId" name="booking_id">

                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="mb-3">
                                                                <label for="images" class="form-label fw-medium">Select
                                                                    Images <span class="text-danger">*</span></label>
                                                                <input type="file" class="form-control" id="images"
                                                                    name="images[]" multiple
                                                                    accept="image/jpeg,image/png,image/jpg,image/gif"
                                                                    required>
                                                                <small class="text-muted">You can select up to 50 images.
                                                                    Max 5MB per image.</small>
                                                                <div id="imagePreview" class="row mt-2 g-2"></div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="mb-3">
                                                                <label for="gallery_name"
                                                                    class="form-label fw-medium">Gallery Name</label>
                                                                <input type="text" class="form-control"
                                                                    id="gallery_name" name="gallery_name"
                                                                    placeholder="e.g., Wedding Gallery">
                                                                <small class="text-muted">Leave empty to use event
                                                                    name</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="description"
                                                                    class="form-label fw-medium">Description</label>
                                                                <textarea class="form-control" id="description" name="description" rows="3"
                                                                    placeholder="Brief description of the gallery..."></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="text-end border-top pt-3">
                                                        <button type="button" class="btn btn-default me-1"
                                                            id="cancelUploadBtn">
                                                            <i class="ti ti-x me-1"></i>Cancel
                                                        </button>
                                                        <button type="submit" class="btn btn-primary" id="uploadBtn"
                                                            style="background-color: #3475db; border-color: #3475db;">
                                                            <span class="spinner-border spinner-border-sm d-none"
                                                                id="uploadSpinner" role="status"
                                                                aria-hidden="true"></span>
                                                            <i class="ti ti-cloud-upload me-1"></i>Upload Images
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Gallery Images Section --}}
                                <div class="row g-2 mb-3" id="galleryImagesSection" style="display: none;">
                                    <h5 class="card-title text-primary">GALLERY IMAGES</h5>

                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <p class="mb-0 fw-medium">Total Images: <span id="imageCount">0</span></p>
                                            <div>
                                                <button type="button" class="btn btn-sm btn-soft-primary me-2"
                                                    id="editGalleryBtn">
                                                    <i class="ti ti-edit me-1"></i>Edit Gallery Info
                                                </button>
                                                <button type="button" class="btn btn-sm btn-soft-danger"
                                                    id="deleteGalleryBtn">
                                                    <i class="ti ti-trash me-1"></i>Delete Entire Gallery
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Loading Spinner for Images --}}
                                    <div class="col-12" id="manageLoadingSpinner">
                                        <div class="text-center py-3">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 text-muted">Loading gallery images...</p>
                                        </div>
                                    </div>

                                    {{-- Images Container --}}
                                    <div class="col-12">
                                        <div id="imagesContainer" class="row"></div>
                                    </div>

                                    {{-- Edit Gallery Form --}}
                                    <div class="col-12">
                                        <div class="card mt-3 border shadow-sm d-none" id="editGalleryForm">
                                            <div class="card-body">
                                                <h6 class="fw-semibold mb-3">Edit Gallery Information</h6>
                                                <form id="updateGalleryForm">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" id="edit_gallery_id">

                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_gallery_name"
                                                                    class="form-label fw-medium">Gallery Name</label>
                                                                <input type="text" class="form-control"
                                                                    id="edit_gallery_name" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <label for="edit_status"
                                                                    class="form-label fw-medium">Status</label>
                                                                <select class="form-select" id="edit_status">
                                                                    <option value="active">Active</option>
                                                                    <option value="inactive">Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="mb-3">
                                                                <label for="edit_description"
                                                                    class="form-label fw-medium">Description</label>
                                                                <textarea class="form-control" id="edit_description" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="text-end border-top pt-3">
                                                        <button type="button" class="btn btn-sm btn-default"
                                                            id="cancelEditBtn">
                                                            <i class="ti ti-x me-1"></i>Cancel
                                                        </button>
                                                        <button type="submit" class="btn btn-sm btn-primary"
                                                            style="background-color: #3475db; border-color: #3475db;">
                                                            <i class="ti ti-device-floppy me-1"></i>Save Changes
                                                        </button>
                                                    </div>
                                                </form>
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
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let selectedBookingId = null;
            let currentGalleryId = null;

            // Create/Manage Gallery button click
            $(document).on('click', '.create-gallery, .manage-gallery', function() {
                const bookingId = $(this).data('booking-id');
                selectedBookingId = bookingId;

                // Reset modal
                $('#modalLoadingSpinner').show();
                $('#galleryContent').hide();
                $('#manageLoadingSpinner').show();
                $('#galleryImagesSection').hide();
                $('#uploadFormContainer').hide();
                $('#showUploadFormBtn').prop('disabled', false);
                $('#uploadGalleryForm')[0].reset();
                $('#imagePreview').empty();
                $('#editGalleryForm').addClass('d-none');

                // Set booking ID in form
                $('#bookingId').val(bookingId);

                // Load gallery details
                $.ajax({
                    url: `{{ url('studio-photographer/online-gallery') }}/${bookingId}/details`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            // Fill booking info
                            $('#bookingReference').text(response.booking.booking_reference);
                            $('#clientName').text(response.booking.client_name);
                            $('#clientEmail').text(response.booking.client_email);
                            $('#eventDate').text(response.booking.event_date);

                            if (response.has_gallery && response.gallery) {
                                currentGalleryId = response.gallery.id;
                                $('#edit_gallery_id').val(response.gallery.id);

                                $('#noGalleryMessage').hide();
                                $('#galleryInfo').show();

                                // Header info
                                $('#galleryNameDisplay').text(response.gallery.gallery_name);
                                $('#bookingReferenceDisplay').html(
                                    '<i class="ti ti-hash me-1"></i> ' + response.booking
                                    .booking_reference);

                                let statusBadge = response.gallery.status === 'active' ?
                                    '<span class="badge badge-soft-success p-1"><i class="ti ti-check me-1"></i>Active</span>' :
                                    '<span class="badge badge-soft-danger p-1"><i class="ti ti-x me-1"></i>Inactive</span>';
                                $('#galleryStatusBadge').html(statusBadge);

                                // Gallery info table
                                $('#galleryReference').text(response.gallery.gallery_reference);
                                $('#galleryName').text(response.gallery.gallery_name);
                                $('#totalPhotos').text(response.gallery.total_photos || 0);
                                $('#galleryDescription').text(response.gallery.description ||
                                    'No description provided');

                                $('#publishedDate').text(response.gallery.published_at ?
                                    new Date(response.gallery.published_at)
                                    .toLocaleDateString() : 'Not published');

                                // Fill edit form
                                $('#edit_gallery_name').val(response.gallery.gallery_name);
                                $('#edit_description').val(response.gallery.description || '');
                                $('#edit_status').val(response.gallery.status);

                                // Load gallery images
                                loadGalleryImages(response.gallery);
                            } else {
                                currentGalleryId = null;
                                $('#noGalleryMessage').show();
                                $('#galleryInfo').hide();
                                $('#galleryImagesSection').hide();
                                $('#manageLoadingSpinner').hide();

                                // Show empty header
                                $('#galleryNameDisplay').text('No Gallery Yet');
                                $('#bookingReferenceDisplay').html(
                                    '<i class="ti ti-hash me-1"></i> ' + response.booking
                                    .booking_reference);
                                $('#galleryStatusBadge').html(
                                    '<span class="badge badge-soft-warning p-1"><i class="ti ti-alert-circle me-1"></i>No Gallery</span>'
                                );
                            }

                            $('#modalLoadingSpinner').hide();
                            $('#galleryContent').show();

                            // Show modal
                            $('#galleryModal').modal('show');

                            // Re-initialize lucide icons
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to load gallery details';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                        $('#modalLoadingSpinner').hide();
                    }
                });
            });

            // Show upload form
            $('#showUploadFormBtn').on('click', function() {
                $('#uploadFormContainer').slideDown();
                $(this).prop('disabled', true);
            });

            // Cancel upload
            $('#cancelUploadBtn').on('click', function() {
                $('#uploadFormContainer').slideUp();
                $('#showUploadFormBtn').prop('disabled', false);
                $('#uploadGalleryForm')[0].reset();
                $('#imagePreview').empty();
            });

            // Load gallery images function
            function loadGalleryImages(gallery) {
                $('#manageLoadingSpinner').show();
                $('#galleryImagesSection').hide();

                if (!gallery.images || gallery.images.length === 0) {
                    // Empty state HTML
                    $('#imagesContainer').html(`
                        <div class="col-12">
                            <div class="text-center py-5 bg-light rounded">
                                <i class="ti ti-photo-off fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-3">No images in this gallery yet.</p>
                                <button class="btn btn-primary btn-sm" id="quickUploadBtn" style="background-color: #3475db; border-color: #3475db;">
                                    <i class="ti ti-cloud-upload me-1"></i>Upload Images Now
                                </button>
                            </div>
                        </div>
                    `);
                    $('#imageCount').text('0');
                    $('#manageLoadingSpinner').hide();
                    $('#galleryImagesSection').show();

                    // Quick upload button inside empty state
                    $(document).off('click', '#quickUploadBtn').on('click', '#quickUploadBtn', function() {
                        $('#showUploadFormBtn').click();
                    });

                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                    return;
                }

                $('#imageCount').text(gallery.images.length);

                let imagesHtml = '';
                $.each(gallery.images, function(index, image) {
                    imagesHtml += `
                        <div class="col-md-3 col-sm-4 col-6 mb-3">
                            <div class="card">
                                <div class="position-relative">
                                    <img src="{{ asset('storage') }}/${image}" class="card-img-top" alt="Gallery Image" style="height: 150px; object-fit: cover;">
                                    <span class="position-absolute top-0 start-0 bg-primary text-white p-1 m-2 rounded small"><i class="ti ti-photo me-1"></i>${index + 1}</span>
                                </div>
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="ti ti-calendar-time me-1"></i>
                                            ${new Date().toLocaleDateString()}
                                        </small>
                                        <button class="btn btn-sm btn-soft-danger delete-image" data-image="${image}" title="Delete this image">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#imagesContainer').html(imagesHtml);
                $('#manageLoadingSpinner').hide();
                $('#galleryImagesSection').show();

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }

            // Image preview on file selection
            $('#images').on('change', function() {
                const files = $(this)[0].files;
                const previewContainer = $('#imagePreview');
                previewContainer.empty();

                if (files.length > 50) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Too Many Files',
                        text: 'You can only select up to 50 images.'
                    });
                    $(this).val('');
                    return;
                }

                $.each(files, function(i, file) {
                    if (file.size > 5 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'File Too Large',
                            text: `${file.name} exceeds 5MB limit.`
                        });
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewContainer.append(`
                            <div class="col-md-3 col-sm-4 col-6">
                                <div class="card">
                                    <img src="${e.target.result}" class="card-img-top" alt="Preview" style="height: 100px; object-fit: cover;">
                                    <div class="card-body p-1 text-center">
                                        <small class="text-truncate d-block">${file.name.substring(0, 15)}...</small>
                                    </div>
                                </div>
                            </div>
                        `);
                    }
                    reader.readAsDataURL(file);
                });
            });

            // Upload gallery form submit
            $('#uploadGalleryForm').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const files = $('#images')[0].files;

                if (files.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Images',
                        text: 'Please select at least one image to upload.'
                    });
                    return;
                }

                $('#uploadSpinner').removeClass('d-none');
                $('#uploadBtn').prop('disabled', true);

                $.ajax({
                    url: `{{ url('studio-photographer/online-gallery') }}/${selectedBookingId}/upload`,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });

                            // Refresh the page after success
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to upload images';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    },
                    complete: function() {
                        $('#uploadSpinner').addClass('d-none');
                        $('#uploadBtn').prop('disabled', false);
                    }
                });
            });

            // Delete single image
            $(document).on('click', '.delete-image', function() {
                const imagePath = $(this).data('image');

                Swal.fire({
                    title: 'Delete Image',
                    text: 'Are you sure you want to delete this image?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('studio-photographer/online-gallery') }}/${currentGalleryId}/image`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}',
                                image_path: imagePath
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });

                                    // Reload the page
                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr) {
                                let message = 'Failed to delete image';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message
                                });
                            }
                        });
                    }
                });
            });

            // Delete entire gallery
            $('#deleteGalleryBtn').on('click', function() {
                Swal.fire({
                    title: 'Delete Gallery',
                    text: 'Are you sure you want to delete this entire gallery? All images will be permanently removed.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete gallery!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('studio-photographer/online-gallery') }}/${currentGalleryId}`,
                            type: 'DELETE',
                            data: {
                                _token: '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        timerProgressBar: true
                                    });

                                    setTimeout(() => {
                                        location.reload();
                                    }, 2000);
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr) {
                                let message = 'Failed to delete gallery';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                }
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: message
                                });
                            }
                        });
                    }
                });
            });

            // Edit gallery button
            $('#editGalleryBtn').on('click', function() {
                $('#editGalleryForm').removeClass('d-none');
                // Scroll to edit form
                $('html, body').animate({
                    scrollTop: $('#editGalleryForm').offset().top - 100
                }, 500);
            });

            // Cancel edit
            $('#cancelEditBtn').on('click', function() {
                $('#editGalleryForm').addClass('d-none');
            });

            // Update gallery form
            $('#updateGalleryForm').on('submit', function(e) {
                e.preventDefault();

                const galleryId = $('#edit_gallery_id').val();

                $.ajax({
                    url: `{{ url('studio-photographer/online-gallery') }}/${galleryId}`,
                    type: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        gallery_name: $('#edit_gallery_name').val(),
                        description: $('#edit_description').val(),
                        status: $('#edit_status').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true
                            });

                            $('#editGalleryForm').addClass('d-none');

                            // Reload the page
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to update gallery';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message
                        });
                    }
                });
            });

            // Modal close event
            $('#galleryModal').on('hidden.bs.modal', function() {
                $('#uploadFormContainer').hide();
                $('#showUploadFormBtn').prop('disabled', false);
                $('#uploadGalleryForm')[0].reset();
                $('#imagePreview').empty();
                $('#editGalleryForm').addClass('d-none');
            });
        });
    </script>
@endsection
