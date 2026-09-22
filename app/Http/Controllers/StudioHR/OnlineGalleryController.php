<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Traits\Notifiable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Thin studio-HR gallery controller.
 *
 * The owner controller scopes galleries to studios the signed-in user owns,
 * which an HR employee never does. This one scopes to the studios the HR
 * account is assigned to through its RBAC roles, then reuses the same
 * StudioOnlineGalleryModel behaviour as the owner flow.
 */
class OnlineGalleryController extends Controller
{
    use Notifiable;

    /**
     * Header title shared by the gallery views.
     */
    private const PAGE_TITLE = 'Online Gallery';

    /**
     * Display in-progress or completed bookings with the online gallery add-on.
     */
    public function index()
    {
        $studioIds = $this->assignedStudioIds();
        $bookings = collect([]);

        if ($studioIds->isNotEmpty()) {
            $bookings = BookingModel::whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->whereIn('status', ['in_progress', 'completed'])
                ->whereHas('packages', function ($query) {
                    $query->where('package_type', 'studio')
                        ->whereHas('studioPackage', function ($packageQuery) {
                            $packageQuery->where('online_gallery', 1);
                        });
                })
                ->with([
                    'client:id,first_name,last_name,email',
                    'packages.studioPackage:id,package_name,online_gallery',
                    'category:id,category_name',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($bookings as $booking) {
                $booking->has_gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->exists();
                $booking->gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->first();
                $booking->formatted_event_date = $booking->event_date
                    ? \Carbon\Carbon::parse($booking->event_date)->format('M d, Y')
                    : null;
            }
        }

        return view('studio-hr.view-online-gallery', [
            'bookings' => $bookings,
            'pageTitle' => self::PAGE_TITLE,
        ]);
    }

    /**
     * Get gallery details for a booking inside the HR account's studios.
     */
    public function getGalleryDetails($bookingId)
    {
        try {
            $studioIds = $this->assignedStudioIds();

            if ($studioIds->isEmpty()) {
                return $this->errorResponse('No studio is assigned to your account.', 404);
            }

            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['client:id,first_name,last_name,email'])
                ->firstOrFail();

            $gallery = StudioOnlineGalleryModel::where('booking_id', $bookingId)->first();

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'event_name' => $booking->event_name,
                    'event_date' => $booking->event_date
                        ? \Carbon\Carbon::parse($booking->event_date)->format('M d, Y')
                        : null,
                    'client_name' => trim(($booking->client->first_name ?? '').' '.($booking->client->last_name ?? '')),
                    'client_email' => $booking->client->email ?? null,
                ],
                'gallery' => $gallery,
                'has_gallery' => $gallery ? true : false,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error fetching gallery details: '.$e->getMessage(), 500);
        }
    }

    /**
     * Upload images for a booking gallery inside the HR account's studios.
     */
    public function uploadImages(Request $request, $bookingId)
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1|max:50',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'gallery_name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            $studioIds = $this->assignedStudioIds();

            if ($studioIds->isEmpty()) {
                return $this->errorResponse('No studio is assigned to your account.', 404);
            }

            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->whereIn('status', ['in_progress', 'completed'])
                ->firstOrFail();

            $hasOnlineGallery = $booking->packages()
                ->where('package_type', 'studio')
                ->whereHas('studioPackage', function ($query) {
                    $query->where('online_gallery', 1);
                })->exists();

            if (!$hasOnlineGallery) {
                return $this->errorResponse('This booking does not include online gallery feature.', 400);
            }

            $gallery = StudioOnlineGalleryModel::where('booking_id', $bookingId)->first();

            if ($gallery && $gallery->gallery_status === 'published') {
                return $this->errorResponse('This gallery is already published to the client. Ask the client to request a revision before uploading new photos.', 400);
            }

            DB::beginTransaction();

            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $image->store('studio-online-galleries/'.$bookingId, 'public');
            }

            if ($gallery) {
                $allImages = array_merge($gallery->images ?? [], $uploadedImages);
                $gallery->update([
                    'images' => $allImages,
                    'total_photos' => count($allImages),
                    'gallery_name' => $request->gallery_name ?? $gallery->gallery_name,
                    'description' => $request->description ?? $gallery->description,
                ]);

                $message = count($uploadedImages).' image(s) added to gallery successfully.';
            } else {
                $gallery = StudioOnlineGalleryModel::create([
                    'booking_id' => $bookingId,
                    'studio_id' => $booking->provider_id,
                    'client_id' => $booking->client_id,
                    'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
                    'gallery_name' => $request->gallery_name ?? $booking->event_name.' Gallery',
                    'description' => $request->description,
                    'images' => $uploadedImages,
                    'total_photos' => count($uploadedImages),
                    'status' => 'active',
                    'gallery_status' => 'draft',
                ]);

                $message = 'Gallery created with '.count($uploadedImages).' image(s) successfully. Publish it to make it visible to the client.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'gallery' => $gallery,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Error uploading images: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete an image from a gallery inside the HR account's studios.
     */
    public function deleteImage(Request $request, $galleryId)
    {
        try {
            $request->validate([
                'image_path' => 'required|string',
            ]);

            $gallery = $this->findGalleryInScope($galleryId);

            if (! $gallery) {
                return $this->errorResponse('Gallery not found.', 404);
            }

            $images = $gallery->images ?? [];

            if (($key = array_search($request->image_path, $images)) !== false) {
                unset($images[$key]);
                Storage::disk('public')->delete($request->image_path);
                $images = array_values($images);

                $gallery->update([
                    'images' => $images,
                    'total_photos' => count($images),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully.',
                    'total_photos' => count($images),
                ]);
            }

            return $this->errorResponse('Image not found.', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting image: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete a whole gallery inside the HR account's studios.
     */
    public function deleteGallery($galleryId)
    {
        try {
            $gallery = $this->findGalleryInScope($galleryId);

            if (! $gallery) {
                return $this->errorResponse('Gallery not found.', 404);
            }

            foreach ($gallery->images ?? [] as $image) {
                Storage::disk('public')->delete($image);
            }

            $gallery->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gallery deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting gallery: '.$e->getMessage(), 500);
        }
    }

    /**
     * Update gallery info inside the HR account's studios.
     */
    public function updateGallery(Request $request, $galleryId)
    {
        try {
            $request->validate([
                'gallery_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive',
            ]);

            $gallery = $this->findGalleryInScope($galleryId);

            if (! $gallery) {
                return $this->errorResponse('Gallery not found.', 404);
            }

            $gallery->update([
                'gallery_name' => $request->gallery_name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gallery updated successfully.',
                'gallery' => $gallery,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error updating gallery: '.$e->getMessage(), 500);
        }
    }

    /**
     * Send a draft gallery to the studio owner for approval inside the HR account's studios.
     */
    public function submitForApproval($galleryId)
    {
        try {
            $gallery = $this->findGalleryInScope($galleryId);

            if (! $gallery) {
                return $this->errorResponse('Gallery not found.', 404);
            }

            if ($gallery->isPublished()) {
                return $this->errorResponse('This gallery is already published to the client.', 400);
            }

            $gallery->update([
                'approval_status' => StudioOnlineGalleryModel::APPROVAL_PENDING,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gallery submitted for owner approval.',
                'gallery' => $gallery,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error submitting gallery for approval: '.$e->getMessage(), 500);
        }
    }

    /**
     * Publish an approved gallery inside the HR account's studios.
     */
    public function publish($galleryId)
    {
        try {
            $gallery = $this->findGalleryInScope($galleryId);

            if (! $gallery) {
                return $this->errorResponse('Gallery not found.', 404);
            }

            if (! $gallery->canPublish()) {
                return $this->errorResponse('This gallery needs owner approval before you can publish it.', 403);
            }

            $gallery->update([
                'gallery_status' => StudioOnlineGalleryModel::GALLERY_STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            if ($gallery->client) {
                $this->notifyGalleryPublished($gallery, $gallery->client);
            }

            return response()->json([
                'success' => true,
                'message' => 'Gallery published to client successfully.',
                'gallery' => $gallery,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Error publishing gallery: '.$e->getMessage(), 500);
        }
    }

    /**
     * Studio IDs the signed-in HR account is assigned to.
     */
    private function assignedStudioIds()
    {
        return Auth::user()->getAssignedStudioIds('studio-hr');
    }

    /**
     * Load a gallery guarded to the HR account's assigned studios, or null.
     */
    private function findGalleryInScope($galleryId): ?StudioOnlineGalleryModel
    {
        $studioIds = $this->assignedStudioIds();

        if ($studioIds->isEmpty()) {
            return null;
        }

        return StudioOnlineGalleryModel::where('id', $galleryId)
            ->whereIn('studio_id', $studioIds)
            ->first();
    }

    /**
     * Build a JSON error response.
     */
    private function errorResponse(string $message, int $status)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
