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

    /**
     * Tutorial sections. The first explains how the platform works for
     * everyone; the rest explain each tool: what it is, why it matters, and
     * where to find it ([label, route name]).
     *
     * @return array<int, array{title: string, what: string, why: string, where: array{0: string, 1: string}|null}>
     */
    private function steps(string $role): array
    {
        $overview = [
            'title' => 'How Platinum works',
            'what' => 'Platinum connects clients with photography studios and freelance photographers. Studios have owners and staff; freelancers work on their own and can also join studios.',
            'why' => 'Every booking follows the same path, so everyone knows what happens next and who is responsible.',
            'where' => null,
            'lifecycle' => true,
        ];

        return [$overview, ...match ($role) {
            'client' => [
                ['title' => 'Set your budget', 'what' => 'Your budget is the amount you plan to spend on each kind of photography.', 'why' => 'Platinum uses it to show how well each package fits, so you compare studios by price at a glance. Prices are in pesos (₱), and discounted packages show the price you pay.', 'where' => ['Open Budget', 'client.budget.index']],
                ['title' => 'Book your session', 'what' => 'Pick a studio or freelancer, look at their portfolio, packages, and cameras, then choose a date and time.', 'why' => 'Fixed-length packages set the end time for you. For a shoot at your venue, you drop a pin on the map so your photographer can check in when they arrive.', 'where' => ['Browse providers', 'client.dashboard']],
                ['title' => 'Find your photos', 'what' => 'When your photos are ready, the provider publishes them to your online gallery.', 'why' => 'You can download them, and where the photographer added the original, drag the slider to compare the photo before and after editing.', 'where' => ['Open Online Gallery', 'client.online-gallery.index']],
                ['title' => 'Message your booking team', 'what' => 'Messages is a private conversation with the people working on your booking.', 'why' => 'You get updates in one place: arrival alerts, replacement photographers, and delivery news also arrive in your notifications and email.', 'where' => ['Open My Bookings', 'client.my-bookings.index']],
            ],
            'owner' => [
                ['title' => 'Add your services', 'what' => 'Your studio profile and services tell clients what you shoot.', 'why' => 'Clients only find verified studios with an active subscription, so finish your permit and plan first.', 'where' => ['Open Services', 'owner.services.index']],
                ['title' => 'Build your packages', 'what' => 'A package is a priced offer: hours, inclusions, edited photos, and an optional discount.', 'why' => 'Each package can have its own discount. Fixed-length packages set the end time for clients; flexible ones may run past closing until midnight.', 'where' => ['Open Packages', 'owner.packages.index']],
                ['title' => 'Add your staff', 'what' => 'Staff accounts are your photographers, HR, and finance team. You can also list your cameras and their specs.', 'why' => 'Clients see your cameras before booking, and you can only assign photographers who belong to your studio.', 'where' => ['Open Photographers', 'owner.studio-photographers.index']],
                ['title' => 'Set staff permissions', 'what' => 'Roles decide what each staff member can see and change.', 'why' => 'Give people only the access their work needs. This protects client data and payments.', 'where' => ['Open Roles', 'owner.role.index']],
                ['title' => 'Manage your bookings', 'what' => 'Bookings is where you assign photographers, follow payments, and approve photo delivery.', 'why' => 'Photographers check in with GPS when they arrive, and you get an alert. If one has to leave mid-shoot, the booking is flagged so you can assign a replacement.', 'where' => ['Open Bookings', 'owner.booking.index']],
            ],
            default => [
                ['title' => 'Add your services', 'what' => 'Your profile and services show clients who you are and what you shoot.', 'why' => 'Working with other freelancers? Add a shared group name on your profile so clients see you listed together.', 'where' => ['Open My Profile', 'freelancer.profile.index']],
                ['title' => 'Build your packages', 'what' => 'A package is a priced offer with hours, inclusions, and an optional discount.', 'why' => 'Clear packages and an up-to-date schedule let clients book you without back-and-forth.', 'where' => ['Open Packages', 'freelancer.packages.index']],
                ['title' => 'Manage your bookings', 'what' => 'You get your own direct bookings, and studio assignments if a studio adds you as a member.', 'why' => 'For studio work you check in with GPS when you arrive. If you cannot finish, cancel with a reason and the studio sends a replacement.', 'where' => ['Open My Bookings', 'freelancer.booking.index']],
                ['title' => 'Deliver your photos', 'what' => 'The online gallery is where you upload finished photos for the client.', 'why' => 'Add the unedited original to a photo and the client can compare before and after with a slider.', 'where' => ['Open Online Gallery', 'freelancer.online-gallery.index']],
            ],
        }];
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
