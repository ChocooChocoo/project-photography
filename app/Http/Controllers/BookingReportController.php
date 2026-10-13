<?php

namespace App\Http\Controllers;

use App\Models\BookingModel;
use App\Models\NotificationModel;
use App\Models\ReportModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BookingReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $request->validate(['status' => 'nullable|in:open,reviewing,resolved,dismissed']);
        $reports = ReportModel::with('reporter', 'booking')->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))->latest()->paginate(20)->withQueryString();

        return view('admin.view-reports', compact('reports'));
    }

    public function show(Request $request, $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $report = ReportModel::with('reporter', 'booking')->findOrFail($report);

        return view('admin.report-details', compact('report'));
    }

    public function store(Request $request, $booking)
    {
        $data = $request->validate(['target_type' => 'required|in:studio,photographer', 'target_id' => 'required|integer|min:1',
            'reason' => 'required|string|max:255', 'details' => 'required|string|min:10|max:5000',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);
        $path = null;
        try {
            return DB::transaction(function () use ($request, $booking, $data, &$path) {
                $booking = BookingModel::where('client_id', $request->user()->id)->where('status', 'completed')->where('booking_type', 'studio')->lockForUpdate()->findOrFail($booking);
                if ($data['target_type'] === 'studio') {
                    abort_unless((int) $booking->provider_id === (int) $data['target_id'], 404);
                } else {
                    $booking->assignedPhotographers()->where('photographer_id', $data['target_id'])->where('status', '!=', 'cancelled')->firstOrFail();
                }
                $key = $request->user()->id.':'.$booking->id.':'.$data['target_type'].':'.$data['target_id'];
                abort_if(ReportModel::where('active_key', $key)->exists(), 409, 'An open report already exists for this target and booking.');
                if ($request->hasFile('evidence')) {
                    $path = $request->file('evidence')->store('report-evidence', 'local');
                    abort_unless(is_string($path) && $path !== '', 500, 'Evidence could not be stored. Try again.');
                }
                unset($data['evidence']);
                $report = ReportModel::create($data + ['reporter_id' => $request->user()->id, 'booking_id' => $booking->id,
                    'status' => 'open', 'active_key' => $key, 'evidence' => $path]);

                return response()->json(['success' => true, 'report' => $report], 201);
            });
        } catch (\Throwable $error) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
    }

    public function update(Request $request, $report)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['status' => 'required|in:reviewing,resolved,dismissed',
            'resolution_note' => 'required_if:status,resolved,dismissed|nullable|string|max:5000']);

        return DB::transaction(function () use ($report, $data) {
            $report = ReportModel::lockForUpdate()->findOrFail($report);
            if ($report->status === $data['status'] && $report->resolution_note === ($data['resolution_note'] ?? null)) {
                return response()->json(['success' => true, 'report' => $report]);
            }
            abort_unless(in_array($report->status, ['open', 'reviewing'], true), 422, 'Closed reports cannot be reopened.');
            $report->update($data + ['active_key' => in_array($data['status'], ['resolved', 'dismissed'], true) ? null : $report->active_key]);
            $ownerId = $report->booking?->studio?->user_id;
            foreach (array_unique(array_filter([$report->reporter_id, $ownerId])) as $userId) {
                NotificationModel::create(['user_id' => $userId, 'type' => 'report_status', 'title' => 'Report '.$report->status,
                    'message' => 'Report #'.$report->id.' is now '.$report->status.'.',
                    'data' => ['report_id' => $report->id, 'booking_id' => $report->booking_id,
                        'route' => route($userId === $report->reporter_id ? 'client.feedback.show' : 'owner.feedback.index', $userId === $report->reporter_id ? ['booking' => $report->booking_id] : [], false)]]);
            }

            return response()->json(['success' => true, 'report' => $report]);
        });
    }

    public function evidence(Request $request, $report)
    {
        $report = ReportModel::findOrFail($report);
        abort_unless($request->user()->role === 'admin' || (int) $report->reporter_id === $request->user()->id, 404);
        abort_unless($report->evidence && Storage::disk('local')->exists($report->evidence), 404);

        return Storage::disk('local')->download($report->evidence, 'report-'.$report->id.'.'.pathinfo($report->evidence, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store']);
    }
}
