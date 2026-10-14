<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Show the onboarding flow or redirect completed users to their dashboard.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        if (! in_array($user->role, ['client', 'owner', 'freelancer'], true)
            || ($user->onboarding_completed_at && ! $request->boolean('replay'))) {
            return redirect()->route($this->dashboardRoute($user->role));
        }

        return view('onboarding', ['steps' => $this->steps($user->role)]);
    }

    /**
     * Mark onboarding as completed and send the user to their dashboard.
     */
    public function complete(Request $request)
    {
        $user = $request->user();
        if (in_array($user->role, ['client', 'owner', 'freelancer'], true) && ! $user->onboarding_completed_at) {
            $user->update(['onboarding_completed_at' => now()]);
        }

        return redirect()->route($this->dashboardRoute($user->role));
    }

    private function steps(string $role): array
    {
        return match ($role) {
            'client' => [
                ['Set your budget', 'Open Budget to set your spending limit for each service category. Compare studio packages before choosing a session.'],
                ['Book your session', 'Browse studios on your dashboard. View their portfolio and packages, then choose a date and time. Review the terms and payment details before confirming.'],
                ['Find your photos', 'Open Online Gallery after your booking team publishes your photos. My Bookings shows preparation, delivery and feedback actions.'],
                ['Message your booking team', 'Use Message from your booking details to contact eligible team members. Your conversations are available in Messages.'],
            ],
            'owner' => [
                ['Add your services', 'Create your studio and complete its permit and subscription requirements. Add the services your studio offers before building packages.'],
                ['Build your packages', 'Set package prices, inclusions and booking terms. Keep your schedule current so clients can choose available sessions.'],
                ['Add your staff', 'Add staff accounts and assign photographers to your studio. Invited staff must change their initial password before using their portal.'],
                ['Set staff permissions', 'Use Roles and Permissions to control what each staff role can view and manage. Give staff only the access their work requires.'],
                ['Manage your bookings', 'Review bookings, assign photographers and track payments. Approve photo delivery before clients receive it, and use Messages for booking communication.'],
            ],
            default => [
                ['Add your services', 'Complete your freelancer profile and list the photography services you offer. Add portfolio images so clients can see your work.'],
                ['Build your packages', 'Set prices and package inclusions, then update your schedule so clients can choose available sessions.'],
                ['Manage your bookings', 'Review your direct bookings and any studio assignments. My Bookings and Studio Assignments keep these workflows separate.'],
                ['Deliver your photos', 'Use Online Gallery for your direct booking deliveries. Use Messages to communicate with eligible participants in your bookings.'],
            ],
        };
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
