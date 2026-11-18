<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\PaymentModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use App\Models\Admin\CategoriesModel;
use App\Models\Freelancer\ProfileModel;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    /**
     * Display current and upcoming bookings
     */
    public function index()
    {
        // Get the current freelancer's user ID
        $userId = Auth::id();
        
        // Get bookings for this freelancer (current and upcoming)
        $bookings = BookingModel::where('provider_id', $userId)
            ->where('booking_type', 'freelancer')
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->orderBy('event_date', 'asc')
            ->with([
                'client:id,first_name,last_name,email,mobile_number',
                'category:id,category_name',
                'packages:id,booking_id,package_name,package_price,discount_percentage,discount_amount',
                'payments:id,booking_id,amount,status'
            ])
            ->get();
        
        return view('freelancer.view-bookings', compact('bookings'));
    }

    /**
     * Get booking details for modal
     */
    public function getBookingDetails($id)
    {
        try {
            $booking = BookingModel::with([
                'client:id,first_name,last_name,email,mobile_number',
                'category:id,category_name',
                'packages:id,booking_id,package_name,package_price,discount_percentage,discount_amount,package_inclusions,duration,maximum_edited_photos,coverage_scope,materials,photo_edit_option,enhancement_amount,editing_services',
                'payments:id,booking_id,amount,status,payment_method,paid_at'
            ])->findOrFail($id);
            
            // Check if booking belongs to the freelancer
            $userId = Auth::id();
            
            if ($booking->provider_id != $userId || $booking->booking_type != 'freelancer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this booking.'
                ], 403);
            }
            
            return response()->json([
                'success' => true,
                'booking' => $booking,
                'client' => $booking->client,
                'category' => $booking->category,
                'packages' => $booking->packages,
                'payments' => $booking->payments,
                'contract' => $booking->contractSummary('freelancer'),
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:confirmed,rejected,in_progress,completed,cancelled',
                'reason' => 'required_if:status,rejected,cancelled|nullable|string|max:500'
            ]);
            
            $booking = BookingModel::findOrFail($id);
            
            // Check if booking belongs to the freelancer
            $userId = Auth::id();
            
            if ($booking->provider_id != $userId || $booking->booking_type != 'freelancer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this booking.'
                ], 403);
            }
            
            $oldStatus = $booking->status;
            $newStatus = $request->status;
            
            // Validate status transition
            $allowedTransitions = [
                'pending' => ['confirmed', 'rejected', 'cancelled'],
                'confirmed' => ['in_progress', 'cancelled'],
                'in_progress' => ['completed', 'cancelled'],
            ];
            
            if (!isset($allowedTransitions[$oldStatus]) || !in_array($newStatus, $allowedTransitions[$oldStatus])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition from ' . $oldStatus . ' to ' . $newStatus
                ], 400);
            }

            // Long-term bookings need both contract signatures before they can be confirmed
            if ($newStatus === 'confirmed' && ! $booking->canBeConfirmed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This long-term booking cannot be confirmed until both parties have signed the contract.'
                ], 400);
            }

            // ✅ NEW RULE: Cannot mark as completed if payment is not fully paid
            if ($newStatus === 'completed' && $booking->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot mark booking as completed. Payment must be fully paid first.'
                ], 400);
            }

            if ($newStatus === 'completed' && $reason = $booking->getDeliveryCompletionBlockReason()) {
                return response()->json(['success' => false, 'message' => $reason], 400);
            }
            
            $updateData = ['status' => $newStatus];
            
            // Add reason for rejection or cancellation
            if (in_array($newStatus, ['rejected', 'cancelled']) && $request->filled('reason')) {
                $updateData['cancellation_reason'] = $request->reason;
            }
            
            // Mark as completed - update completion time and open the revision window
            if ($newStatus === 'completed') {
                $updateData['completed_at'] = now();
                $updateData['revision_deadline'] = now()->addDays(7);
            }
            
            $booking->update($updateData);
            
            // If rejected or cancelled, check if refund is needed
            if (in_array($newStatus, ['rejected', 'cancelled'])) {
                $this->handleCancellationRefund($booking);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Booking status updated successfully.',
                'booking' => $booking->fresh(['client', 'category', 'packages', 'payments'])
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating booking status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
    * Update payment status
    */
    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'payment_status' => 'required|in:paid,refunded',
                'notes' => 'nullable|string|max:500'
            ]);
            
            $booking = BookingModel::findOrFail($id);
            
            // Check if booking belongs to the freelancer
            $userId = Auth::id();
            
            if ($booking->provider_id != $userId || $booking->booking_type != 'freelancer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this booking.'
                ], 403);
            }

            // Any booking with a balance left can be marked fully paid, for
            // example when the client paid the rest in cash.
            if ($request->payment_status === 'paid') {
                if (in_array($booking->payment_status, ['refunded', 'partially_refunded'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A refunded booking cannot be marked as fully paid.'
                    ], 400);
                }
                if ($booking->remaining_balance <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This booking is already fully paid.'
                    ], 400);
                }
            }

            // Can only mark as refunded if currently paid
            if ($request->payment_status === 'refunded' && $booking->payment_status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => $booking->payment_status === 'partially_paid'
                        ? 'A downpayment is non-refundable. Only fully paid bookings can be refunded.'
                        : 'Only fully paid bookings can be refunded.'
                ], 400);
            }
            
            if ($request->payment_status === 'refunded') {
                $booking->payment_status = 'refunded';
                $booking->save();
            }

            // Record the rest of the balance as the final payment
            if ($request->payment_status === 'paid') {
                $finalPayment = $booking->remaining_balance;

                if ($finalPayment > 0) {
                    $payment = PaymentModel::create([
                        'booking_id' => $booking->id,
                        'payment_reference' => PaymentModel::generatePaymentReference(),
                        'amount' => $finalPayment,
                        'payment_method' => 'manual',
                        'status' => 'succeeded',
                        'payment_details' => [
                            'type' => 'final_payment',
                            'notes' => $request->notes ?? 'Final payment - marked as fully paid',
                            'recorded_by' => $userId,
                            'recorded_at' => now()->toDateTimeString()
                        ],
                        'paid_at' => now()
                    ]);
                    
                    // Create revenue record for this payment
                    SystemRevenueModel::createForPayment($booking, $payment);
                }

                $booking->updatePaymentStatus();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Payment status updated successfully.',
                'booking' => $booking->fresh(['payments'])
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display booking history (completed/cancelled)
     */
    public function history()
    {
        // Get the current freelancer's user ID
        $userId = Auth::id();
        
        // Get completed/cancelled bookings for this freelancer
        $bookings = BookingModel::where('provider_id', $userId)
            ->where('booking_type', 'freelancer')
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('event_date', 'desc')
            ->with([
                'client:id,first_name,last_name,email,mobile_number',
                'category:id,category_name',
                'packages:id,booking_id,package_name,package_price,discount_percentage,discount_amount',
                'payments:id,booking_id,amount,status'
            ])
            ->get();
        
        return view('freelancer.booking-history', compact('bookings'));
    }

    /**
     * Handle cancellation refund logic
     */
    private function handleCancellationRefund($booking)
    {
        // Check if payments exist and need refund
        $payments = $booking->payments()->where('status', 'succeeded')->get();
        
        if ($payments->isNotEmpty()) {
            // In a real system, you would:
            // 1. Initiate refund through payment gateway
            // 2. Update payment records
            // 3. Send refund notification
            
            // For now, we'll just log it
            \Log::info('Booking cancelled/refund needed', [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'total_amount' => $booking->total_amount,
                'total_paid' => $payments->sum('amount')
            ]);
        }
    }

    /**
     * Get total paid amount for a booking
     */
    private function getTotalPaidAmount($booking)
    {
        return PaymentModel::where('booking_id', $booking->id)
            ->where('status', 'succeeded')
            ->sum('amount');
    }
}