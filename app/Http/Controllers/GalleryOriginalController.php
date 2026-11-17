<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesGalleryAccess;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use Illuminate\Http\Request;

/**
 * Uploads the unedited original of a delivered photo. The client then sees
 * a before-and-after slider for that photo.
 */
class GalleryOriginalController extends Controller
{
    use ManagesGalleryAccess;

    public function store(Request $request, string $type, int $galleryId)
    {
        $request->validate([
            'image_path' => 'required|string|max:500',
            'original' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $gallery = match ($type) {
            'studio' => StudioOnlineGalleryModel::with('studio')->findOrFail($galleryId),
            'freelancer' => FreelanceOnlineGalleryModel::findOrFail($galleryId),
            default => abort(404),
        };

        abort_unless($this->canManage($gallery, $type), 403, 'You cannot change this gallery.');

        if ($gallery->gallery_status === 'published') {
            return response()->json([
                'success' => false,
                'message' => 'This gallery is already published. Ask the client to request a revision before changing photos.',
            ], 422);
        }

        if (! in_array($request->image_path, $gallery->images ?? [], true)) {
            return response()->json(['success' => false, 'message' => 'That photo is not in this gallery.'], 422);
        }

        $gallery->attachOriginal($request->file('original'), $request->image_path);

        return response()->json([
            'success' => true,
            'message' => 'Original added. The client sees a before-and-after slider for this photo.',
            'raw_images' => $gallery->raw_images,
        ]);
    }
}
