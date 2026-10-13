<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Schema;

class FreelancerFallbackCandidates
{
    public function __construct(private PhotographerAvailabilityService $availability) {}

    public function staffIds(BookingModel $booking): array
    {
        return StudioPhotographersModel::where('studio_id', $booking->provider_id)->where('status', 'active')
            ->pluck('photographer_id')->map(fn ($id) => (int) $id)->all();
    }

    public function staffAvailable(BookingModel $booking): bool
    {
        $excluded = BookingAssignedPhotographerModel::where('booking_id', $booking->id)
            ->whereIn('status', [...BookingModel::ACTIVE_ASSIGNMENT_STATUSES, 'cancelled'])->pluck('photographer_id')->all();
        $ids = array_values(array_diff($this->staffIds($booking), $excluded));

        return collect($this->availability->getAvailabilityMapForBooking($booking, $ids))->contains('is_available', true);
    }

    public function candidates(BookingModel $booking): array
    {
        if (! Schema::hasColumn('tbl_user_roles', 'booking_id') || $this->staffAvailable($booking)) {
            return [];
        }
        $members = StudioMemberModel::approved()->where('studio_id', $booking->provider_id)->pluck('freelancer_id');
        $users = UserModel::whereIn('id', $members)->where('role', 'freelancer')->where('status', 'active')->get();
        $availability = $this->availability->getAvailabilityMapForBooking($booking, $users->pluck('id')->all());
        $excluded = BookingAssignedPhotographerModel::where('booking_id', $booking->id)
            ->whereIn('status', [...BookingModel::ACTIVE_ASSIGNMENT_STATUSES, 'cancelled'])->pluck('photographer_id')->all();

        return $users->map(function ($user) use ($availability, $excluded) {
            $state = $availability[$user->id];
            if (in_array($user->id, $excluded)) {
                $state['is_available'] = false;
                $state['availability_reason'] = 'Already assigned or rejected this assignment.';
                $state['availability_status'] = 'unavailable';
            }

            return ['id' => $user->id, 'name' => $user->first_name.' '.$user->last_name,
                'is_freelancer' => true, 'position' => 'Freelancer fallback', 'status' => 'approved',
                'years_experience' => null, 'specialization' => null] + $state;
        })->all();
    }

    public function validate(BookingModel $booking, array $ids): array
    {
        $freelancers = UserModel::whereIn('id', $ids)->where('role', 'freelancer')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (! $freelancers) {
            return [];
        }
        $eligible = collect($this->candidates($booking))->where('is_available', true)->pluck('id')->all();
        if (array_diff($freelancers, $eligible)) {
            throw new \RuntimeException(json_encode(['message' => 'Freelancer fallback requires approved membership, no available studio photographer and no booking conflict.'], JSON_THROW_ON_ERROR));
        }
        if (! app(FreelancerBookingAccess::class)->eventEnd($booking)->isFuture()) {
            throw new \RuntimeException(json_encode(['message' => 'This event has already ended.'], JSON_THROW_ON_ERROR));
        }

        return $freelancers;
    }
}
