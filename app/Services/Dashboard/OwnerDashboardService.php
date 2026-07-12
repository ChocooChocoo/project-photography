<?php

namespace App\Services\Dashboard;

use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\RoleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioRatingModel;
use App\Models\SystemRevenueModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;

/**
 * Build analytics for the studio owner dashboard.
 */
class OwnerDashboardService extends BaseDashboardService
{
    /**
     * Build the owner dashboard payload.
     *
     * @param  \App\Models\UserModel  $user
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function build(UserModel $user, array $input = []): array
    {
        $filters = $this->resolveFilters($input);
        [$startDate, $endDate] = $this->getBounds($filters);
        $granularity = $this->getTrendGranularity($startDate, $endDate);
        $labels = $this->buildTrendLabels($startDate, $endDate, $granularity);
        $studio = StudiosModel::query()->where('user_id', $user->id)->first();

        if (!$studio) {
            return $this->makeDashboardPayload(
                $filters,
                [
                    $this->makeKpi('employees', 'Active Employees', '0', 'ti ti-users', 'primary', 'Linked Studio', 'None'),
                    $this->makeKpi('bookings', 'Total Bookings', '0', 'ti ti-calendar-event', 'warning', 'Completed', '0'),
                    $this->makeKpi('revenue', 'Revenue', $this->formatCurrency(0), 'ti ti-cash-banknote', 'success', 'Ratings', '0'),
                    $this->makeKpi('ratings', 'Average Rating', '0.0', 'ti ti-star', 'info', 'Reviews', '0'),
                ],
                [],
                [
                    $this->makeTable(
                        'recent_bookings',
                        'Recent Bookings',
                        ['Reference', 'Client', 'Event Date', 'Status'],
                        [],
                        'No studio is currently linked to this owner account.'
                    ),
                ],
                [
                    'subtitle' => 'No studio is currently linked to this owner account.',
                    'studio_name' => null,
                ]
            );
        }

        $bookings = BookingModel::query()
            ->with(['client', 'category'])
            ->where('provider_id', $studio->id)
            ->where('booking_type', 'studio')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $payments = PaymentModel::query()
            ->where('status', 'succeeded')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereHas('booking', function ($query) use ($studio) {
                $query->where('provider_id', $studio->id)
                    ->where('booking_type', 'studio');
            })
            ->with('booking')
            ->get();

        $ratings = StudioRatingModel::query()
            ->where('studio_id', $studio->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $employeeRoleNames = [
            'studio-hr-manager',
            'studio-hr-staff',
            'studio-finance-manager',
            'studio-finance-staff',
            'studio-photographer',
        ];

        $employeeIds = DB::table('tbl_user_roles')
            ->join('tbl_roles', 'tbl_user_roles.role_id', '=', 'tbl_roles.id')
            ->where('tbl_user_roles.studio_id', $studio->id)
            ->whereIn('tbl_roles.name', $employeeRoleNames)
            ->pluck('tbl_user_roles.user_id')
            ->unique()
            ->values();

        $employees = UserModel::query()->whereIn('id', $employeeIds)->get();
        $bookingTrend = $this->groupTrendValues(
            $bookings,
            fn (BookingModel $booking) => $booking->created_at,
            null,
            $granularity
        );
        $incomeTrend = $this->groupTrendValues(
            $payments,
            fn (PaymentModel $payment) => $payment->paid_at ?? $payment->created_at,
            fn (PaymentModel $payment) => $payment->amount,
            $granularity
        );

        $statusCategories = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
        $statusData = collect($statusCategories)
            ->map(fn (string $status) => $bookings->where('status', $status)->count())
            ->values()
            ->all();

        $ratingCategories = ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'];
        $ratingData = [1, 2, 3, 4, 5];
        $ratingSeries = collect($ratingData)
            ->map(fn (int $rating) => $ratings->where('rating', $rating)->count())
            ->values()
            ->all();

        $serviceChartLabels = $bookings
            ->groupBy(fn (BookingModel $booking) => optional($booking->category)->category_name ?? 'Uncategorized')
            ->sortKeys()
            ->keys()
            ->values()
            ->all();

        $serviceChartValues = collect($serviceChartLabels)
            ->map(function (string $label) use ($bookings) {
                return $bookings->filter(
                    fn (BookingModel $booking) => (optional($booking->category)->category_name ?? 'Uncategorized') === $label
                )->count();
            })
            ->values()
            ->all();

        $recentBookingRows = $bookings
            ->sortByDesc('created_at')
            ->take(8)
            ->map(function (BookingModel $booking): array {
                return [
                    $booking->booking_reference,
                    optional($booking->client)->full_name ?? 'N/A',
                    optional($booking->event_date)?->format('M d, Y') ?? 'N/A',
                    ucfirst((string) $booking->status),
                ];
            })
            ->values()
            ->all();

        return $this->makeDashboardPayload(
            $filters,
            [
                $this->makeKpi(
                    'employees',
                    'Active Employees',
                    (string) $employees->where('status', 'active')->count(),
                    'ti ti-users',
                    'primary',
                    'All Employees',
                    (string) $employees->count()
                ),
                $this->makeKpi(
                    'bookings',
                    'Total Bookings',
                    (string) $bookings->count(),
                    'ti ti-calendar-event',
                    'warning',
                    'Completed',
                    (string) $bookings->where('status', BookingModel::STATUS_COMPLETED)->count()
                ),
                $this->makeKpi(
                    'revenue',
                    'Revenue',
                    $this->formatCurrency((float) $payments->sum('amount')),
                    'ti ti-cash-banknote',
                    'success',
                    'Paid Transactions',
                    (string) $payments->count()
                ),
                $this->makeKpi(
                    'ratings',
                    'Average Rating',
                    number_format((float) $ratings->avg('rating'), 1),
                    'ti ti-star',
                    'info',
                    'Reviews',
                    (string) $ratings->count()
                ),
            ],
            [
                $this->makeChart('bookings_by_service', 'Bookings by Service', 'bar', [
                    [
                        'name' => 'Bookings',
                        'data' => $serviceChartValues,
                    ],
                ], $serviceChartLabels),
                $this->makeChart('income_trend', 'Income Trend', 'line', [
                    [
                        'name' => 'Income',
                        'data' => $this->normalizeTrendData($labels, $incomeTrend),
                    ],
                ], $labels),
                $this->makeChart('booking_status', 'Booking Status Breakdown', 'donut', [
                    [
                        'name' => 'Bookings',
                        'data' => $statusData,
                    ],
                ], collect($statusCategories)->map(fn (string $status) => str($status)->replace('_', ' ')->title()->toString())->all()),
                $this->makeChart('rating_distribution', 'Rating Distribution', 'bar', [
                    [
                        'name' => 'Ratings',
                        'data' => $ratingSeries,
                    ],
                ], $ratingCategories),
            ],
            [
                $this->makeTable(
                    'recent_bookings',
                    'Recent Bookings',
                    ['Reference', 'Client', 'Event Date', 'Status'],
                    $recentBookingRows,
                    'No studio bookings found for the selected range.'
                ),
                $this->makeTable(
                    'income_by_service',
                    'Income by Service Category',
                    ['Service Category', 'Bookings', 'Total Revenue', 'Platform Fee', 'Net Income'],
                    $this->buildIncomeByServiceRows($studio, $startDate, $endDate),
                    'No revenue recorded for the selected range.'
                ),
            ],
            [
                'subtitle' => 'Business performance for ' . $studio->studio_name,
                'studio_name' => $studio->studio_name,
            ]
        );
    }

    /**
     * Build per-service-category income rows from settled revenue records.
     *
     * @return array<int, array<int, string>>
     */
    private function buildIncomeByServiceRows(StudiosModel $studio, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $revenues = SystemRevenueModel::query()
            ->where('provider_type', 'studio')
            ->where('provider_id', $studio->id)
            ->where('revenue_type', 'booking')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('booking.category')
            ->get();

        return $revenues
            ->groupBy(fn (SystemRevenueModel $revenue) => optional(optional($revenue->booking)->category)->category_name ?? 'Uncategorized')
            ->sortKeys()
            ->map(function ($group, string $category): array {
                return [
                    $category,
                    (string) $group->count(),
                    $this->formatCurrency((float) $group->sum('total_amount')),
                    $this->formatCurrency((float) $group->sum('platform_fee_amount')),
                    $this->formatCurrency((float) $group->sum('provider_amount')),
                ];
            })
            ->values()
            ->all();
    }
}
