<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\StudioEquipmentModel;
use App\Models\StudioOwner\StudiosModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudioEquipmentController extends Controller
{
    private function studio($id): StudiosModel
    {
        return StudiosModel::where('user_id', auth()->id())->findOrFail($id);
    }

    private function item($id): StudioEquipmentModel
    {
        $item = StudioEquipmentModel::findOrFail($id);
        $this->studio($item->studio_id);
        return $item;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255', 'type' => 'required|string|max:80',
            'brand' => 'nullable|string|max:255', 'model' => 'nullable|string|max:255',
            'status' => 'required|in:available,maintenance,retired',
        ]);
    }

    public function index($studioId)
    {
        $this->studio($studioId);
        return response()->json(['success' => true, 'data' => StudioEquipmentModel::where('studio_id', $studioId)->orderBy('name')->get()]);
    }

    public function store(Request $request, $studioId)
    {
        $this->studio($studioId);
        $item = StudioEquipmentModel::create(['studio_id' => $studioId] + $this->validated($request));
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, $id)
    {
        $item = $this->item($id);
        $data = $this->validated($request);
        return DB::transaction(function () use ($item, $data) {
            $item = StudioEquipmentModel::lockForUpdate()->findOrFail($item->id);
            $item->update($data);
            return response()->json(['success' => true, 'data' => $item]);
        });
    }

    public function destroy($id)
    {
        $item = $this->item($id);
        return DB::transaction(function () use ($item) {
            $item = StudioEquipmentModel::lockForUpdate()->findOrFail($item->id);
            if ($item->assignments()->whereHas('booking', fn ($q) => $q->whereNotIn('status', ['cancelled', 'completed']))->exists()) {
                return response()->json(['success' => false, 'message' => 'Remove this equipment from active bookings before deleting it.'], 409);
            }
            $item->delete();
            return response()->json(['success' => true]);
        });
    }
}
