@extends('layouts.owner.app')
@section('title', 'Portfolio Gallery')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header card-title">
                            <h4 class="card-title">Upload Portfolio Photos</h4>
                            <small class="text-muted">Portfolio photos are visible to clients even before your first booking on this platform.</small>
                        </div>
                        <div class="card-body">
                            <form id="portfolioUploadForm" enctype="multipart/form-data">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Studio</label>
                                        <select class="form-select" name="studio_id" id="studio_id" required>
                                            <option value="">Select Studio</option>
                                            @foreach($studios as $studio)
                                                <option value="{{ $studio->id }}">{{ $studio->studio_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Gallery Name</label>
                                        <input type="text" class="form-control" name="gallery_name" placeholder="e.g. Wedding Highlights" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea class="form-control" name="description" rows="2" placeholder="Optional"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Images</label>
                                        <input type="file" class="form-control" name="images[]" id="portfolioImages" accept=".jpg,.jpeg,.png" multiple required>
                                        <div class="form-text">JPG/PNG, max 5MB each.</div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3" id="portfolioSubmitBtn">
                                    <span id="portfolioSubmitText">Upload Portfolio</span>
                                    <span id="portfolioSpinner" class="spinner-border spinner-border-sm d-none"></span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header card-title">
                            <h4 class="card-title">Portfolio Galleries</h4>
                        </div>
                        <div class="card-body">
                            @if($portfolioGalleries->isEmpty())
                                <div class="text-center py-4 text-muted">
                                    <i class="ti ti-photo-off fs-1 mb-2 d-block"></i>
                                    No portfolio galleries yet. Upload your first one above.
                                </div>
                            @else
                                <div class="row g-3">
                                    @foreach($portfolioGalleries as $gallery)
                                        <div class="col-md-4">
                                            <div class="card h-100">
                                                @if($gallery->thumbnail)
                                                    <img src="{{ asset('storage/' . $gallery->thumbnail) }}"
                                                         class="card-img-top" style="height: 180px; object-fit: cover;" alt="{{ $gallery->gallery_name }}">
                                                @else
                                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 180px;">
                                                        <i class="ti ti-photo-off fs-1 text-muted"></i>
                                                    </div>
                                                @endif
                                                <div class="card-body">
                                                    <h6 class="card-title text-truncate">{{ $gallery->gallery_name }}</h6>
                                                    <p class="text-muted small mb-2">{{ $gallery->total_photos }} photo(s)</p>
                                                    <button class="btn btn-sm btn-outline-danger delete-portfolio-gallery-btn" data-id="{{ $gallery->id }}">
                                                        <i class="ti ti-trash me-1"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#portfolioUploadForm').submit(function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = $('#portfolioSubmitBtn');

                submitBtn.prop('disabled', true);
                $('#portfolioSubmitText').text('Uploading...');
                $('#portfolioSpinner').removeClass('d-none');

                $.ajax({
                    url: '{{ route("owner.online-gallery.portfolio.store") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Upload failed.' });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Upload failed.' });
                    },
                    complete: function() {
                        submitBtn.prop('disabled', false);
                        $('#portfolioSubmitText').text('Upload Portfolio');
                        $('#portfolioSpinner').addClass('d-none');
                    }
                });
            });

            $(document).on('click', '.delete-portfolio-gallery-btn', function() {
                const galleryId = $(this).data('id');

                Swal.fire({
                    title: 'Delete this gallery?',
                    text: 'This will permanently remove all images in this gallery.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ url('owner/online-gallery') }}/${galleryId}`,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function() {
                                location.reload();
                            },
                            error: function() {
                                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to delete gallery.' });
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection
