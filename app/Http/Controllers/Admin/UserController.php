<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    /**
     * Display listing of users
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $users = UserModel::where('role', '!=', 'admin')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.view-users', compact('users'));
    }

    /**
     * Get users for AJAX
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsers(Request $request): JsonResponse
    {
        $users = UserModel::select([
                'id',
                'uuid',
                'role',
                'user_type',
                'first_name',
                'middle_name',
                'last_name',
                'email',
                'mobile_number',
                'profile_photo',
                'location_id', // Added
                'status',
                'email_verified',
                'created_at'
            ])
            ->with('location') // Eager load location relationship
            ->where('role', '!=', 'admin')
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedUsers = $users->map(function($user) {
            return [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'full_name' => $user->full_name,
                'first_name' => $user->first_name,
                'middle_name' => $user->middle_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'mobile_number' => $user->mobile_number,
                'profile_photo_url' => $user->profile_photo_or_placeholder,
                'formatted_role' => $user->formatted_role,
                'formatted_user_type' => $user->formatted_user_type,
                'formatted_status' => $user->formatted_status,
                'created_at_formatted' => $user->created_at->format('F d, Y'),
                'email_verified_status' => $user->email_verified_status,
                'location' => $user->location ? [
                    'province' => $user->location->province,
                    'municipality' => $user->location->municipality,
                    'formatted_location' => $user->formatted_location
                ] : null,
                'formatted_location' => $user->formatted_location, // Add this
            ];
        });

        return response()->json([
            'success' => true,
            'users' => $formattedUsers
        ]);
    }

    /**
     * Get user details for modal
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getUserDetails(Request $request, $id): JsonResponse
    {
        try {
            $user = UserModel::with('location') // Eager load location
                    ->where('id', $id)
                    ->where('role', '!=', 'admin')
                    ->firstOrFail();
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'full_name' => $user->full_name,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'mobile_number' => $user->mobile_number,
                    'profile_photo' => $user->profile_photo_or_placeholder, // Fixed URL
                    'role' => $user->formatted_role,
                    'user_type' => $user->formatted_user_type,
                    'status' => $user->formatted_status,
                    'email_verified' => $user->email_verified,
                    'email_verified_status' => $user->email_verified_status,
                    'email_verified_at' => $user->email_verified ? 
                        $user->updated_at->format('F d, Y') : 'Not verified',
                    'created_at' => $user->created_at->format('F d, Y'),
                    'created_at_full' => $user->created_at->format('F d, Y h:i A'),
                    'location' => $user->location ? [
                        'province' => $user->location->province,
                        'municipality' => $user->location->municipality,
                        'barangay' => $user->location->barangay,
                        'zip_code' => $user->location->zip_code
                    ] : null,
                    'formatted_location' => $user->formatted_location, // Add this
                    'province' => $user->province, // Add this
                    'municipality' => $user->municipality, // Add this
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * Delete user
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deleteUser(Request $request, $id): JsonResponse
    {
        try {
            $user = UserModel::findOrFail($id);
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user'
            ], 500);
        }
    }
}