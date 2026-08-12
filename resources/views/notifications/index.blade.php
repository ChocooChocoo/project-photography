@extends('layouts.shared.app')
@section('title', 'Notifications')

{{-- CONTENT --}}
@section('content')
    <div class="content-page">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title mb-0">Notifications</h4>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.mark-all-read') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-soft-primary">
                                        <i class="ti ti-check me-1"></i>Mark all read
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="card-body p-0">
                            @forelse($notifications as $notification)
                                <div class="d-flex align-items-start gap-3 border-bottom px-3 py-3 {{ $notification->is_unread ? 'bg-light' : '' }}">
                                    <div class="avatar-sm rounded-circle bg-soft-{{ $notification->color ?: 'primary' }} d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="ti ti-{{ $notification->icon ?: 'bell' }} text-{{ $notification->color ?: 'primary' }}"></i>
                                    </div>

                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h6 class="mb-1">{{ $notification->title }}</h6>
                                            <small class="text-muted flex-shrink-0 ms-2">{{ $notification->time_ago }}</small>
                                        </div>
                                        <p class="text-muted mb-0">{{ $notification->message }}</p>
                                    </div>

                                    <div class="flex-shrink-0 d-flex gap-1">
                                        @if($notification->is_unread)
                                            <form method="POST" action="{{ route('notifications.mark-read', $notification->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-soft-primary" title="Mark as read">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-soft-danger" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 text-muted">
                                    <i class="ti ti-bell-off fs-3 mb-2 d-block"></i>
                                    <span>No notifications yet.</span>
                                </div>
                            @endforelse
                        </div>

                        @if($notifications->hasPages())
                            <div class="card-footer">
                                {{ $notifications->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
