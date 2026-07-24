<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\Freelancer\FreelanceOnlineGalleryModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MyBookingsController extends Controller
{
    use Notifiable;

    /**
     * Display current bookings (pending, confirmed, in_progress)
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $bookings = BookingModel::where('client_id', $userId)
            ->whereIn('status', ['pending', 'confirmed', 'in_progress'])
            ->orderBy('event_date', 'asc')
            ->with([
                'category:id,category_name',
                'packages:id,booking_id,package_name,package_price',
                'payments:id,booking_id,amount,status',
            ])
            ->paginate(10);

        $this->hydrateBookingProviders($bookings, true);

        return view('client.view-my-bookings', compact('bookings'));
    }

    /**
     * Display booking history (completed, cancelled)
     */
    public function history(Request $request)
    {
        $userId = Auth::id();

        $bookings = BookingModel::where('client_id', $userId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderBy('event_date', 'desc')
            ->with([
                'category:id,category_name',
                'packages:id,booking_id,package_name,package_price',
                'payments:id,booking_id,amount,status',
            ])
            ->paginate(10);

        $this->hydrateBookingProviders($bookings);

        return view('client.view-booking-history', compact('bookings'));
    }

    /**
     * Hydrate provider details for paginated bookings in batches.
     */
    private function hydrateBookingProviders(LengthAwarePaginator $bookings, bool $includePaymentDisplay = false): void
    {
        $bookingCollection = $bookings->getCollection();
        $studioIds = $bookingCollection->where('booking_type', 'studio')->pluck('provider_id')->filter()->unique()->values();
        $freelancerIds = $bookingCollection->where('booking_type', '!=', 'studio')->pluck('provider_id')->filter()->unique()->values();

        $studios = StudiosModel::whereIn('id', $studioIds)
            ->select('id', 'studio_name', 'studio_logo', 'downpayment_percentage')
            ->get()
            ->keyBy('id');

        $freelancers = ProfileModel::whereIn('user_id', $freelancerIds)
            ->select('user_id', 'brand_name', 'brand_logo', 'deposit_policy', 'deposit_type', 'deposit_amount')
            ->get()
            ->keyBy('user_id');

        $bookings->setCollection(
            $bookingCollection->map(function ($booking) use ($studios, $freelancers, $includePaymentDisplay) {
                if ($booking->booking_type === 'studio') {
                    $studio = $studios->get($booking->provider_id);
                    $booking->provider = $studio;
                    $booking->downpayment_percentage = $studio->downpayment_percentage ?? 30;

                    return $booking;
                }

                $freelancer = $freelancers->get($booking->provider_id);
                $booking->provider = $freelancer;

                if (!$includePaymentDisplay) {
                    return $booking;
                }

                if ($freelancer && $freelancer->deposit_policy === 'required') {
                    if ($freelancer->deposit_type === 'percentage') {
                        $booking->downpayment_percentage = $freelancer->deposit_amount ?? 30;
                        $booking->payment_display = $booking->downpayment_percentage . '% Downpayment';
                    } elseif ($freelancer->deposit_type === 'fixed') {
                        $booking->downpayment_percentage = 0;
                        $booking->payment_display = 'Fixed: PHP ' . number_format($freelancer->deposit_amount, 2);
                    } else {
                        $booking->downpayment_percentage = 30;
                        $booking->payment_display = '30% Downpayment';
                    }
                } else {
                    $booking->downpayment_percentage = 100;
                    $booking->payment_display = 'Full Payment';
                }

                return $booking;
            })
        );
    }

    /**
     * Get booking details for modal
     */
    public function getBookingDetails($id)
    {
        try {
            $userId = Auth::id();

            $booking = BookingModel::where('client_id', $userId)
                ->with([
                    'category:id,category_name',
                    'packages:id,booking_id,package_name,package_price,package_inclusions,duration,maximum_edited_photos,coverage_scope',
                    'payments:id,booking_id,amount,status,payment_method,paid_at,payment_reference',
                    'assignedPhotographers.photographer:id,first_name,last_name',
                    'assignedPhotographers.studioPhotographer:id,photographer_id,position,specialization,years_of_experience',
                ])
                ->findOrFail($id);

            $provider = null;
            $providerType = null;
            $downpaymentPercentage = 30;

            $depositPolicy = 'required';
            $depositType = 'percentage';
            $depositAmount = 30;
            $depositDisplay = '30% Downpayment';

            if ($booking->booking_type === 'studio') {
                $provider = StudiosModel::where('id', $booking->provider_id)
                    ->select('id', 'studio_name', 'studio_logo', 'contact_number', 'studio_email', 'starting_price', 'downpayment_percentage')
                    ->first();
                $providerType = 'studio';

                if ($provider && $provider->downpayment_percentage) {
                    $downpaymentPercentage = $provider->downpayment_percentage;
                }

                $depositType = 'percentage';
                $depositAmount = $downpaymentPercentage;
                $depositDisplay = $downpaymentPercentage . '% Downpayment';
            } else {
                $provider = ProfileModel::where('user_id', $booking->provider_id)
                    ->select(
                        'user_id as id',
                        'brand_name',
                        'brand_logo',
                        'starting_price',
                        'deposit_policy',
                        'deposit_type',
                        'deposit_amount'
                    )
                    ->first();
                $providerType = 'freelancer';

                if ($provider) {
                    $user = \App\Models\UserModel::where('id', $booking->provider_id)
                        ->select('id', 'email', 'mobile_number')
                        ->first();
                    $provider->contact_email = $user->email ?? null;
                    $provider->contact_number = $user->mobile_number ?? null;
                }

                if ($provider) {
                    $depositPolicy = $provider->deposit_policy ?? 'not_required';

                    if ($depositPolicy === 'required') {
                        $depositType = $provider->deposit_type ?? 'percentage';
                        $depositAmount = $provider->deposit_amount ?? 30;

                        if ($depositType === 'percentage') {
                            $downpaymentPercentage = $depositAmount;
                            $depositDisplay = $depositAmount . '% Downpayment';
                        } else {
                            $downpaymentPercentage = 0;
                            $depositDisplay = 'Fixed Deposit: PHP ' . number_format($depositAmount, 2);
                        }
                    } else {
                        $depositPolicy = 'not_required';
                        $depositType = null;
                        $depositAmount = 0;
                        $downpaymentPercentage = 100;
                        $depositDisplay = 'Full Payment (No Deposit)';
                    }
                } else {
                    $depositPolicy = 'required';
                    $depositType = 'percentage';
                    $depositAmount = 30;
                    $downpaymentPercentage = 30;
                    $depositDisplay = '30% Downpayment';
                }
            }

            $totalPaid = $booking->payments->where('status', 'succeeded')->sum('amount');
            $remainingBalance = $booking->total_amount - $totalPaid;

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'provider' => $provider,
                'provider_type' => $providerType,
                'category' => $booking->category,
                'packages' => $booking->packages,
                'payments' => $booking->payments,
                'assignedPhotographers' => $booking->assignedPhotographers,
                'downpayment_percentage' => $downpaymentPercentage,
                'deposit_info' => [
                    'policy' => $depositPolicy,
                    'type' => $depositType,
                    'amount' => $depositAmount,
                    'display' => $depositDisplay,
                    'is_percentage' => $depositType === 'percentage',
                    'is_fixed' => $depositType === 'fixed',
                    'is_no_deposit' => $depositPolicy === 'not_required',
                ],
                'payment_summary' => [
                    'total_amount' => $booking->total_amount,
                    'down_payment' => $booking->down_payment,
                    'total_paid' => $totalPaid,
                    'remaining_balance' => $remainingBalance,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching booking details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel booking
     */
    public function cancelBooking($id)
    {
        try {
            $userId = Auth::id();

            $booking = BookingModel::where('client_id', $userId)
                ->where('id', $id)
                ->where('status', 'pending')
                ->firstOrFail();

            $eventDate = Carbon::parse($booking->event_date);
            $now = Carbon::now();

            if ($now->diffInHours($eventDate, false) < 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bookings can only be cancelled at least 24 hours before the event date.',
                ]);
            }

            $booking->update([
                'status' => 'cancelled',
                'payment_status' => 'cancelled',
                'cancelled_by' => 'client',
            ]);

            PaymentModel::where('booking_id', $id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Request a revision on a completed booking's gallery.
     */
    public function requestRevision(Request $request, $id)
    {
        try {
            $request->validate([
                'note' => 'nullable|string|max:1000',
            ]);

            $userId = Auth::id();

            $booking = BookingModel::where('client_id', $userId)
                ->where('id', $id)
                ->where('status', 'completed')
                ->firstOrFail();

            if (!$booking->canRequestRevision()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking is no longer eligible for a revision request.',
                ], 403);
            }

            $booking->update(['revision_requested_at' => now()]);

            $recipients = [];

            if ($booking->booking_type === 'studio') {
                $gallery = StudioOnlineGalleryModel::where('booking_id', $id)->first();
                if ($gallery && $gallery->gallery_status === 'published') {
                    $gallery->update(['gallery_status' => 'draft']);
                }

                $studio = StudiosModel::find($booking->provider_id);
                if ($studio && $studio->user) {
                    $recipients[] = $studio->user;
                }
            } elseif ($booking->booking_type === 'freelancer') {
                $gallery = FreelanceOnlineGalleryModel::where('booking_id', $id)->first();
                if ($gallery && $gallery->gallery_status === 'published') {
                    $gallery->update(['gallery_status' => 'draft']);
                }
            }

            $assignedPhotographers = BookingAssignedPhotographerModel::where('booking_id', $id)
                ->with('photographer')
                ->get();

            foreach ($assignedPhotographers as $assignment) {
                if ($assignment->photographer) {
                    $recipients[] = $assignment->photographer;
                }
            }

            if ($booking->booking_type === 'freelancer') {
                $freelancerUser = UserModel::find($booking->provider_id);
                if ($freelancerUser) {
                    $recipients[] = $freelancerUser;
                }
            }

            foreach ($recipients as $recipient) {
                $this->notifyRevisionRequested($booking, $recipient);
            }

            return response()->json([
                'success' => true,
                'message' => 'Your revision request has been sent.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error requesting revision: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment details for remaining balance
     */
    public function getPaymentDetails($id)
    {
        try {
            $userId = Auth::id();

            $booking = BookingModel::where('client_id', $userId)
                ->where('id', $id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->whereIn('payment_status', ['pending', 'partially_paid'])
                ->firstOrFail();

            $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');
            $remainingBalance = $booking->total_amount - $totalPaid;

            $pendingPayment = $booking->payments()
                ->where('status', 'pending')
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->booking_reference,
                    'total_amount' => $booking->total_amount,
                    'total_paid' => $totalPaid,
                    'remaining_balance' => $remainingBalance,
                    'payment_status' => $booking->payment_status,
                    'booking_status' => $booking->status,
                ],
                'has_pending_payment' => $pendingPayment ? true : false,
                'pending_payment_id' => $pendingPayment ? $pendingPayment->id : null,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize payment for remaining balance
     */
    public function initializeBalancePayment(Request $request, $id)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
            ]);

            $userId = Auth::id();

            $booking = BookingModel::where('client_id', $userId)
                ->where('id', $id)
                ->whereIn('status', ['confirmed', 'in_progress'])
                ->whereIn('payment_status', ['pending', 'partially_paid'])
                ->firstOrFail();

            $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');
            $remainingBalance = $booking->total_amount - $totalPaid;

            if ($request->amount > $remainingBalance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed remaining balance of PHP ' . number_format($remainingBalance, 2),
                ]);
            }

            if ($request->amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount must be greater than zero',
                ]);
            }

            $existingPending = $booking->payments()
                ->where('status', 'pending')
                ->latest()
                ->first();

            if ($existingPending) {
                $payment = $existingPending;
            } else {
                $payment = PaymentModel::create([
                    'booking_id' => $booking->id,
                    'payment_reference' => PaymentModel::generatePaymentReference(),
                    'amount' => $request->amount,
                    'payment_method' => 'pending',
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'success' => true,
                'payment' => $payment,
                'booking_reference' => $booking->booking_reference,
                'amount' => $payment->amount,
                'booking_id' => $booking->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error initializing payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Client confirms photographer on-site presence.
     */
    public function confirmPhotographerOnSite(Request $request, $assignmentId)
    {
        try {
            $userId = Auth::id();

            $assignment = BookingAssignedPhotographerModel::where('id', $assignmentId)
                ->with('booking')
                ->firstOrFail();

            if ($assignment->booking->client_id !== $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this assignment.',
                ], 403);
            }

            if (!$assignment->booking->requiresLocationConfirmation()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Arrival confirmation is only required for on-location bookings.',
                ], 403);
            }

            if (!$assignment->on_site_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Photographer has not marked as on-site yet.',
                ]);
            }

            if ($assignment->client_confirmed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already confirmed this photographer\'s presence.',
                ]);
            }

            $request->validate([
                'confirmation_notes' => 'nullable|string|max:500',
            ]);

            $assignment->update([
                'client_confirmed_at' => now(),
                'client_confirmation_notes' => $request->confirmation_notes,
            ]);

            $this->notifyPhotographerConfirmed($assignment);

            return response()->json([
                'success' => true,
                'message' => 'Photographer on-site presence confirmed successfully. The photographer can now begin working.',
                'assignment' => $assignment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error confirming photographer: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assignments pending client confirmation.
     */
    public function getPendingConfirmations()
    {
        try {
            $userId = Auth::id();

            $pendingConfirmations = BookingAssignedPhotographerModel::whereHas('booking', function ($query) use ($userId) {
                $query->where('client_id', $userId)
                    ->where('location_type', 'on-location')
                    ->whereIn('status', ['confirmed', 'in_progress']);
            })
                ->whereNotNull('on_site_at')
                ->whereNull('client_confirmed_at')
                ->with([
                    'booking:id,booking_reference,event_name,event_date,start_time,end_time,location_type',
                    'photographer:id,first_name,last_name,profile_photo',
                    'studio:id,studio_name',
                ])
                ->get();

            return response()->json([
                'success' => true,
                'pending_confirmations' => $pendingConfirmations,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching pending confirmations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Notify photographer that client confirmed.
     */
    private function notifyPhotographerConfirmed($assignment): void
    {
        try {
            $photographerId = $assignment->photographer_id;
            $booking = $assignment->booking;
            $client = Auth::user();

            if (trait_exists('App\Traits\Notifiable')) {
                $notifiable = new class {
                    use \App\Traits\Notifiable;
                };

                $notifiable->createNotification(
                    $photographerId,
                    'client_confirmed_on_site',
                    'Client Confirmed Your Presence',
                    "Client {$client->first_name} {$client->last_name} has confirmed your on-site presence for booking {$booking->booking_reference}. You may now begin working by marking as 'In Progress'.",
                    [
                        'booking_id' => $booking->id,
                        'booking_reference' => $booking->booking_reference,
                        'assignment_id' => $assignment->id,
                        'client_name' => $client->first_name . ' ' . $client->last_name,
                        'route' => route('assigned.bookings', [], false),
                    ],
                    'user-check',
                    'success'
                );
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify photographer of client confirmation: ' . $e->getMessage());
        }
    }
}
