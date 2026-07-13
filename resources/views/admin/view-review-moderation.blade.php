@extends('layouts.admin.app')
@section('title', 'Review Moderation')

@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div data-table data-table-rows-per-page="10" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h4 class="card-title">Review Moderation</h4>
                            <div class="btn-group">
                                <a href="{{ route('admin.reviews.index') }}" class="btn btn-sm {{ !$status ? 'btn-primary' : 'btn-outline-primary' }}">All</a>
                                <a href="{{ route('admin.reviews.index', ['status' => 'published']) }}" class="btn btn-sm {{ $status === 'published' ? 'btn-primary' : 'btn-outline-primary' }}">Published</a>
                                <a href="{{ route('admin.reviews.index', ['status' => 'flagged']) }}" class="btn btn-sm {{ $status === 'flagged' ? 'btn-primary' : 'btn-outline-primary' }}">Flagged</a>
                                <a href="{{ route('admin.reviews.index', ['status' => 'removed']) }}" class="btn btn-sm {{ $status === 'removed' ? 'btn-primary' : 'btn-outline-primary' }}">Removed</a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover table-bordered w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th data-table-sort>Provider</th>
                                        <th data-table-sort>Client</th>
                                        <th data-table-sort>Rating</th>
                                        <th>Review</th>
                                        <th data-table-sort>Status</th>
                                        <th data-table-sort>Date</th>
                                        <th class="text-center" style="width: 1%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($reviews as $review)
                                    <tr data-review-type="{{ $review['type'] }}" data-review-id="{{ $review['id'] }}">
                                        <td>{{ $review['provider_name'] ?: 'N/A' }} <span class="badge badge-soft-secondary">{{ ucfirst($review['type']) }}</span></td>
                                        <td>{{ $review['client_name'] ?: 'N/A' }}</td>
                                        <td>{{ $review['rating'] }} <i class="ti ti-star-filled text-warning"></i></td>
                                        <td style="max-width: 300px;" class="text-truncate">{{ $review['review_text'] }}</td>
                                        <td>
                                            @php
                                                $statusBadge = ['published' => 'badge-soft-success', 'flagged' => 'badge-soft-warning', 'removed' => 'badge-soft-danger'][$review['status']] ?? 'badge-soft-secondary';
                                            @endphp
                                            <span class="badge {{ $statusBadge }} fs-8 px-1 w-100 text-uppercase">{{ $review['status'] }}</span>
                                        </td>
                                        <td>{{ optional($review['created_at'])->format('M d, Y') }}</td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-1">
                                                @if($review['status'] !== 'flagged')
                                                <button class="btn btn-sm review-action-btn" data-action="flag" title="Flag">
                                                    <i class="ti ti-flag fs-lg text-warning"></i>
                                                </button>
                                                @endif
                                                @if($review['status'] !== 'removed')
                                                <button class="btn btn-sm review-action-btn" data-action="remove" title="Remove">
                                                    <i class="ti ti-trash fs-lg text-danger"></i>
                                                </button>
                                                @endif
                                                @if($review['status'] !== 'published')
                                                <button class="btn btn-sm review-action-btn" data-action="republish" title="Publish">
                                                    <i class="ti ti-check fs-lg text-success"></i>
                                                </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No reviews found.</td>
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
@endsection

@section('scripts')
    <script>
        $(document).on('click', '.review-action-btn', function() {
            const $row = $(this).closest('tr');
            const type = $row.data('review-type');
            const id = $row.data('review-id');
            const action = $(this).data('action');

            $.ajax({
                url: `/admin/reviews/${type}/${id}/${action}`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Action failed. Please try again.' });
                }
            });
        });
    </script>
@endsection
