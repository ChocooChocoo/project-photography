<?php

namespace App\Services\Dashboard;

use App\Models\PaymentModel;
use App\Models\Procurement\ProcurementPurchaseOrderModel;
use App\Models\Procurement\ProcurementRequestModel;
use App\Models\StudioHR\GeneratedPayrollModel;
use App\Models\StudioOwner\EmployeeScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Illuminate\Support\Collection;

/**
 * Build analytics for the finance dashboard.
 */
class FinanceDashboardService extends BaseDashboardService
{
    /**
     * Build the finance dashboard payload.
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
        $studioIds = $this->getAssignedStudioIds($user);

        $payments = PaymentModel::query()
            ->where('status', 'succeeded')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->whereHas('booking', function ($query) use ($studioIds) {
                $query->whereIn('provider_id', $studioIds)
                    ->where('booking_type', 'studio');
            })
            ->with('booking')
            ->get();

        $payrolls = GeneratedPayrollModel::query()
            ->whereIn('studio_id', $studioIds)
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->get();

        $purchaseOrders = ProcurementPurchaseOrderModel::query()
            ->whereBetween('order_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('procurementRequest', function ($query) use ($studioIds) {
                $query->whereIn('studio_id', $studioIds);
            })
            ->with('procurementRequest')
            ->get();

        $procurementRequests = ProcurementRequestModel::query()
            ->whereIn('studio_id', $studioIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['studio', 'requester'])
            ->get();

        $revenueTrend = $this->groupTrendValues(
            $payments,
            fn (PaymentModel $payment) => $payment->paid_at ?? $payment->created_at,
            fn (PaymentModel $payment) => $payment->amount,
            $granularity
        );
        $procurementTrend = $this->groupTrendValues(
            $purchaseOrders,
            fn (ProcurementPurchaseOrderModel $purchaseOrder) => $purchaseOrder->order_date,
            fn (ProcurementPurchaseOrderModel $purchaseOrder) => $purchaseOrder->total_amount,
            $granularity
        );
        $payrollTrend = $this->groupTrendValues(
            $payrolls,
            fn (GeneratedPayrollModel $payroll) => $payroll->generated_at ?? $payroll->created_at,
            fn (GeneratedPayrollModel $payroll) => $payroll->net_amount,
            $granularity
        );

        $revenueSeries = $this->normalizeTrendData($labels, $revenueTrend);
        $expenseSeries = collect($this->normalizeTrendData($labels, $procurementTrend))
            ->zip($this->normalizeTrendData($labels, $payrollTrend))
            ->map(fn ($pair) => (float) $pair[0] + (float) $pair[1])
            ->values()
            ->all();
        $netSeries = collect($revenueSeries)
            ->zip($expenseSeries)
            ->map(fn ($pair) => (float) $pair[0] - (float) $pair[1])
            ->values()
            ->all();

        $statusLabels = [
            'Pending Review',
            'Approved',
            'Ordered',
            'Delivered',
            'Payment Processing',
            'Completed',
        ];
        $statusCounts = [
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_PENDING_FINANCE_REVIEW)->count(),
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_APPROVED)->count(),
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_ORDERED)->count(),
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_DELIVERED)->count(),
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_PAYMENT_PROCESSING)->count(),
            $procurementRequests->where('status', ProcurementRequestModel::STATUS_COMPLETED)->count(),
        ];

        $recentProcurementRows = $procurementRequests
            ->sortByDesc('created_at')
            ->take(8)
            ->map(function (ProcurementRequestModel $request): array {
                return [
                    $request->request_reference,
                    optional($request->studio)->studio_name ?? 'N/A',
                    $request->status_label,
                    $this->formatCurrency((float) ($request->approved_total ?? $request->estimated_total ?? 0)),
                ];
            })
            ->values()
            ->all();

        $revenue = (float) $payments->sum('amount');
        $procurementExpenses = (float) $purchaseOrders->sum('total_amount');
        $payrollExpense = (float) $payrolls->sum('net_amount');
        $netBalance = $revenue - $procurementExpenses - $payrollExpense;

        return $this->makeDashboardPayload(
            $filters,
            [
                $this->makeKpi(
                    'revenue',
                    'Revenue',
                    $this->formatCurrency($revenue),
                    'ti ti-cash-banknote',
                    'success',
                    'Paid Transactions',
                    (string) $payments->count()
                ),
                $this->makeKpi(
                    'expenses',
                    'Expenses',
                    $this->formatCurrency($procurementExpenses),
                    'ti ti-receipt-2',
                    'warning',
                    'Purchase Orders',
                    (string) $purchaseOrders->count()
                ),
                $this->makeKpi(
                    'payroll_expense',
                    'Payroll Expense',
                    $this->formatCurrency($payrollExpense),
                    'ti ti-report-money',
                    'info',
                    'Payroll Entries',
                    (string) $payrolls->count()
                ),
                $this->makeKpi(
                    'net_balance',
                    'Net Balance',
                    $this->formatCurrency($netBalance),
                    'ti ti-chart-line',
                    'primary',
                    'Open Procurement',
                    (string) $procurementRequests->whereNotIn('status', [ProcurementRequestModel::STATUS_COMPLETED, ProcurementRequestModel::STATUS_CANCELLED])->count()
                ),
            ],
            [
                $this->makeChart('financial_trend', 'Financial Trend', 'line', [
                    ['name' => 'Revenue', 'data' => $revenueSeries],
                    ['name' => 'Expenses', 'data' => $expenseSeries],
                    ['name' => 'Net', 'data' => $netSeries],
                ], $labels),
                $this->makeChart('expense_breakdown', 'Expense Breakdown', 'donut', [
                    [
                        'name' => 'Expenses',
                        'data' => [$procurementExpenses, $payrollExpense],
                    ],
                ], ['Procurement', 'Payroll']),
                $this->makeChart('procurement_status', 'Procurement Status Summary', 'bar', [
                    [
                        'name' => 'Requests',
                        'data' => $statusCounts,
                    ],
                ], $statusLabels),
            ],
            [
                $this->makeTable(
                    'recent_procurement',
                    'Recent Procurement Activity',
                    ['Reference', 'Studio', 'Status', 'Approved Total'],
                    $recentProcurementRows,
                    'No procurement activity found for the selected range.'
                ),
            ],
            [
                'subtitle' => 'Financial operations across assigned studios.',
                'assigned_studios' => $studioIds->all(),
            ]
        );
    }

    /**
     * Resolve assigned studio IDs using the current finance scope pattern.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    private function getAssignedStudioIds(UserModel $user): Collection
    {
        $studioIds = $user->getAssignedStudioIds('studio-finance');

        if ($studioIds->isEmpty()) {
            $studioIds = EmployeeScheduleModel::query()->where('user_id', $user->id)->pluck('studio_id');
        }

        if ($studioIds->isEmpty()) {
            $studioIds = StudiosModel::query()->where('user_id', $user->id)->pluck('id');
        }

        return $studioIds->filter()->unique()->values();
    }
}
