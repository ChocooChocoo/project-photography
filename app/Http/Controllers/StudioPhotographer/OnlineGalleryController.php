<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineGalleryController extends Controller
{
    /**
     * Display list of in-progress or completed bookings assigned to this photographer.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get all actively assigned bookings where this photographer can manage gallery delivery.
        $assignments = BookingAssignedPhotographerModel::where('photographer_id', $userId)
            ->whereIn('status', ['on_site', 'in_progress', 'completed'])
            ->with([
                'booking.client:id,first_name,last_name,email',
                'booking.category:id,category_name',
                'booking.packages.studioPackage',
                'booking.studioOnlineGallery',
                'studio:id,studio_name'
            ])
            ->get();

        // Extract bookings from assignments and add gallery info
        $bookings = collect();
        
        foreach ($assignments as $assignment) {
            $booking = $assignment->booking;
            
            if ($booking && $booking->booking_type === 'studio' && $booking->requiresOnlineGalleryUpload()) {
                $booking->has_gallery = (bool) $booking->studioOnlineGallery;
                $booking->gallery = $booking->studioOnlineGallery;
                $booking->formatted_event_date = \Carbon\Carbon::parse($booking->event_date)->format('M d, Y');
                
                $bookings->push($booking);
            }
        }
        
        return view('studio-photographer.view-online-gallery', compact('bookings'));
    }

    /**
     * Get gallery details for a booking (only if assigned to this photographer)
     */
    public function getGalleryDetails($bookingId)
    {
        try {
            $userId = Auth::id();
            
            // Check if photographer is assigned to this booking
            $assignment = BookingAssignedPhotographerModel::where('photographer_id', $userId)
                ->where('booking_id', $bookingId)
                ->whereIn('status', ['on_site', 'in_progress', 'completed'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to access this booking\'s gallery.'
                ], 403);
            }

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->where('booking_type', 'studio')
                ->with(['client:id,first_name,last_name,email', 'packages.studioPackage'])
                ->firstOrFail();

            if (!$booking->requiresOnlineGalleryUpload()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking does not include online gallery feature.'
                ], 400);
            }

            // Get existing gallery if any
            $gallery = StudioOnlineGalleryModel::where('booking_id', $bookingId)->first();

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'event_name' => $booking->event_name,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                    'client_name' => $booking->client->first_name . ' ' . $booking->client->last_name,
                    'client_email' => $booking->client->email,
                ],
                'gallery' => $gallery,
                'has_gallery' => $gallery ? true : false
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching gallery details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload images for online gallery (only if assigned to this booking)
     */
    public function uploadImages(Request $request, $bookingId)
    {
        try {
            $request->validate([
                'images' => 'required|array|min:1|max:50',
                'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
                'gallery_name' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            $userId = Auth::id();
            
            // Check if photographer is assigned to this booking
            $assignment = BookingAssignedPhotographerModel::where('photographer_id', $userId)
                ->where('booking_id', $bookingId)
                ->whereIn('status', ['on_site', 'in_progress', 'completed'])
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to upload to this booking\'s gallery.'
                ], 403);
            }

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->where('booking_type', 'studio')
                ->with(['packages.studioPackage'])
                ->firstOrFail();

            // Check if booking has online gallery package
            if (!$booking->requiresOnlineGalleryUpload()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking does not include online gallery feature.'
                ], 400);
            }

            // Check if gallery already exists
            $gallery = StudioOnlineGalleryModel::where('booking_id', $bookingId)->first();

            if ($gallery && $gallery->gallery_status === 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'This gallery is already published to the client. Ask the client to request a revision before uploading new photos.'
                ], 400);
            }

            DB::beginTransaction();

            // Upload images
            $uploadedImages = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('studio-online-galleries/' . $bookingId, 'public');
                    $uploadedImages[] = $path;
                }
            }

            if ($gallery) {
                // Update existing gallery
                $existingImages = $gallery->images ?? [];
                $allImages = array_merge($existingImages, $uploadedImages);
                
                $gallery->update([
                    'images' => $allImages,
                    'total_photos' => count($allImages),
                    'gallery_name' => $request->gallery_name ?? $gallery->gallery_name,
                    'description' => $request->description ?? $gallery->description,
                ]);
                
                $message = count($uploadedImages) . ' image(s) added to gallery successfully.';
            } else {
                // Create new gallery
                $gallery = StudioOnlineGalleryModel::create([
                    'booking_id' => $bookingId,
                    'studio_id' => $booking->provider_id,
                    'client_id' => $booking->client_id,
                    'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
                    'gallery_name' => $request->gallery_name ?? $booking->event_name . ' Gallery',
                    'description' => $request->description,
                    'images' => $uploadedImages,
                    'total_photos' => count($uploadedImages),
                    'status' => 'active',
                    'gallery_status' => 'draft',
                ]);

                $message = 'Gallery created with ' . count($uploadedImages) . ' image(s) successfully. Awaiting owner review before publishing.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'gallery' => $gallery
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error uploading images: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an image from gallery (only if assigned to this booking)
     */
    public function deleteImage(Request $request, $galleryId)
    {
        try {
            $request->validate([
                'image_path' => 'required|string'
            ]);

            $userId = Auth::id();
            
            // Get gallery and check if photographer is assigned to the booking
            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->with('booking')
                ->firstOrFail();

            // Check if photographer is assigned to this booking
            $assignment = BookingAssignedPhotographerModel::where('photographer_id', $userId)
                ->where('booking_id', $gallery->booking_id)
                ->exists();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete images from this gallery.'
                ], 403);
            }

            $images = $gallery->images ?? [];
            
            // Find and remove the image
            if (($key = array_search($request->image_path, $images)) !== false) {
                unset($images[$key]);
                
                // Delete file from storage
                Storage::disk('public')->delete($request->image_path);
                
                // Re-index array
                $images = array_values($images);
                
                $gallery->update([
                    'images' => $images,
                    'total_photos' => count($images)
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully.',
                    'total_photos' => count($images)
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Image not found.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete entire gallery (only if assigned to this booking)
     */
    public function deleteGallery($galleryId)
    {
        try {
            $userId = Auth::id();
            
            // Get gallery and check if photographer is assigned to the booking
            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->with('booking')
                ->firstOrFail();

            // Check if photographer is assigned to this booking
            $assignment = BookingAssignedPhotographerModel::where('photographer_id', $userId)
                ->where('booking_id', $gallery->booking_id)
                ->exists();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this gallery.'
                ], 403);
            }

            // Delete all images from storage
            $images = $gallery->images ?? [];
            foreach ($images as $image) {
                Storage::disk('public')->delete($image);
            }

            // Delete the gallery record
            $gallery->delete();

            return response()->json([
                'success' => true,
                'message' => 'Gallery deleted successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting gallery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update gallery info (only if assigned to this booking)
     */
    public function updateGallery(Request $request, $galleryId)
    {
        try {
            $request->validate([
                'gallery_name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:active,inactive'
            ]);

            $userId = Auth::id();
            
            // Get gallery and check if photographer is assigned to the booking
            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->with('booking')
                ->firstOrFail();

            // Check if photographer is assigned to this booking
            $assignment = BookingAssignedPhotographerModel::where('photographer_id', $userId)
                ->where('booking_id', $gallery->booking_id)
                ->exists();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to update this gallery.'
                ], 403);
            }

            $gallery->update([
                'gallery_name' => $request->gallery_name,
                'description' => $request->description,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Gallery updated successfully.',
                'gallery' => $gallery
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating gallery: ' . $e->getMessage()
            ], 500);
        }
    }
}
