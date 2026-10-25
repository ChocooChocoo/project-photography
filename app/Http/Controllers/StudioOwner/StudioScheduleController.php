<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StudioOwner\StudioScheduleRequest;
use App\Models\StudioOwner\StudioScheduleModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Admin\LocationModel;
use Illuminate\Support\Facades\Auth;

class StudioScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ownerId = Auth::id();
        
        // Get all schedules for the owner's verified studios
        $schedules = StudioScheduleModel::with(['studio', 'location'])
            ->whereHas('studio', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId)
                    ->where('status', 'verified');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all municipalities for coverage area dropdown in modals
        $locations = \App\Models\Admin\LocationModel::active()
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->get()
            ->pluck('municipality')
            ->toArray();

        return view('owner.view-studios-schedules', compact('schedules', 'locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->setupStudioSchedule();
    }

    /**
     * Show the setup form for creating studio schedules.
     */
    public function setupStudioSchedule()
    {
        $ownerId = Auth::id();
        
        // Get only verified studios owned by the current user
        $studios = StudiosModel::where('user_id', $ownerId)
            ->where('status', 'verified')
            ->orderBy('studio_name')
            ->get();

        // Get all municipalities for coverage area dropdown
        $locations = LocationModel::active()
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->get()
            ->pluck('municipality')
            ->toArray();

        return view('owner.setup-studios-schedules', compact('studios', 'locations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StudioScheduleRequest $request)
    {
        try {
            // Get the studio to get its location_id
            $studio = StudiosModel::where('id', $request->studio_id)
                ->where('status', 'verified')
                ->firstOrFail();

            // Create the schedule
            $schedule = StudioScheduleModel::create([
                'studio_id' => $request->studio_id,
                'location_id' => $studio->location_id,
                'operating_days' => $request->operating_days,
                'opening_time' => $request->opening_time,
                'closing_time' => $request->closing_time,
                'booking_limit' => $request->booking_limit,
                'advance_booking' => $request->advance_booking,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Studio schedule created successfully!',
                'data' => $schedule
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ownerId = Auth::id();
        
        $schedule = StudioScheduleModel::with(['studio', 'location'])
            ->where('id', $id)
            ->whereHas('studio', function($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $ownerId = Auth::id();
            
            $schedule = StudioScheduleModel::where('id', $id)
                ->whereHas('studio', function($query) use ($ownerId) {
                    $query->where('user_id', $ownerId);
                })
                ->firstOrFail();

            $schedule->delete();

            return response()->json([
                'success' => true,
                'message' => 'Studio schedule deleted successfully!'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule: ' . $e->getMessage()
            ], 500);
        }
    }
}