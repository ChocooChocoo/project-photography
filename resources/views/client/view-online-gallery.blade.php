@extends('layouts.client.app')
@section('title', 'My Online Galleries')

{{-- STYLES --}}
@section('styles')
    <style>
        .gallery-card {
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: default;
        }

        .gallery-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .gallery-thumbnail {
            transition: opacity 0.2s;
        }

        .gallery-thumbnail:hover {
            opacity: 0.9;
        }

        .gallery-image-card {
            transition: transform 0.2s;
            cursor: pointer;
        }

        .gallery-image-card:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .modal-fullscreen .modal-content {
            background-color: #1a1a1a;
        }

        .modal-fullscreen .btn-close-white {
            filter: brightness(0) invert(1);
        }

        #fullscreenImage {
            max-width: 100%;
            max-height: 90vh;
            object-fit: contain;
        }
    </style>
@endsection

{{-- CONTENTS --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3 mb-3">
                <div class="col-12">
                    <div class="page-title-box">
                        <h4 class="page-title">My Online Galleries</h4>
                    </div>
                </div>
            </div>

            <div class="row">
                @forelse($galleries as $gallery)
                    <div class="col-xl-4 col-md-6">
                        <div class="card gallery-card" data-gallery-id="{{ $gallery->id }}" data-gallery-type="{{ $gallery->type }}">
                            <div class="position-relative">
                                @if($gallery->thumbnail)
                                    <img src="{{ asset('storage/' . $gallery->thumbnail) }}" 
                                        class="card-img-top gallery-thumbnail" 
                                        alt="{{ $gallery->gallery_name }}"
                                        style="height: 220px; width: 100%; object-fit: cover; cursor: pointer;">
                                @else
                                    <div class="bg-light d-flex align-items-center justify-content-center" 
                                        style="height: 220px; width: 100%; cursor: pointer;">
                                        <i class="ti ti-photo-off fs-1 text-muted"></i>
                                    </div>
                                @endif
                                
                                <span class="position-absolute top-0 end-0 badge bg-primary m-3">
                                    <i class="ti ti-photo me-1"></i>{{ $gallery->total_photos }} Photos
                                </span>
                                
                                <span class="position-absolute top-0 start-0 badge badge-soft-primary m-3">
                                    {{ $gallery->type === 'studio' ? 'Studio' : 'Freelancer' }}
                                </span>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title text-truncate mb-2">{{ $gallery->gallery_name }}</h5>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ti ti-building-store fs-5 text-primary me-2"></i>
                                    <span class="text-muted small">{{ $gallery->provider_name }}</span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ti ti-hash fs-5 text-primary me-2"></i>
                                    <span class="text-muted small">{{ $gallery->booking_reference }}</span>
                                </div>
                                
                                <div class="d-flex align-items-center mb-2">
                                    <i class="ti ti-calendar fs-5 text-primary me-2"></i>
                                    <span class="text-muted small">{{ $gallery->event_date }}</span>
                                </div>
                                
                                @if($gallery->description)
                                    <p class="card-text text-muted small mt-2">
                                        {{ Str::limit($gallery->description, 80) }}
                                    </p>
                                @endif
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="text-muted small">
                                        <i class="ti ti-clock me-1"></i>{{ $gallery->created_at }}
                                    </span>
                                    
                                    <button class="btn btn-primary btn-sm view-gallery-btn"
                                            data-gallery-id="{{ $gallery->id }}"
                                            data-gallery-type="{{ $gallery->type }}"
                                            style="background-color: #3475db; border-color: #3475db;">
                                        <i class="ti ti-eye me-1"></i>View Gallery
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="ti ti-photo-off fs-1 text-muted mb-3 d-block"></i>
                                <h5>No Galleries Available</h5>
                                <p class="text-muted">You don't have any online galleries yet. Galleries will appear here once your booking is in progress or completed and the studio has published your photos.</p>
                            </div>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Gallery Modal --}}
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalLabel">Loading Gallery...</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Loading Spinner --}}
                    <div id="modalLoadingSpinner" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading gallery images...</p>
                    </div>

                    {{-- Gallery Content --}}
                    <div id="galleryContent" style="display: none;">
                        {{-- Gallery Info --}}
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-light-primary rounded-circle p-3 me-3">
                                        <i class="ti ti-library-photo fs-1 text-primary"></i>
                                    </div>
                                    <div>
                                        <h3 id="galleryName" class="mb-1"></h3>
                                        <p class="text-muted mb-0">
                                            <span id="providerName"></span> • 
                                            <span id="galleryDate"></span> • 
                                            <span id="photoCount"></span> photos
                                        </p>
                                    </div>
                                </div>
                                
                                <p id="galleryDescription" class="text-muted mb-3"></p>
                                
                                <div class="alert alert-info d-flex align-items-center py-2" role="alert">
                                    <i class="ti ti-info-circle me-2 fs-5"></i>
                                    <small>Click on any image to view in full screen</small>
                                </div>
                            </div>
                        </div>

                        {{-- Images Grid --}}
                        <div class="row g-3" id="imagesGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fullscreen Image Modal --}}
    <div class="modal fade" id="fullscreenImageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen">
            <div class="modal-content bg-dark">
                <div class="modal-header border-secondary">
                    <h6 class="modal-title text-white" id="fullscreenImageTitle"></h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center p-0">
                    <img src="" id="fullscreenImage" class="img-fluid" style="max-height: 90vh;" alt="Fullscreen Image">
                </div>
                <div class="modal-footer border-secondary d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-light prev-image-btn" id="prevImageBtn">
                        <i class="ti ti-chevron-left me-1"></i>Previous
                    </button>
                    <span class="text-white mx-3 align-self-center" id="imageCounter"></span>
                    <button type="button" class="btn btn-outline-light next-image-btn" id="nextImageBtn">
                        Next<i class="ti ti-chevron-right ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- SCRIPTS --}}
@section('scripts')
    <script>
        $(document).ready(function() {
            let currentImages = [];
            let currentIndex = 0;
            let currentGalleryId = null;
            let currentGalleryType = null;

            // View Gallery button click
            $('.view-gallery-btn').on('click', function() {
                const galleryId = $(this).data('gallery-id');
                const galleryType = $(this).data('gallery-type');
                
                currentGalleryId = galleryId;
                currentGalleryType = galleryType;

                // Reset modal
                $('#modalLoadingSpinner').show();
                $('#galleryContent').hide();
                $('#imagesGrid').empty();

                // Load gallery details
                $.ajax({
                    url: `{{ url('client/online-gallery') }}/${galleryId}/${galleryType}`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            const gallery = response.gallery;
                            
                            // Set gallery info
                            $('#galleryModalLabel').text(gallery.gallery_name);
                            $('#galleryName').text(gallery.gallery_name);
                            $('#providerName').html('<i class="ti ti-building-store me-1"></i>' + gallery.provider_name);
                            $('#galleryDate').html('<i class="ti ti-calendar me-1"></i>' + gallery.event_date);
                            $('#photoCount').html('<i class="ti ti-photo me-1"></i>' + gallery.total_photos);
                            $('#galleryDescription').text(gallery.description || 'No description provided');

                            // Store images for fullscreen navigation
                            currentImages = gallery.images;

                            // Generate images grid
                            let imagesHtml = '';
                            $.each(gallery.images, function(index, image) {
                                imagesHtml += `
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card gallery-image-card" data-index="${index}">
                                            <img src="{{ asset('storage') }}/${image}" 
                                                class="card-img-top gallery-image" 
                                                alt="Gallery Image ${index + 1}"
                                                style="height: 200px; width: 100%; object-fit: cover; cursor: pointer;">
                                            <div class="card-body p-2 text-center">
                                                <small class="text-muted">Photo ${index + 1}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            $('#imagesGrid').html(imagesHtml);
                            
                            $('#modalLoadingSpinner').hide();
                            $('#galleryContent').show();

                            // Initialize lucide icons
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }

                            // Show modal
                            $('#galleryModal').modal('show');
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to load gallery';
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

            // Click on gallery image to view fullscreen
            $(document).on('click', '.gallery-image', function() {
                const card = $(this).closest('.gallery-image-card');
                const index = card.data('index');
                
                currentIndex = index;
                
                // Set fullscreen image
                $('#fullscreenImage').attr('src', $(this).attr('src'));
                $('#fullscreenImageTitle').text(`Photo ${index + 1} of ${currentImages.length}`);
                $('#imageCounter').text(`${index + 1} / ${currentImages.length}`);
                
                // Show/hide navigation buttons based on position
                if (currentImages.length <= 1) {
                    $('#prevImageBtn, #nextImageBtn').hide();
                } else {
                    $('#prevImageBtn, #nextImageBtn').show();
                    
                    // Enable/disable buttons based on position
                    $('#prevImageBtn').prop('disabled', index === 0);
                    $('#nextImageBtn').prop('disabled', index === currentImages.length - 1);
                }
                
                // Show modal
                $('#fullscreenImageModal').modal('show');
            });

            // Previous image in fullscreen
            $('#prevImageBtn').on('click', function() {
                if (currentIndex > 0) {
                    currentIndex--;
                    updateFullscreenImage();
                }
            });

            // Next image in fullscreen
            $('#nextImageBtn').on('click', function() {
                if (currentIndex < currentImages.length - 1) {
                    currentIndex++;
                    updateFullscreenImage();
                }
            });

            // Update fullscreen image
            function updateFullscreenImage() {
                const imageUrl = `{{ asset('storage') }}/${currentImages[currentIndex]}`;
                $('#fullscreenImage').attr('src', imageUrl);
                $('#fullscreenImageTitle').text(`Photo ${currentIndex + 1} of ${currentImages.length}`);
                $('#imageCounter').text(`${currentIndex + 1} / ${currentImages.length}`);
                
                // Update button states
                $('#prevImageBtn').prop('disabled', currentIndex === 0);
                $('#nextImageBtn').prop('disabled', currentIndex === currentImages.length - 1);
            }

            // Keyboard navigation for fullscreen modal
            $(document).on('keydown', function(e) {
                if ($('#fullscreenImageModal').is(':visible')) {
                    if (e.key === 'ArrowLeft') {
                        $('#prevImageBtn').click();
                    } else if (e.key === 'ArrowRight') {
                        $('#nextImageBtn').click();
                    } else if (e.key === 'Escape') {
                        $('#fullscreenImageModal').modal('hide');
                    }
                }
            });

            // Modal close event
            $('#galleryModal').on('hidden.bs.modal', function() {
                $('#galleryModalLabel').text('Loading Gallery...');
                $('#modalLoadingSpinner').show();
                $('#galleryContent').hide();
            });

            // Fullscreen modal close event
            $('#fullscreenImageModal').on('hidden.bs.modal', function() {
                $('#fullscreenImage').attr('src', '');
            });
        });
    </script>
@endsection