<?php

namespace App\Http\Middleware;

use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\ServicesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnforceStudioSubscriptionAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $capability = 'manage'): Response
    {
        $routeName = (string) $request->route()?->getName();
        $user = Auth::user();
        $this->sharePortalSubscriptionState($user);

        // A role row has no studio column, so a role write cannot name one
        // studio. Keep role, permission, and user role writes exempt or the
        // gate sends a multi studio owner to a 403 on save.
        if ($request->input('type') === 'freelancer'
            || str_starts_with($routeName, 'owner.subscription.')
            || str_starts_with($routeName, 'owner.role.')
            || str_starts_with($routeName, 'owner.permission.')
            || str_starts_with($routeName, 'owner.user-roles.')
            || $routeName === 'owner.profile') {
            return $next($request);
        }

        if (in_array($routeName, ['owner.studio.create', 'owner.studio.store'], true)
            && $user
            && StudiosModel::where('user_id', $user->id)->doesntExist()) {
            return $next($request);
        }

        $fulfillmentRoutes = [
            'owner.booking.assign.photographers',
            'owner.booking.remove.assignment',
            'owner.booking.update.assignment.status',
            'owner.booking.update.status',
            'owner.booking.complete',
            'owner.online-gallery.upload',
            'owner.online-gallery.delete-image',
            'owner.online-gallery.delete',
            'owner.online-gallery.update',
            'owner.online-gallery.publish',
            'studio-photographer.online-gallery.upload',
        ];

        if ($capability === 'fulfill-paid-booking' || in_array($routeName, $fulfillmentRoutes, true)) {
            $booking = $this->resolveBooking($request);

            if ($booking
                && $booking->booking_type === 'studio'
                && $booking->payments()->where('status', 'succeeded')->exists()) {
                return $next($request);
            }
        }

        if (in_array($request->method(), ['GET', 'HEAD'], true)
            && ! preg_match('/(?:\.create|\.edit|\.invite|\.apply|setup)/', $routeName)) {
            return $next($request);
        }

        $studioId = $this->resolveStudioId($request);
        $hasAccess = $studioId !== null
            && StudiosModel::whereKey($studioId)->subscriptionAccessible()->exists();

        if ($hasAccess) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'An active subscription or grace period is required for this studio.',
                'redirect' => $this->redirectForUser($user),
            ], 403);
        }

        return redirect($this->redirectForUser($user))
            ->with('error', 'An active subscription or grace period is required for this studio.');
    }

    private function resolveBooking(Request $request): ?BookingModel
    {
        $bookingId = $request->route('bookingId') ?? $request->input('booking_id');

        if (! is_numeric($bookingId) && (str_contains((string) $request->route()?->getName(), 'booking') || str_contains($request->path(), 'booking'))) {
            $bookingId = $request->route('id');
        }

        if (! is_numeric($bookingId) && $request->route('galleryId')) {
            $bookingId = StudioOnlineGalleryModel::find($request->route('galleryId'))?->booking_id;
        }

        if (! is_numeric($bookingId) && $request->route('id') && str_contains((string) $request->route()?->getName(), 'assignment')) {
            $bookingId = BookingAssignedPhotographerModel::find($request->route('id'))?->booking_id;
        }

        return is_numeric($bookingId) ? BookingModel::find((int) $bookingId) : null;
    }

    private function resolveStudioId(Request $request): ?int
    {
        foreach (['studio_id', 'studioId', 'provider_id'] as $key) {
            $value = $request->route($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        $booking = $this->resolveBooking($request);
        if ($booking?->booking_type === 'studio') {
            return (int) $booking->provider_id;
        }

        $routeName = (string) $request->route()?->getName();
        if (str_starts_with($routeName, 'owner.studio.') && is_numeric($request->route('id'))) {
            return (int) $request->route('id');
        }

        $package = $request->route('package') ?? $request->input('package_id');
        if (($request->input('type') === 'studio' || str_contains($routeName, 'package')) && $package) {
            $package = $package instanceof PackagesModel ? $package : PackagesModel::find($package);

            if ($package) {
                return (int) $package->studio_id;
            }
        }

        if (str_contains($routeName, 'services') && is_numeric($request->route('id'))) {
            $service = ServicesModel::find($request->route('id'));

            if ($service) {
                return (int) $service->studio_id;
            }
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $studioIds = in_array($user->role, ['owner', 'owner-super-admin'], true)
            ? StudiosModel::where('user_id', $user->id)->pluck('id')
            : (method_exists($user, 'getAssignedStudioIds') ? $user->getAssignedStudioIds($user->role) : collect());

        return $studioIds->count() === 1 ? (int) $studioIds->first() : null;
    }

    private function redirectForUser($user): string
    {
        return match ($user?->role) {
            'owner', 'owner-super-admin' => route('owner.subscription.index'),
            'studio-photographer' => route('studio-photographer.dashboard'),
            'studio-hr' => route('studio-hr.dashboard'),
            'studio-finance' => route('studio-finance.dashboard'),
            default => route('client.dashboard'),
        };
    }

    private function sharePortalSubscriptionState($user): void
    {
        if (! $user || ! in_array($user->role, ['owner', 'owner-super-admin', 'studio-photographer', 'studio-hr', 'studio-finance'], true)) {
            return;
        }

        if (in_array($user->role, ['owner', 'owner-super-admin'], true)) {
            $studioIds = StudiosModel::where('user_id', $user->id)->pluck('id');
        } else {
            $studioIds = method_exists($user, 'getAssignedStudioIds')
                ? $user->getAssignedStudioIds($user->role)
                : collect();

            if ($user->role === 'studio-photographer' && $studioIds->isEmpty()) {
                $studioId = $user->studioPhotographerProfile?->studio_id;
                $studioIds = $studioId ? collect([$studioId]) : collect();
            }
        }

        $studios = StudiosModel::with(['currentAccessSubscription', 'latestSubscription'])
            ->whereIn('id', $studioIds)
            ->get();

        View::share('subscriptionAccessStudios', $studios);
        View::share('hasStudioSubscriptionAccess', $studios->contains(
            fn (StudiosModel $studio) => $studio->currentAccessSubscription?->hasAccess()
        ));
    }
}
