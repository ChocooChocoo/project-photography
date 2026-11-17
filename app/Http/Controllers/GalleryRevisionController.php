<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesGalleryAccess;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\NotificationModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Replaces a photo the client flagged for revision. The gallery stays
 * published; when the last flagged photo is replaced, the client is told.
 */
class GalleryRevisionController extends Controller
{
    use ManagesGalleryAccess;

    public function replace(Request $request, string $type, int $galleryId): JsonResponse
    {
        $request->validate([
            'image_path' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $gallery = match ($type) {
            'studio' => StudioOnlineGalleryModel::with('studio')->findOrFail($galleryId),
            'freelancer' => FreelanceOnlineGalleryModel::findOrFail($galleryId),
            default => abort(404),
        };

        abort_unless($this->canManage($gallery, $type), 403, 'You cannot change this gallery.');

        $oldPath = $request->input('image_path');
        $images = $gallery->images ?? [];
        $flagged = $gallery->revision_photos ?? [];
        $index = array_search($oldPath, $images, true);

        if ($index === false || ! in_array($oldPath, $flagged, true)) {
            return response()->json([
                'success' => false,
                'message' => 'The client did not request a revision of that photo.',
            ], 422);
        }

        $newPath = $request->file('image')->store(dirname($oldPath), 'public');
        $images[$index] = $newPath;

        // Re-key the before-and-after pair first so the saving hook keeps the original.
        $pairs = $gallery->raw_images ?? [];
        if (array_key_exists($oldPath, $pairs)) {
            $pairs[$newPath] = $pairs[$oldPath];
            unset($pairs[$oldPath]);
        }

        $flagged = array_values(array_diff($flagged, [$oldPath]));
        $resolved = $flagged === [];

        $gallery->raw_images = $pairs ?: null;
        $gallery->images = $images;
        $gallery->revision_photos = $flagged ?: null;
        if ($resolved) {
            $gallery->revision_resolved_at = now();
        }
        $gallery->save();

        Storage::disk('public')->delete($oldPath);

        if ($resolved) {
            $booking = $gallery->booking;
            NotificationModel::create([
                'user_id' => $gallery->client_id ?? $booking?->client_id,
                'type' => 'gallery_revision_ready',
                'title' => 'Revised photos ready',
                'message' => 'The photos you asked to revise'.($booking ? ' for '.$booking->booking_reference : '').' have been replaced.',
                'data' => [
                    'booking_id' => $gallery->booking_id,
                    'gallery_id' => $gallery->id,
                    'route' => route('client.online-gallery.index', [], false),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $resolved ? 'Photo replaced. All requested revisions are done and the client was notified.' : 'Photo replaced.',
            'images' => $gallery->images,
            'image_path' => $newPath,
            'revision_photos' => $gallery->revision_photos ?? [],
        ]);
    }
}
