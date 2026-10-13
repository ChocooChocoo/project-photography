<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\ConversationModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;

class ChatAccess
{
    public function studios(UserModel $user): array
    {
        return match ($user->role) {
            'owner' => StudiosModel::where('user_id', $user->id)->pluck('id')->all(),
            'studio-photographer' => StudioPhotographersModel::where('photographer_id', $user->id)->where('status', 'active')->pluck('studio_id')->all(),
            'studio-hr', 'studio-finance' => $user->getAssignedStudioIds()->all(),
            default => [],
        };
    }

    public function participant(UserModel $user, BookingModel $booking): bool
    {
        if ($user->status !== 'active') {
            return false;
        }
        if ($booking->client_id === $user->id) {
            return true;
        }
        if ($booking->booking_type === 'freelancer') {
            return $user->role === 'freelancer' && $booking->provider_id === $user->id;
        }
        if ($booking->booking_type !== 'studio') {
            return false;
        }
        if ($user->role === 'owner') {
            return in_array($booking->provider_id, $this->studios($user));
        }
        if ($user->role === 'freelancer') {
            $assignment = BookingAssignedPhotographerModel::where('booking_id', $booking->id)->where('photographer_id', $user->id)->first();

            return $assignment && app(FreelancerBookingAccess::class)->allows($user, $assignment);
        }
        if (! in_array($booking->provider_id, $this->studios($user))) {
            return false;
        }
        if ($user->role === 'studio-photographer') {
            return BookingAssignedPhotographerModel::where('booking_id', $booking->id)->where('photographer_id', $user->id)->where('status', '!=', 'cancelled')->exists();
        }

        return collect(['owner.bookings.manage', $user->role.'.bookings.view', $user->role.'.bookings.manage'])
            ->contains(fn ($permission) => $user->hasPermission($permission, $booking->provider_id));
    }

    public function canTalk(UserModel $one, UserModel $two, ?BookingModel $booking): bool
    {
        if ($one->id === $two->id || $one->status !== 'active' || $two->status !== 'active') {
            return false;
        }
        if ($booking) {
            return $this->participant($one, $booking) && $this->participant($two, $booking);
        }

        return count(array_intersect($this->studios($one), $this->studios($two))) > 0;
    }

    public function canRead(UserModel $user, ConversationModel $conversation): bool
    {
        if (! in_array($user->id, [$conversation->user_one_id, $conversation->user_two_id], true) || $user->status !== 'active') {
            return false;
        }
        if ($conversation->booking_id) {
            return $conversation->booking && $this->participant($user, $conversation->booking);
        }
        $other = $conversation->otherUser($user->id);

        return $other && $this->canTalk($user, $other, null);
    }

    public function canSend(UserModel $user, ConversationModel $conversation): bool
    {
        $other = $conversation->otherUser($user->id);

        return $this->canRead($user, $conversation) && $other && $this->canTalk($user, $other, $conversation->booking);
    }

    public function contacts(UserModel $user, ?BookingModel $booking)
    {
        if ($booking) {
            abort_unless($this->participant($user, $booking), 403);
        }
        // Return only eligible names; never expose a global directory or contact details.
        $ids = [];
        if ($booking) {
            $ids[] = $booking->client_id;
            if ($booking->booking_type === 'freelancer') {
                $ids[] = $booking->provider_id;
            } else {
                $ids[] = StudiosModel::find($booking->provider_id)?->user_id;
                $ids = [...$ids, ...BookingAssignedPhotographerModel::where('booking_id', $booking->id)->pluck('photographer_id')->all()];
                $ids = [...$ids, ...\Illuminate\Support\Facades\DB::table('tbl_user_roles')->where('studio_id', $booking->provider_id)->pluck('user_id')->all()];
            }
        } else {
            $studios = $this->studios($user);
            $ids = StudiosModel::whereIn('id', $studios)->pluck('user_id')->all();
            $ids = [...$ids, ...StudioPhotographersModel::whereIn('studio_id', $studios)->where('status', 'active')->pluck('photographer_id')->all()];
            $ids = [...$ids, ...\Illuminate\Support\Facades\DB::table('tbl_user_roles')->whereIn('studio_id', $studios)->pluck('user_id')->all()];
        }

        return UserModel::whereIn('id', array_filter($ids))->where('status', 'active')->get()
            ->filter(fn ($other) => $this->canTalk($user, $other, $booking))
            ->map(fn ($other) => ['id' => $other->id, 'name' => $other->full_name])->values();
    }
}
