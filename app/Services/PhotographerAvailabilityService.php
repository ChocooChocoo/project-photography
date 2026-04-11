<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\LeaveRequestModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use Illuminate\Support\Collection;

class PhotographerAvailabilityService
{
    /**
     * Assignment statuses that mean the photographer is reserved.
     *
     * @var array<int, string>
     */
    private const RESERVED_STATUSES = ['assigned', 'confirmed', 'on_site'];

    /**
     * Assignment statuses that mean the photographer is actively working.
     *
     * @var array<int, string>
     */
    private const ONGOING_STATUSES = ['in_progress'];

    /**
     * Get availability details for a collection of photographers.
     *
     * @param BookingModel $booking
     * @param array<int> $photographerIds
     * @return array<int, array<string, mixed>>
     */
    public function getAvailabilityMapForBooking(BookingModel $booking, array $photographerIds): array
    {
        if (empty($photographerIds)) {
            return [];
        }

        $photographerIds = array_values(array_unique(array_map('intval', $photographerIds)));
        $bookingDate = $booking->event_date?->toDateString();

        $approvedLeaves = LeaveRequestModel::query()
            ->whereIn('user_id', $photographerIds)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $bookingDate)
            ->whereDate('end_date', '>=', $bookingDate)
            ->get()
            ->groupBy('user_id');

        $conflictingAssignments = BookingAssignedPhotographerModel::query()
            ->with([
                'booking:id,booking_reference,event_name,event_date,start_time,end_time,status',
            ])
            ->whereIn('photographer_id', $photographerIds)
            ->where('booking_id', '!=', $booking->id)
            ->whereIn('status', array_merge(self::RESERVED_STATUSES, self::ONGOING_STATUSES))
            ->whereHas('booking', function ($query) use ($booking, $bookingDate) {
                $query->whereDate('event_date', $bookingDate)
                    ->where(function ($timeQuery) use ($booking) {
                        $timeQuery->where(function ($overlapQuery) use ($booking) {
                            $overlapQuery->whereTime('start_time', '<', $booking->end_time)
                                ->whereTime('end_time', '>', $booking->start_time);
                        });
                    });
            })
            ->get()
            ->groupBy('photographer_id');

        $availabilityMap = [];

        foreach ($photographerIds as $photographerId) {
            $availabilityMap[$photographerId] = $this->buildAvailabilityPayload(
                $booking,
                $photographerId,
                $approvedLeaves->get($photographerId, collect()),
                $conflictingAssignments->get($photographerId, collect())
            );
        }

        return $availabilityMap;
    }

    /**
     * Get availability details for a single photographer.
     *
     * @param BookingModel $booking
     * @param int $photographerId
     * @return array<string, mixed>
     */
    public function getAvailabilityForBooking(BookingModel $booking, int $photographerId): array
    {
        return $this->getAvailabilityMapForBooking($booking, [$photographerId])[$photographerId];
    }

    /**
     * Build the standardized availability payload for one photographer.
     *
     * @param BookingModel $booking
     * @param int $photographerId
     * @param Collection<int, LeaveRequestModel> $leaveConflicts
     * @param Collection<int, BookingAssignedPhotographerModel> $assignmentConflicts
     * @return array<string, mixed>
     */
    private function buildAvailabilityPayload(
        BookingModel $booking,
        int $photographerId,
        Collection $leaveConflicts,
        Collection $assignmentConflicts
    ): array {
        $conflicts = [];

        foreach ($leaveConflicts as $leaveRequest) {
            $conflicts[] = [
                'type' => 'leave',
                'status' => 'on_leave',
                'message' => 'On approved leave for ' . $leaveRequest->start_date?->format('M d, Y') . ' to ' . $leaveRequest->end_date?->format('M d, Y') . '.',
                'leave_type' => $leaveRequest->leave_type_label,
                'request_reference' => $leaveRequest->request_reference,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
            ];
        }

        foreach ($assignmentConflicts as $assignmentConflict) {
            $conflictingBooking = $assignmentConflict->booking;
            $isOngoing = in_array($assignmentConflict->status, self::ONGOING_STATUSES, true);

            $conflicts[] = [
                'type' => $isOngoing ? 'ongoing_booking' : 'reserved_assignment',
                'status' => $isOngoing ? 'in_progress_conflict' : 'reserved_conflict',
                'message' => $isOngoing
                    ? 'Already in progress on booking ' . ($conflictingBooking->booking_reference ?? 'N/A') . '.'
                    : 'Reserved for booking ' . ($conflictingBooking->booking_reference ?? 'N/A') . '.',
                'assignment_status' => $assignmentConflict->status,
                'booking_id' => $conflictingBooking->id ?? null,
                'booking_reference' => $conflictingBooking->booking_reference ?? null,
                'booking_event_name' => $conflictingBooking->event_name ?? null,
                'booking_date' => $conflictingBooking->event_date?->format('Y-m-d'),
                'booking_time' => $this->formatBookingTimeRange($conflictingBooking),
            ];
        }

        [$availabilityStatus, $availabilityReason] = $this->resolvePrimaryAvailability($conflicts);

        return [
            'photographer_id' => $photographerId,
            'booking_id' => $booking->id,
            'is_available' => empty($conflicts),
            'availability_status' => $availabilityStatus,
            'availability_reason' => $availabilityReason,
            'availability_conflicts' => $conflicts,
        ];
    }

    /**
     * Resolve the primary availability state shown to the owner.
     *
     * @param array<int, array<string, mixed>> $conflicts
     * @return array<int, string>
     */
    private function resolvePrimaryAvailability(array $conflicts): array
    {
        if (empty($conflicts)) {
            return ['available', 'Available for assignment.'];
        }

        $priorityOrder = ['on_leave', 'in_progress_conflict', 'reserved_conflict'];

        foreach ($priorityOrder as $priorityStatus) {
            foreach ($conflicts as $conflict) {
                if (($conflict['status'] ?? null) === $priorityStatus) {
                    return [$priorityStatus, (string) ($conflict['message'] ?? 'Unavailable for assignment.')];
                }
            }
        }

        return ['unavailable', 'Unavailable for assignment.'];
    }

    /**
     * Format the conflicting booking time range.
     *
     * @param BookingModel|null $booking
     * @return string|null
     */
    private function formatBookingTimeRange(?BookingModel $booking): ?string
    {
        if (!$booking || !$booking->start_time || !$booking->end_time) {
            return null;
        }

        return date('h:i A', strtotime($booking->start_time)) . ' - ' . date('h:i A', strtotime($booking->end_time));
    }
}
