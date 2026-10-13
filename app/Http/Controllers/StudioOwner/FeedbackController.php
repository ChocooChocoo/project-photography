<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\PhotographerRatingModel;
use App\Models\PhotoRatingModel;
use App\Models\ServiceRatingModel;
use App\Models\StudioRatingModel;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'owner', 403);
        $reviews = collect();
        foreach (['studio' => StudioRatingModel::class, 'service' => ServiceRatingModel::class, 'photographer' => PhotographerRatingModel::class, 'photo' => PhotoRatingModel::class] as $type => $model) {
            $items = $model::whereHas('booking', fn ($query) => $query->where('booking_type', 'studio')->whereIn('provider_id', \App\Models\StudioOwner\StudiosModel::where('user_id', $request->user()->id)->select('id')))
                ->with('booking.client')->latest()->get();
            foreach ($items as $review) {
                $reviews->push(['type' => $type, 'review' => $review]);
            }
        }
        $reviews = $reviews->sortByDesc(fn ($item) => $item['review']->created_at)->values();

        return view('owner.view-feedback', compact('reviews'));
    }
}
