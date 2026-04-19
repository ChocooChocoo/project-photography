<?php

namespace App\Http\Controllers\StudioPhotographer;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCsvExporter;
use App\Services\Dashboard\PhotographerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Display the studio photographer dashboard.
     */
    public function index(Request $request, PhotographerDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->build($request->user(), $request->all());

        return view('studio-photographer.dashboard', [
            'dashboard' => $dashboard,
            'dashboardConfig' => [
                'title' => 'Studio Photographer Dashboard',
                'subtitle' => 'Your assignment performance and upcoming work.',
                'filterRoute' => route('studio-photographer.dashboard.filter'),
                'exportRoute' => route('studio-photographer.dashboard.export'),
            ],
        ]);
    }

    /**
     * Refresh studio photographer dashboard data.
     */
    public function filter(Request $request, PhotographerDashboardService $dashboardService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Studio photographer dashboard data loaded successfully.',
            'data' => $dashboardService->build($request->user(), $request->all()),
        ]);
    }

    /**
     * Export studio photographer dashboard data.
     */
    public function export(
        Request $request,
        PhotographerDashboardService $dashboardService,
        DashboardCsvExporter $dashboardCsvExporter
    ): StreamedResponse {
        return $dashboardCsvExporter->download(
            'studio-photographer-dashboard-' . now()->format('Ymd-His') . '.csv',
            $dashboardService->build($request->user(), $request->all())
        );
    }
}
