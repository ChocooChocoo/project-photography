<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCsvExporter;
use App\Services\Dashboard\FinanceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handle finance dashboard requests.
 */
class DashboardController extends Controller
{
    /**
     * Display the finance dashboard page.
     */
    public function index(Request $request, FinanceDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->build($request->user(), $request->all());

        return view('studio-finance.dashboard', [
            'dashboard' => $dashboard,
            'dashboardConfig' => [
                'title' => 'Finance Dashboard',
                'subtitle' => 'Financial operations across assigned studios.',
                'filterRoute' => route('studio-finance.dashboard.filter'),
                'exportRoute' => route('studio-finance.dashboard.export'),
            ],
        ]);
    }

    /**
     * Refresh finance dashboard data.
     */
    public function filter(Request $request, FinanceDashboardService $dashboardService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Finance dashboard data loaded successfully.',
            'data' => $dashboardService->build($request->user(), $request->all()),
        ]);
    }

    /**
     * Export finance dashboard data.
     */
    public function export(
        Request $request,
        FinanceDashboardService $dashboardService,
        DashboardCsvExporter $dashboardCsvExporter
    ): StreamedResponse {
        return $dashboardCsvExporter->download(
            'studio-finance-dashboard-' . now()->format('Ymd-His') . '.csv',
            $dashboardService->build($request->user(), $request->all())
        );
    }
}
