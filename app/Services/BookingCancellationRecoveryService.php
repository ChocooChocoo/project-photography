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

    public function ownerEscalate(BookingCancellationRecoveryModel $recovery, ?string $reason = null): BookingCancellationRecoveryModel
    {
        DB::transaction(function () use ($recovery, $reason) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            if (! in_array($recovery->status, [self::STATUS_AWAITING_REPLACEMENT, self::STATUS_REPLACEMENT_PROPOSED, self::STATUS_AWAITING_CLIENT], true)) {
                throw new \DomainException('This recovery can no longer be escalated.');
            }
            $this->queueRefund($recovery, now()->greaterThan($recovery->deadline)
                ? 'Photographer replacement deadline expired.'
                : ($reason ?: 'Owner escalated photographer cancellation.'));
        });

        return $recovery->fresh();
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
            $booking->update([
                'status' => BookingModel::STATUS_CANCELLED,
                'cancelled_by' => 'photographer',
                'cancellation_reason' => 'The assigned photographer became unavailable.',
                'payment_status' => BookingModel::PAYMENT_REFUND_PENDING,
            ]);

            BookingAssignedPhotographerModel::where('booking_id', $booking->id)
                ->whereIn('status', ['assigned', 'confirmed', 'on_site', 'in_progress'])
                ->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Booking cancelled while resolving photographer availability.']);

            $recovery->update(['status' => self::STATUS_REFUND_PENDING, 'resolved_at' => now(), 'outcome_reason' => $reason]);
        });
    }

    public function completeRefund(BookingCancellationRecoveryModel $recovery, string|array $providerReferences, ?string $notes = null): bool
    {
        if ($recovery->status === self::STATUS_REFUNDED) {
            return true;
        }
        if ($recovery->status !== self::STATUS_REFUND_PENDING) {
            return false;
        }

        return DB::transaction(function () use ($recovery, $providerReferences, $notes) {
            $recovery = BookingCancellationRecoveryModel::query()->lockForUpdate()->findOrFail($recovery->id);
            $payments = PaymentModel::where('booking_id', $recovery->booking_id)->where('status', 'succeeded')->lockForUpdate()->get();
            if ($payments->isEmpty()) {
                return false;
            }

            if ($payments->count() > 1 && ! is_array($providerReferences)) {
                return false;
            }

            $references = [];

            foreach ($payments as $payment) {
                $reference = is_array($providerReferences)
                    ? ($providerReferences[$payment->id] ?? $providerReferences[$payment->payment_reference] ?? null)
                    : $providerReferences;
                if (! $reference || ($payment->refund_reference && $payment->refund_reference !== $reference) || PaymentModel::where('refund_reference', $reference)->whereKey('!=', $payment->id)->exists()) {
                    return false;
                }
                $references[$payment->id] = $reference;
            }
            if (count($references) !== count(array_unique($references))) {
                return false;
            }

            foreach ($payments as $payment) {
                $payment->update(['status' => 'refunded', 'refund_reference' => $references[$payment->id], 'refund_notes' => $notes, 'refunded_at' => now()]);
                SystemRevenueModel::where('payment_id', $payment->id)->update(['status' => 'refunded']);
            }

            BookingModel::whereKey($recovery->booking_id)->update(['payment_status' => BookingModel::PAYMENT_REFUNDED]);
            $recovery->update(['status' => self::STATUS_REFUNDED, 'resolved_at' => now()]);

            return true;
        });
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
