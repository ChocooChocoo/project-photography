<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudioOwner\StudioMemberInviteRequest;
use App\Models\StudioOwner\StudioMemberModel;
use App\Models\StudioOwner\UserModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Freelancer\ProfileModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StudioMemberController extends Controller
{
    /**
     * Display a listing of invited members.
     */
    public function index()
    {
        $userId = auth()->id();
        [$studios, $studio] = $this->chosenStudio();
        
        if (!$studio) {
            return view('owner.view-members', [
                'members' => collect(),
                'studios' => $studios,
                'studio' => null
            ]);
        }

        // Get all invitations for this studio with correct relationships
        $members = StudioMemberModel::with([
                'freelancer',
                'freelancer.freelancerProfile',
                'freelancer.freelancerProfile.categories',
                'inviter'
            ])
            ->where('studio_id', $studio->id)
            ->latest()
            ->get();

        return view('owner.view-members', [
            'studios' => $studios,
            'members' => $members,
            'studio' => $studio
        ]);
    }

    /**
     * Display freelancers available for invitation.
     */
    public function invite()
    {
        $userId = auth()->id();
        [$studios, $studio] = $this->chosenStudio();
        
        if (!$studio) {
            return view('owner.invite-members', [
                'freelancers' => collect(),
                'studios' => $studios,
                'studio' => null,
                'invitedFreelancerIds' => collect()
            ]);
        }

        // Get all freelancers (users with role 'freelancer') with their profiles
        $freelancers = UserModel::with([
                'freelancerProfile',
                'freelancerProfile.categories',
                'freelancerProfile.schedule',
                'freelancerProfile.services'
            ])
            ->where('role', 'freelancer')
            ->where('status', 'active')
            ->where('id', '!=', $userId) // Exclude self
            ->get();

        // Get IDs of already invited freelancers for this studio
        $invitedFreelancerIds = StudioMemberModel::where('studio_id', $studio->id)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('freelancer_id');

        return view('owner.invite-members', [
            'freelancers' => $freelancers,
            'studios' => $studios,
            'studio' => $studio,
            'invitedFreelancerIds' => $invitedFreelancerIds
        ]);
    }

    /**
     * Get freelancer details for modal.
     */
    public function getFreelancerDetails($id): JsonResponse
    {
        try {
            $freelancer = UserModel::with([
                'freelancerProfile',
                'freelancerProfile.categories',
                'freelancerProfile.schedule',
                'freelancerProfile.services',
                'freelancerProfile.services.category',
                'freelancerProfile.location'
            ])->where('role', 'freelancer')
              ->where('id', $id)
              ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $freelancer
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Freelancer not found.'
            ], 404);
        }
    }

    /**
     * Store a newly created invitation.
     */
    public function store(StudioMemberInviteRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $invitation = StudioMemberModel::create([
                'studio_id' => $request->studio_id,
                'freelancer_id' => $request->freelancer_id,
                'invited_by' => auth()->id(),
                'invitation_message' => $request->invitation_message,
                'status' => 'pending',
                'invited_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully!',
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send invitation. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an invitation.
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        try {
            $invitation = StudioMemberModel::findOrFail($id);
            
            // Check if user owns the studio
            $studio = StudiosModel::where('id', $invitation->studio_id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$studio) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to cancel this invitation.'
                ], 403);
            }

            if (!$invitation->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending invitations can be cancelled.'
                ], 400);
            }

            $invitation->update([
                'status' => 'cancelled',
                'response_message' => $request->cancellation_reason,
                'responded_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully.'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->recordNotFound();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel invitation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply members view (for future implementation).
     */
    public function apply()
    {
        return view('owner.apply-members');
    }

    /**
     * The owner's studios and the one picked with ?studio_id= (default: the first).
     * An owner can invite freelancers to any studio they own, not only the first.
     */
    private function chosenStudio(): array
    {
        $studios = StudiosModel::where('user_id', auth()->id())->orderBy('id')->get();

        return [$studios, $studios->firstWhere('id', request()->integer('studio_id')) ?? $studios->first()];
    }
}
