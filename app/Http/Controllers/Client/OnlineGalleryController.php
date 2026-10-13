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
    private function downloadableGallery($id, string $type)
    {
        abort_unless(in_array($type, ['studio', 'freelancer'], true), 404);
        $model = $type === 'studio' ? StudioOnlineGalleryModel::class : FreelanceOnlineGalleryModel::class;
        return $model::whereKey($id)->where('status', 'active')
            ->where('gallery_status', 'published')->where('approval_status', 'approved')
            ->whereHas('booking', fn ($query) => $query->where('client_id', Auth::id()))
            ->firstOrFail();
    }

    private function galleryFile($gallery, string $type, $file): array
    {
        abort_unless(ctype_digit((string) $file), 404);
        $path = ($gallery->images ?? [])[(int) $file] ?? null;
        $prefix = $type === 'studio' ? 'studio-online-galleries/' : 'freelancer-online-galleries/';
        abort_unless(is_string($path) && str_starts_with($path, $prefix)
            && ! str_contains($path, '..') && ! str_contains($path, '\\'), 404);
        $disk = \Illuminate\Support\Facades\Storage::disk($gallery->storage_disk ?? 'public');
        abort_unless($disk->exists($path), 404, 'Gallery file is unavailable.');
        return [$disk, $path];
    }

    public function downloadFile($id, string $type, $file)
    {
        $gallery = $this->downloadableGallery($id, $type);
        [$disk, $path] = $this->galleryFile($gallery, $type, $file);
        return $disk->download($path, basename($path), ['Cache-Control' => 'private, no-store']);
    }

    public function downloadZip($id, string $type)
    {
        $gallery = $this->downloadableGallery($id, $type);
        abort_if(empty($gallery->images), 422, 'No uploaded files to download. Use the Drive link instead.');
        $directory = storage_path('app/private/gallery-downloads');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Could not create download directory.');
        }
        $temporary = tempnam($directory, 'gallery-');
        if ($temporary === false) throw new \RuntimeException('Could not create download file.');
        $zip = new \ZipArchive;
        try {
            if ($zip->open($temporary, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create gallery archive.');
            }
            foreach (array_keys($gallery->images) as $index) {
                [$disk, $path] = $this->galleryFile($gallery, $type, $index);
                if (! $zip->addFile($disk->path($path), ($index + 1).'-'.basename($path))) {
                    throw new \RuntimeException('Could not add gallery file.');
                }
            }
            if (! $zip->close()) throw new \RuntimeException('Could not finish gallery archive.');
            // tempnam's empty-file stat must not turn the completed ZIP into a 204 response.
            clearstatcache(true, $temporary);
            return response()->download($temporary, preg_replace('/[^A-Za-z0-9_-]/', '-', $gallery->gallery_reference).'.zip', ['Cache-Control' => 'private, no-store'])
                ->deleteFileAfterSend(true);
        } catch (\Throwable $error) {
            if ($zip->status === \ZipArchive::ER_OK) $zip->close();
            if (is_file($temporary)) unlink($temporary);
            throw $error;
        }
    }

    /**
     * Display list of galleries for the authenticated client
     */
    public function index()
    {
        $clientId = Auth::id();
        
        // Gallery delivery starts once the booking is In Progress; completed bookings stay visible.
        // Draft galleries are still hidden because each lookup below requires gallery_status = published.
        $bookings = BookingModel::where('client_id', $clientId)
            ->whereIn('status', [BookingModel::STATUS_IN_PROGRESS, BookingModel::STATUS_COMPLETED])
            ->with([
                'category:id,category_name',
                'packages'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        $galleries = collect([]);

        // Only an approved gallery can reach the client. A legacy row has no approval record,
        // so a null approval_status stays visible.
        $approvalFilter = function ($query) {
            $query->where('approval_status', 'approved')->orWhereNull('approval_status');
        };

        foreach ($bookings as $booking) {
            $gallery = null;
            $galleryType = null;

            // Check for studio gallery
            if ($booking->booking_type === 'studio') {
                $gallery = StudioOnlineGalleryModel::where('booking_id', $booking->id)
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->where($approvalFilter)
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
                    ->where($approvalFilter)
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
                    ->whereHas('booking', fn ($query) => $query->where('client_id', $clientId))
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->where(function ($query) {
                        $query->where('approval_status', 'approved')->orWhereNull('approval_status');
                    })
                    ->with(['studio', 'booking.client'])
                    ->firstOrFail();

                $providerName = $gallery->studio->studio_name ?? 'Studio';
            }
            elseif ($type === 'freelancer') {
                $gallery = FreelanceOnlineGalleryModel::where('id', $id)
                    ->where('client_id', $clientId)
                    ->whereHas('booking', fn ($query) => $query->where('client_id', $clientId))
                    ->where('status', 'active')
                    ->where('gallery_status', 'published')
                    ->where(function ($query) {
                        $query->where('approval_status', 'approved')->orWhereNull('approval_status');
                    })
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
                    'download_urls' => $gallery->isApproved() ? collect($gallery->images ?? [])->keys()
                        ->map(fn ($index) => route('client.online-gallery.download', [$gallery->id, $type, $index], false))->all() : [],
                    'zip_url' => $gallery->isApproved() && ! empty($gallery->images)
                        ? route('client.online-gallery.zip', [$gallery->id, $type], false) : null,
                    'drive_link' => $gallery->isApproved() ? $gallery->drive_link : null,
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
