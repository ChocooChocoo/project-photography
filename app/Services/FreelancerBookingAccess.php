<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudioMemberModel;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FreelancerBookingAccess
{
    use Notifiable;

    public function eventEnd(BookingModel $booking): Carbon
    {
        $end = Carbon::parse(($booking->event_end_date ?? $booking->event_date)->format('Y-m-d').' '.$booking->end_time, 'Asia/Manila');
        if (! $booking->event_end_date && $booking->end_time <= $booking->start_time) {
            $end->addDay();
        }

        return $end;
    }

    public function grant(BookingAssignedPhotographerModel $assignment): void
    {
        $booking = $assignment->booking;
        abort_unless($assignment->is_freelancer && $booking->booking_type === 'studio'
            && ! in_array($booking->status, ['cancelled', 'completed'])
            && StudioMemberModel::approved()->where('studio_id', $assignment->studio_id)->where('freelancer_id', $assignment->photographer_id)->exists()
            && $this->eventEnd($booking)->isFuture(), 422, 'Freelancer membership or event window is not eligible.');
        $role = RoleModel::where('name', 'studio-freelancer')->where('portal', 'freelancer')->where('status', 'active')->firstOrFail();
        $created = DB::table('tbl_user_roles')->insertOrIgnore([
            'user_id' => $assignment->photographer_id, 'role_id' => $role->id,
            'studio_id' => $assignment->studio_id, 'booking_id' => $booking->id,
            'grant_scope' => 'booking:'.$booking->id,
            'expires_at' => $this->eventEnd($booking)->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($created) {
            $this->createNotification($assignment->photographer_id, 'freelancer_access_granted', 'Studio booking access granted',
                'Access to '.$booking->booking_reference.' ends at '.$this->eventEnd($booking)->format('M d, Y H:i').' Asia/Manila.',
                ['booking_id' => $booking->id, 'route' => '/freelancer/studio-assignments'], 'user-check', 'info');
        }
    }

    public function allows(\App\Models\UserModel $user, BookingAssignedPhotographerModel $assignment, string $action = 'view'): bool
    {
        if (! Schema::hasColumn('tbl_user_roles', 'booking_id') || $user->role !== 'freelancer' || $user->status !== 'active'
            || ! $assignment->is_freelancer || $assignment->photographer_id !== $user->id
            || in_array($assignment->status, ['cancelled', 'completed'])) {
            return false;
        }
        $booking = $assignment->booking;

        return $booking && $booking->booking_type === 'studio' && ! in_array($booking->status, ['cancelled', 'completed'])
            && $this->eventEnd($booking)->isFuture()
            && StudioMemberModel::approved()->where('studio_id', $assignment->studio_id)->where('freelancer_id', $user->id)->exists()
            && DB::table('tbl_user_roles')->where('user_id', $user->id)->where('studio_id', $assignment->studio_id)
                ->where('booking_id', $booking->id)->where('expires_at', '>', now())->exists()
            && $user->hasPermission('freelancer.studio-assignments.'.$action, $assignment->studio_id, $booking->id);
    }

    public function revoke(int $bookingId, ?int $userId = null): int
    {
        if (! Schema::hasColumn('tbl_user_roles', 'booking_id')) {
            return 0;
        }

        return DB::transaction(function () use ($bookingId, $userId) {
            $rows = DB::table('tbl_user_roles')->where('booking_id', $bookingId)
                ->when($userId, fn ($query) => $query->where('user_id', $userId))->lockForUpdate()->get();
            foreach ($rows as $row) {
                DB::table('tbl_user_roles')->where('id', $row->id)->delete();
                $this->createNotification($row->user_id, 'freelancer_access_revoked', 'Studio booking access ended',
                    'Temporary access to studio booking #'.$bookingId.' has ended.',
                    ['booking_id' => $bookingId, 'route' => '/freelancer/studio-assignments'], 'lock', 'info');
                $owner = DB::table('tbl_studios')->where('id', $row->studio_id)->value('user_id');
                if ($owner) {
                    $this->createNotification($owner, 'freelancer_access_revoked', 'Freelancer access ended',
                        'Temporary freelancer access to booking #'.$bookingId.' has ended.',
                        ['booking_id' => $bookingId, 'route' => '/owner/view/bookings'], 'lock', 'info');
                }
            }

            return $rows->count();
        });
    }

    public function revokeExpired(): int
    {
        if (! Schema::hasColumn('tbl_user_roles', 'booking_id')) {
            return 0;
        }
        $rows = DB::table('tbl_user_roles')->whereNotNull('booking_id')->where('expires_at', '<=', now())->get();
        $count = 0;
        foreach ($rows as $row) {
            $count += $this->revoke($row->booking_id, $row->user_id);
        }

        return $count;
    }
}
