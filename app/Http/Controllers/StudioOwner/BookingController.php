<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Traits\Notifiable;
use App\Http\Requests\StudioOwner\AssignBookingPhotographersRequest;
use App\Http\Requests\StudioOwner\UpdateBookingStatusRequest;
use App\Services\PhotographerAvailabilityService;

class BookingController extends Controller
{
    use Notifiable;

    /**
     * Create a new controller instance.
     *
     * @param PhotographerAvailabilityService $photographerAvailabilityService
     */
    public function __construct(private PhotographerAvailabilityService $photographerAvailabilityService)
    {
    }

    public function index()
    {
        $userId = Auth::id();
        
        // Get ALL studios owned by this user
        $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
        
        if (empty($studioIds)) {
            return view('owner.view-bookings')->with('bookings', collect([]));
        }
        
        // Get bookings for ALL studios owned by this user
        $bookings = BookingModel::whereIn('provider_id', $studioIds)
            ->where('booking_type', 'studio')
            ->with([
                'client:id,first_name,last_name,email,mobile_number',
                'category:id,category_name',
                'packages',
                'assignedPhotographers' => function($query) {
                    $query->with(['photographer:id,first_name,last_name']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('owner.view-bookings', compact('bookings'));
    }

    /**
     * Display booking history
     */
    public function history()
    {
        $userId = Auth::id();
        
        // Get ALL studios owned by this user
        $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
        
        if (empty($studioIds)) {
            return view('owner.booking-history')->with('bookings', collect([]));
        }
        
        // Get completed/cancelled bookings for ALL studios
        $bookings = BookingModel::whereIn('provider_id', $studioIds)
            ->where('booking_type', 'studio')
            ->whereIn('status', ['completed', 'cancelled'])
            ->with([
                'client:id,first_name,last_name',
                'category:id,category_name',
                'packages'
            ])
            ->orderBy('updated_at', 'desc')
            ->get();
        
        return view('owner.booking-history', compact('bookings'));
    }

    /**
     * Get booking details for modal view
     */
    public function getBookingDetails($id)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }
            
            // Get the booking - check if it belongs to ANY of the owner's studios
            $booking = BookingModel::where('id', $id)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with([
                    'studioOnlineGallery',
                    'client:id,first_name,last_name,email,mobile_number',
                    'category:id,category_name',
                    'packages.studioPackage',
                    'payments' => function($query) {
                        $query->orderBy('created_at', 'desc');
                    },
                    'assignedPhotographers' => function($query) {
                        $query->with([
                            'photographer:id,first_name,last_name,email',
                            'studioPhotographer'
                        ]);
                    }
                ])
                ->firstOrFail();
            
            // Calculate total paid
            $totalPaid = $booking->payments->where('status', 'succeeded')->sum('amount');
            
            // Get available statuses for dropdown
            $availableStatuses = $booking->getAvailableStatuses();
            
            // Check if all photographers have completed their assignments
            $allPhotographersCompleted = true;
            $hasAssignedPhotographers = $booking->assignedPhotographers->count() > 0;
            
            foreach ($booking->assignedPhotographers as $assignment) {
                if ($assignment->status !== 'completed') {
                    $allPhotographersCompleted = false;
                    break;
                }
            }

            $requiresOnlineGallery = $booking->requiresOnlineGalleryUpload();
            $hasUploadedGalleryContent = $booking->hasUploadedGalleryContent();
            $galleryBlockReason = $booking->getGalleryCompletionBlockReason();
            
            // Get maximum photographers allowed based on package
            $maxPhotographers = $this->getMaxPhotographersFromPackage($booking);
            $currentAssignedCount = $booking->assignedPhotographers->count();
            
            // Get package details for display
            $bookingPackage = $booking->packages->first();
            $packageDetails = null;
            
            if ($bookingPackage && $bookingPackage->package_type === 'studio') {
                $packageDetails = \App\Models\StudioOwner\PackagesModel::find($bookingPackage->package_id);
            }
            
            // Owner can only complete booking if:
            // 1. Booking is in 'in_progress' status
            // 2. All assigned photographers have marked as completed
            // 3. Booking is fully paid
            // 4. Required gallery images have been uploaded
            $canOwnerComplete = $booking->status === 'in_progress' && 
                                $allPhotographersCompleted && 
                                $totalPaid >= $booking->total_amount &&
                                $booking->isGalleryReadyForCompletion();

            $completionBlockers = $this->getOwnerCompletionBlockers(
                $booking,
                $allPhotographersCompleted,
                $totalPaid
            );
            
            return response()->json([
                'success' => true,
                'booking' => $booking,
                'client' => $booking->client,
                'category' => $booking->category,
                'packages' => $booking->packages,
                'payments' => $booking->payments,
                'assignedPhotographers' => $booking->assignedPhotographers,
                'available_statuses' => $availableStatuses,
                'can_owner_complete' => $canOwnerComplete,
                'can_mark_completed' => $canOwnerComplete,
                'total_paid' => $totalPaid,
                'status_badge_class' => $booking->getStatusBadgeClass(),
                'payment_status_badge_class' => $booking->getPaymentStatusBadgeClass(),
                'max_photographers' => $maxPhotographers,
                'current_assigned_count' => $currentAssignedCount,
                'package_photographer_count' => $packageDetails->photographer_count ?? 1,
                'requires_online_gallery' => $requiresOnlineGallery,
                'has_uploaded_gallery_content' => $hasUploadedGalleryContent,
                'completion_block_reason' => $galleryBlockReason,
                'completion_blockers' => $completionBlockers,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current completion blockers for an owner-facing booking flow.
     */
    private function getOwnerCompletionBlockers(BookingModel $booking, bool $allPhotographersCompleted, float $totalPaid): array
    {
        $blockers = [];

        if ($booking->status !== BookingModel::STATUS_IN_PROGRESS) {
            $blockers[] = 'Booking must be in progress before it can be completed.';
        }

        if ($totalPaid < (float) $booking->total_amount) {
            $blockers[] = 'Booking must be fully paid before it can be completed.';
        }

        if (!$allPhotographersCompleted) {
            $blockers[] = 'All assigned photographers must mark their assignments as completed before the owner can complete the booking.';
        }

        if (!$booking->isGalleryReadyForCompletion()) {
            $blockers[] = $booking->getGalleryCompletionBlockReason();
        }

        return $blockers;
    }

    /**
     * Get maximum photographers allowed from the booking's package
     */
    private function getMaxPhotographersFromPackage($booking)
    {
        // Get the booking package
        $bookingPackage = $booking->packages->first();
        
        if (!$bookingPackage) {
            return 1; // Default to 1 if no package
        }
        
        // Get the actual package from tbl_packages based on package_id and package_type
        if ($bookingPackage->package_type === 'studio') {
            $package = \App\Models\StudioOwner\PackagesModel::find($bookingPackage->package_id);
        } else {
            $package = \App\Models\Freelancer\PackagesModel::find($bookingPackage->package_id);
        }
        
        if ($package && isset($package->photographer_count)) {
            return (int) $package->photographer_count;
        }
        
        return 1; // Default to 1 if no photographer_count specified
    }

    /**
     * Get available photographers for assignment
     */
    public function getAvailablePhotographers($bookingId)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }
            
            // Get the booking - check if it belongs to ANY of the owner's studios
            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['packages', 'category'])
                ->firstOrFail();
            
            // Get the specific studio for this booking (for photographers)
            $studio = StudiosModel::find($booking->provider_id);
            
            // Don't allow assignment if booking is in progress or completed
            if (in_array($booking->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign photographers to a booking that is in progress or already completed.'
                ]);
            }
            
            // Get required photographer count from package
            $requiredPhotographers = $this->getMaxPhotographersFromPackage($booking);
            $currentAssignedCount = BookingAssignedPhotographerModel::where('booking_id', $bookingId)->count();
            $remainingNeeded = $requiredPhotographers - $currentAssignedCount;
            
            // Get package details for display
            $bookingPackage = $booking->packages->first();
            $packageName = 'N/A';
            $packageDetails = null;
            
            if ($bookingPackage) {
                if ($bookingPackage->package_type === 'studio') {
                    $packageDetails = \App\Models\StudioOwner\PackagesModel::find($bookingPackage->package_id);
                }
                $packageName = $bookingPackage->package_name;
            }
            
            // Check if requirement is already met
            if ($remainingNeeded <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => "This booking already has all {$requiredPhotographers} required photographers assigned."
                ]);
            }
            
            // Get all active studio photographers
            $studioPhotographers = StudioPhotographersModel::where('studio_id', $studio->id)
                ->where('status', 'active')
                ->with(['photographer:id,first_name,last_name'])
                ->get();

            $assignedPhotographerIds = BookingAssignedPhotographerModel::where('booking_id', $bookingId)
                ->pluck('photographer_id')
                ->map(fn ($photographerId) => (int) $photographerId)
                ->toArray();

            $availabilityMap = $this->photographerAvailabilityService->getAvailabilityMapForBooking(
                $booking,
                $studioPhotographers->pluck('photographer_id')->map(fn ($photographerId) => (int) $photographerId)->toArray()
            );

            $availablePhotographers = [];
            foreach ($studioPhotographers as $studioPhotographer) {
                $photographerId = (int) $studioPhotographer->photographer_id;
                $availability = $availabilityMap[$photographerId] ?? [
                    'is_available' => true,
                    'availability_status' => 'available',
                    'availability_reason' => 'Available for assignment.',
                    'availability_conflicts' => [],
                ];

                if (in_array($photographerId, $assignedPhotographerIds, true)) {
                    $availability = [
                        'is_available' => false,
                        'availability_status' => 'already_assigned',
                        'availability_reason' => 'Already assigned to this booking.',
                        'availability_conflicts' => [
                            [
                                'type' => 'already_assigned',
                                'status' => 'already_assigned',
                                'message' => 'Already assigned to this booking.',
                                'booking_id' => $booking->id,
                                'booking_reference' => $booking->booking_reference,
                            ],
                        ],
                    ];
                }

                $availablePhotographers[] = [
                    'id' => $photographerId,
                    'name' => $studioPhotographer->photographer->first_name . ' ' . $studioPhotographer->photographer->last_name,
                    'position' => $studioPhotographer->position,
                    'status' => $studioPhotographer->status,
                    'years_experience' => $studioPhotographer->years_of_experience,
                    'specialization' => $studioPhotographer->specialization,
                    'is_available' => $availability['is_available'],
                    'availability_status' => $availability['availability_status'],
                    'availability_reason' => $availability['availability_reason'],
                    'availability_conflicts' => $availability['availability_conflicts'],
                ];
            }

            $assignablePhotographerCount = collect($availablePhotographers)
                ->where('is_available', true)
                ->count();
            
            return response()->json([
                'success' => true,
                'photographers' => $availablePhotographers,
                'booking' => [
                    'reference' => $booking->booking_reference,
                    'event_name' => $booking->event_name,
                    'event_date' => \Carbon\Carbon::parse($booking->event_date)->format('M d, Y'),
                    'category' => $booking->category->category_name ?? 'N/A'
                ],
                'assignment_info' => [
                    'required_photographers' => $requiredPhotographers,
                    'current_assigned' => $currentAssignedCount,
                    'remaining_needed' => $remainingNeeded,
                    'is_initial_assignment' => ($currentAssignedCount === 0),
                    'assignable_photographers' => $assignablePhotographerCount,
                    'package_name' => $packageName,
                    'package_details' => $packageDetails ? [
                        'photographer_count' => $packageDetails->photographer_count,
                        'duration' => $packageDetails->duration,
                        'maximum_edited_photos' => $packageDetails->maximum_edited_photos
                    ] : null
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available photographers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign photographers to booking
     */
    public function assignPhotographers(AssignBookingPhotographersRequest $request, $bookingId)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }
            
            // Get the booking - check if it belongs to ANY of the owner's studios
            $booking = BookingModel::where('id', $bookingId)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['packages', 'client:id,first_name,last_name'])
                ->firstOrFail();
            
            // Get the specific studio for this booking
            $studio = StudiosModel::find($booking->provider_id);
            
            // Don't allow assignment if booking is in progress or completed
            if (in_array($booking->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign photographers to a booking that is in progress or already completed.'
                ]);
            }
            
            // Get required photographer count from package
            $requiredPhotographers = $this->getMaxPhotographersFromPackage($booking);
            $currentAssignedCount = BookingAssignedPhotographerModel::where('booking_id', $bookingId)->count();
            $requestedPhotographerIds = collect($request->input('photographer_ids', []))
                ->map(fn ($photographerId) => (int) $photographerId)
                ->unique()
                ->values()
                ->all();
            
            // Check if we're doing initial assignment or adding more
            if ($currentAssignedCount === 0) {
                // Initial assignment - must assign EXACTLY the required number
                if (count($requestedPhotographerIds) != $requiredPhotographers) {
                    return response()->json([
                        'success' => false,
                        'message' => "This package requires exactly {$requiredPhotographers} photographer(s). Please select {$requiredPhotographers} photographers."
                    ], 422);
                }
            } else {
                // Adding more photographers - check if total will equal required number
                $totalAfterAssignment = $currentAssignedCount + count($requestedPhotographerIds);
                
                if ($totalAfterAssignment > $requiredPhotographers) {
                    return response()->json([
                        'success' => false,
                        'message' => "This package requires a total of {$requiredPhotographers} photographer(s). You currently have {$currentAssignedCount} assigned. You can only add " . ($requiredPhotographers - $currentAssignedCount) . " more."
                    ], 422);
                }
                
                if ($totalAfterAssignment < $requiredPhotographers) {
                    return response()->json([
                        'success' => false,
                        'message' => "This package requires a total of {$requiredPhotographers} photographer(s). You currently have {$currentAssignedCount} assigned. You need to add " . ($requiredPhotographers - $currentAssignedCount) . " more to complete the required count."
                    ], 422);
                }
            }

            $alreadyAssignedPhotographerIds = BookingAssignedPhotographerModel::where('booking_id', $bookingId)
                ->pluck('photographer_id')
                ->map(fn ($photographerId) => (int) $photographerId)
                ->toArray();

            $duplicateSelections = array_values(array_intersect($requestedPhotographerIds, $alreadyAssignedPhotographerIds));

            if (!empty($duplicateSelections)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected photographers are already assigned to this booking.',
                    'photographer_conflicts' => collect($duplicateSelections)->map(function ($photographerId) use ($booking) {
                        return [
                            'photographer_id' => $photographerId,
                            'availability_status' => 'already_assigned',
                            'availability_reason' => 'Already assigned to this booking.',
                            'availability_conflicts' => [
                                [
                                    'type' => 'already_assigned',
                                    'status' => 'already_assigned',
                                    'message' => 'Already assigned to this booking.',
                                    'booking_id' => $booking->id,
                                    'booking_reference' => $booking->booking_reference,
                                ],
                            ],
                        ];
                    })->values()->all(),
                ], 409);
            }

            $availabilityMap = $this->photographerAvailabilityService->getAvailabilityMapForBooking($booking, $requestedPhotographerIds);
            $unavailableSelections = collect($availabilityMap)
                ->filter(fn ($availability) => !$availability['is_available'])
                ->values();

            if ($unavailableSelections->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => $unavailableSelections->first()['availability_reason'] ?? 'One or more selected photographers are unavailable.',
                    'photographer_conflicts' => $unavailableSelections->all(),
                ], 409);
            }
            
            [$assignedCount, $createdAssignments] = DB::transaction(function () use (
                $requestedPhotographerIds,
                $booking,
                $bookingId,
                $studio,
                $userId,
                $request
            ) {
                $freshAvailabilityMap = $this->photographerAvailabilityService->getAvailabilityMapForBooking($booking, $requestedPhotographerIds);
                $freshUnavailableSelections = collect($freshAvailabilityMap)
                    ->filter(fn ($availability) => !$availability['is_available'])
                    ->values();

                if ($freshUnavailableSelections->isNotEmpty()) {
                    throw new \RuntimeException(json_encode([
                        'message' => $freshUnavailableSelections->first()['availability_reason'] ?? 'One or more selected photographers are unavailable.',
                        'photographer_conflicts' => $freshUnavailableSelections->all(),
                    ], JSON_THROW_ON_ERROR));
                }

                $assignedCount = 0;
                $createdAssignments = [];

                foreach ($requestedPhotographerIds as $photographerId) {
                    $exists = BookingAssignedPhotographerModel::where('booking_id', $bookingId)
                        ->where('photographer_id', $photographerId)
                        ->exists();

                    if ($exists) {
                        throw new \RuntimeException(json_encode([
                            'message' => 'One or more selected photographers are already assigned to this booking.',
                            'photographer_conflicts' => [
                                [
                                    'photographer_id' => $photographerId,
                                    'availability_status' => 'already_assigned',
                                    'availability_reason' => 'Already assigned to this booking.',
                                    'availability_conflicts' => [
                                        [
                                            'type' => 'already_assigned',
                                            'status' => 'already_assigned',
                                            'message' => 'Already assigned to this booking.',
                                            'booking_id' => $booking->id,
                                            'booking_reference' => $booking->booking_reference,
                                        ],
                                    ],
                                ],
                            ],
                        ], JSON_THROW_ON_ERROR));
                    }

                    $assignment = BookingAssignedPhotographerModel::create([
                        'booking_id' => $bookingId,
                        'studio_id' => $studio->id,
                        'photographer_id' => $photographerId,
                        'assigned_by' => $userId,
                        'status' => 'assigned',
                        'assignment_notes' => $request->assignment_notes,
                        'assigned_at' => now(),
                        'response_deadline' => now()->addHours(24),
                    ]);

                    $assignedCount++;
                    $createdAssignments[] = $assignment;
                }

                return [$assignedCount, $createdAssignments];
            });
            
            // Calculate new totals AFTER successful assignment
            $newTotal = $currentAssignedCount + $assignedCount;
            $isComplete = ($newTotal == $requiredPhotographers);
            
            // ========== START: ADD NOTIFICATION FOR ASSIGNED PHOTOGRAPHERS AND OWNER ==========
            if ($assignedCount > 0 && !empty($createdAssignments)) {
                // Get the owner's name for the notification message
                $owner = Auth::user();
                $ownerName = $owner->first_name . ' ' . $owner->last_name;
                
                // Get client name
                $client = $booking->client;
                $clientName = $client ? $client->first_name . ' ' . $client->last_name : 'A client';
                
                // Format event details
                $formattedDate = \Carbon\Carbon::parse($booking->event_date)->format('F d, Y');
                $formattedTime = date('h:i A', strtotime($booking->start_time)) . ' - ' . date('h:i A', strtotime($booking->end_time));
                
                // Get package name
                $packageName = 'N/A';
                if ($booking->packages && $booking->packages->count() > 0) {
                    $packageName = $booking->packages->first()->package_name;
                }
                
                // Determine if this is part of a batch assignment
                $isBatchAssignment = $assignedCount > 1;
                $batchText = $isBatchAssignment ? " (as part of a team of {$assignedCount})" : "";
                
                // ========== NOTIFICATION 1: Send to each newly assigned photographer ==========
                $photographerNames = []; // Track names for owner notification
                
                foreach ($createdAssignments as $index => $assignment) {
                    // Get photographer details for the notification
                    $photographer = UserModel::find($assignment->photographer_id);
                    $photographerName = $photographer ? $photographer->first_name . ' ' . $photographer->last_name : 'A photographer';
                    $photographerNames[] = $photographerName;
                    
                    // Prepare notification data for photographer
                    $photographerNotificationData = [
                        'assignment_id' => $assignment->id,
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->booking_reference,
                        'studio_id' => $studio->id,
                        'studio_name' => $studio->studio_name,
                        'client_name' => $clientName,
                        'event_date' => $booking->event_date,
                        'event_time' => $booking->start_time . ' - ' . $booking->end_time,
                        'formatted_date' => $formattedDate,
                        'formatted_time' => $formattedTime,
                        'package_name' => $packageName,
                        'total_amount' => $booking->total_amount,
                        'down_payment' => $booking->down_payment,
                        'location_type' => $booking->location_type,
                        'category_id' => $booking->category_id,
                        'assigned_by' => $owner->id,
                        'assigned_by_name' => $ownerName,
                        'assignment_notes' => $request->assignment_notes,
                        'route' => route('assigned.bookings', [], false),
                        'is_batch_assignment' => $isBatchAssignment,
                        'batch_size' => $assignedCount,
                        'notification_type' => 'photographer_notification'
                    ];
                    
                    // Add location details if available
                    if ($booking->location_type === 'on-location') {
                        if ($booking->multiple_locations) {
                            $photographerNotificationData['has_multiple_locations'] = true;
                            $photographerNotificationData['location_count'] = count($booking->multiple_locations);
                        } else {
                            $photographerNotificationData['city'] = $booking->city;
                            $photographerNotificationData['barangay'] = $booking->barangay;
                            $photographerNotificationData['venue_name'] = $booking->venue_name;
                        }
                    }
                    
                    // Create notification for photographer
                    $this->createNotification(
                        $assignment->photographer_id,                           // recipient: photographer ID
                        'new_booking_assignment',                               // notification type
                        'New Booking Assignment!',                              // title
                        "You have been assigned to a booking for {$packageName} on {$formattedDate}{$batchText}.", // message
                        $photographerNotificationData,                          // data payload
                        'user-plus',                                            // icon (using Lucide icon name)
                        'info'                                                   // color
                    );
                    
                    \Log::info('Booking assignment notification sent to photographer', [
                        'photographer_id' => $assignment->photographer_id,
                        'photographer_name' => $photographerName,
                        'assignment_id' => $assignment->id,
                        'booking_id' => $booking->id,
                        'is_batch' => $isBatchAssignment
                    ]);
                }
                
                // ========== NOTIFICATION 2: Send to owner confirming successful assignment ==========
                
                // Prepare notification data for owner
                $ownerNotificationData = [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'studio_id' => $studio->id,
                    'studio_name' => $studio->studio_name,
                    'client_name' => $clientName,
                    'event_date' => $booking->event_date,
                    'formatted_date' => $formattedDate,
                    'event_time' => $booking->start_time . ' - ' . $booking->end_time,
                    'package_name' => $packageName,
                    'assigned_photographers' => $photographerNames,
                    'assigned_photographer_ids' => $requestedPhotographerIds,
                    'assigned_count' => $assignedCount,
                    'assignment_notes' => $request->assignment_notes,
                    'route' => route('owner.booking.details', ['id' => $booking->id], false),
                    'is_batch_assignment' => $isBatchAssignment,
                    'notification_type' => 'owner_notification',
                    'current_assigned_count' => $newTotal,
                    'required_photographers' => $requiredPhotographers,
                    'is_complete' => $isComplete
                ];
                
                // Determine the message based on assignment status
                $ownerMessage = '';
                if ($isComplete) {
                    $ownerMessage = "All {$requiredPhotographers} required photographers have been successfully assigned to booking {$booking->booking_reference}.";
                } else {
                    $remainingNeeded = $requiredPhotographers - $newTotal;
                    $ownerMessage = "{$assignedCount} photographer(s) have been assigned to booking {$booking->booking_reference}. " .
                                "You still need to assign {$remainingNeeded} more photographer(s) to complete the requirement.";
                }
                
                // Create notification for owner
                $this->createNotification(
                    $owner->id,                                                // recipient: owner ID
                    'photographer_assigned',                                   // notification type
                    'Photographer Assignment Confirmed',                        // title
                    $ownerMessage,                                             // dynamic message
                    $ownerNotificationData,                                    // data payload
                    'check-circle',                                            // icon (using Lucide icon name)
                    'success'                                                  // color
                );
                
                \Log::info('Assignment confirmation notification sent to owner', [
                    'owner_id' => $owner->id,
                    'booking_id' => $booking->id,
                    'assigned_count' => $assignedCount,
                    'is_complete' => $isComplete
                ]);
                
                // Log progress update if adding to existing assignments
                if ($currentAssignedCount > 0 && $assignedCount > 0) {
                    \Log::info('Progress update: Added ' . $assignedCount . ' photographers to existing ' . $currentAssignedCount);
                }
            }
            // ========== END: ADD NOTIFICATIONS ==========
            
            return response()->json([
                'success' => true,
                'message' => $assignedCount . ' photographer(s) assigned successfully.' . 
                            ($isComplete ? ' All required photographers have been assigned.' : ''),
                'assignment_info' => [
                    'current_assigned' => $newTotal,
                    'required_photographers' => $requiredPhotographers,
                    'remaining_needed' => $requiredPhotographers - $newTotal,
                    'is_complete' => $isComplete
                ]
            ]);
            
        } catch (\RuntimeException $e) {
            $payload = json_decode($e->getMessage(), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($payload)) {
                return response()->json([
                    'success' => false,
                    'message' => $payload['message'] ?? 'One or more selected photographers are unavailable.',
                    'photographer_conflicts' => $payload['photographer_conflicts'] ?? [],
                ], 409);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error assigning photographers: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning photographers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove photographer assignment
     */
    public function removePhotographerAssignment($assignmentId)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }
            
            $assignment = BookingAssignedPhotographerModel::where('id', $assignmentId)
                ->whereIn('studio_id', $studioIds)
                ->firstOrFail();
            
            // Don't allow removal if booking is in progress or completed
            $booking = BookingModel::find($assignment->booking_id);
            if (in_array($booking->status, ['in_progress', 'completed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove photographer from a booking that is in progress or already completed.'
                ]);
            }
            
            $assignment->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Photographer assignment removed successfully.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing assignment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the owner-managed booking status.
     */
    public function updateStatus(UpdateBookingStatusRequest $request, $id)
    {
        try {
            $userId = Auth::id();
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();

            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }

            $booking = BookingModel::where('id', $id)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['payments', 'assignedPhotographers', 'packages.studioPackage', 'studioOnlineGallery'])
                ->firstOrFail();

            $newStatus = $request->status;

            if (!$booking->canTransitionTo($newStatus) && $newStatus !== BookingModel::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid booking status transition.'
                ], 422);
            }

            if ($newStatus === BookingModel::STATUS_COMPLETED) {
                $totalPaid = (float) $booking->payments->where('status', 'succeeded')->sum('amount');
                $allPhotographersCompleted = $booking->assignedPhotographers->every(function ($assignment) {
                    return $assignment->status === 'completed';
                });

                $blockers = $this->getOwnerCompletionBlockers($booking, $allPhotographersCompleted, $totalPaid);

                if (!empty($blockers)) {
                    return response()->json([
                        'success' => false,
                        'message' => $blockers[0]
                    ], 403);
                }
            }

            $booking->status = $newStatus;

            if ($newStatus === BookingModel::STATUS_CANCELLED) {
                $booking->cancellation_reason = $request->cancellation_reason;
                $booking->cancelled_by = 'studio';

                // Flag for manual refund review if the client had already paid.
                // No auto-refund yet — that's a later automation phase.
                if ($booking->payments->where('status', 'succeeded')->isNotEmpty()) {
                    $booking->payment_status = 'refund_pending';
                }
            }

            $booking->save();

            if ($newStatus === BookingModel::STATUS_CANCELLED && $booking->client) {
                $studio = StudiosModel::find($booking->provider_id);
                $this->notifyBookingCancelledByStudio(
                    $booking,
                    $booking->client,
                    $studio->studio_name ?? 'the studio',
                    $request->cancellation_reason
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully.',
                'booking' => $booking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Owner completes the booking (final step)
     */
    public function completeBooking($id)
    {
        try {
            $userId = Auth::id();
            
            // Get ALL studios owned by this user
            $studioIds = StudiosModel::where('user_id', $userId)->pluck('id')->toArray();
            
            if (empty($studioIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No studios found for this owner'
                ], 404);
            }
            
            // Get the booking - check if it belongs to ANY of the owner's studios
            $booking = BookingModel::where('id', $id)
                ->whereIn('provider_id', $studioIds)
                ->where('booking_type', 'studio')
                ->with(['packages.studioPackage', 'studioOnlineGallery'])
                ->firstOrFail();
            
            // Check if booking is in progress
            if ($booking->status !== 'in_progress') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking must be in progress before it can be completed.'
                ]);
            }
            
            // Check if fully paid
            $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');
            if ($totalPaid < $booking->total_amount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking must be fully paid before it can be completed.'
                ]);
            }
            
            // Check if all photographers have completed their assignments
            $assignments = BookingAssignedPhotographerModel::where('booking_id', $id)->get();
            
            // If there are no photographers assigned, that's fine
            if ($assignments->count() > 0) {
                foreach ($assignments as $assignment) {
                    if ($assignment->status !== 'completed') {
                        return response()->json([
                            'success' => false,
                            'message' => 'All assigned photographers must mark their assignments as completed before the owner can complete the booking.'
                        ]);
                    }
                }
            }

            if (!$booking->isGalleryReadyForCompletion()) {
                return response()->json([
                    'success' => false,
                    'message' => $booking->getGalleryCompletionBlockReason()
                ], 403);
            }
            
            // Update booking status to completed
            $booking->status = BookingModel::STATUS_COMPLETED;
            $booking->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Booking completed successfully.',
                'booking' => $booking
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing booking: ' . $e->getMessage()
            ], 500);
        }
    }
}
