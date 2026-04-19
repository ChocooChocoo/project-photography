<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardCsvExporter;
use App\Services\Dashboard\OwnerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Display the studio owner dashboard.
     */
    public function index(Request $request, OwnerDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->build($request->user(), $request->all());

        return view('owner.dashboard', [
            'dashboard' => $dashboard,
            'dashboardConfig' => [
                'title' => 'Studio Owner Dashboard',
                'subtitle' => 'Business performance for your linked studio.',
                'filterRoute' => route('owner.dashboard.filter'),
                'exportRoute' => route('owner.dashboard.export'),
            ],
        ]);
    }

    /**
     * Refresh owner dashboard data.
     */
    public function filter(Request $request, OwnerDashboardService $dashboardService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Studio owner dashboard data loaded successfully.',
            'data' => $dashboardService->build($request->user(), $request->all()),
        ]);
    }

    /**
     * Export owner dashboard data as CSV.
     */
    public function export(
        Request $request,
        OwnerDashboardService $dashboardService,
        DashboardCsvExporter $dashboardCsvExporter
    ): StreamedResponse {
        return $dashboardCsvExporter->download(
            'owner-dashboard-' . now()->format('Ymd-His') . '.csv',
            $dashboardService->build($request->user(), $request->all())
        );
    }
}
