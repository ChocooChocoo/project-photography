<?php

namespace App\Services;

use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudioPhotographersModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;

/**
 * Staff cap per studio plan. Staff = active users who are studio employees,
 * studio photographers, or approved freelancer members. Null limit = unlimited.
 */
class StudioStaffLimit
{
    /** @return array{used:int,limit:?int,remaining:?int,allowed:bool,plan:?string} */
    public static function summary(int $studioId): array
    {
        $plan = StudiosModel::find($studioId)?->currentAccessSubscription?->plan;
        $limit = $plan?->staff_limit;

        $employeeIds = UserModel::whereIn('role', ['studio-hr', 'studio-finance', 'studio-photographer'])
            ->whereExists(function ($q) use ($studioId) {
                $q->select(DB::raw(1))
                    ->from('tbl_user_roles')
                    ->whereColumn('tbl_user_roles.user_id', 'tbl_users.id')
                    ->where('tbl_user_roles.studio_id', $studioId);
            })
            ->pluck('id');

        $ids = $employeeIds
            ->merge(StudioPhotographersModel::where('studio_id', $studioId)->pluck('photographer_id'))
            ->merge(StudioMemberModel::where('studio_id', $studioId)->approved()->pluck('freelancer_id'))
            ->unique();

        $used = UserModel::whereIn('id', $ids)->where('status', 'active')->count();
        $remaining = $limit === null ? null : max(0, $limit - $used);

        return [
            'used' => $used,
            'limit' => $limit,
            'remaining' => $remaining,
            'allowed' => $remaining === null || $remaining > 0,
            'plan' => $plan?->name,
        ];
    }

    /** Error message when one more active staff member would exceed the plan limit, else null. */
    public static function violation(int $studioId): ?string
    {
        $s = self::summary($studioId);

        return $s['allowed']
            ? null
            : "Your {$s['plan']} plan allows up to {$s['limit']} staff and this studio has reached it. Upgrade the plan or deactivate a staff account.";
    }
}
