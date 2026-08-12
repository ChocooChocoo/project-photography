<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudioPhotographerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthorized($request);
        }

        $user = Auth::user();
        if ($user->role !== 'studio-photographer') {
            return $this->handleForbidden($request, $user);
        }

        $response = $next($request);

        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }

    private function handleUnauthorized(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to access this page.',
                'redirect' => route('login')
            ], 401);
        }

        return redirect()->route('login')->with('error', 'Please login to access this page.');
    }

    private function handleForbidden(Request $request, $user)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Studio photographer privileges required.',
                'redirect' => $this->getUserDashboard($user->role)
            ], 403);
        }

        return redirect($this->getUserDashboard($user->role))
            ->with('error', 'Access denied. Studio photographer privileges required.');
    }

    private function getUserDashboard($role): string
    {
        $routes = [
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'freelancer' => 'freelancer.dashboard',
            'client' => 'client.dashboard',
            'studio-hr' => 'studio-hr.dashboard',
            'studio-finance' => 'studio-finance.dashboard'
        ];

        return route($routes[$role] ?? 'login');
    }
}
