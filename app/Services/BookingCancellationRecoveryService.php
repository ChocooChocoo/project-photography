<?php

namespace App\Services;

use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\SystemRevenueModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BookingCancellationRecoveryService
{
    public const STATUS_AWAITING_REPLACEMENT = 'awaiting_replacement';

    public const STATUS_REPLACEMENT_PROPOSED = 'replacement_proposed';

    public const STATUS_AWAITING_CLIENT = 'awaiting_client';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REFUND_PENDING = 'refund_pending';

    public const STATUS_REFUNDED = 'refunded';

    public function photographerCancelled(BookingAssignedPhotographerModel $assignment, ?string $reason = null): BookingCancellationRecoveryModel
    {
        return DB::transaction(function () use ($assignment, $reason) {
            $assignment = BookingAssignedPhotographerModel::query()->lockForUpdate()->findOrFail($assignment->id);
            $booking = BookingModel::query()->lockForUpdate()->findOrFail($assignment->booking_id);

            if ($booking->booking_type !== 'studio' || ! $booking->payments()->where('status', 'succeeded')->exists()) {
                $assignment->markAsCancelled($reason);
                throw new \DomainException('Only paid studio bookings can enter photographer recovery.');
            }

            if ((int) $assignment->studio_id !== (int) $booking->provider_id) {
                throw new \DomainException('Assignment studio does not match the booking studio.');
            }

            $assignment->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $existing = BookingCancellationRecoveryModel::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                $assignment->update(['recovery_id' => $existing->id]);

                return $existing;
            }

            $recovery = BookingCancellationRecoveryModel::create([
                'booking_id' => $booking->id,
                'studio_id' => $assignment->studio_id,
                'original_assignment_id' => $assignment->id,
                'status' => self::STATUS_AWAITING_REPLACEMENT,
                'deadline' => $this->deadline($booking),
                'photographer_reason' => $reason,
            ]);

            $assignment->update(['recovery_id' => $recovery->id]);

            return $recovery;
        });
    }

    public function proposeReplacement(BookingCancellationRecoveryModel $recovery, BookingAssignedPhotographerModel $replacement): BookingCancellationRecoveryModel
    {
        $expired = false;
        $result = DB::transaction(function () use ($recovery, $replacement, &$expired) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            $booking = BookingModel::query()->lockForUpdate()->findOrFail($recovery->booking_id);
            $replacement = BookingAssignedPhotographerModel::query()->lockForUpdate()->findOrFail($replacement->id);

            if (now()->greaterThanOrEqualTo($recovery->deadline)) {
                $expired = true;

                return $recovery;
            }

            if ($recovery->status !== self::STATUS_AWAITING_REPLACEMENT
                || $replacement->booking_id !== $recovery->booking_id
                || $replacement->studio_id !== $recovery->studio_id
                || $recovery->replacement_assignment_id
                || ! in_array($replacement->status, ['assigned', 'confirmed'], true)) {
                throw new \DomainException('This replacement is not available for the recovery.');
            }

            if (Schema::hasTable('tbl_studio_photographers')
                && ! \App\Models\StudioOwner\StudioPhotographersModel::query()
                    ->where('studio_id', $recovery->studio_id)
                    ->where('photographer_id', $replacement->photographer_id)
                    ->where('status', 'active')
                    ->exists()) {
                throw new \DomainException('Replacement must be an active photographer in the same studio.');
            }

            $availability = app(self::class)->availability($booking, (int) $replacement->photographer_id);
            if (! $availability['is_available']) {
                throw new \DomainException('Replacement photographer is not available for the booking time.');
            }

            $replacement->update(['recovery_id' => $recovery->id]);
            $recovery->update([
                'replacement_assignment_id' => $replacement->id,
                'status' => self::STATUS_REPLACEMENT_PROPOSED,
                'replacement_proposed_at' => now(),
            ]);

            return $recovery->fresh();
        });

        if ($expired) {
            $this->queueRefund($result, 'Photographer replacement deadline expired.');
            throw new \DomainException('The photographer replacement deadline has expired.');
        }

        return $result;
    }

    public function replacementConfirmed(BookingCancellationRecoveryModel $recovery): BookingCancellationRecoveryModel
    {
        $expired = false;
        $result = DB::transaction(function () use ($recovery, &$expired) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            if (now()->greaterThanOrEqualTo($recovery->deadline)) {
                $expired = true;

                return $recovery;
            }
            $replacement = $recovery->replacementAssignment()->lockForUpdate()->first();
            if ($recovery->status !== self::STATUS_REPLACEMENT_PROPOSED || ! $replacement) {
                throw new \DomainException('A proposed replacement is required before confirmation.');
            }

            $replacement->update(['status' => 'confirmed', 'confirmed_at' => now()]);
            $recovery->update([
                'status' => self::STATUS_AWAITING_CLIENT,
                'replacement_confirmed_at' => now(),
            ]);

            return $recovery->fresh();
        });

        if ($expired) {
            $this->queueRefund($result, 'Photographer replacement deadline expired.');
            throw new \DomainException('The photographer replacement deadline has expired.');
        }

        return $result;
    }

    public function clientRespond(BookingCancellationRecoveryModel $recovery, bool $accepted): bool
    {
        $expired = false;
        $result = DB::transaction(function () use ($recovery, $accepted, &$expired) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            if ($recovery->status !== self::STATUS_AWAITING_CLIENT) {
                throw new \DomainException('This replacement response is no longer available.');
            }
            if (now()->greaterThanOrEqualTo($recovery->deadline)) {
                $expired = true;

                return false;
            }

            if ($accepted) {
                $recovery->update(['status' => self::STATUS_ACCEPTED, 'client_responded_at' => now(), 'resolved_at' => now(), 'outcome_reason' => 'Replacement accepted by client.']);

                return true;
            }

            $recovery->update(['client_responded_at' => now()]);
            $this->queueRefund($recovery, 'Replacement rejected by client.');

            return false;
        });

        if ($expired) {
            $this->queueRefund($recovery, 'Photographer replacement deadline expired.');
            throw new \DomainException('The photographer replacement deadline has expired.');
        }

        return $result;
    }

    public function ownerEscalate(BookingCancellationRecoveryModel $recovery, ?string $reason = null, ?float $refundAmount = null): BookingCancellationRecoveryModel
    {
        DB::transaction(function () use ($recovery, $reason, $refundAmount) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);

            // A client-cancelled recovery is already in the refund queue. Here the
            // owner only records the refund target they are willing to return.
            if ($recovery->status === self::STATUS_REFUND_PENDING) {
                if ($refundAmount === null) {
                    throw new \DomainException('This recovery is already in the refund queue. Enter a refund amount to change the target.');
                }

                // Only a client-initiated cancellation leaves the refund amount to
                // the owner. A business-initiated cancellation refunds the client
                // in full and the owner cannot reduce it.
                $booking = BookingModel::query()->find($recovery->booking_id);
                if ($booking && $booking->cancelled_by !== 'client') {
                    throw new \DomainException('A business-initiated cancellation refunds the client in full and cannot be reduced.');
                }

                $this->applyRefundTarget($recovery, $refundAmount, $reason);

                return;
            }

            if (! in_array($recovery->status, [self::STATUS_AWAITING_REPLACEMENT, self::STATUS_REPLACEMENT_PROPOSED, self::STATUS_AWAITING_CLIENT], true)) {
                throw new \DomainException('This recovery can no longer be escalated.');
            }
            $this->queueRefund($recovery, now()->greaterThan($recovery->deadline)
                ? 'Photographer replacement deadline expired.'
                : ($reason ?: 'Owner escalated photographer cancellation.'));
        });

        return $recovery->fresh();
    }

    /**
     * Record the refund amount the business owner chose for a client-cancelled
     * full payment, expressed as the admin queue's refund target.
     */
    public function setRefundTarget(BookingCancellationRecoveryModel $recovery, float $amount, ?string $reason = null): BookingCancellationRecoveryModel
    {
        DB::transaction(function () use ($recovery, $amount, $reason) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            $this->applyRefundTarget($recovery, $amount, $reason);
        });

        return $recovery->fresh();
    }

    /**
     * Validate and persist an owner or admin refund target within a transaction.
     */
    private function applyRefundTarget(BookingCancellationRecoveryModel $recovery, float $amount, ?string $reason = null): void
    {
        if ($recovery->status !== self::STATUS_REFUND_PENDING) {
            throw new \DomainException('The refund target can only be set while the refund is pending.');
        }

        $booking = BookingModel::query()->lockForUpdate()->findOrFail($recovery->booking_id);
        $paidTotal = (float) PaymentModel::where('booking_id', $booking->id)
            ->where('status', 'succeeded')->sum('amount');

        $amount = round($amount, 2);
        if ($amount < 0 || $amount > $paidTotal) {
            throw new \DomainException('The refund amount must be between 0 and the total amount paid.');
        }

        $percentage = $paidTotal > 0 ? round($amount / $paidTotal * 100, 2) : 0.0;

        $update = [];
        if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_percentage')) {
            $update['refund_percentage'] = $percentage;
        }
        if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_amount')) {
            $update['refund_amount'] = $amount;
        }
        if ($reason !== null && $reason !== '') {
            $update['outcome_reason'] = $reason;
        }
        if (! empty($update)) {
            $recovery->update($update);
        }
    }

    public function escalateExpired(): int
    {
        $recoveries = BookingCancellationRecoveryModel::whereIn('status', [self::STATUS_AWAITING_REPLACEMENT, self::STATUS_REPLACEMENT_PROPOSED, self::STATUS_AWAITING_CLIENT])
            ->where('deadline', '<=', now())->get();

        foreach ($recoveries as $recovery) {
            $this->queueRefund($recovery, 'Photographer replacement deadline expired.');
        }

        return $recoveries->count();
    }

    public function queueRefund(BookingCancellationRecoveryModel $recovery, string $reason): void
    {
        DB::transaction(function () use ($recovery, $reason) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            if (in_array($recovery->status, [self::STATUS_REFUND_PENDING, self::STATUS_REFUNDED], true)) {
                return;
            }

            $booking = BookingModel::query()->lockForUpdate()->findOrFail($recovery->booking_id);
            $succeededTotal = (float) PaymentModel::where('booking_id', $booking->id)
                ->where('status', 'succeeded')->sum('amount');
            $percentage = $this->refundPercentage($booking);
            $target = round($succeededTotal * $percentage / 100, 2);

            $booking->update([
                'status' => BookingModel::STATUS_CANCELLED,
                'cancelled_by' => $booking->cancelled_by ?: 'photographer',
                'cancellation_reason' => $booking->cancellation_reason ?: 'The assigned photographer became unavailable.',
                'payment_status' => BookingModel::PAYMENT_REFUND_PENDING,
            ]);

            BookingAssignedPhotographerModel::where('booking_id', $booking->id)
                ->whereIn('status', ['assigned', 'confirmed', 'on_site', 'in_progress'])
                ->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Booking cancelled while resolving photographer availability.']);

            $recoveryUpdate = [
                'status' => self::STATUS_REFUND_PENDING,
                'resolved_at' => now(),
                'outcome_reason' => $reason,
            ];
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_percentage')) {
                $recoveryUpdate['refund_percentage'] = $percentage;
            }
            if (Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_amount')) {
                $recoveryUpdate['refund_amount'] = $target;
            }
            $recovery->update($recoveryUpdate);
        });
    }

    /**
     * Record a client-initiated cancellation and return the refund recovery, if
     * one should be queued.
     *
     * The down payment is non-refundable for every booking type, so a booking
     * that was only paid down (or not paid at all) queues nothing. A booking the
     * client paid in full enters the refund queue with no target set: the owner
     * chooses the amount on the cancellation screen and the admin queue defaults
     * to it, capped by the total paid.
     */
    public function clientCancelled(BookingModel $booking, ?string $reason = null): ?BookingCancellationRecoveryModel
    {
        return DB::transaction(function () use ($booking, $reason) {
            $booking = BookingModel::query()->lockForUpdate()->findOrFail($booking->id);

            $paidTotal = (float) PaymentModel::where('booking_id', $booking->id)
                ->where('status', 'succeeded')->sum('amount');

            $existing = BookingCancellationRecoveryModel::query()
                ->where('booking_id', $booking->id)
                ->lockForUpdate()
                ->first();

            // Down payment not refundable: no target is queued for the owner or
            // the admin queue, for any booking type including freelancer.
            if ($paidTotal <= 0 || ! $booking->isFullyPaid()) {
                return $existing;
            }

            if ($existing) {
                if (! in_array($existing->status, [self::STATUS_REFUND_PENDING, self::STATUS_REFUNDED], true)) {
                    $existing->update([
                        'status' => self::STATUS_REFUND_PENDING,
                        'resolved_at' => now(),
                        'outcome_reason' => $reason,
                    ]);
                }

                return $existing->fresh();
            }

            // No automatic split is derived from the down payment percentage. The
            // refund_amount stays unset until the owner sets the target.
            return BookingCancellationRecoveryModel::create([
                'booking_id' => $booking->id,
                'studio_id' => $booking->booking_type === 'studio' ? $booking->provider_id : null,
                'status' => self::STATUS_REFUND_PENDING,
                'resolved_at' => now(),
                'outcome_reason' => $reason,
            ]);
        });
    }

    public function completeRefund(BookingCancellationRecoveryModel $recovery, string|array $providerReferences, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($recovery, $providerReferences, $notes) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            if ($recovery->status === self::STATUS_REFUNDED) {
                return true;
            }
            if ($recovery->status !== self::STATUS_REFUND_PENDING) {
                return false;
            }

            $payments = PaymentModel::where('booking_id', $recovery->booking_id)
                ->where('status', 'succeeded')->lockForUpdate()->get();
            if ($payments->isEmpty() || ($payments->count() > 1 && ! is_array($providerReferences))) {
                return false;
            }

            $references = [];
            foreach ($payments as $payment) {
                $reference = is_array($providerReferences)
                    ? ($providerReferences[$payment->id] ?? $providerReferences[$payment->payment_reference] ?? null)
                    : $providerReferences;
                if (! $reference || ($payment->refund_reference && $payment->refund_reference !== $reference)
                    || PaymentModel::where('refund_reference', $reference)->whereKey('!=', $payment->id)->exists()) {
                    return false;
                }
                $references[$payment->id] = $reference;
            }
            if (count($references) !== count(array_unique($references))) {
                return false;
            }

            $totalPaid = round((float) $payments->sum('amount'), 2);
            $target = round((float) ($recovery->refund_amount ?? $totalPaid), 2);
            $remaining = $target;

            // Allocate the target across the succeeded payments. A payment is only
            // marked refunded when its own refunded amount covers its full value;
            // otherwise the partial amount is recorded against an honest status so
            // the paid total is not quietly zeroed out.
            foreach ($payments as $index => $payment) {
                $owed = round((float) $payment->amount, 2);
                $amount = $index === $payments->count() - 1
                    ? $remaining
                    : min($owed, $remaining);
                $amount = max(0, round($amount, 2));
                $remaining = round($remaining - $amount, 2);

                if ($amount <= 0) {
                    continue;
                }

                $fullyRefunded = $amount >= $owed - 0.001;
                $paymentUpdate = [
                    'status' => $fullyRefunded ? 'refunded' : 'partially_refunded',
                    'refund_reference' => $references[$payment->id],
                    'refund_notes' => $notes,
                    'refunded_at' => now(),
                ];
                if (Schema::hasColumn('tbl_payments', 'refunded_amount')) {
                    $paymentUpdate['refunded_amount'] = $amount;
                }
                $payment->update($paymentUpdate);

                // Revenue is only reversed to the extent the client was actually
                // refunded, so a partial payment leaves its revenue untouched.
                if ($fullyRefunded) {
                    SystemRevenueModel::where('payment_id', $payment->id)->update(['status' => 'refunded']);
                }
            }

            // The booking is only fully refunded when the whole paid total went back.
            BookingModel::whereKey($recovery->booking_id)->update([
                'payment_status' => $target >= $totalPaid - 0.001
                    ? BookingModel::PAYMENT_REFUNDED
                    : BookingModel::PAYMENT_REFUND_PENDING,
            ]);
            $recovery->update(['status' => self::STATUS_REFUNDED, 'resolved_at' => now()]);

            return true;
        });
    }

    private function refundPercentage(BookingModel $booking): float
    {
        // A business-initiated cancellation always refunds the client in full.
        if ($booking->cancelled_by !== 'client') {
            return 100.0;
        }

        // When the client cancels, the down payment is not refundable for any
        // booking type, including freelancer bookings. A booking paid in full is
        // refunded only by the amount the business owner chooses, so no automatic
        // percentage is derived here; the owner (or admin) sets the target.
        return 0.0;
    }

    private function deadline(BookingModel $booking): Carbon
    {
        $now = Carbon::now('Asia/Manila');
        $eventStart = Carbon::parse($booking->event_date->format('Y-m-d').' '.$booking->start_time, 'Asia/Manila');

        return $now->copy()->addDay()->min($eventStart->subHours(2));
    }

    private function availability(BookingModel $booking, int $photographerId): array
    {
        if (! Schema::hasTable('tbl_leave_requests')) {
            return ['is_available' => true];
        }

        return app(PhotographerAvailabilityService::class)->getAvailabilityForBooking($booking, $photographerId);
    }
}
