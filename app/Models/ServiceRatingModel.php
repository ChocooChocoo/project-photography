<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRatingModel extends Model
{
    protected $table = 'tbl_service_ratings';

    protected $guarded = ['id'];

    protected $casts = ['rating' => 'integer'];

    protected static function booted(): void
    {
        static::saved(fn ($rating) => $rating->updateAggregate());
        static::deleted(fn ($rating) => $rating->updateAggregate());
    }

    public function updateAggregate(): void
    {
        $type = match ($this->getTable()) {
            'tbl_photographer_ratings' => 'photographer',
            'tbl_photo_ratings' => 'photo',
            default => 'service',
        };
        $target = match ($type) {
            'photographer' => ['photographer_id' => $this->photographer_id],
            'photo' => ['gallery_id' => $this->gallery_id, 'gallery_type' => $this->gallery_type],
            default => ['package_id' => $this->package_id, 'package_type' => $this->package_type],
        };
        $stats = static::where($target)->published()->selectRaw('COALESCE(AVG(rating), 0) as average, COUNT(rating) as total')->first();
        \Illuminate\Support\Facades\DB::table('tbl_feedback_aggregates')->updateOrInsert([
            'target_type' => $type, 'target_scope' => $target['package_type'] ?? $target['gallery_type'] ?? 'studio',
            'target_id' => $target['package_id'] ?? $target['gallery_id'] ?? $target['photographer_id'],
        ], ['avg_rating' => round((float) $stats->average, 2), 'total_reviews' => $stats->total]);
    }

    public function booking()
    {
        return $this->belongsTo(BookingModel::class, 'booking_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
