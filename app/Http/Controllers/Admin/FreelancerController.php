<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\UserModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FreelancerController extends Controller
{
    /**
     * Display the freelancers list.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);

        $freelancers = UserModel::where('role', 'freelancer')
            ->with(['freelancerProfile' => function($query) {
                $query->with(['categories', 'location', 'schedule', 'services']);
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return view('admin.view-freelancers', compact('freelancers'));
    }

    /**
     * Update a freelancer's basic account details.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $freelancer = UserModel::where('role', 'freelancer')->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ]);

        $freelancer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Freelancer updated successfully',
        ]);
    }

    /**
     * Delete a freelancer.
     */
    public function destroy($id): JsonResponse
    {
        UserModel::where('role', 'freelancer')->findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Freelancer deleted successfully',
        ]);
    }
}
