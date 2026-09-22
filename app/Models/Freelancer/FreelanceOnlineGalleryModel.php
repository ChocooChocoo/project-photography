<?php

namespace App\Models\Freelancer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookingModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Storage;

class FreelanceOnlineGalleryModel extends Model
{
    use HasFactory;

    /**
     * Gallery delivery states.
     */
    public const GALLERY_STATUS_DRAFT = 'draft';

    public const GALLERY_STATUS_PUBLISHED = 'published';

    /**
     * Approval states for a gallery.
     */
    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const APPROVAL_CANCELLED = 'cancelled';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_freelancer_online_gallery';

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
        'freelancer_id',
        'client_id',
        'gallery_type',
        'gallery_reference',
        'gallery_name',
        'description',
        'images',
        'status',
        'total_photos',
        'published_at',
        'gallery_status',
        'approval_status',
        'rejection_reason',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'images' => 'array',
        'published_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the booking associated with the gallery.
     */
    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    /**
     * Get the freelancer associated with the gallery.
     */
    public function freelancer()
    {
        return $this->belongsTo(UserModel::class, 'freelancer_id');
    }

    /**
     * Get the client associated with the gallery.
     */
    public function client()
    {
        return $this->belongsTo(UserModel::class, 'client_id');
    }

    /**
     * Generate a unique gallery reference.
     */
    public static function generateGalleryReference()
    {
        do {
            $reference = 'FL-GAL-' . strtoupper(uniqid());
        } while (self::where('gallery_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Check if gallery is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Get total photos count.
     */
    public function getTotalPhotosCountAttribute()
    {
        return count($this->images ?? []);
    }

    /**
     * Get first image as thumbnail.
     */
    public function getThumbnailAttribute()
    {
        $path = $this->images[0] ?? null;

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Scope to filter by freelancer.
     */
    public function scopeByFreelancer($query, $freelancerId)
    {
        return $query->where('freelancer_id', $freelancerId);
    }

    /**
     * Scope to filter by client.
     */
    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope to filter active galleries.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if gallery is published to the client.
     */
    public function isPublished()
    {
        return $this->gallery_status === self::GALLERY_STATUS_PUBLISHED;
    }

    /**
     * Check if the gallery waits for owner approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /**
     * Check if the owner approved the gallery.
     */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    /**
     * Check if the gallery may be published to the client.
     */
    public function canPublish(): bool
    {
        return $this->isApproved();
    }

    /**
     * Gallery statuses the model accepts.
     *
     * @return array<int, string>
     */
    public static function allowedGalleryStatuses(): array
    {
        return [
            self::GALLERY_STATUS_DRAFT,
            self::GALLERY_STATUS_PUBLISHED,
        ];
    }

    /**
     * Scope to filter published galleries.
     */
    public function scopePublished($query)
    {
        return $query->where('gallery_status', self::GALLERY_STATUS_PUBLISHED);
    }

    /**
     * Scope to filter draft galleries.
     */
    public function scopeDraft($query)
    {
        return $query->where('gallery_status', self::GALLERY_STATUS_DRAFT);
    }
}