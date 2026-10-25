<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioOwner\UserModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MemberInvitationController extends Controller
{
    /**
     * Display a listing of invitations for the freelancer.
     */
    public function index()
    {
        $freelancerId = auth()->id();
        
        // Get all invitations for this freelancer with studio and owner details
        $invitations = StudioMemberModel::with([
                'studio',
                'studio.location',
                'inviter'
            ])
            ->where('freelancer_id', $freelancerId)
            ->latest('invited_at')
            ->get();

        return view('freelancer.member-invitation', [
            'invitations' => $invitations
        ]);
    }

    /**
     * Get invitation details for modal.
     */
    public function getInvitationDetails($id): JsonResponse
    {
        try {
            $invitation = StudioMemberModel::with([
                    'studio',
                    'studio.location',
                    'inviter'
                ])
                ->where('id', $id)
                ->where('freelancer_id', auth()->id())
                ->firstOrFail();

            // Ensure inviter data is properly loaded
            $invitation->inviter_name = $invitation->inviter ? $invitation->inviter->full_name : 'Unknown Owner';
            $invitation->inviter_email = $invitation->inviter ? $invitation->inviter->email : 'N/A';
            $invitation->inviter_profile_picture = $invitation->inviter ? $invitation->inviter->profile_picture : null;

            return response()->json([
                'success' => true,
                'data' => $invitation
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation not found.'
            ], 404);
        }
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, $id): JsonResponse
    {
        try {
            $invitation = StudioMemberModel::where('id', $id)
                ->where('freelancer_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            if ($message = \App\Services\StudioStaffLimit::violation((int) $invitation->studio_id)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            $invitation->update([
                'status' => 'approved',
                'responded_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation accepted successfully!'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept invitation. Please try again.'
            ], 500);
        }
    }

    /**
     * Reject an invitation.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'nullable|string|max:500'
        ]);

        try {
            $invitation = StudioMemberModel::where('id', $id)
                ->where('freelancer_id', auth()->id())
                ->where('status', 'pending')
                ->firstOrFail();

            $invitation->update([
                'status' => 'rejected',
                'response_message' => $request->rejection_reason,
                'responded_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation declined.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline invitation. Please try again.'
            ], 500);
        }
    }
}