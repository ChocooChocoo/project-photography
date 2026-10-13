<?php

namespace App\Http\Middleware;

use App\Models\StudioOwner\PermissionModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthorized($request);
        }

        $user = Auth::user();
        if ($request->routeIs('freelancer.studio-assignments.*') && $request->route('id')) {
            $assignment = \App\Models\StudioOwner\BookingAssignedPhotographerModel::find($request->route('id'));
            $allowed = $assignment && collect($permissions)->contains(function ($permission) use ($user, $assignment) {
                $action = str($permission)->afterLast('.')->toString();
                return app(\App\Services\FreelancerBookingAccess::class)->allows($user, $assignment, $action);
            });
            abort_unless($allowed, 403, 'Temporary booking access is unavailable or has ended.');
            return $next($request);
        }
        $studioId = $this->resolveStudioId($request);
        // Normalize the required permission. UserModel::hasPermission normalizes
        // the granted permission with the same helper before the compare.
        $resolvedPermissions = collect($permissions)
            ->flatMap(fn (string $permission) => array_filter(array_map('trim', explode(',', $permission))))
            ->map(fn (string $permission) => PermissionModel::normalizeIdentifier($permission))
            ->filter()
            ->values();

        if ($resolvedPermissions->isEmpty()) {
            return $this->handleForbidden($request, $user);
        }

        $hasAnyPermission = $resolvedPermissions->contains(
            fn (string $permission) => $user->hasPermission($permission, $studioId)
        );

        if (!$hasAnyPermission) {
            return $this->handleForbidden($request, $user);
        }

        return $next($request);
    }

    /**
     * Resolve the relevant studio ID from the request.
     */
    private function resolveStudioId(Request $request): ?int
    {
        foreach (['studio_id', 'studioId'] as $key) {
            $value = $request->route($key) ?? $request->input($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * Handle unauthenticated requests.
     */
    private function handleUnauthorized(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to access this page.',
                'redirect' => route('login'),
            ], 401);
        }

        return redirect()->route('login')->with('error', 'Please login to access this page.');
    }

    /**
     * Handle forbidden requests.
     */
    private function handleForbidden(Request $request, $user)
    {
        $redirect = $this->getUserDashboard($user->role);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this page.',
                'redirect' => $redirect,
            ], 403);
        }

        return redirect($redirect)->with('error', 'You do not have permission to access this page.');
    }

    /**
     * Resolve dashboard route by portal role.
     */
    private function getUserDashboard(string $role): string
    {
        $routes = [
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'freelancer' => 'freelancer.dashboard',
            'client' => 'client.dashboard',
            'studio-photographer' => 'studio-photographer.dashboard',
            'studio-hr' => 'studio-hr.dashboard',
            'studio-finance' => 'studio-finance.dashboard',
        ];

        return route($routes[$role] ?? 'login');
    }
}
