<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict access to studio finance users only.
 */
class StudioFinanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $this->handleUnauthorized($request);
        }

        $user = Auth::user();

        if (!$user->isStudioFinance()) {
            return $this->handleForbidden($request, $user);
        }

        $response = $next($request);

        // headers->add() works for every response type; ->header() is missing on
        // streamed and file downloads, which crashed Dashboard Export.
        $response->headers->add([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);

        return $response;
    }

    /**
     * Handle unauthenticated requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
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
     * Handle requests from users without finance access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  object  $user
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    private function handleForbidden(Request $request, $user)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Finance privileges required.',
                'redirect' => $this->getUserDashboard($user->role),
            ], 403);
        }

        return redirect($this->getUserDashboard($user->role))
            ->with('error', 'Access denied. Finance privileges required.');
    }

    /**
     * Resolve the dashboard route for the authenticated user role.
     *
     * @param  string  $role
     * @return string
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
