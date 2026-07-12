<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingAssignedPhotographerModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_booking_assigned_photographers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'studio_id',
        'photographer_id',
        'assigned_by',
        'status',
        'assignment_notes',
        'cancellation_reason',
        'assigned_at',
        'response_deadline',
        'confirmed_at',
        'on_site_at',                  // NEW
        'client_confirmed_at',         // NEW
        'client_confirmation_notes',   // NEW
        'started_at',                  // NEW
        'completed_at',
        'cancelled_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'assigned_at'           => 'datetime',
        'response_deadline'     => 'datetime',
        'confirmed_at'          => 'datetime',
        'on_site_at'            => 'datetime',
        'client_confirmed_at'   => 'datetime',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    // ────────────────────────────────────────────────
    //  Relationships
    // ────────────────────────────────────────────────

    /**
     * Get the booking associated with the assignment.
     */
    public function booking()
    {
        return $this->belongsTo(\App\Models\BookingModel::class, 'booking_id');
    }

    /**
     * Get the studio associated with the assignment.
     */
    public function studio()
    {
        return $this->belongsTo(\App\Models\StudioOwner\StudiosModel::class, 'studio_id');
    }

    /**
     * Get the photographer (user) assigned.
     */
    public function photographer()
    {
        return $this->belongsTo(\App\Models\UserModel::class, 'photographer_id');
    }

    /**
     * Get the user who assigned the photographer.
     */
    public function assigner()
    {
        return $this->belongsTo(\App\Models\UserModel::class, 'assigned_by');
    }

    /**
     * Get the studio photographer details (pivot/mapping table).
     */
    public function studioPhotographer()
    {
        return $this->belongsTo(
            \App\Models\StudioOwner\StudioPhotographersModel::class,
            'photographer_id',
            'photographer_id'
        );
    }

    // ────────────────────────────────────────────────
    //  Status & State Helpers
    // ────────────────────────────────────────────────

    /**
     * Check if the assignment is currently active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['assigned', 'confirmed', 'on_site']);
    }

    /**
     * Check if photographer has marked themselves as on-site.
     */
    public function isOnSite(): bool
    {
        return !is_null($this->on_site_at) && is_null($this->started_at);
    }

    /**
     * Check if the client has confirmed the photographer's on-site presence.
     */
    public function isClientConfirmed(): bool
    {
        return !is_null($this->client_confirmed_at);
    }

    /**
     * Check if the photographer has missed the response deadline
     * without accepting or rejecting the assignment.
     */
    public function isPastDeadline(): bool
    {
        return $this->status === 'assigned'
            && !is_null($this->response_deadline)
            && now()->greaterThan($this->response_deadline);
    }

    // ────────────────────────────────────────────────
    //  State Transition Methods
    // ────────────────────────────────────────────────

    /**
     * Mark assignment as confirmed (photographer accepted the job).
     */
    public function markAsConfirmed()
    {
        $this->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark that the photographer has arrived at the location.
     *
     * @throws \Exception
     */
    public function markAsOnSite()
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Assignment must be confirmed first before marking as on-site.');
        }

        $this->update([
            'status'     => 'on_site',  // ✅ Changed from 'in_progress' to 'on_site'
            'on_site_at' => now(),
        ]);
    }

    /**
     * Mark job as in progress after client confirms photographer is on site.
     *
     * @param string|null $confirmationNotes Optional notes from client
     * @throws \Exception
     */
    public function markAsInProgressWithClientConfirmation($confirmationNotes = null)
    {
        if (!$this->on_site_at) {
            throw new \Exception('Photographer must mark as on-site first.');
        }

        $this->update([
            'status'                  => 'in_progress',
            'started_at'              => now(),
            'client_confirmed_at'     => now(),
            'client_confirmation_notes' => $confirmationNotes,
        ]);
    }

    /**
     * Mark assignment as completed.
     * Requires client confirmation before completion.
     *
     * @throws \Exception
     */
    public function markAsCompleted()
    {
        if (!$this->client_confirmed_at) {
            throw new \Exception('Client must confirm on-site presence before work can be completed.');
        }

        $this->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark assignment as cancelled.
     */
    public function markAsCancelled($reason = null)
    {
        $this->update([
            'status'             => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at'       => now(),
        ]);
    }

    // ────────────────────────────────────────────────
    //  Accessors / Display Helpers
    // ────────────────────────────────────────────────

    /**
     * Get formatted on-site status information (useful for UI).
     */
    public function getOnSiteStatusAttribute(): array
    {
        if ($this->on_site_at && $this->client_confirmed_at) {
            return [
                'status'      => 'confirmed',
                'message'     => 'Photographer on-site (Confirmed by client)',
                'time'        => $this->on_site_at,
                'badge_class' => 'badge-soft-success',
                'icon'        => 'ti ti-circle-check'
            ];
        }

        if ($this->on_site_at && !$this->client_confirmed_at) {
            return [
                'status'      => 'pending_client',
                'message'     => 'Photographer on-site (Awaiting client confirmation)',
                'time'        => $this->on_site_at,
                'badge_class' => 'badge-soft-warning',
                'icon'        => 'ti ti-clock'
            ];
        }

        return [
            'status'      => 'not_on_site',
            'message'     => 'Not on-site yet',
            'time'        => null,
            'badge_class' => 'badge-soft-secondary',
            'icon'        => 'ti ti-map-pin'
        ];
    }

    /**
     * ========== NEW HELPER METHODS ==========
     */

    /**
     * Check if photographer can mark as in progress
     * Requires: on_site_at set AND client_confirmed_at set
     */
    public function canMarkAsInProgress(): bool
    {
        return !is_null($this->on_site_at) && 
            !is_null($this->client_confirmed_at) && 
            $this->status === 'confirmed';
    }

    /**
     * Check if photographer can mark as completed
     * Requires: in_progress status and client confirmed
     */
    public function canMarkAsCompleted(): bool
    {
        return $this->status === 'in_progress' && 
            !is_null($this->client_confirmed_at);
    }

    /**
     * Get the next available action for the photographer
     */
    public function getNextActionAttribute(): ?string
    {
        if ($this->status === 'assigned') {
            return 'confirm';
        }
        
        if ($this->status === 'confirmed') {
            if (!$this->on_site_at) {
                return 'on_site';
            }
            if (!$this->client_confirmed_at) {
                return 'waiting_client';
            }
            return 'start_work';
        }
        
        if ($this->status === 'in_progress') {
            return 'complete';
        }
        
        return null;
    }

    /**
     * Check if the photographer can cancel this assignment
     * Cancellation is only allowed in 'assigned' or 'confirmed' states
     * Once 'on_site' or beyond, cancellation is prohibited
     */
    public function canCancel(): bool
    {
        // Allowed cancellation states
        $allowedCancellationStates = ['assigned', 'confirmed'];
        
        // Check if current status is in allowed states
        return in_array($this->status, $allowedCancellationStates);
    }

    /**
     * Get the reason why cancellation is not allowed (for error messages)
     */
    public function getCancellationRestrictionReason(): ?string
    {
        if ($this->canCancel()) {
            return null;
        }
        
        $restrictedStates = [
            'on_site' => 'You have already marked as on-site and cannot cancel this booking.',
            'in_progress' => 'You have already started working and cannot cancel this booking.',
            'completed' => 'This booking has been completed and cannot be cancelled.',
            'cancelled' => 'This booking is already cancelled.'
        ];
        
        return $restrictedStates[$this->status] ?? 'You cannot cancel this booking at its current stage.';
    }
}