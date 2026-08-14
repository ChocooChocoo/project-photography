<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudioMemberModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_members';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'studio_id',
        'freelancer_id',
        'invited_by',
        'invitation_message',
        'status',
        'response_message',
        'invited_at',
        'responded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invited_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the studio associated with the invitation.
     */
    public function studio(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StudioOwner\StudiosModel::class, 'studio_id');
    }

    /**
     * Get the freelancer user associated with the invitation.
     */
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StudioOwner\UserModel::class, 'freelancer_id');
    }

    /**
     * Get the owner who sent the invitation.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(\App\Models\StudioOwner\UserModel::class, 'invited_by');
    }

    /**
     * Scope for pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved invitations.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for rejected invitations.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invitation is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if invitation is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if invitation is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}