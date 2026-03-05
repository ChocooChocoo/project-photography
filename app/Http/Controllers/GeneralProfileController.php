<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class GeneralProfileController extends Controller
{
    /**
     * Display admin profile page
     */
    public function admin()
    {
        $user = auth()->user();
        return view('admin.view-user-profile', compact('user'));
    }

    /**
     * Display owner profile page
     */
    public function owner()
    {
        $user = auth()->user();
        return view('owner.view-user-profile', compact('user'));
    }

    /**
     * Display freelancer profile page
     */
    public function freelancer()
    {
        $user = auth()->user();
        return view('freelancer.view-user-profile', compact('user'));
    }

    /**
     * Display studio photographer profile page
     */
    public function studioPhotographer()
    {
        $user = auth()->user();
        return view('studio-photographer.view-user-profile', compact('user'));
    }

    /**
     * Display client profile page
     */
    public function client()
    {
        $user = auth()->user();
        return view('client.view-user-profile', compact('user'));
    }

    /**
     * Display studio hr profile page
     */
    public function studioHR()
    {
        $user = auth()->user();
        return view('studio-hr.view-user-profile', compact('user'));
    }

    /**
     * Get user data for AJAX request
     */
    public function getUserData()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'first_name' => $user->first_name,
                    'middle_name' => $user->middle_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'mobile_number' => $user->mobile_number,
                    'role' => $user->role,
                    'role_display' => $this->getRoleDisplay($user->role),
                    'profile_photo' => $user->profile_photo_url,
                    'cover_photo' => $user->cover_photo_url,
                    'has_profile_photo' => $user->hasProfilePhoto(),
                    'has_cover_photo' => $user->hasCoverPhoto(),
                    'email_verified' => $user->email_verified,
                    'status' => $user->status,
                    'created_at' => $user->created_at ? $user->created_at->format('M d, Y') : 'N/A',
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getUserData: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error occurred'
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request)
    {
        try {
            $user = auth()->user();
            $data = $request->except(['current_password', 'password', 'password_confirmation', 'profile_photo', 'cover_photo']);
            
            // Handle password update
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
            
            // Handle profile photo upload
            if ($request->hasFile('profile_photo')) {
                // Delete old profile photo if exists
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }
                
                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $data['profile_photo'] = $path;
            }
            
            // Handle cover photo upload
            if ($request->hasFile('cover_photo')) {
                // Delete old cover photo if exists
                if ($user->cover_photo && Storage::disk('public')->exists($user->cover_photo)) {
                    Storage::disk('public')->delete($user->cover_photo);
                }
                
                $path = $request->file('cover_photo')->store('cover-photos', 'public');
                $data['cover_photo'] = $path;
            }
            
            // Update user
            $user->update($data);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully!',
                'data' => [
                    'profile_photo' => $user->profile_photo_url,
                    'cover_photo' => $user->cover_photo_url ?? asset('assets/images/profile-bg.jpg'),
                    'full_name' => $user->full_name,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile. Please try again.',
                'errors' => ['system' => [$e->getMessage()]]
            ], 500);
        }
    }

    /**
     * Get role display name
     */
    private function getRoleDisplay($role)
    {
        $roles = [
            'admin' => 'Administrator',
            'owner' => 'Studio Owner',
            'freelancer' => 'Freelancer',
            'client' => 'Client',
            'studio-photographer' => 'Studio Photographer',
        ];
        
        return $roles[$role] ?? ucfirst($role);
    }
}