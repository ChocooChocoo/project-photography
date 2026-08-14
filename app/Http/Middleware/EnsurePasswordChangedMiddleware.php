<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePasswordChangedMiddleware
{
    /**
     * Redirect users flagged for a mandatory first-login password change.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowedRoutes = [
                'password.change',
                'password.change.store',
                'onboarding',
                'onboarding.complete',
                'auth.logout',
            ];

            if (!in_array($request->route()?->getName(), $allowedRoutes, true)) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }
}
