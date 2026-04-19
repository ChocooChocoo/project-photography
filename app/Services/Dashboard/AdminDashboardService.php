<?php

namespace App\Services\Dashboard;

use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;

/**
 * Build analytics for the admin dashboard.
 */
class AdminDashboardService extends BaseDashboardService
{
    /**
     * Build the admin dashboard payload.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function build(array $input = []): array
    {
        $filters = $this->resolveFilters($input);
        [$startDate, $endDate] = $this->getBounds($filters);
        $granularity = $this->getTrendGranularity($startDate, $endDate);
        $labels = $this->buildTrendLabels($startDate, $endDate, $granularity);

        $users = UserModel::query()->get();
        $studios = StudiosModel::query()->get();
        $freelancers = ProfileModel::query()->with('user')->get();
        $bookings = BookingModel::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        $revenues = SystemRevenueModel::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
        $registrations = UserModel::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get();

        $verifiedFreelancers = $freelancers->filter(
            fn (ProfileModel $profile) => (bool) optional($profile->user)->email_verified
        )->count();

        $registrationTrend = $this->groupTrendValues(
            $registrations,
            fn (UserModel $user) => $user->created_at,
            null,
            $granularity
        );

        $revenueTrend = $this->groupTrendValues(
            $revenues,
            fn (SystemRevenueModel $revenue) => $revenue->created_at,
            fn (SystemRevenueModel $revenue) => $revenue->total_amount,
            $granularity
        );

        $bookingTrend = $this->groupTrendValues(
            $bookings,
            fn (BookingModel $booking) => $booking->created_at,
            null,
            $granularity
        );

        $roleDistribution = ['Admin', 'Owners', 'Freelancers', 'Clients', 'Studio HR', 'Finance', 'Photographers'];
        $roleDistributionData = [
            $users->where('role', 'admin')->count(),
            $users->where('role', 'owner')->count(),
            $users->where('role', 'freelancer')->count(),
            $users->where('role', 'client')->count(),
            $users->where('role', 'studio-hr')->count(),
            $users->where('role', 'studio-finance')->count(),
            $users->where('role', 'studio-photographer')->count(),
        ];

        $recentRows = $registrations
            ->take(8)
            ->map(function (UserModel $user): array {
                return [
                    $user->full_name,
                    ucfirst((string) $user->role),
                    $user->status,
                    $user->created_at?->format('M d, Y h:i A') ?? 'N/A',
                ];
            })
            ->values()
            ->all();

        return $this->makeDashboardPayload(
            $filters,
            [
                $this->makeKpi(
                    'total_users',
                    'Total Users',
                    (string) $users->count(),
                    'ti ti-users',
                    'primary',
                    'By Role',
                    (string) collect($roleDistributionData)->sum()
                ),
                $this->makeKpi(
                    'verified_providers',
                    'Verified Providers',
                    (string) ($studios->whereIn('status', ['verified', 'active'])->count() + $verifiedFreelancers),
                    'ti ti-badge-check',
                    'success',
                    'Studios / Freelancers',
                    $studios->whereIn('status', ['verified', 'active'])->count() . ' / ' . $verifiedFreelancers
                ),
                $this->makeKpi(
                    'active_users',
                    'Active Users',
                    (string) $users->where('status', 'active')->count(),
                    'ti ti-user-check',
                    'info',
                    'Inactive Users',
                    (string) $users->where('status', 'inactive')->count()
                ),
                $this->makeKpi(
                    'total_revenue',
                    'Total Revenue',
                    $this->formatCurrency((float) $revenues->sum('total_amount')),
                    'ti ti-cash-banknote',
                    'warning',
                    'Bookings In Range',
                    (string) $bookings->count()
                ),
            ],
            [
                $this->makeChart('registration_trend', 'New Registrations', 'line', [
                    [
                        'name' => 'Registrations',
                        'data' => $this->normalizeTrendData($labels, $registrationTrend),
                    ],
                ], $labels),
                $this->makeChart('revenue_trend', 'Revenue Trend', 'area', [
                    [
                        'name' => 'Revenue',
                        'data' => $this->normalizeTrendData($labels, $revenueTrend),
                    ],
                ], $labels),
                $this->makeChart('user_distribution', 'User Distribution', 'donut', [
                    [
                        'name' => 'Users',
                        'data' => $roleDistributionData,
                    ],
                ], $roleDistribution),
                $this->makeChart('booking_volume', 'Booking Volume', 'bar', [
                    [
                        'name' => 'Bookings',
                        'data' => $this->normalizeTrendData($labels, $bookingTrend),
                    ],
                ], $labels),
            ],
            [
                $this->makeTable(
                    'recent_registrations',
                    'Recent Registrations',
                    ['Name', 'Role', 'Status', 'Registered At'],
                    $recentRows,
                    'No registrations found for the selected range.'
                ),
            ],
            [
                'subtitle' => 'Platform-wide performance and activity snapshot.',
            ]
        );
    }
}
