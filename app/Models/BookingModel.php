<?php

namespace App\Models;

use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
    * Booking Status Constants
    */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
    * Payment Status Constants
    */
    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    /**
    * The table associated with the model.
    *
    * @var string
    */
    protected $table = 'tbl_bookings';

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
        'booking_reference',
        'client_id',
        'booking_type',
        'provider_id',
        'category_id',
        'event_name',
        'event_date',
        'start_time',
        'end_time',
        'location_type',
        'venue_name',
        'street',
        'barangay',
        'city',
        'province',
        'multiple_locations',
        'special_requests',
        'total_amount',
        'down_payment',
        'remaining_balance',
        'deposit_policy',
        'payment_type',
        'status',
        'payment_status',
        'cancellation_reason',
        'cancelled_by',
    ];

    /**
    * The attributes that should be cast.
    *
    * @var array<string, string>
    */
    protected $casts = [
        'event_date' => 'date',
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'multiple_locations' => 'array',
    ];

    /**
    * Get the client who made the booking.
    */
    public function client()
    {
        return $this->belongsTo(UserModel::class, 'client_id');
    }

    /**
    * Get the studio for studio bookings.
    */
    public function studio()
    {
        if ($this->booking_type === 'studio') {
            return $this->belongsTo(\App\Models\StudioOwner\StudiosModel::class, 'provider_id');
        }
        return null;
    }

    /**
    * Get the freelancer for freelancer bookings.
    */
    public function freelancer()
    {
        if ($this->booking_type === 'freelancer') {
            return $this->belongsTo(\App\Models\Freelancer\ProfileModel::class, 'provider_id', 'user_id');
        }
        return null;
    }

    /**
    * Get the category for the booking.
    */
    public function category()
    {
        return $this->belongsTo(\App\Models\Admin\CategoriesModel::class, 'category_id');
    }

    /**
    * Get the packages for this booking.
    */
    public function packages()
    {
        return $this->hasMany(BookingPackageModel::class, 'booking_id');
    }

    /**
    * Get the payments for this booking.
    */
    public function payments()
    {
        return $this->hasMany(PaymentModel::class, 'booking_id');
    }

    /**
    * Get assigned photographers for this booking.
    */
    public function assignedPhotographers()
    {
        return $this->hasMany(\App\Models\StudioOwner\BookingAssignedPhotographerModel::class, 'booking_id');
    }

    /**
     * Get the studio online gallery associated with this booking.
     */
    public function studioOnlineGallery()
    {
        return $this->hasOne(StudioOnlineGalleryModel::class, 'booking_id');
    }

    /**
    * Get the provider based on booking type.
    */
    public function provider()
    {
        if ($this->booking_type === 'studio') {
            return $this->studio();
        } elseif ($this->booking_type === 'freelancer') {
            return $this->freelancer();
        }
        return null;
    }

    /**
    * Generate a unique booking reference.
    */
    public static function generateBookingReference()
    {
        do {
            $reference = 'BK-' . strtoupper(uniqid());
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
    * Check if booking is confirmed.
    */
    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    /**
    * Check if booking is paid.
    */
    public function isPaid()
    {
        return $this->payment_status === 'paid';
    }

    /**
    * Check if booking is partially paid.
    */
    public function isPartiallyPaid()
    {
        return $this->payment_status === 'partially_paid';
    }

    /**
    * Check if payment type is full payment.
    */
    public function isFullPayment()
    {
        return $this->payment_type === 'full_payment';
    }

    /**
     * Check if this booking requires photographer arrival confirmation.
     */
    public function requiresLocationConfirmation(): bool
    {
        return $this->location_type === 'on-location';
    }

    /**
    * Check if all photographers have completed their assignments
    */
    public function allPhotographersCompleted(): bool
    {
        $assignments = $this->assignedPhotographers;
        
        if ($assignments->isEmpty()) {
            return false;
        }
        
        foreach ($assignments as $assignment) {
            if ($assignment->status !== 'completed') {
                return false;
            }
        }
        
        return true;
    }

    /**
    *  FIXED: Get total paid amount from successful payments 
    */
    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'succeeded')
            ->sum('amount');
    }

    /**
    * Remaining balance = total_amount - total_paid
    */
    public function getRemainingBalanceAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->total_paid);
    }

    /**
    *  FIXED: Check if booking is fully paid 
    */
    public function isFullyPaid(): bool
    {
        return $this->total_paid >= (float) $this->total_amount;
    }

    /**
    *  NEW: Update payment status based on total paid 
    */
    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->total_paid;
        
        if ($totalPaid <= 0) {
            $this->payment_status = self::PAYMENT_PENDING;
        } elseif ($totalPaid < (float) $this->total_amount) {
            $this->payment_status = self::PAYMENT_PARTIALLY_PAID;
        } else {
            $this->payment_status = self::PAYMENT_PAID;
        }
        
        $this->saveQuietly(); // Save without firing events
    }

    /**
    *  NEW: Recalculate and update remaining balance 
    */
    public function recalculateRemainingBalance(): void
    {
        // Don't modify the attribute directly - let the accessor handle it
        // Just ensure payment status is correct
        $this->updatePaymentStatus();
    }

    /**
    * Check if booking can be marked as completed.
    * Only allowed if payment is fully paid.
    */
    public function canMarkAsCompleted(): bool
    {
        return $this->isFullyPaid() && 
               $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if this studio booking requires online gallery delivery before completion.
     */
    public function requiresOnlineGalleryUpload(): bool
    {
        if ($this->booking_type !== 'studio') {
            return false;
        }

        $this->loadMissing('packages.studioPackage');

        $requiresFromSnapshot = $this->packages
            ->where('package_type', 'studio')
            ->contains(function (BookingPackageModel $package) {
                return (bool) optional($package->studioPackage)->online_gallery;
            });

        if ($requiresFromSnapshot) {
            return true;
        }

        $hasResolvableStudioSnapshot = $this->packages
            ->where('package_type', 'studio')
            ->contains(function (BookingPackageModel $package) {
                return !is_null($package->package_id);
            });

        // Fallback for older bookings missing tbl_booking_packages snapshots or partial snapshot payloads.
        if ($this->packages->isEmpty() || !$hasResolvableStudioSnapshot) {
            $matchedStudioPackage = StudioPackagesModel::query()
                ->where('studio_id', $this->provider_id)
                ->where('category_id', $this->category_id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->where('package_price', $this->total_amount)
                        ->orWhere('package_name', $this->event_name);
                })
                ->orderByRaw('CASE WHEN package_price = ? THEN 0 ELSE 1 END', [(float) $this->total_amount])
                ->first();

            return (bool) optional($matchedStudioPackage)->online_gallery;
        }

        return false;
    }

    /**
     * Check if this booking has at least one uploaded gallery image.
     */
    public function hasUploadedGalleryContent(): bool
    {
        $gallery = $this->relationLoaded('studioOnlineGallery')
            ? $this->studioOnlineGallery
            : $this->studioOnlineGallery()->first();

        if (!$gallery) {
            return false;
        }

        $images = is_array($gallery->images) ? array_filter($gallery->images) : [];
        $totalPhotos = (int) ($gallery->total_photos ?? 0);

        return $totalPhotos > 0 || count($images) > 0;
    }

    /**
     * Check if required gallery delivery is satisfied.
     */
    public function isGalleryReadyForCompletion(): bool
    {
        if (!$this->requiresOnlineGalleryUpload()) {
            return true;
        }

        return $this->hasUploadedGalleryContent();
    }

    /**
     * Get the blocking reason when completion is not yet allowed.
     */
    public function getGalleryCompletionBlockReason(): ?string
    {
        if (!$this->requiresOnlineGalleryUpload() || $this->hasUploadedGalleryContent()) {
            return null;
        }

        return 'Cannot mark as completed until at least one image is uploaded to the client\'s online gallery.';
    }

    /**
    * Check if status transition is allowed.
    */
    public function canTransitionTo(string $newStatus): bool
    {
        // Allowed status transitions
        $allowedTransitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];
        
        $allowed = $allowedTransitions[$this->status] ?? [];
        
        if (!in_array($newStatus, $allowed)) {
            return false;
        }

        // Special rule: Completed status requires full payment
        if ($newStatus === self::STATUS_COMPLETED) {
            return $this->canMarkAsCompleted();
        }

        return true;
    }

    /**
    * Get available next statuses for dropdown.
    */
    public function getAvailableStatuses(): array
    {
        $statuses = [];
        $allowedTransitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ][$this->status] ?? [];
        
        foreach ($allowedTransitions as $status) {
            if ($status === self::STATUS_COMPLETED && !$this->canMarkAsCompleted()) {
                continue; // Skip completed if not fully paid
            }
            
            $statuses[$status] = ucwords(str_replace('_', ' ', $status));
        }
        
        return $statuses;
    }

    /**
    * Get status badge class.
    */
    public function getStatusBadgeClass(): string
    {
        $classes = [
            self::STATUS_PENDING => 'badge-soft-warning',
            self::STATUS_CONFIRMED => 'badge-soft-success',
            self::STATUS_IN_PROGRESS => 'badge-soft-info',
            self::STATUS_COMPLETED => 'badge-soft-secondary',
            self::STATUS_CANCELLED => 'badge-soft-danger'
        ];
        
        return $classes[$this->status] ?? 'badge-soft-secondary';
    }

    /**
    * Get payment status badge class.
    */
    public function getPaymentStatusBadgeClass(): string
    {
        $classes = [
            self::PAYMENT_PENDING => 'badge-soft-warning',
            self::PAYMENT_PARTIALLY_PAID => 'badge-soft-info',
            self::PAYMENT_PAID => 'badge-soft-success',
            self::PAYMENT_FAILED => 'badge-soft-danger',
            self::PAYMENT_REFUNDED => 'badge-soft-secondary'
        ];
        
        return $classes[$this->payment_status] ?? 'badge-soft-secondary';
    }

    /**
     * Check if booking has multiple locations
     */
    public function hasMultipleLocations(): bool
    {
        return !empty($this->multiple_locations) && count($this->multiple_locations) > 1;
    }

    /**
     * Get formatted locations for display
     */
    public function getFormattedLocationsAttribute(): array
    {
        if (empty($this->multiple_locations)) {
            // Fallback to single location fields
            return [[
                'venue_name' => $this->venue_name,
                'street' => $this->street,
                'barangay' => $this->barangay,
                'city' => $this->city,
                'province' => $this->province,
            ]];
        }

        return $this->multiple_locations;
    }

    /**
     * Get primary location for display (first location)
     */
    public function getPrimaryLocationAttribute(): ?array
    {
        $locations = $this->formatted_locations;
        return $locations[0] ?? null;
    }

    /**
     * Get location count
     */
    public function getLocationCountAttribute(): int
    {
        if (!empty($this->multiple_locations)) {
            return count($this->multiple_locations);
        }
        
        // If using single location fields
        return !empty($this->city) ? 1 : 0;
    }
}
