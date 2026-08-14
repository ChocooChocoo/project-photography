<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientFavoriteModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoritesController extends Controller
{
    /**
     * Toggle a studio in the authenticated client's favorites.
     */
    public function toggle(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isClient()) {
            return response()->json([
                'success' => false,
                'message' => 'Client privileges required.',
            ], 403);
        }

        $request->validate([
            'studio_id' => [
                'required',
                'integer',
                Rule::exists('tbl_studios', 'id')->where(function ($query) {
                    $query->whereIn('status', ['verified', 'active']);
                }),
            ],
        ]);

        $favorite = ClientFavoriteModel::where('client_id', $user->id)
            ->where('studio_id', $request->studio_id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $favorited = false;
        } else {
            ClientFavoriteModel::create([
                'client_id' => $user->id,
                'studio_id' => $request->studio_id,
            ]);
            $favorited = true;
        }

        return response()->json([
            'success' => true,
            'favorited' => $favorited,
        ]);
    }
}
