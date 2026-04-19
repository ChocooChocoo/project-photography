<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\AdminDashboardService;
use App\Services\Dashboard\DashboardCsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request, AdminDashboardService $dashboardService)
    {
        $dashboard = $dashboardService->build($request->all());

        return view('admin.dashboard', [
            'dashboard' => $dashboard,
            'dashboardConfig' => [
                'title' => 'Admin Dashboard',
                'subtitle' => 'Platform-wide performance and activity snapshot.',
                'filterRoute' => route('admin.dashboard.filter'),
                'exportRoute' => route('admin.dashboard.export'),
            ],
        ]);
    }

    /**
     * Refresh admin dashboard data.
     */
    public function filter(Request $request, AdminDashboardService $dashboardService): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Admin dashboard data loaded successfully.',
            'data' => $dashboardService->build($request->all()),
        ]);
    }

    /**
     * Export admin dashboard data as CSV.
     */
    public function export(
        Request $request,
        AdminDashboardService $dashboardService,
        DashboardCsvExporter $dashboardCsvExporter
    ): StreamedResponse {
        return $dashboardCsvExporter->download(
            'admin-dashboard-' . now()->format('Ymd-His') . '.csv',
            $dashboardService->build($request->all())
        );
    }
}
