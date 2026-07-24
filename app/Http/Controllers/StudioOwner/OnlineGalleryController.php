<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\PackagesModel;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineGalleryController extends Controller
{
    use Notifiable;

    /**
     * Display list of in-progress or completed bookings with online gallery feature.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get ALL studios owned by this user
        $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
        
        $bookings = collect([]);
        
        if (!empty($studioIds)) {
            // Get in-progress or completed bookings where packages have online_gallery = true
            $bookings = BookingModel::whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->whereIn('status', ['in_progress', 'completed'])
                ->whereHas('packages', function($q) {
                    $q->where('package_type', 'studio')
                      ->whereHas('studioPackage', function($p) {
                          $p->where('online_gallery', 1);
                      });
                })
                ->with([
                    'client:id,first_name,last_name,email',
                    'packages.studioPackage:id,package_name,online_gallery',
                    'category:id,category_name'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Check if each booking has an existing gallery
            foreach ($bookings as $booking) {
                $booking->has_gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->exists();
                $booking->gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)->first();
                $booking->formatted_event_date = \Carbon\Carbon::parse($booking->event_date)->format('M d, Y');
            }
        }
        
        return view('owner.view-online-gallery', compact('bookings'));
    }

    /**
     * Get gallery details for a booking.
     */
    public function getGalleryDetails($bookingId)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['client:id,first_name,last_name,email'])
                ->firstOrFail();

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
     * Upload images for online gallery.
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
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->whereIn('status', ['in_progress', 'completed'])
                ->firstOrFail();

            // Check if booking has online gallery package
            $hasOnlineGallery = $booking->packages()
                ->where('package_type', 'studio')
                ->whereHas('studioPackage', function($q) {
                    $q->where('online_gallery', 1);
                })->exists();

            if (!$hasOnlineGallery) {
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

                $message = 'Gallery created with ' . count($uploadedImages) . ' image(s) successfully. Publish it to make it visible to the client.';
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
     * Delete an image from gallery.
     */
    public function deleteImage(Request $request, $galleryId)
    {
        try {
            $request->validate([
                'image_path' => 'required|string'
            ]);

            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->whereIn('studio_id', $studioIds)
                ->firstOrFail();

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
     * Delete entire gallery.
     */
    public function deleteGallery($galleryId)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->whereIn('studio_id', $studioIds)
                ->firstOrFail();

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
     * Update gallery info.
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
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->whereIn('studio_id', $studioIds)
                ->firstOrFail();

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

    /**
     * Publish a draft gallery, making it visible to the client.
     */
    public function publish($galleryId)
    {
        try {
            $userId = Auth::id();
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();

            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found'
                ], 404);
            }

            $gallery = StudioOnlineGalleryModel::where('id', $galleryId)
                ->whereIn('studio_id', $studioIds)
                ->firstOrFail();

            $gallery->update([
                'gallery_status' => 'published',
                'published_at' => now(),
            ]);

            if ($gallery->client) {
                $this->notifyGalleryPublished($gallery, $gallery->client);
            }

            return response()->json([
                'success' => true,
                'message' => 'Gallery published to client successfully.',
                'gallery' => $gallery
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error publishing gallery: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display portfolio galleries (independent of bookings).
     */
    public function portfolio()
    {
        $userId = Auth::id();
        $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();

        $studios = StudiosModel::where('user_id', $userId)->get(['id', 'studio_name']);

        $portfolioGalleries = StudioOnlineGalleryModel::whereIn('studio_id', $studioIds)
            ->where('gallery_type', 'portfolio')
            ->latest()
            ->get();

        return view('owner.view-portfolio-gallery', compact('studios', 'portfolioGalleries'));
    }

    /**
     * Upload images to a portfolio gallery (not tied to a booking).
     */
    public function storePortfolio(Request $request)
    {
        try {
            $request->validate([
                'studio_id' => 'required|integer',
                'gallery_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'images' => 'required|array|min:1',
                'images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $userId = Auth::id();
            $studio = StudiosModel::where('id', $request->studio_id)
                ->where('user_id', $userId)
                ->firstOrFail();

            DB::beginTransaction();

            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $image->store('studio-online-galleries/portfolio/' . $studio->id, 'public');
            }

            $gallery = StudioOnlineGalleryModel::create([
                'booking_id' => null,
                'studio_id' => $studio->id,
                'client_id' => null,
                'gallery_type' => 'portfolio',
                'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
                'gallery_name' => $request->gallery_name,
                'description' => $request->description,
                'images' => $uploadedImages,
                'total_photos' => count($uploadedImages),
                'status' => 'active',
                'published_at' => now(),
                'gallery_status' => 'published',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Portfolio gallery created with ' . count($uploadedImages) . ' image(s).',
                'gallery' => $gallery,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error uploading portfolio images: ' . $e->getMessage()
            ], 500);
        }
    }
}
