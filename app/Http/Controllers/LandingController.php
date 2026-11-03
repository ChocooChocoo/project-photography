<?php

namespace App\Http\Controllers;

use App\Models\Freelancer\ProfileModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Support\Facades\Storage;

/**
 * Public landing page. Signed-in users go to their portal dashboard.
 * Every number and photo shown comes from real platform data; sections
 * with no data are hidden rather than filled with placeholders.
 */
class LandingController extends Controller
{
    private const DASHBOARDS = [
        'admin' => 'admin.dashboard',
        'owner' => 'owner.dashboard',
        'freelancer' => 'freelancer.dashboard',
        'client' => 'client.dashboard',
        'studio-photographer' => 'studio-photographer.dashboard',
        'studio-hr' => 'studio-hr.dashboard',
        'studio-finance' => 'studio-finance.dashboard',
    ];

    public function __invoke()
    {
        if ($user = auth()->user()) {
            return redirect()->route(self::DASHBOARDS[$user->role] ?? 'login');
        }

        // rescue(): the landing page must render even if a table is missing.
        $photos = rescue(fn () => $this->photos(), [], false);
        $studios = rescue(fn () => $this->featuredStudios(), collect(), false);
        $counts = rescue(fn () => [
            'studios' => StudiosModel::whereIn('status', ['approved', 'active', 'verified'])->subscriptionAccessible()->count(),
            'freelancers' => ProfileModel::count(),
        ], ['studios' => 0, 'freelancers' => 0], false);

        return view('landing', compact('photos', 'studios', 'counts'));
    }

    /**
     * Up to 8 real photos: active package covers, then studio portfolio
     * images. Only files that exist on the public disk.
     *
     * @return array<int, string> public-disk paths
     */
    private function photos(): array
    {
        $covers = PackagesModel::where('status', 'active')->whereNotNull('cover_images')
            ->latest()->limit(20)->pluck('cover_images')->flatten();
        $portfolio = StudioOnlineGalleryModel::where('gallery_type', 'portfolio')->where('status', 'active')
            ->latest()->limit(10)->pluck('images')->flatten();

        return $covers->merge($portfolio)
            ->filter(fn ($path) => is_string($path) && $path !== '' && Storage::disk('public')->exists($path))
            ->unique()->take(8)->values()->all();
    }

    private function featuredStudios()
    {
        return StudiosModel::whereIn('status', ['approved', 'active', 'verified'])
            ->subscriptionAccessible()
            ->with('location')
            ->orderByDesc('avg_rating')->orderByDesc('total_reviews')
            ->limit(3)->get();
    }
}
