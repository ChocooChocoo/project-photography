<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\Freelancer\PackagesModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineGalleryController extends Controller
{
    /**
     * Display list of completed bookings with online gallery feature.
     */
    public function index()
    {
        $userId = Auth::id();
        
        // Get freelancer profile to verify if exists
        $profile = ProfileModel::where('user_id', $userId)->first();
        
        $bookings = collect([]);
        
        if ($profile) {
            // Get completed bookings where packages have online_gallery = true
            // Note: For freelancers, we need to check if the package has online_gallery field
            // Since the freelancer packages table doesn't have online_gallery field yet,
            // we'll assume all completed bookings are eligible or add migration later
            
            $bookings = BookingModel::where('provider_id', $userId)
                ->where('booking_type', 'freelancer')
                ->where('status', 'completed')
                ->with([
                    'client:id,first_name,last_name,email',
                    'packages',
                    'category:id,category_name'
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Check if each booking has an existing gallery
            foreach ($bookings as $booking) {
                $booking->has_gallery = FreelanceOnlineGalleryModel::where('booking_id', $booking->id)->exists();
                $booking->gallery = FreelanceOnlineGalleryModel::where('booking_id', $booking->id)->first();
                $booking->formatted_event_date = \Carbon\Carbon::parse($booking->event_date)->format('M d, Y');
                
                // Get package name
                if ($booking->packages->isNotEmpty()) {
                    $package = $booking->packages->first();
                    // For freelancer packages, we need to get from freelancer packages table
                    if ($package->package_type === 'freelancer' && $package->freelancerPackage) {
                        $booking->package_name = $package->freelancerPackage->package_name;
                    } else {
                        $booking->package_name = 'N/A';
                    }
                } else {
                    $booking->package_name = 'N/A';
                }
            }
        }
        
        return view('freelancer.view-online-gallery', compact('bookings'));
    }

    /**
     * Get gallery details for a booking.
     */
    public function getGalleryDetails($bookingId)
    {
        try {
            $userId = Auth::id();

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->where('provider_id', $userId)
                ->where('booking_type', 'freelancer')
                ->with(['client:id,first_name,last_name,email'])
                ->firstOrFail();

            // Get existing gallery if any
            $gallery = FreelanceOnlineGalleryModel::where('booking_id', $bookingId)->first();

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

            // Get the booking
            $booking = BookingModel::where('id', $bookingId)
                ->where('provider_id', $userId)
                ->where('booking_type', 'freelancer')
                ->where('status', 'completed')
                ->firstOrFail();

            DB::beginTransaction();

            // Check if gallery already exists
            $gallery = FreelanceOnlineGalleryModel::where('booking_id', $bookingId)->first();
            
            // Upload images
            $uploadedImages = [];
            
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('freelancer-online-galleries/' . $bookingId, 'public');
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
                $gallery = FreelanceOnlineGalleryModel::create([
                    'booking_id' => $bookingId,
                    'freelancer_id' => $userId,
                    'client_id' => $booking->client_id,
                    'gallery_reference' => FreelanceOnlineGalleryModel::generateGalleryReference(),
                    'gallery_name' => $request->gallery_name ?? $booking->event_name . ' Gallery',
                    'description' => $request->description,
                    'images' => $uploadedImages,
                    'total_photos' => count($uploadedImages),
                    'status' => 'active',
                    'published_at' => now(),
                    'gallery_status' => 'published',
                ]);

                $message = 'Gallery created with ' . count($uploadedImages) . ' image(s) successfully.';
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

            $gallery = FreelanceOnlineGalleryModel::where('id', $galleryId)
                ->where('freelancer_id', $userId)
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

            $gallery = FreelanceOnlineGalleryModel::where('id', $galleryId)
                ->where('freelancer_id', $userId)
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

            $gallery = FreelanceOnlineGalleryModel::where('id', $galleryId)
                ->where('freelancer_id', $userId)
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
     * Display portfolio galleries (independent of bookings).
     */
    public function portfolio()
    {
        $userId = Auth::id();

        $portfolioGalleries = FreelanceOnlineGalleryModel::where('freelancer_id', $userId)
            ->where('gallery_type', 'portfolio')
            ->latest()
            ->get();

        return view('freelancer.view-portfolio-gallery', compact('portfolioGalleries'));
    }

    /**
     * Upload images to a portfolio gallery (not tied to a booking).
     */
    public function storePortfolio(Request $request)
    {
        try {
            $request->validate([
                'gallery_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'images' => 'required|array|min:1',
                'images.*' => 'image|mimes:jpg,jpeg,png|max:5120',
            ]);

            $userId = Auth::id();

            DB::beginTransaction();

            $uploadedImages = [];
            foreach ($request->file('images') as $image) {
                $uploadedImages[] = $image->store('freelancer-online-galleries/portfolio/' . $userId, 'public');
            }

            $gallery = FreelanceOnlineGalleryModel::create([
                'booking_id' => null,
                'freelancer_id' => $userId,
                'client_id' => null,
                'gallery_type' => 'portfolio',
                'gallery_reference' => FreelanceOnlineGalleryModel::generateGalleryReference(),
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