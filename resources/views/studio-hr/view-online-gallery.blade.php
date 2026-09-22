@extends('layouts.studio-hr.app')
@section('title', $pageTitle ?? 'Online Gallery')

@section('styles')
    <style>
        .gallery-thumb {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 0.5rem;
        }

        .gallery-photo-grid img {
            width: 100%;
            height: 110px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">{{ $pageTitle ?? 'Online Gallery' }}</h5>
                            <span class="badge bg-soft-primary p-2">
                                <i class="ti ti-photo me-1"></i> {{ $bookings->count() }} booking(s)
                            </span>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Booking</th>
                                            <th>Client</th>
                                            <th>Event Date</th>
                                            <th>Gallery</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bookings as $booking)
                                            <tr>
                                                <td>
                                                    <span class="fw-semibold">{{ $booking->event_name }}</span>
                                                    <div class="text-muted small">{{ $booking->booking_reference }}</div>
                                                </td>
                                                <td>
                                                    {{ trim(($booking->client->first_name ?? '').' '.($booking->client->last_name ?? '')) ?: 'N/A' }}
                                                </td>
                                                <td>{{ $booking->formatted_event_date ?? 'N/A' }}</td>
                                                <td>
                                                    @if($booking->has_gallery)
                                                        @php($status = optional($booking->gallery)->gallery_status ?? 'draft')
                                                        @php($approval = optional($booking->gallery)->approval_status)
                                                        @if($status === 'published')
                                                            <span class="badge bg-success">Published</span>
                                                        @elseif($approval === 'pending')
                                                            <span class="badge bg-info">Pending Approval</span>
                                                        @elseif($approval === 'rejected')
                                                            <span class="badge bg-danger">Rejected</span>
                                                        @elseif($approval === 'approved')
                                                            <span class="badge bg-success">Approved</span>
                                                        @else
                                                            <span class="badge bg-warning">Draft</span>
                                                        @endif
                                                    @else
                                                        <span class="badge bg-secondary">Not started</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <button type="button"
                                                            class="btn btn-sm btn-primary js-gallery-manage"
                                                            data-booking-id="{{ $booking->id }}"
                                                            data-booking-name="{{ $booking->event_name }}">
                                                        <i class="ti ti-photo-edit me-1"></i> Manage
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    No bookings with online gallery are available for your assigned studios.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Gallery Modal --}}
    <div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="galleryModalTitle">Manage Gallery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="galleryModalAlert"></div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Gallery Name</label>
                            <input type="text" class="form-control" id="galleryName">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Images</label>
                            <input type="file" class="form-control" id="galleryImages" accept="image/*" multiple>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="galleryDescription" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="fw-semibold mb-2">Photos (<span id="galleryPhotoCount">0</span>)</div>
                        <div class="row g-2 gallery-photo-grid" id="galleryPhotoGrid"></div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div>
                        <button type="button" class="btn btn-outline-danger" id="galleryDeleteBtn" disabled>
                            <i class="ti ti-trash me-1"></i> Delete Gallery
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-secondary" id="galleryPublishBtn" disabled>
                            <i class="ti ti-send me-1"></i> Publish
                        </button>
                        <button type="button" class="btn btn-primary" id="gallerySubmitBtn" disabled>
                            <i class="ti ti-check me-1"></i> Submit for approval
                        </button>
                        <button type="button" class="btn btn-primary" id="gallerySaveBtn">
                            <i class="ti ti-device-floppy me-1"></i> Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            // Read the token at request time. A retry must not reuse an old value.
            function csrfToken() {
                if (window.PlatinumSession && typeof window.PlatinumSession.token === 'function') {
                    return window.PlatinumSession.token();
                }

                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? (meta.getAttribute('content') || '') : '';
            }

            // Ask the server for a fresh token.
            function refreshToken() {
                if (window.PlatinumSession && typeof window.PlatinumSession.refresh === 'function') {
                    return window.PlatinumSession.refresh();
                }

                return Promise.resolve();
            }

            // Send one request. Retry it one time after a 419 response.
            async function send(url, options) {
                const settings = Object.assign({}, options);
                settings.headers = Object.assign({}, options && options.headers);

                let response = await fetch(url, settings);

                if (response.status === 419 && window.PlatinumSession) {
                    try {
                        await refreshToken();
                    } catch (error) {
                        return response;
                    }

                    settings.headers['X-CSRF-TOKEN'] = csrfToken();
                    response = await fetch(url, settings);
                }

                return response;
            }

            const modalEl = document.getElementById('galleryModal');
            const modal = new bootstrap.Modal(modalEl);
            const alertBox = document.getElementById('galleryModalAlert');
            const nameInput = document.getElementById('galleryName');
            const descriptionInput = document.getElementById('galleryDescription');
            const imagesInput = document.getElementById('galleryImages');
            const photoGrid = document.getElementById('galleryPhotoGrid');
            const photoCount = document.getElementById('galleryPhotoCount');
            const saveBtn = document.getElementById('gallerySaveBtn');
            const publishBtn = document.getElementById('galleryPublishBtn');
            const submitBtn = document.getElementById('gallerySubmitBtn');
            const deleteBtn = document.getElementById('galleryDeleteBtn');

            let currentBookingId = null;
            let currentGallery = null;

            const routes = {
                details: @json(route('studio-hr.online-gallery.details', ['bookingId' => '__ID__'])),
                upload: @json(route('studio-hr.online-gallery.upload', ['bookingId' => '__ID__'])),
                update: @json(route('studio-hr.online-gallery.update', ['galleryId' => '__ID__'])),
                submit: @json(route('studio-hr.online-gallery.submit', ['galleryId' => '__ID__'])),
                publish: @json(route('studio-hr.online-gallery.publish', ['galleryId' => '__ID__'])),
                delete: @json(route('studio-hr.online-gallery.delete', ['galleryId' => '__ID__'])),
                deleteImage: @json(route('studio-hr.online-gallery.delete-image', ['galleryId' => '__ID__'])),
            };

            function paintPhotoGrid(gallery) {
                const images = (gallery && gallery.images) ? gallery.images : [];
                photoGrid.innerHTML = '';
                photoCount.textContent = images.length;

                images.forEach(function (path) {
                    const col = document.createElement('div');
                    col.className = 'col-3';
                    const img = document.createElement('img');
                    img.src = '/storage/' + path;
                    img.alt = 'Gallery photo';
                    col.appendChild(img);
                    photoGrid.appendChild(col);
                });
            }

            function clearAlert() {
                alertBox.innerHTML = '';
            }

            function showAlert(message, type) {
                alertBox.innerHTML = '<div class="alert alert-' + type + ' py-2">' + message + '</div>';
            }

            function updateActionButtons() {
                const gallery = currentGallery;
                const approval = gallery ? (gallery.approval_status || null) : null;
                const isPublished = !!gallery && gallery.gallery_status === 'published';

                submitBtn.disabled = !gallery || isPublished || !(approval === null || approval === 'rejected');
                publishBtn.disabled = !gallery || isPublished || approval !== 'approved';
            }

            async function openGallery(bookingId, bookingName) {
                currentBookingId = bookingId;
                currentGallery = null;
                clearAlert();
                imagesInput.value = '';
                photoGrid.innerHTML = '';
                photoCount.textContent = '0';
                submitBtn.disabled = true;
                publishBtn.disabled = true;
                deleteBtn.disabled = true;
                document.getElementById('galleryModalTitle').textContent = 'Manage Gallery - ' + bookingName;

                modal.show();

                try {
                    const response = await send(routes.details.replace('__ID__', bookingId), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await response.json();

                    if (!data.success) {
                        showAlert(data.message || 'Unable to load gallery.', 'danger');
                        return;
                    }

                    currentGallery = data.gallery;
                    nameInput.value = currentGallery?.gallery_name || '';
                    descriptionInput.value = currentGallery?.description || '';
                    paintPhotoGrid(currentGallery);

                    const hasGallery = !!currentGallery;
                    updateActionButtons();
                    deleteBtn.disabled = !hasGallery;
                } catch (error) {
                    showAlert('Unable to load gallery.', 'danger');
                }
            }

            saveBtn.addEventListener('click', async function () {
                if (!currentBookingId) {
                    return;
                }

                clearAlert();
                const formData = new FormData();
                formData.append('gallery_name', nameInput.value);
                formData.append('description', descriptionInput.value);

                Array.from(imagesInput.files || []).forEach(function (file) {
                    formData.append('images[]', file);
                });

                const hasNewImages = (imagesInput.files || []).length > 0;

                try {
                    if (hasNewImages) {
                        const response = await send(routes.upload.replace('__ID__', currentBookingId), {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                            body: formData,
                        });
                        const data = await response.json();
                        if (!data.success) {
                            showAlert(data.message || 'Upload failed.', 'danger');
                            return;
                        }
                        currentGallery = data.gallery;
                        imagesInput.value = '';
                    }

                    if (currentGallery) {
                        const response = await send(routes.update.replace('__ID__', currentGallery.id), {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken(),
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                gallery_name: nameInput.value,
                                description: descriptionInput.value,
                                status: currentGallery.status || 'active',
                            }),
                        });
                        const data = await response.json();
                        if (!data.success) {
                            showAlert(data.message || 'Update failed.', 'danger');
                            return;
                        }
                        currentGallery = data.gallery;
                    }

                    paintPhotoGrid(currentGallery);
                    updateActionButtons();
                    deleteBtn.disabled = !currentGallery;
                    showAlert('Gallery saved successfully.', 'success');
                } catch (error) {
                    showAlert('Unable to save gallery.', 'danger');
                }
            });

            submitBtn.addEventListener('click', async function () {
                if (!currentGallery) {
                    return;
                }
                clearAlert();

                try {
                    const response = await send(routes.submit.replace('__ID__', currentGallery.id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (!data.success) {
                        showAlert(data.message || 'Submit failed.', 'danger');
                        return;
                    }
                    currentGallery = data.gallery;
                    updateActionButtons();
                    showAlert('Gallery submitted for owner approval.', 'success');
                } catch (error) {
                    showAlert('Unable to submit gallery.', 'danger');
                }
            });

            publishBtn.addEventListener('click', async function () {
                if (!currentGallery) {
                    return;
                }
                clearAlert();

                try {
                    const response = await send(routes.publish.replace('__ID__', currentGallery.id), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (!data.success) {
                        showAlert(data.message || 'Publish failed.', 'danger');
                        return;
                    }
                    currentGallery = data.gallery;
                    updateActionButtons();
                    showAlert(data.message || 'Gallery published.', 'success');
                } catch (error) {
                    showAlert('Unable to publish gallery.', 'danger');
                }
            });

            deleteBtn.addEventListener('click', async function () {
                if (!currentGallery || !window.confirm('Delete this gallery and all its photos?')) {
                    return;
                }
                clearAlert();

                try {
                    const response = await send(routes.delete.replace('__ID__', currentGallery.id), {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    });
                    const data = await response.json();
                    if (!data.success) {
                        showAlert(data.message || 'Delete failed.', 'danger');
                        return;
                    }
                    currentGallery = null;
                    paintPhotoGrid(null);
                    updateActionButtons();
                    deleteBtn.disabled = true;
                    showAlert('Gallery deleted successfully.', 'success');
                } catch (error) {
                    showAlert('Unable to delete gallery.', 'danger');
                }
            });

            document.querySelectorAll('.js-gallery-manage').forEach(function (button) {
                button.addEventListener('click', function () {
                    openGallery(button.dataset.bookingId, button.dataset.bookingName);
                });
            });
        })();
    </script>
@endsection
