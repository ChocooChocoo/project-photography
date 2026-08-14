<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding flow or redirect completed users to their dashboard.
     */
    public function show()
    {
        if (auth()->user()->onboarding_completed_at) {
            return redirect()->route($this->dashboardRoute(auth()->user()->role));
        }

        return view('onboarding');
    }

    /**
     * Mark onboarding as completed and send the user to their dashboard.
     */
    public function complete(Request $request)
    {
        auth()->user()->update([
            'onboarding_completed_at' => now(),
        ]);

        return redirect()->route($this->dashboardRoute(auth()->user()->role));
    }

    /**
     * Map a role to its portal dashboard route.
     */
    private function dashboardRoute(string $role): string
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

        return $routes[$role] ?? 'client.dashboard';
    }
}
