<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudioHRMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return $this->handleUnauthorized($request);
        }
        
        // Check if user is studio-hr
        $user = Auth::user();
        if (!$user->isStudioHr()) {
            return $this->handleForbidden($request, $user);
        }
        
        // Get the response
        $response = $next($request);
        
        // Add cache control headers
        return $response->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                        ->header('Pragma', 'no-cache')
                        ->header('Expires', '0');
    }
    
    /**
     * Handle unauthorized access (not logged in)
     */
    private function handleUnauthorized(Request $request)
    {
        // For AJAX requests, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to access this page.',
                'redirect' => route('login')
            ], 401);
        }
        
        // For regular browser requests, redirect with flash message
        return redirect()->route('login')->with('error', 'Please login to access this page.');
    }
    
    /**
     * Handle forbidden access (wrong role)
     */
    private function handleForbidden(Request $request, $user)
    {
        // For AJAX requests, return JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. HR privileges required.',
                'redirect' => $this->getUserDashboard($user->role)
            ], 403);
        }
        
        // For regular browser requests, redirect with flash message
        return redirect($this->getUserDashboard($user->role))
            ->with('error', 'Access denied. HR privileges required.');
    }
    
    /**
     * Get user's dashboard route based on role
     */
    private function getUserDashboard($role): string
    {
        $routes = [
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'freelancer' => 'freelancer.dashboard',
            'client' => 'client.dashboard',
            'studio-photographer' => 'studio-photographer.dashboard',
            'studio-hr' => 'studio-hr.dashboard'
        ];
        
        return route($routes[$role] ?? 'login');
    }
}