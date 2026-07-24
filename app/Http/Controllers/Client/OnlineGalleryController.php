<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use Illuminate\Support\Facades\Auth;

class OnlineGalleryController extends Controller
{
    /**
     * Display list of galleries for the authenticated client
     */
    public function index()
    {
        $clientId = Auth::id();
        
        // Get all completed bookings for this client
        $bookings = BookingModel::where('client_id', $clientId)
            ->where('status', 'completed')
            ->with([
                'category:id,category_name',
                'packages'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $galleries = collect([]);

        foreach ($bookings as $booking) {
            $gallery = null;
            $galleryType = null;
            
            // Check for studio gallery
            if ($booking->booking_type === 'studio') {
                $gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->first();
                
                if ($gallery) {
                    $galleryType = 'studio';
                    $gallery->provider_name = $gallery->studio->studio_name ?? 'Studio';
                }
            } 
            // Check for freelancer gallery
            elseif ($booking->booking_type === 'freelancer') {
                $gallery = FreelanceOnlineGalleryModel::where('booking_id', $booking->id)
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->first();
                
                if ($gallery) {
                    $galleryType = 'freelancer';
                    // Get freelancer name from user model
                    $freelancer = $gallery->freelancer;
                    $gallery->provider_name = $freelancer ? $freelancer->first_name . ' ' . $freelancer->last_name : 'Freelancer';
                }
            }

            // Only add if gallery exists and is active
            if ($gallery) {
                $galleries->push((object)[
                    'id' => $gallery->id,
                    'gallery_reference' => $gallery->gallery_reference,
                    'gallery_name' => $gallery->gallery_name,
                    'description' => $gallery->description,
                    'thumbnail' => $gallery->thumbnail,
                    'total_photos' => $gallery->total_photos,
                    'provider_name' => $gallery->provider_name,
                    'booking_reference' => $booking->booking_reference,
                    'event_name' => $booking->event_name,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                    'category' => $booking->category->category_name ?? 'N/A',
                    'type' => $galleryType,
                    'created_at' => $gallery->created_at->format('M d, Y'),
                    'images' => $gallery->images ?? []
                ]);
            }
        }
        
        return view('client.view-online-gallery', compact('galleries'));
    }

    /**
     * Get gallery details for a specific gallery
     */
    public function getGalleryDetails($id, $type)
    {
        try {
            $clientId = Auth::id();
            
            if ($type === 'studio') {
                $gallery = StudioOnlineGalleryModel::where('id', $id)
                    ->where('client_id', $clientId)
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->with(['studio', 'booking.client'])
                    ->firstOrFail();

                $providerName = $gallery->studio->studio_name ?? 'Studio';
            }
            elseif ($type === 'freelancer') {
                $gallery = FreelanceOnlineGalleryModel::where('id', $id)
                    ->where('client_id', $clientId)
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->with(['freelancer', 'booking.client'])
                    ->firstOrFail();
                
                $freelancer = $gallery->freelancer;
                $providerName = $freelancer ? $freelancer->first_name . ' ' . $freelancer->last_name : 'Freelancer';
            } 
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid gallery type'
                ], 400);
            }

            // Get booking details
            $booking = $gallery->booking;

            return response()->json([
                'success' => true,
                'gallery' => [
                    'id' => $gallery->id,
                    'gallery_reference' => $gallery->gallery_reference,
                    'gallery_name' => $gallery->gallery_name,
                    'description' => $gallery->description,
                    'images' => $gallery->images ?? [],
                    'total_photos' => $gallery->total_photos,
                    'provider_name' => $providerName,
                    'type' => $type,
                    'created_at' => $gallery->created_at->format('M d, Y'),
                    'booking_reference' => $booking->booking_reference,
                    'event_name' => $booking->event_name,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gallery not found or access denied'
            ], 404);
        }
    }
}