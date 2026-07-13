<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookingModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;

class StudioRatingModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_studio_ratings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'client_id',
        'studio_id',
        'rating',
        'title',
        'review_text',
        'review_type',
        'preset_used',
        'is_recommend',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'is_recommend' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the booking associated with this rating.
     */
    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    /**
     * Get the client who left the rating.
     */
    public function client()
    {
        return $this->belongsTo(UserModel::class, 'client_id');
    }

    /**
     * Get the studio being rated.
     */
    public function studio()
    {
        return $this->belongsTo(\App\Models\StudioOwner\StudiosModel::class, 'studio_id');
    }

    /**
     * Scope a query to only include published reviews.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Recompute and store the stored avg_rating/total_reviews for a studio
     * from its published reviews, replacing per-request avg() calculation.
     */
    public static function updateAggregate(int $studioId): void
    {
        $stats = self::where('studio_id', $studioId)
            ->published()
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        \App\Models\StudioOwner\StudiosModel::where('id', $studioId)->update([
            'avg_rating' => round($stats->avg_rating, 2),
            'total_reviews' => $stats->total_reviews,
        ]);
    }

    /**
     * Get review type based on rating.
     */
    public static function getReviewTypeFromRating($rating)
    {
        if ($rating >= 4) {
            return 'positive';
        } elseif ($rating >= 3) {
            return 'neutral';
        } else {
            return 'negative';
        }
    }

    /**
     * Get preset reviews based on rating.
     */
    public static function getPresetReviews($rating)
    {
        $presets = [
            'positive' => [
                'Amazing experience! The photos turned out beautifully and the staff was very professional.',
                'Loved the studio setup and lighting. Highly recommended!',
                'Fast, friendly, and great quality photos. Will definitely come back.',
                'The photographer made us feel comfortable, and the results exceeded expectations.',
                'Very happy with the final edits — worth every penny.',
                'Clean studio, smooth session, and stunning photos!'
            ],
            'neutral' => [
                'Overall okay experience. Photos were nice, but turnaround could be faster.',
                'Good quality pictures, though communication could improve.',
                'Studio was nice, session felt a bit rushed.',
                'Satisfied with the results, but expected a bit more guidance during the shoot.'
            ],
            'negative' => [
                'Photos were below expectations and delivery was delayed.',
                'Studio experience was average, and communication needs improvement.',
                'Not satisfied with the editing quality.',
                'Session felt rushed and results didnt match what was promised.'
            ]
        ];

        $type = self::getReviewTypeFromRating($rating);
        return [
            'type' => $type,
            'reviews' => $presets[$type] ?? []
        ];
    }

    /**
     * Check if a booking can be reviewed.
     */
    public static function canReview($bookingId, $clientId)
    {
        $booking = BookingModel::where('id', $bookingId)
            ->where('client_id', $clientId)
            ->where('status', 'completed')
            ->first();

        if (!$booking) {
            return false;
        }

        // Check if already reviewed
        $existingReview = self::where('booking_id', $bookingId)->exists();
        
        return !$existingReview;
    }

    /**
     * Get rating badge class.
     */
    public function getRatingBadgeClass()
    {
        $classes = [
            5 => 'badge-soft-success',
            4 => 'badge-soft-success',
            3 => 'badge-soft-warning',
            2 => 'badge-soft-danger',
            1 => 'badge-soft-danger',
        ];
        
        return $classes[$this->rating] ?? 'badge-soft-secondary';
    }

    /**
     * Get rating stars HTML.
     */
    public function getRatingStarsAttribute()
    {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $stars .= '<i class="ti ti-star-filled text-warning fs-5"></i>';
            } else {
                $stars .= '<i class="ti ti-star text-warning fs-5"></i>';
            }
        }
        return $stars;
    }
}