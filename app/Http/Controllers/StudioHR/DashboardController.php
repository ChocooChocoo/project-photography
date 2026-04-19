<?php

namespace App\Http\Controllers\StudioHR;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCsvExporter;
use App\Services\Dashboard\HrDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Display the HR dashboard.
     */
    public function index(Request $request, HrDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->build($request->user(), $request->all());

        return view('studio-hr.dashboard', [
            'dashboard' => $dashboard,
            'dashboardConfig' => [
                'title' => 'Human Resource Dashboard',
                'subtitle' => 'Workforce performance across assigned studios.',
                'filterRoute' => route('studio-hr.dashboard.filter'),
                'exportRoute' => route('studio-hr.dashboard.export'),
            ],
        ]);
    }

    /**
     * Refresh HR dashboard data.
     */
    public function filter(Request $request, HrDashboardService $dashboardService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'HR dashboard data loaded successfully.',
            'data' => $dashboardService->build($request->user(), $request->all()),
        ]);
    }

    /**
     * Export HR dashboard data as CSV.
     */
    public function export(
        Request $request,
        HrDashboardService $dashboardService,
        DashboardCsvExporter $dashboardCsvExporter
    ): StreamedResponse {
        return $dashboardCsvExporter->download(
            'studio-hr-dashboard-' . now()->format('Ymd-His') . '.csv',
            $dashboardService->build($request->user(), $request->all())
        );
    }
}
