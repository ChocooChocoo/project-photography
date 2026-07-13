<?php

namespace App\Traits;

use App\Models\NotificationModel;

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
            $notification = NotificationModel::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data ? json_encode($data) : null,
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