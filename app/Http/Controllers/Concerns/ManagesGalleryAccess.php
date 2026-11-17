<?php

namespace App\Http\Controllers\Concerns;

use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use Illuminate\Support\Facades\Auth;

trait ManagesGalleryAccess
{
    /**
     * The studio owner and photographers assigned to the shoot manage a
     * studio gallery; a freelancer manages their own gallery.
     */
    protected function canManage($gallery, string $type): bool
    {
        $userId = (int) Auth::id();

        if ($type === 'freelancer') {
            return (int) $gallery->freelancer_id === $userId;
        }

        return (int) $gallery->studio?->user_id === $userId
            || BookingAssignedPhotographerModel::where('booking_id', $gallery->booking_id)
                ->where('photographer_id', $userId)
                ->whereIn('status', ['on_site', 'in_progress', 'completed'])
                ->exists();
    }
}
