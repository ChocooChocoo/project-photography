@if(auth()->check() && \Illuminate\Support\Facades\Route::has(auth()->user()->role.'.messages.index'))
<div class="topbar-item">
    <a href="{{ route(auth()->user()->role.'.messages.index') }}" class="topbar-link position-relative d-inline-flex align-items-center justify-content-center" style="min-width: 44px; min-height: 44px;" aria-label="Messages"
        @if(request()->routeIs(auth()->user()->role.'.messages.*')) aria-current="page" @endif>
        <i class="ti ti-messages fs-xxl" aria-hidden="true"></i>
        <span class="badge text-bg-danger badge-circle topbar-badge d-none" data-chat-unread="{{ route(auth()->user()->role.'.messages.unread', [], false) }}" aria-live="polite"></span>
    </a>
</div>
@endif
