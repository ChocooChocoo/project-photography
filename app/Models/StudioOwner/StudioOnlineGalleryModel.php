<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookingModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\Storage;

class StudioOnlineGalleryModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_online_gallery';

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
        'client_id',
        'gallery_reference',
        'gallery_name',
        'description',
        'images',
        'status',
        'total_photos',
        'published_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'images' => 'array',
        'published_at' => 'datetime',
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
     * Get the studio associated with the gallery.
     */
    public function studio()
    {
        return $this->belongsTo(StudiosModel::class, 'studio_id');
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
            $reference = 'GAL-' . strtoupper(uniqid());
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
     * Scope to filter by studio.
     */
    public function scopeByStudio($query, $studioId)
    {
        return $query->where('studio_id', $studioId);
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
}