<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\BookingRescheduleRequestModel;
use App\Models\Freelancer\FreelancerScheduleModel;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\UserModel;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rescheduling requests: the client asks for a new date or time, and the
 * studio owner or freelancer approves, rejects or offers other slots. Every
 * slot is checked against the provider's other bookings, its operating days
 * and the photographers already assigned to the booking.
 */
class BookingRescheduleService
{
    use Notifiable;

    public function __construct(private PhotographerAvailabilityService $photographers) {}

    public function request(BookingModel $booking, UserModel $client, array $slot, ?string $reason): BookingRescheduleRequestModel
    {
        return DB::transaction(function () use ($booking, $client, $slot, $reason) {
            $booking = BookingModel::lockForUpdate()->findOrFail($booking->id);
            $this->assertReschedulable($booking);
            if (BookingRescheduleRequestModel::where('booking_id', $booking->id)->whereIn('status', BookingRescheduleRequestModel::OPEN_STATUSES)->exists()) {
                throw new \DomainException('This booking already has a reschedule request waiting for an answer.');
            }

            $request = BookingRescheduleRequestModel::create([
                'booking_id' => $booking->id,
                'requested_by' => $client->id,
                'requested_date' => $slot['date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'reason' => $reason,
                'status' => 'pending',
            ]);

            $this->createNotification($this->providerUserId($booking), 'reschedule_requested', 'Reschedule requested',
                "The client asked to move booking {$booking->booking_reference} to ".$this->label($slot).'.',
                ['booking_id' => $booking->id, 'reschedule_id' => $request->id], 'calendar-event', 'warning');

            return $request;
        });
    }

    public function approve(BookingRescheduleRequestModel $request, UserModel $provider): void
    {
        DB::transaction(function () use ($request, $provider) {
            [$request, $booking] = $this->lockOpen($request, ['pending']);
            $slot = ['date' => $request->requested_date->toDateString(), 'start_time' => $request->start_time, 'end_time' => $request->end_time];
            $this->moveBooking($booking, $slot);
            $request->update(['status' => 'approved', 'responded_by' => $provider->id, 'responded_at' => now()]);

            $this->createNotification($booking->client_id, 'reschedule_approved', 'Reschedule approved',
                "Booking {$booking->booking_reference} now takes place on ".$this->label($slot).'.',
                ['booking_id' => $booking->id, 'reschedule_id' => $request->id], 'calendar-check', 'success');
        });
    }

    public function reject(BookingRescheduleRequestModel $request, UserModel $provider, ?string $note): void
    {
        DB::transaction(function () use ($request, $provider, $note) {
            [$request, $booking] = $this->lockOpen($request, ['pending']);
            $request->update(['status' => 'rejected', 'response_note' => $note, 'responded_by' => $provider->id, 'responded_at' => now()]);

            $this->createNotification($booking->client_id, 'reschedule_rejected', 'Reschedule declined',
                "Booking {$booking->booking_reference} stays on its original date.".($note ? ' '.$note : ''),
                ['booking_id' => $booking->id, 'reschedule_id' => $request->id], 'calendar-x', 'danger');
        });
    }

    /**
     * @param  array<int, array{date: string, start_time: string, end_time: string}>  $alternatives
     */
    public function offerAlternatives(BookingRescheduleRequestModel $request, UserModel $provider, array $alternatives, ?string $note): void
    {
        DB::transaction(function () use ($request, $provider, $alternatives, $note) {
            [$request, $booking] = $this->lockOpen($request, ['pending']);
            foreach ($alternatives as $slot) {
                $this->assertAvailable($booking, $slot);
            }
            $request->update(['status' => 'alternatives_offered', 'alternatives' => array_values($alternatives), 'response_note' => $note, 'responded_by' => $provider->id, 'responded_at' => now()]);

            $this->createNotification($booking->client_id, 'reschedule_alternatives', 'Other dates offered',
                "Your requested time for booking {$booking->booking_reference} is not available. Choose one of: "
                    .implode('; ', array_map(fn ($slot) => $this->label($slot), $alternatives)).'.'.($note ? ' '.$note : ''),
                ['booking_id' => $booking->id, 'reschedule_id' => $request->id, 'alternatives' => array_values($alternatives)], 'calendar-event', 'info');
        });
    }

    public function acceptAlternative(BookingRescheduleRequestModel $request, int $index): void
    {
        DB::transaction(function () use ($request, $index) {
            [$request, $booking] = $this->lockOpen($request, ['alternatives_offered']);
            $slot = $request->alternatives[$index] ?? throw new \DomainException('Choose one of the offered dates.');
            $this->moveBooking($booking, $slot);
            $request->update(['status' => 'accepted', 'requested_date' => $slot['date'], 'start_time' => $slot['start_time'], 'end_time' => $slot['end_time']]);

            $this->createNotification($this->providerUserId($booking), 'reschedule_accepted', 'Offered date accepted',
                'The client chose '.$this->label($slot)." for booking {$booking->booking_reference}.",
                ['booking_id' => $booking->id, 'reschedule_id' => $request->id], 'calendar-check', 'success');
        });
    }

    /**
     * Throws a DomainException with a plain message when the slot cannot be used.
     */
    public function assertAvailable(BookingModel $booking, array $slot): void
    {
        $date = Carbon::parse($slot['date']);
        if ($date->lte(today())) {
            throw new \DomainException('Choose a date after today.');
        }
        if ($slot['end_time'] <= $slot['start_time']) {
            throw new \DomainException('The end time must be after the start time.');
        }

        $days = $this->operatingDays($booking);
        if ($days && ! in_array(strtolower($date->format('l')), $days, true)) {
            throw new \DomainException(($booking->booking_type === 'studio' ? 'The studio' : 'The photographer').' does not work on '.$date->format('l').'s.');
        }

        if ($booking->booking_type === 'studio' && $booking->location_type === 'in-studio'
            && $message = StudioScheduleModel::where('studio_id', $booking->provider_id)->first()?->outsideHoursMessage($slot['start_time'], $slot['end_time'])) {
            throw new \DomainException($message);
        }

        if (BookingModel::timeslotTaken($booking->booking_type, $booking->provider_id, $slot['date'], $slot['start_time'], $slot['end_time'], null, $booking->id)) {
            throw new \DomainException('Another booking already uses '.$this->label($slot).'.');
        }

        $photographerIds = $booking->assignedPhotographers()->where('status', '!=', 'cancelled')->pluck('photographer_id')->all();
        if ($photographerIds) {
            $probe = $booking->replicate();
            $probe->id = $booking->id;
            $probe->event_date = $slot['date'];
            $probe->event_end_date = null;
            $probe->start_time = $slot['start_time'];
            $probe->end_time = $slot['end_time'];
            foreach ($this->photographers->getAvailabilityMapForBooking($probe, $photographerIds) as $id => $availability) {
                if (! ($availability['is_available'] ?? true)) {
                    throw new \DomainException((UserModel::find($id)?->full_name ?? 'An assigned photographer').' is not available at the new time.');
                }
            }
        }
    }

    public function label(array $slot): string
    {
        $date = Carbon::parse($slot['date']);

        return $date->format('M j, Y').' '.Carbon::parse($slot['start_time'])->format('g:i A').' to '.Carbon::parse($slot['end_time'])->format('g:i A');
    }

    private function moveBooking(BookingModel $booking, array $slot): void
    {
        $this->assertReschedulable($booking);
        $this->assertAvailable($booking, $slot);
        $booking->update(['event_date' => $slot['date'], 'event_end_date' => null, 'start_time' => $slot['start_time'], 'end_time' => $slot['end_time']]);

        foreach ($booking->assignedPhotographers()->where('status', '!=', 'cancelled')->pluck('photographer_id') as $photographerId) {
            $this->createNotification($photographerId, 'booking_rescheduled', 'Booking moved',
                "Booking {$booking->booking_reference} now takes place on ".$this->label($slot).'.',
                ['booking_id' => $booking->id], 'calendar-event', 'info');
        }
    }

    private function assertReschedulable(BookingModel $booking): void
    {
        if (! in_array($booking->status, [BookingModel::STATUS_PENDING, BookingModel::STATUS_CONFIRMED], true)) {
            throw new \DomainException('Only pending or confirmed bookings can be rescheduled.');
        }
    }

    /**
     * @return array{0: BookingRescheduleRequestModel, 1: BookingModel}
     */
    private function lockOpen(BookingRescheduleRequestModel $request, array $statuses): array
    {
        $request = BookingRescheduleRequestModel::lockForUpdate()->findOrFail($request->id);
        if (! in_array($request->status, $statuses, true)) {
            throw new \DomainException('This reschedule request has already been answered.');
        }

        return [$request, BookingModel::lockForUpdate()->findOrFail($request->booking_id)];
    }

    /**
     * @return array<int, string>
     */
    private function operatingDays(BookingModel $booking): array
    {
        $days = $booking->booking_type === 'studio'
            ? StudioScheduleModel::where('studio_id', $booking->provider_id)->value('operating_days')
            : FreelancerScheduleModel::where('user_id', $booking->provider_id)->value('operating_days');
        // Some rows hold the list as a JSON string inside the JSON column.
        while (is_string($days)) {
            $decoded = json_decode($days, true);
            if ($decoded === null) {
                break;
            }
            $days = $decoded;
        }

        return is_array($days) ? array_map('strtolower', $days) : [];
    }

    private function providerUserId(BookingModel $booking): ?int
    {
        return $booking->booking_type === 'studio'
            ? \App\Models\StudioOwner\StudiosModel::whereKey($booking->provider_id)->value('user_id')
            : (int) $booking->provider_id;
    }
}
