<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\UserModel;

class FreelancerRatingModel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_freelancer_ratings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'booking_id',
        'client_id',
        'freelancer_id',
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
     * Get the freelancer being rated.
     */
    public function freelancer()
    {
        return $this->belongsTo(\App\Models\Freelancer\ProfileModel::class, 'freelancer_id', 'user_id');
    }

    /**
     * Scope a query to only include published reviews.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Recompute and store the stored avg_rating/total_reviews for a
     * freelancer from its published reviews, replacing per-request avg().
     */
    public static function updateAggregate(int $freelancerUserId): void
    {
        $stats = self::where('freelancer_id', $freelancerUserId)
            ->published()
            ->selectRaw('COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews')
            ->first();

        \App\Models\Freelancer\ProfileModel::where('user_id', $freelancerUserId)->update([
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
     * Get preset reviews based on rating for freelancers.
     */
    public static function getPresetReviews($rating)
    {
        $presets = [
            'positive' => [
                'Amazing experience! The freelancer was very professional and the photos turned out beautifully.',
                'Loved working with this freelancer. Highly recommended!',
                'Fast, friendly, and delivered great quality photos. Will definitely book again.',
                'The freelancer made us feel comfortable throughout the shoot, and the results exceeded expectations.',
                'Very happy with the final edits — worth every penny.',
                'Great communication, smooth session, and stunning photos!'
            ],
            'neutral' => [
                'Overall okay experience. Photos were nice, but delivery could be faster.',
                'Good quality pictures, though communication could improve.',
                'Session felt a bit rushed, but the results were satisfactory.',
                'Satisfied with the results, but expected a bit more guidance during the shoot.'
            ],
            'negative' => [
                'Photos were below expectations and delivery was delayed.',
                'Communication needs improvement, and the results were average.',
                'Not satisfied with the editing quality.',
                'Session felt rushed and the results didnt match what was promised.'
            ]
        ];

        $type = self::getReviewTypeFromRating($rating);
        return [
            'type' => $type,
            'reviews' => $presets[$type] ?? []
        ];
    }

    /**
     * Check if a booking can be reviewed for freelancer.
     */
    public static function canReview($bookingId, $clientId)
    {
        $booking = BookingModel::where('id', $bookingId)
            ->where('client_id', $clientId)
            ->where('status', 'completed')
            ->where('booking_type', 'freelancer')
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