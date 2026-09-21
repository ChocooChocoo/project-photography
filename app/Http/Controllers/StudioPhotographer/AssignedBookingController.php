<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioPhotographer\UpdateAssignmentStatusRequest;
use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudiosModel;
use App\Services\BookingCancellationRecoveryService;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\Auth;

class AssignedBookingController extends Controller
{
    use Notifiable;

    /**
     * Display assigned bookings for the photographer
     */
    public function index()
    {
        $userId = Auth::id();

        // Get all assignments for this photographer
        $assignments = BookingAssignedPhotographerModel::where('photographer_id', $userId)
            ->with([
                'booking:id,booking_reference,client_id,event_name,event_date,start_time,end_time,total_amount,status,payment_status',
                'booking.client:id,first_name,last_name,email,mobile_number',
                'studio:id,studio_name',
                'assigner:id,first_name,last_name',
            ])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return view('studio-photographer.view-assigned-booking', compact('assignments'));
    }

    /**
     * Get booking details for modal view
     */
    public function getBookingDetails($assignmentId)
    {
        try {
            $userId = Auth::id();

            // Get the assignment with related data
            $assignment = BookingAssignedPhotographerModel::where('id', $assignmentId)
                ->where('photographer_id', $userId)
                ->with([
                    'booking' => function ($query) {
                        $query->with([
                            'client:id,first_name,last_name,email,mobile_number',
                            'category:id,category_name',
                            'packages:id,booking_id,package_id,package_type,package_name,package_price,package_inclusions,duration,maximum_edited_photos,coverage_scope',
                            'packages.studioPackage:id,package_name,online_gallery',
                            'payments:id,booking_id,amount,status,payment_method,paid_at',
                        ]);
                    },
                    'studio:id,studio_name,studio_logo',
                    'assigner:id,first_name,last_name',
                ])
                ->firstOrFail();

            // Get the studio if not loaded through relationship
            if (! $assignment->studio && $assignment->studio_id) {
                $assignment->studio = StudiosModel::find($assignment->studio_id);
            }

            // Get the booking
            $booking = $assignment->booking;

            $equipment = \App\Models\StudioOwner\BookingEquipmentModel::where('booking_id', $booking->id)->get();

            // ========== NEW: Calculate payment status for photographer view ==========
            $totalPaid = $booking->payments->where('status', 'succeeded')->sum('amount');
            $isFullyPaid = $totalPaid >= (float) $booking->total_amount;
            $remainingBalance = (float) $booking->total_amount - $totalPaid;
            $requiresOnlineGallery = $booking->requiresOnlineGalleryUpload();
            $hasUploadedGalleryContent = $booking->hasUploadedGalleryContent();
            $completionBlockReason = $booking->getGalleryCompletionBlockReason();
            // ========== End of payment calculation ==========
            $requiresLocationConfirmation = $booking->requiresLocationConfirmation();

            return response()->json([
                'success' => true,
                'assignment' => $assignment,
                'booking' => $booking,
                'studio' => $assignment->studio,
                'assigner' => $assignment->assigner,
                // ========== NEW: Add payment status flags ==========
                'payment_info' => [
                    'total_amount' => (float) $booking->total_amount,
                    'total_paid' => $totalPaid,
                    'remaining_balance' => $remainingBalance,
                    'is_fully_paid' => $isFullyPaid,
                    'payment_status' => $booking->payment_status,
                ],
                'requires_online_gallery' => $requiresOnlineGallery,
                'has_uploaded_gallery_content' => $hasUploadedGalleryContent,
                'completion_block_reason' => $completionBlockReason,
                'requires_location_confirmation' => $requiresLocationConfirmation,
                'equipment' => $equipment,
                // ========== End of payment info ==========
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking details: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update assignment status (confirm/on_site/in_progress/complete/cancel)
     */
    public function updateAssignmentStatus(UpdateAssignmentStatusRequest $request, $assignmentId)
    {
        try {
            $userId = Auth::id();
            $photographerName = Auth::user() ? Auth::user()->first_name.' '.Auth::user()->last_name : 'Photographer';

            $assignment = BookingAssignedPhotographerModel::where('id', $assignmentId)
                ->where('photographer_id', $userId)
                ->firstOrFail();

            $booking = BookingModel::with(['packages.studioPackage', 'studioOnlineGallery'])
                ->findOrFail($assignment->booking_id);

            $requiresLocationConfirmation = $booking->requiresLocationConfirmation();

            // ========== NEW: Check payment status before allowing completion ==========
            if ($request->status === 'completed') {
                $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');

                if ($totalPaid < $booking->total_amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot mark as completed because the booking is not fully paid. Remaining balance: PHP '.number_format($booking->total_amount - $totalPaid, 2),
                    ], 403);
                }

                if (! $booking->isGalleryReadyForCompletion()) {
                    return response()->json([
                        'success' => false,
                        'message' => $booking->getGalleryCompletionBlockReason(),
                    ], 403);
                }
            }
            // ========== End of payment check ==========

            // ========== Check cancellation restrictions ==========
            if ($request->status === 'cancelled') {
                // Check if cancellation is allowed at this stage
                if (! $assignment->canCancel()) {
                    $reason = $assignment->getCancellationRestrictionReason();

                    return response()->json([
                        'success' => false,
                        'message' => 'Cancellation not allowed: '.$reason,
                    ], 403);
                }
            }

            // Prevent updating if already cancelled
            if ($assignment->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment has already been cancelled.',
                ]);
            }

            $updateData = ['status' => $request->status];
            $recovery = null;

            switch ($request->status) {
                case 'confirmed':
                    $updateData['confirmed_at'] = now();

                    $replacementRecovery = $assignment->recovery_id
                        ? BookingCancellationRecoveryModel::find($assignment->recovery_id)
                        : null;
                    if ($replacementRecovery && $replacementRecovery->status === BookingCancellationRecoveryService::STATUS_REPLACEMENT_PROPOSED) {
                        app(BookingCancellationRecoveryService::class)->replacementConfirmed($replacementRecovery);
                        $recoveryUrl = route('client.booking.recovery.view', $replacementRecovery->id, false);
                        $this->createNotification(
                            $booking->client_id,
                            'photographer_replacement_ready',
                            'Photographer replacement ready',
                            'A same-studio replacement photographer is ready for your review.',
                            ['booking_id' => $booking->id, 'recovery_id' => $replacementRecovery->id, 'route' => $recoveryUrl],
                            'user-check',
                            'info'
                        );
                        $owner = $assignment->studio?->user;
                        if ($owner) {
                            $this->createNotification(
                                $owner->id,
                                'photographer_replacement_confirmed',
                                'Replacement photographer confirmed',
                                "The replacement photographer confirmed booking #{$booking->booking_reference}.",
                                ['booking_id' => $booking->id, 'recovery_id' => $replacementRecovery->id, 'route' => route('owner.booking.recovery.view', $replacementRecovery->id, false)],
                                'check-circle',
                                'success'
                            );
                        }
                        break;
                    }

                    // When photographer accepts, update main booking status to in_progress
                    if (in_array($booking->status, ['pending', 'confirmed'])) {
                        $booking->status = 'in_progress';
                        $booking->save();
                    }

                    $this->createOwnerNotification(
                        $assignment,
                        'photographer_confirmed_assignment',
                        'Photographer Accepted Assignment',
                        "Photographer {$photographerName} accepted the assignment for booking #{$booking->booking_reference}.",
                        [
                            'booking_id' => $booking->id,
                            'booking_reference' => $booking->booking_reference,
                            'assignment_id' => $assignment->id,
                            'photographer_name' => $photographerName,
                            'route' => route('owner.booking.index', [], false),
                        ],
                        'user-check',
                        'success'
                    );
                    break;

                    // On-site status
                case 'on_site':
                    // Check if photographer has confirmed first
                    if ($assignment->status !== 'confirmed') {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must confirm the assignment first before marking as on-site.',
                        ]);
                    }

                    $updateData['on_site_at'] = now();

                    // A studio booking that is still Confirmed advances to In Progress
                    // once the photographer is on site. This breaks the circular gate:
                    // the client can then confirm the photographer before work starts.
                    if ($booking->booking_type === 'studio' && $booking->status === BookingModel::STATUS_CONFIRMED) {
                        $booking->status = BookingModel::STATUS_IN_PROGRESS;
                        $booking->save();
                    }

                    // Create notification for client to confirm on-site presence
                    $this->createClientConfirmationNotification($assignment);
                    break;

                case 'in_progress':
                    // Every booking type passes through On Site and client
                    // confirmation before the photographer may start work.
                    if (! $assignment->on_site_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must mark as on-site first before starting work.',
                        ]);
                    }

                    if (! $assignment->client_confirmed_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Waiting for client to confirm your on-site presence. Please ask the client to confirm via their dashboard before starting work.',
                        ]);
                    }

                    $updateData['started_at'] = now();

                    // Make sure booking is in_progress
                    if ($booking->status !== BookingModel::STATUS_IN_PROGRESS) {
                        $booking->status = BookingModel::STATUS_IN_PROGRESS;
                        $booking->save();
                    }
                    break;

                case 'completed':
                    // Check if client has confirmed
                    if ($requiresLocationConfirmation && ! $assignment->client_confirmed_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Client must confirm your on-site presence before you can mark as completed.',
                        ]);
                    }

                    // Check if assignment is in progress first
                    if ($assignment->status !== 'in_progress') {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must mark the assignment as in progress first before completing.',
                        ]);
                    }

                    // ========== REMOVED: Duplicate payment check (moved to top) ==========

                    $updateData['completed_at'] = now();

                    // Check if ALL photographers have completed their assignments
                    $allPhotographersCompleted = true;
                    $otherAssignments = BookingAssignedPhotographerModel::where('booking_id', $assignment->booking_id)
                        ->where('id', '!=', $assignment->id)
                        ->get();

                    foreach ($otherAssignments as $other) {
                        if ($other->status !== 'completed') {
                            $allPhotographersCompleted = false;
                            break;
                        }
                    }

                    // If this is the last photographer to complete, update booking status
                    if ($allPhotographersCompleted) {
                        \Log::info('All photographers completed for booking: '.$assignment->booking_id);
                    }

                    $this->createOwnerNotification(
                        $assignment,
                        'photographer_completed_assignment',
                        'Photographer Completed Assignment',
                        "Photographer {$photographerName} completed the assignment for booking #{$booking->booking_reference}.",
                        [
                            'booking_id' => $booking->id,
                            'booking_reference' => $booking->booking_reference,
                            'assignment_id' => $assignment->id,
                            'photographer_name' => $photographerName,
                            'route' => route('owner.booking.index', [], false),
                        ],
                        'circle-check',
                        'success'
                    );
                    break;

                case 'cancelled':
                    $updateData['cancelled_at'] = now();
                    $updateData['cancellation_reason'] = $request->cancellation_reason;

                    if ($booking->booking_type === 'studio' && $booking->payments()->where('status', 'succeeded')->exists()) {
                        $recovery = app(BookingCancellationRecoveryService::class)
                            ->photographerCancelled($assignment, $request->cancellation_reason);
                        $updateData = [];
                    }

                    $this->createOwnerNotification(
                        $assignment,
                        'photographer_cancelled_assignment',
                        'Photographer Cancelled Assignment',
                        "Photographer {$photographerName} cancelled the assignment for booking #{$booking->booking_reference}. Reason: {$request->cancellation_reason}.",
                        [
                            'booking_id' => $booking->id,
                            'booking_reference' => $booking->booking_reference,
                            'assignment_id' => $assignment->id,
                            'photographer_name' => $photographerName,
                            'reason' => $request->cancellation_reason,
                            'route' => route('owner.booking.index', [], false),
                        ],
                        'calendar-x',
                        'danger'
                    );
                    break;
            }

            if ($updateData) {
                $assignment->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Assignment status updated successfully.',
                'assignment' => $assignment->fresh(),
                'recovery' => $recovery,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment status: '.$e->getMessage(),
            ], 500);
        }
    }

    // ========== Helper method to create client confirmation notification ==========
    private function createClientConfirmationNotification($assignment)
    {
        try {
            $booking = BookingModel::find($assignment->booking_id);
            $clientId = $booking->client_id;
            $photographer = Auth::user();

            $this->createNotification(
                $clientId,
                'photographer_on_site',
                'Photographer On-Site Confirmation',
                "Photographer {$photographer->first_name} {$photographer->last_name} has arrived on-site. Please confirm their presence.",
                [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'assignment_id' => $assignment->id,
                    'photographer_name' => $photographer->first_name.' '.$photographer->last_name,
                    'route' => route('client.my-bookings.index', [], false),
                ],
                'map-pin',
                'info'
            );

            \Log::info('Client confirmation notification sent', [
                'client_id' => $clientId,
                'booking_id' => $booking->id,
                'assignment_id' => $assignment->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send client confirmation notification: '.$e->getMessage());
        }
    }

    // ========== Helper method to create studio owner notification ==========
    private function createOwnerNotification($assignment, $type, $title, $message, $data, $icon, $color)
    {
        try {
            $owner = $assignment->studio?->user;

            if (! $owner) {
                return;
            }

            $this->createNotification($owner->id, $type, $title, $message, $data, $icon, $color);

            \Log::info('Owner notification sent', [
                'owner_id' => $owner->id,
                'type' => $type,
                'assignment_id' => $assignment->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to send owner notification: '.$e->getMessage());
        }
    }
}
