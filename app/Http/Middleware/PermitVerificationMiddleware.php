<?php

namespace App\Http\Middleware;

use App\Models\StudioOwner\StudiosModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermitVerificationMiddleware
{
    /**
     * Gate the owner portal until the owner has at least one studio with a
     * verified permit. Owners with no studio, or with at least one usable
     * studio (status verified/active and permit not expired), pass through.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        $userId = $user->getAuthIdentifier();

        $hasAnyStudio = StudiosModel::where('user_id', $userId)->exists();

        $hasUsableStudio = StudiosModel::where('user_id', $userId)
            ->whereIn('status', ['verified', 'active'])
            ->where(function ($query) {
                $query->whereNull('permit_expiry_date')
                    ->orWhereDate('permit_expiry_date', '>=', today());
            })
            ->exists();

        if (!$hasAnyStudio || $hasUsableStudio) {
            return $next($request);
        }

        return redirect()->route('owner.studio.permit.notice');
    }
}
