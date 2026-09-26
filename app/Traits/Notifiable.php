<?php

namespace App\Traits;

use App\Mail\SubscriptionLifecycleMail;
use App\Models\NotificationModel;
use App\Models\StudioPlanModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

trait Notifiable
{
    /**
     * Create a notification for a user.
     *
     * @param int $userId
     * @param string $type
     * @param string $title
     * @param string $message
     * @param array|null $data
     * @param string|null $icon
     * @param string|null $color
     * @return NotificationModel|null
     */
    public function createNotification($userId, $type, $title, $message, $data = null, $icon = null, $color = null)
    {
        try {
            $data = is_array($data) ? $data : [];

            if (empty($data['route'])) {
                $fallback = $this->fallbackRouteForType($type, $userId);

                if ($fallback !== null) {
                    $data['route'] = $fallback;
                }
            }

            if ($data === []) {
                $data = null;
            }

            $notification = NotificationModel::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
                'icon' => $icon,
                'color' => $color,
            ]);
            
            \Log::info('Notification created', [
                'user_id' => $userId,
                'type' => $type,
                'notification_id' => $notification->id
            ]);
            
            return $notification;
        } catch (\Exception $e) {
            \Log::error('Failed to create notification: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback route when a notification type has no stored route.
     *
     * The value is a route name, or a role-to-route map when the recipient
     * can belong to more than one portal. An unknown type returns null so
     * the row renders with no target instead of a broken link.
     */
    private function fallbackRouteForType(string $type, ?int $userId): ?string
    {
        $routes = [
            'revision_requested' => [
                'owner' => 'owner.booking.index',
                'studio-photographer' => 'assigned.bookings',
                'freelancer' => 'freelancer.booking.index',
            ],
            'review_received' => [
                'owner' => 'owner.profile',
                'studio-photographer' => 'studio-photographer.profile',
                'freelancer' => 'freelancer.profile',
            ],
            'budget_exceeded' => 'client.budget.index',
        ];

        if (! array_key_exists($type, $routes)) {
            return null;
        }

        $candidate = $routes[$type];

        if (is_array($candidate)) {
            $role = $userId ? UserModel::find($userId)?->role : null;

            $candidate = $candidate[$role] ?? reset($candidate);
        }

        return Route::has($candidate) ? route($candidate, [], false) : null;
    }

    /**
     * Create a studio approved notification.
     *
     * @param object $studio
     * @return NotificationModel|null
     */
    public function notifyStudioApproved($studio)
    {
        return $this->createNotification(
            $studio->user_id,
            'studio_approved',
            'Studio Registration Approved',
            "Your studio '{$studio->studio_name}' has been approved and is now verified.",
            [
                'studio_id' => $studio->id,
                'studio_name' => $studio->studio_name,
                'route' => route('owner.studio.index', [], false)
            ],
            'check-circle',
            'success'
        );
    }

    /**
     * Create a studio rejected notification.
     *
     * @param object $studio
     * @param string $rejectionNote
     * @return NotificationModel|null
     */
    public function notifyStudioRejected($studio, $rejectionNote)
    {
        return $this->createNotification(
            $studio->user_id,
            'studio_rejected',
            'Studio Registration Rejected',
            "Your studio '{$studio->studio_name}' has been rejected. Reason: {$rejectionNote}",
            [
                'studio_id' => $studio->id,
                'studio_name' => $studio->studio_name,
                'rejection_note' => $rejectionNote,
                'route' => route('owner.studio.create', [], false)
            ],
            'x-circle',
            'danger'
        );
    }

    /**
     * Create a booking confirmation notification.
     *
     * @param object $booking
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyBookingConfirmed($booking, $user)
    {
        return $this->createNotification(
            $user->id,
            'booking_confirmed',
            'Booking Confirmed',
            "Your booking #{$booking->booking_reference} has been confirmed.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'calendar-check',
            'success'
        );
    }

    /**
     * Create a payment received notification.
     *
     * @param object $payment
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyPaymentReceived($payment, $user)
    {
        return $this->createNotification(
            $user->id,
            'payment_received',
            'Payment Received',
            "Payment of ₱" . number_format($payment->amount, 2) . " has been received.",
            [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'amount' => $payment->amount,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'credit-card',
            'success'
        );
    }

    /**
     * Create a booking-cancelled-by-studio notification.
     *
     * @param object $booking
     * @param object $user
     * @param string $studioName
     * @param string $reason
     * @return NotificationModel|null
     */
    public function notifyBookingCancelledByStudio($booking, $user, $studioName, $reason)
    {
        return $this->createNotification(
            $user->id,
            'booking_cancelled_by_studio',
            'Booking Cancelled',
            "Your booking #{$booking->booking_reference} has been cancelled by {$studioName}. Reason: {$reason}. Please contact us or re-book.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'reason' => $reason,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'calendar-x',
            'danger'
        );
    }

    /**
     * Create a booking-cancelled-by-client notification.
     *
     * @param object $booking
     * @param object $user
     * @param string $reason
     * @return NotificationModel|null
     */
    public function notifyBookingCancelledByClient($booking, $user, $reason)
    {
        return $this->createNotification(
            $user->id,
            'booking_cancelled_by_client',
            'Booking Cancelled',
            "Booking #{$booking->booking_reference} has been cancelled by the client. Reason: {$reason}.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'reason' => $reason,
                'route' => route('owner.booking.index', [], false)
            ],
            'calendar-x',
            'danger'
        );
    }

    /**
     * Create a booking-expired notification.
     *
     * @param object $booking
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyBookingExpired($booking, $user, string $routeName = 'client.my-bookings.index')
    {
        return $this->createNotification(
            $user->id,
            'booking_expired',
            'Booking Expired',
            "Booking #{$booking->booking_reference} was not confirmed in time and has expired.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'route' => route($routeName, [], false)
            ],
            'calendar-x',
            'warning'
        );
    }

    /**
     * Create a booking-completed notification.
     *
     * @param object $booking
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyBookingCompleted($booking, $user)
    {
        return $this->createNotification(
            $user->id,
            'booking_completed',
            'Booking Completed',
            "Your booking #{$booking->booking_reference} has been marked as completed. Thank you for booking with us!",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'circle-check',
            'success'
        );
    }

    /**
     * Create a photographer-assigned notification for the client.
     *
     * @param object $booking
     * @param object $client
     * @return NotificationModel|null
     */
    public function notifyPhotographerAssigned($booking, $client)
    {
        return $this->createNotification(
            $client->id,
            'photographer_assigned',
            'Photographer Assigned',
            "Your photographer for booking #{$booking->booking_reference} has been assigned.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'user-check',
            'info'
        );
    }

    /**
     * Create a gallery-published notification for the client.
     *
     * @param object $gallery
     * @param object $client
     * @return NotificationModel|null
     */
    public function notifyGalleryPublished($gallery, $client)
    {
        return $this->createNotification(
            $client->id,
            'gallery_published',
            'Gallery Published',
            "Your photo gallery \"{$gallery->gallery_name}\" is now available to view.",
            [
                'gallery_id' => $gallery->id,
                'route' => route('client.online-gallery.index', [], false)
            ],
            'photo',
            'success'
        );
    }

    /**
     * Create a revision-requested notification for the owner/photographer.
     *
     * @param object $booking
     * @param object $recipient
     * @return NotificationModel|null
     */
    public function notifyRevisionRequested($booking, $recipient)
    {
        return $this->createNotification(
            $recipient->id,
            'revision_requested',
            'Revision Requested',
            "The client requested a revision for booking #{$booking->booking_reference}.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
            ],
            'refresh',
            'warning'
        );
    }

    /**
     * Create a review-received notification for the provider.
     *
     * @param object $rating
     * @param object $provider
     * @return NotificationModel|null
     */
    public function notifyReviewReceived($rating, $provider)
    {
        return $this->createNotification(
            $provider->id,
            'review_received',
            'New Review Received',
            "You received a new {$rating->rating}-star review.",
            [
                'rating_id' => $rating->id,
                'rating' => $rating->rating,
            ],
            'star',
            'info'
        );
    }

    /**
     * Create an assignment-deadline-warning notification for the photographer.
     *
     * @param object $assignment
     * @param object $photographer
     * @return NotificationModel|null
     */
    public function notifyAssignmentDeadlineWarning($assignment, $photographer)
    {
        return $this->createNotification(
            $photographer->id,
            'assignment_deadline_warning',
            'Assignment Response Deadline Approaching',
            "You have a booking assignment awaiting your response before {$assignment->response_deadline->format('M d, g:i A')}.",
            [
                'assignment_id' => $assignment->id,
                'booking_id' => $assignment->booking_id,
                'route' => route('assigned.bookings', [], false)
            ],
            'clock-exclamation',
            'warning'
        );
    }

    /**
     * Create a subscription-expiring notification for the owner.
     *
     * @param object $studio
     * @param object $owner
     * @return NotificationModel|null
     */
    public function notifySubscriptionExpiring($studio, $owner)
    {
        return $this->createNotification(
            $owner->id,
            'subscription_expiring',
            'Subscription Expiring Soon',
            "The subscription for \"{$studio->studio_name}\" expires in 7 days. Renew to keep your premium features.",
            [
                'studio_id' => $studio->id,
                'route' => route('owner.subscription.index', [], false)
            ],
            'alert-triangle',
            'warning'
        );
    }

    /**
     * Create a trial-ending notification for the owner.
     *
     * @param object $studio
     * @param object $owner
     * @param int $daysLeft
     * @return NotificationModel|null
     */
    public function notifyTrialEnding($studio, $owner, $daysLeft)
    {
        return $this->createNotification(
            $owner->id,
            'trial_ending',
            'Free Trial Ending Soon',
            "Your free trial for \"{$studio->studio_name}\" ends in {$daysLeft} day(s). Add a payment method to keep your plan active.",
            [
                'studio_id' => $studio->id,
                'route' => route('owner.subscription.index', [], false)
            ],
            'clock-exclamation',
            'warning'
        );
    }

    /**
     * Notify a studio owner about one subscription lifecycle milestone.
     */
    public function notifySubscriptionLifecycle(StudioPlanModel $subscription, string $event): ?NotificationModel
    {
        $studio = $subscription->studio;
        $owner = $studio?->user;

        if (! $studio || ! $owner) {
            return null;
        }

        $deadline = str_starts_with($event, 'grace_') || $event === 'expired'
            ? $subscription->graceDeadline()
            : ($subscription->trial_ends_at ?? $subscription->end_date->copy()->endOfDay());
        $existing = NotificationModel::where('user_id', $owner->id)
            ->where('type', $this->subscriptionNotificationType($event))
            ->get()
            ->contains(fn (NotificationModel $notification) =>
                ($notification->data['subscription_id'] ?? null) === $subscription->id
                && ($notification->data['event'] ?? null) === $event
                && ($notification->data['deadline'] ?? null) === $deadline->toDateTimeString()
            );

        if ($existing) {
            return null;
        }

        [$title, $message] = $this->subscriptionNotificationContent($studio->studio_name, $event, $deadline);
        $notification = $this->createNotification(
            $owner->id,
            $this->subscriptionNotificationType($event),
            $title,
            $message,
            [
                'studio_id' => $studio->id,
                'subscription_id' => $subscription->id,
                'event' => $event,
                'deadline' => $deadline->toDateTimeString(),
                'route' => route('owner.subscription.index', [], false),
            ],
            'clock-exclamation',
            'warning'
        );

        if (! $notification) {
            return null;
        }

        try {
            Mail::to($owner->email)->send(new SubscriptionLifecycleMail(
                $subscription,
                $title,
                $message,
                $deadline
            ));
        } catch (\Throwable $exception) {
            Log::error('Subscription lifecycle email failed', [
                'subscription_id' => $subscription->id,
                'event' => $event,
                'error' => $exception->getMessage(),
            ]);
        }

        return $notification;
    }

    private function subscriptionNotificationType(string $event): string
    {
        return match (true) {
            str_starts_with($event, 'ending_') => 'subscription_ending',
            $event === 'expired' => 'subscription_expired',
            default => 'subscription_grace',
        };
    }

    private function subscriptionNotificationContent(string $studioName, string $event, $deadline): array
    {
        if (str_starts_with($event, 'ending_')) {
            $days = (int) str($event)->afterLast('_')->toString();

            return [
                'Subscription Ending Soon',
                "The subscription for \"{$studioName}\" ends in {$days} day(s), followed by a 7-day grace period.",
            ];
        }

        if ($event === 'grace_entered') {
            return [
                'Subscription Grace Period Started',
                "The subscription for \"{$studioName}\" is in grace until {$deadline->format('M d, Y g:i A')}.",
            ];
        }

        if (str_starts_with($event, 'grace_')) {
            $days = (int) str($event)->afterLast('_')->toString();

            return [
                'Subscription Grace Period Ending',
                "The grace period for \"{$studioName}\" ends in {$days} day(s).",
            ];
        }

        return [
            'Subscription Expired',
            "The subscription for \"{$studioName}\" has expired. Subscribe again to restore commercial access.",
        ];
    }

    /**
     * Create a budget-exceeded notification.
     *
     * @param object $budget
     * @param object $client
     * @return NotificationModel|null
     */
    public function notifyBudgetExceeded($budget, $client)
    {
        return $this->createNotification(
            $client->id,
            'budget_exceeded',
            'Budget Limit Reached',
            "Your spending on \"{$budget->budget_name}\" has reached your set maximum of ₱" . number_format($budget->maximum_budget, 2) . ".",
            [
                'budget_id' => $budget->id,
            ],
            'wallet',
            'warning'
        );
    }

    /**
     * Create a new message notification.
     *
     * @param object $message
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyNewMessage($message, $user)
    {
        return $this->createNotification(
            $user->id,
            'new_message',
            'New Message',
            "You have a new message from {$message->sender->full_name}",
            [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender->full_name,
                'route' => route('messages.show', $message->conversation_id, false)
            ],
            'message-circle',
            'primary'
        );
    }

    /**
     * Create a payment failed notification.
     *
     * @param object $payment
     * @param object $user
     * @return NotificationModel|null
     */
    public function notifyPaymentFailed($payment, $user)
    {
        return $this->createNotification(
            $user->id,
            'payment_failed',
            'Payment Failed',
            "Your payment of ₱" . number_format($payment->amount, 2) . " could not be processed. Please try again.",
            [
                'payment_id' => $payment->id,
                'payment_reference' => $payment->payment_reference,
                'amount' => $payment->amount,
                'route' => route('client.my-bookings.index', [], false)
            ],
            'credit-card-off',
            'danger'
        );
    }

    /**
     * Create a reminder notification.
     *
     * @param object $booking
     * @param object $user
     * @param string $days
     * @return NotificationModel|null
     */
    public function notifyReminder($booking, $user, $days)
    {
        return $this->createNotification(
            $user->id,
            'reminder',
            'Upcoming Booking Reminder',
            "Your booking on {$booking->event_date->format('F d, Y')} is in {$days} days.",
            [
                'booking_id' => $booking->id,
                'booking_reference' => $booking->booking_reference,
                'event_date' => $booking->event_date->format('Y-m-d'),
                'route' => route('client.my-bookings.index', [], false)
            ],
            'bell',
            'warning'
        );
    }
}
