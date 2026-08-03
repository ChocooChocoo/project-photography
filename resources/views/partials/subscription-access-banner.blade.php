@foreach(($subscriptionAccessStudios ?? collect()) as $accessStudio)
    @php($accessSubscription = $accessStudio->currentAccessSubscription ?? $accessStudio->latestSubscription)

    @if($accessSubscription?->isInGrace())
        <div class="container-fluid px-3 pt-3">
            <div class="alert alert-warning mb-0" role="alert">
                <strong>{{ $accessStudio->studio_name }} is in its subscription grace period.</strong>
                {{ $accessSubscription->graceDaysRemaining() }} day(s) remain, until
                {{ $accessSubscription->graceDeadline()->format('M d, Y g:i A') }}.
                @if(in_array(auth()->user()?->role, ['owner', 'owner-super-admin'], true))
                    <a href="{{ route('owner.subscription.index') }}" class="alert-link">Manage subscription</a>.
                @else
                    Contact the studio owner to prevent access restrictions.
                @endif
            </div>
        </div>
    @elseif(!$accessSubscription?->hasAccess())
        <div class="container-fluid px-3 pt-3">
            <div class="alert alert-danger mb-0" role="alert">
                <strong>{{ $accessStudio->studio_name }} has no active subscription.</strong>
                Commercial changes and new bookings are restricted.
                @if(in_array(auth()->user()?->role, ['owner', 'owner-super-admin'], true))
                    <a href="{{ route('owner.subscription.index') }}" class="alert-link">Subscribe to restore access</a>.
                @else
                    Contact the studio owner to restore access.
                @endif
            </div>
        </div>
    @endif
@endforeach
