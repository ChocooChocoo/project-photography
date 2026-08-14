<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudioOwner\StudiosModel;
use App\Traits\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudioApprovedMail;
use App\Mail\StudioRejectedMail;

class StudioController extends Controller
{
    use Notifiable;

    public const REJECTION_REASONS = [
        'Incomplete documents',
        'Expired permit',
        'Unclear permit photo',
        'Name mismatch',
        'Invalid business type',
        'Other',
    ];

    public function index()
    {
        $studios = StudiosModel::with(['user', 'location', 'category', 'categories', 'schedules'])
            ->whereIn('status', ['verified', 'active', 'inactive'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.view-studios', compact('studios'));
    }

    public function pending()
    {
        $studios = StudiosModel::with(['user', 'location', 'category', 'categories', 'schedules'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.pending-studios', compact('studios'));
    }

    public function approve(Request $request, $id)
    {
        try {
            $studio = StudiosModel::with('user')->findOrFail($id);
            
            $studio->update([
                'status' => 'verified',
                'rejection_note' => null,
            ]);
            
            // Send approval email to studio owner
            Mail::to($studio->user->email)->send(new StudioApprovedMail($studio));
            
            // USE THE TRAIT METHOD INSTEAD OF DIRECT CREATION
            $this->notifyStudioApproved($studio);
            
            return response()->json([
                'success' => true,
                'message' => 'Studio approved successfully! Email and notification sent to owner.',
                'alert_color' => '#007BFF',
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Approve studio error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve studio: ' . $e->getMessage(),
                'alert_color' => '#DC3545'
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'rejection_note' => 'required|string|in:' . implode(',', self::REJECTION_REASONS),
                'rejection_note_additional' => 'nullable|string|max:500',
            ]);
            
            $studio = StudiosModel::with('user')->findOrFail($id);

            $note = $request->rejection_note;

            if ($request->filled('rejection_note_additional')) {
                $note .= ' - ' . $request->rejection_note_additional;
            }
            
            $studio->update([
                'status' => 'rejected',
                'rejection_note' => $note,
            ]);
            
            // Send rejection email to studio owner
            Mail::to($studio->user->email)->send(new StudioRejectedMail($studio));
            
            // USE THE TRAIT METHOD INSTEAD OF DIRECT CREATION
            $this->notifyStudioRejected($studio, $request->rejection_note);
            
            return response()->json([
                'success' => true,
                'message' => 'Studio rejected successfully. Email and notification sent to owner.',
                'alert_color' => '#007BFF',
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'alert_color' => '#DC3545'
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Reject studio error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject studio: ' . $e->getMessage(),
                'alert_color' => '#DC3545'
            ], 500);
        }
    }
}