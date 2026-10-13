@if(auth()->check() && \Illuminate\Support\Facades\Route::has(auth()->user()->role.'.messages.index'))
<li class="side-nav-item {{ request()->routeIs(auth()->user()->role.'.messages.*') ? 'active' : '' }}">
    <a href="{{ route(auth()->user()->role.'.messages.index') }}" class="side-nav-link">
        <span class="menu-icon"><i class="ti ti-messages" aria-hidden="true"></i></span>
        <span class="menu-text">Messages</span>
        <span class="badge text-bg-secondary ms-auto d-none" data-chat-unread="{{ route(auth()->user()->role.'.messages.unread', [], false) }}" aria-live="polite"></span>
    </a>
</li>
@endif
