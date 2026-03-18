<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StudioPhotographer\UpdateAssignmentStatusRequest;

class AssignedBookingController extends Controller
{
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
                'assigner:id,first_name,last_name'
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
                    'booking' => function($query) {
                        $query->with([
                            'client:id,first_name,last_name,email,mobile_number',
                            'category:id,category_name',
                            'packages:id,booking_id,package_name,package_price,package_inclusions,duration,maximum_edited_photos,coverage_scope',
                            'payments:id,booking_id,amount,status,payment_method,paid_at'
                        ]);
                    },
                    'studio:id,studio_name,studio_logo',
                    'assigner:id,first_name,last_name'
                ])
                ->firstOrFail();
            
            // Get the studio if not loaded through relationship
            if (!$assignment->studio && $assignment->studio_id) {
                $assignment->studio = StudiosModel::find($assignment->studio_id);
            }
            
            // Get the booking
            $booking = $assignment->booking;
            
            return response()->json([
                'success' => true,
                'assignment' => $assignment,
                'booking' => $booking,
                'studio' => $assignment->studio,
                'assigner' => $assignment->assigner
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update assignment status (confirm/in_progress/complete/cancel)
     */
    public function updateAssignmentStatus(UpdateAssignmentStatusRequest $request, $assignmentId)
    {
        try {
            $userId = Auth::id();
            
            $assignment = BookingAssignedPhotographerModel::where('id', $assignmentId)
                ->where('photographer_id', $userId)
                ->firstOrFail();
            
            // Prevent updating if already cancelled
            if ($assignment->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'This assignment has already been cancelled.'
                ]);
            }
            
            $updateData = ['status' => $request->status];
            
            switch ($request->status) {
                case 'confirmed':
                    $updateData['confirmed_at'] = now();
                    
                    // When photographer accepts, update main booking status to in_progress
                    $booking = BookingModel::find($assignment->booking_id);
                    if (in_array($booking->status, ['pending', 'confirmed'])) {
                        $booking->status = 'in_progress';
                        $booking->save();
                    }
                    break;
                
                // On-site status
                case 'on_site':
                    // Check if photographer has confirmed first
                    if ($assignment->status !== 'confirmed') {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must confirm the assignment first before marking as on-site.'
                        ]);
                    }
                    
                    $updateData['on_site_at'] = now();
                    
                    // Create notification for client to confirm on-site presence
                    $this->createClientConfirmationNotification($assignment);
                    break;
                    
                case 'in_progress':
                    // ========== UPDATED: Check if client has confirmed on-site presence ==========
                    if (!$assignment->on_site_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must mark as on-site first before starting work.'
                        ]);
                    }
                    
                    // Check if client has confirmed - this is now required before starting
                    if (!$assignment->client_confirmed_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Waiting for client to confirm your on-site presence. Please ask the client to confirm via their dashboard before starting work.'
                        ]);
                    }
                    
                    $updateData['started_at'] = now();
                    
                    // Make sure booking is in_progress
                    $booking = BookingModel::find($assignment->booking_id);
                    if ($booking->status !== 'in_progress') {
                        $booking->status = 'in_progress';
                        $booking->save();
                    }
                    break;
                    
                case 'completed':
                    // Check if client has confirmed
                    if (!$assignment->client_confirmed_at) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Client must confirm your on-site presence before you can mark as completed.'
                        ]);
                    }
                    
                    // Check if assignment is in progress first
                    if ($assignment->status !== 'in_progress') {
                        return response()->json([
                            'success' => false,
                            'message' => 'You must mark the assignment as in progress first before completing.'
                        ]);
                    }
                    
                    // Check if booking is fully paid
                    $booking = BookingModel::find($assignment->booking_id);
                    $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');
                    
                    if ($totalPaid < $booking->total_amount) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot mark as completed because the booking is not fully paid. Remaining balance: PHP ' . number_format($booking->total_amount - $totalPaid, 2)
                        ]);
                    }
                    
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
                        \Log::info('All photographers completed for booking: ' . $assignment->booking_id);
                    }
                    break;
                    
                case 'cancelled':
                    $updateData['cancelled_at'] = now();
                    $updateData['cancellation_reason'] = $request->cancellation_reason;
                    break;
            }
            
            $assignment->update($updateData);
            
            return response()->json([
                'success' => true,
                'message' => 'Assignment status updated successfully.',
                'assignment' => $assignment
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating assignment status: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========== NEW: Helper method to create client confirmation notification ==========
    private function createClientConfirmationNotification($assignment)
    {
        try {
            $booking = BookingModel::find($assignment->booking_id);
            $clientId = $booking->client_id;
            $photographer = Auth::user();
            
            // Use the existing notification system
            if (trait_exists('App\Traits\Notifiable')) {
                $notifiable = new class {
                    use \App\Traits\Notifiable;
                };
                
                $notifiable->createNotification(
                    $clientId,
                    'photographer_on_site',
                    'Photographer On-Site Confirmation',
                    "Photographer {$photographer->first_name} {$photographer->last_name} has arrived on-site. Please confirm their presence.",
                    [
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->booking_reference,
                        'assignment_id' => $assignment->id,
                        'photographer_name' => $photographer->first_name . ' ' . $photographer->last_name,
                        'route' => route('client.my-bookings.index', [], false)
                    ],
                    'map-pin',
                    'info'
                );
            }
            
            \Log::info('Client confirmation notification sent', [
                'client_id' => $clientId,
                'booking_id' => $booking->id,
                'assignment_id' => $assignment->id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to send client confirmation notification: ' . $e->getMessage());
        }
    }
}