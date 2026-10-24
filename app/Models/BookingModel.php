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
     * Assignment statuses that still hold a photographer slot on a booking.
     * A cancelled or completed assignment does not consume a slot.
     *
     * @var array<int, string>
     */
    public const ACTIVE_ASSIGNMENT_STATUSES = ['assigned', 'confirmed', 'on_site', 'in_progress'];

    /**
     * Payment Status Constants
     */
    public const PAYMENT_PENDING = 'pending';

    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';

    public const PAYMENT_PAID = 'paid';

    public const PAYMENT_FAILED = 'failed';

    public const PAYMENT_REFUND_PENDING = 'refund_pending';

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
        'expires_at',
        'completed_at',
        'revision_requested_at',
        'revision_deadline',
        'booking_frequency',
        'recurrence_pattern',
        'parent_booking_id',
        'event_end_date',
        'booking_duration_type',
        'terms_accepted_at',
        'preferred_camera_brand',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'event_date' => 'date',
        'event_end_date' => 'date',
        'terms_accepted_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'down_payment' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'multiple_locations' => 'array',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
        'revision_requested_at' => 'datetime',
        'revision_deadline' => 'datetime',
        'recurrence_pattern' => 'array',
    ];

    public const DURATION_SHORT_TERM = 'short_term';

    public const DURATION_LONG_TERM = 'long_term';

    /**
     * Bookings whose date span (event_date..event_end_date, or the single day) overlaps the given range.
     */
    public function scopeOverlappingDates($query, $startDate, $endDate = null)
    {
        $startDate = \Carbon\Carbon::parse($startDate)->toDateString();
        $endDate = \Carbon\Carbon::parse($endDate ?? $startDate)->toDateString();

        return $query->whereDate('event_date', '<=', $endDate)
            ->whereRaw('date(coalesce(event_end_date, event_date)) >= ?', [$startDate]);
    }

    protected static function booted(): void
    {
        static::updated(function ($booking) {
            if ($booking->wasChanged('status') && in_array($booking->status, ['cancelled', 'completed'])) {
                app(\App\Services\FreelancerBookingAccess::class)->revoke($booking->id);
            }
        });
        static::deleted(fn ($booking) => app(\App\Services\FreelancerBookingAccess::class)->revoke($booking->id));
    }

    /**
     * Short-term covers one day; long-term covers two or more days.
     */
    public static function durationTypeFor($startDate, $endDate = null): string
    {
        if (! $endDate) {
            return self::DURATION_SHORT_TERM;
        }

        return \Carbon\Carbon::parse($endDate)->gt(\Carbon\Carbon::parse($startDate))
            ? self::DURATION_LONG_TERM
            : self::DURATION_SHORT_TERM;
    }

    public function contract()
    {
        return $this->hasOne(BookingContractModel::class, 'booking_id');
    }

    public function events()
    {
        return $this->hasMany(BookingEventModel::class, 'booking_id')->orderBy('occurred_at')->orderBy('id');
    }

    public function equipment()
    {
        return $this->hasMany(\App\Models\StudioOwner\BookingEquipmentModel::class, 'booking_id');
    }

    public function preparationVisible(): bool
    {
        return in_array($this->payment_status, [self::PAYMENT_PAID, self::PAYMENT_PARTIALLY_PAID, self::PAYMENT_REFUND_PENDING, self::PAYMENT_REFUNDED], true);
    }

    /**
     * Contract state for the booking detail screens, or null for short-term bookings.
     *
     * @param  string  $role  client|owner|freelancer, picks the contract page URL
     */
    public function contractSummary(string $role): ?array
    {
        if (! $this->requiresContract() || ! $this->contract) {
            return null;
        }

        return [
            'status' => $this->contract->status,
            'client_signed' => $this->contract->client_signed_at !== null,
            'provider_signed' => $this->contract->studio_signed_at !== null,
            'fully_signed' => $this->contract->isFullySigned(),
            'url' => route($role.'.contracts.show', ['booking' => $this->id], false),
        ];
    }

    public function requiresContract(): bool
    {
        return $this->booking_duration_type === self::DURATION_LONG_TERM;
    }

    public function contractFullySigned(): bool
    {
        return $this->contract?->isFullySigned() === true;
    }

    /**
     * Confirmation gate: long-term bookings need both signatures first.
     */
    public function canBeConfirmed(): bool
    {
        return ! $this->requiresContract() || $this->contractFullySigned();
    }

    /**
     * Move a pending booking to confirmed unless the contract gate blocks it.
     */
    public function confirmIfEligible(): bool
    {
        if ($this->status !== self::STATUS_PENDING || ! $this->canBeConfirmed()) {
            return false;
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->save();

        return true;
    }

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
     * Get the parent booking of a recurring child session.
     */
    public function parent()
    {
        return $this->belongsTo(BookingModel::class, 'parent_booking_id');
    }

    /**
     * Get the child sessions of a recurring booking.
     */
    public function children()
    {
        return $this->hasMany(BookingModel::class, 'parent_booking_id');
    }

    /**
     * Check if the booking is part of a recurring series.
     */
    public function isRecurring(): bool
    {
        return $this->booking_frequency === 'recurring';
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
            $reference = 'BK-'.strtoupper(uniqid());
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
        $assignments = $this->assignedPhotographers->where('status', '!=', 'cancelled');

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
        $previous = $this->payment_status;
        $totalPaid = $this->total_paid;

        if ($totalPaid <= 0) {
            $this->payment_status = self::PAYMENT_PENDING;
        } elseif ($totalPaid < (float) $this->total_amount) {
            $this->payment_status = self::PAYMENT_PARTIALLY_PAID;
        } else {
            $this->payment_status = self::PAYMENT_PAID;
        }

        $this->saveQuietly(); // Save without firing events
        if ($previous !== self::PAYMENT_PAID && $this->payment_status === self::PAYMENT_PAID) {
            app(\App\Services\BookingEventRecorder::class)->record($this->id, 'payment_settled', 'Payment settled', 'payment_settled');
        }
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
               $this->status === self::STATUS_IN_PROGRESS &&
               $this->getDeliveryCompletionBlockReason() === null;
    }

    /**
     * Check if the client can still request a revision on this booking.
     */
    public function canRequestRevision(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->revision_deadline
            && now()->lt($this->revision_deadline)
            && ! $this->revision_requested_at;
    }

    /**
     * Check if this studio booking requires online gallery delivery before completion.
     */
    public function requiresOnlineGalleryUpload(): bool
    {
        if ($this->delivery_type === 'hardcopy') return false;
        if (in_array($this->delivery_type, ['softcopy', 'both'], true)) return true;
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
                return ! is_null($package->package_id);
            });

        // Fallback for older bookings missing tbl_booking_packages snapshots or partial snapshot payloads.
        if ($this->packages->isEmpty() || ! $hasResolvableStudioSnapshot) {
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

        if (! $gallery) {
            return false;
        }

        $images = is_array($gallery->images) ? array_filter($gallery->images) : [];
        $totalPhotos = (int) ($gallery->total_photos ?? 0);

        $validDrive = $gallery->drive_link && \Illuminate\Support\Facades\Validator::make(
            ['link' => $gallery->drive_link], ['link' => ['url', new \App\Rules\DriveLink]]
        )->passes();
        return $totalPhotos > 0 || count($images) > 0 || $validDrive;
    }

    /**
     * Check if the booking has an online gallery the client can see.
     */
    public function onlineGalleryIsPublished(): bool
    {
        $gallery = $this->relationLoaded('studioOnlineGallery')
            ? $this->studioOnlineGallery
            : $this->studioOnlineGallery()->first();

        return $gallery !== null && $gallery->status === 'active' && $gallery->isPublished()
            && ($gallery->approval_status === null || $gallery->isApproved());
    }

    /**
     * Check if required gallery delivery is satisfied. The gallery must hold at
     * least one image and be published, because a draft is invisible to the client.
     */
    public function isGalleryReadyForCompletion(): bool
    {
        if (! $this->requiresOnlineGalleryUpload()) {
            return true;
        }

        return $this->hasUploadedGalleryContent() && $this->onlineGalleryIsPublished();
    }

    /**
     * Get the blocking reason when completion is not yet allowed.
     */
    public function getGalleryCompletionBlockReason(): ?string
    {
        if (! $this->requiresOnlineGalleryUpload()) {
            return null;
        }

        if (! $this->hasUploadedGalleryContent()) {
            return 'Cannot mark as completed until at least one image is uploaded or a Google Drive link is shared in the client\'s online gallery.';
        }

        if (! $this->onlineGalleryIsPublished()) {
            return 'Cannot mark as completed until the client\'s online gallery is published.';
        }

        return null;
    }

    /**
     * The provider's downpayment setting, or null when bookings are full payment only.
     *
     * Studios: owner switch requires_downpayment, with downpayment_type percentage
     * (downpayment_percentage) or fixed (downpayment_amount). Freelancers:
     * deposit_policy, deposit_type and deposit_amount on the profile.
     *
     * @return array{type: string, value: float, label: string}|null
     */
    public static function depositTerms(string $providerType, $provider): ?array
    {
        if ($providerType === 'studio') {
            if (! $provider || $provider->requires_downpayment === false) {
                return null;
            }
            $type = $provider->downpayment_type === 'fixed' ? 'fixed' : 'percentage';
            $value = (float) ($type === 'fixed' ? $provider->downpayment_amount : ($provider->downpayment_percentage ?? 30));
        } else {
            if (! $provider || $provider->deposit_policy !== 'required') {
                return null;
            }
            $type = $provider->deposit_type === 'fixed' ? 'fixed' : 'percentage';
            $value = (float) ($provider->deposit_amount ?? 30);
        }

        if ($value <= 0) {
            return null;
        }

        $label = $type === 'fixed'
            ? '₱'.number_format($value, 2).' downpayment'
            : rtrim(rtrim(number_format($value, 2), '0'), '.').'% downpayment';

        return ['type' => $type, 'value' => $value, 'label' => $label];
    }

    /**
     * Work out what the client pays now. Studio clients choose a downpayment or
     * full payment when the studio allows it; a freelancer's policy decides.
     *
     * @return array{payment_type: string, down_payment: float, remaining_balance: float, deposit_policy: string, downpayment_percentage: float, deposit_type: ?string, deposit_amount: float}
     */
    public static function paymentTerms(string $providerType, $provider, float $total, ?string $requested): array
    {
        $terms = self::depositTerms($providerType, $provider);
        $wantsDownpayment = $providerType === 'studio' ? $requested === 'downpayment' : true;

        if ($terms && $wantsDownpayment) {
            $down = $terms['type'] === 'fixed'
                ? min($terms['value'], $total)
                : round($total * $terms['value'] / 100, 2);

            if ($down < $total) {
                return [
                    'payment_type' => 'downpayment',
                    'down_payment' => $down,
                    'remaining_balance' => round($total - $down, 2),
                    'deposit_policy' => $terms['type'] === 'fixed'
                        ? 'Fixed: ₱'.number_format($terms['value'], 2)
                        : rtrim(rtrim(number_format($terms['value'], 2), '0'), '.').'%',
                    'downpayment_percentage' => $terms['type'] === 'percentage' ? $terms['value'] : 0,
                    'deposit_type' => $terms['type'],
                    'deposit_amount' => $terms['value'],
                ];
            }
        }

        return [
            'payment_type' => 'full_payment',
            'down_payment' => $total,
            'remaining_balance' => 0,
            'deposit_policy' => '100% (Full payment)',
            'downpayment_percentage' => 100,
            'deposit_type' => null,
            'deposit_amount' => 0,
        ];
    }

    /**
     * The payment terms this booking was made with, for display. Read from the
     * booking itself so a later change to the provider's setting does not
     * rewrite what the client agreed to.
     *
     * @return array{policy: string, type: ?string, amount: float, display: string, is_percentage: bool, is_fixed: bool, is_no_deposit: bool}
     */
    public function depositInfo(): array
    {
        if ($this->payment_type !== 'downpayment') {
            return ['policy' => 'not_required', 'type' => null, 'amount' => 0.0, 'display' => 'Full Payment',
                'is_percentage' => false, 'is_fixed' => false, 'is_no_deposit' => true];
        }

        $down = (float) $this->getRawOriginal('down_payment');
        if (str_starts_with((string) $this->deposit_policy, 'Fixed')) {
            return ['policy' => 'required', 'type' => 'fixed', 'amount' => $down,
                'display' => 'Fixed downpayment: ₱'.number_format($down, 2),
                'is_percentage' => false, 'is_fixed' => true, 'is_no_deposit' => false];
        }

        $total = (float) $this->total_amount;
        $percent = (float) $this->deposit_policy ?: ($total > 0 ? round($down / $total * 100, 2) : 0);

        return ['policy' => 'required', 'type' => 'percentage', 'amount' => $percent,
            'display' => rtrim(rtrim(number_format($percent, 2), '0'), '.').'% Downpayment',
            'is_percentage' => true, 'is_fixed' => false, 'is_no_deposit' => false];
    }

    /**
     * What happens to the client's money if they cancel now, or null when
     * nothing has been paid. A downpayment is kept; a full payment can be
     * refunded up to the amount paid, at the provider's discretion.
     */
    public function cancellationRefundNote(): ?string
    {
        $paid = (float) ($this->relationLoaded('payments')
            ? $this->payments->where('status', 'succeeded')->sum('amount')
            : $this->payments()->where('status', 'succeeded')->sum('amount'));
        if ($paid <= 0) {
            return null;
        }

        $provider = $this->booking_type === 'studio' ? 'the studio' : 'the photographer';

        return $this->isFullyPaid()
            ? 'You paid in full, so '.$provider.' will decide your refund, up to ₱'.number_format($paid, 2).'.'
            : 'Your downpayment of ₱'.number_format($paid, 2).' is non-refundable and is kept by '.$provider.'.';
    }

    /**
     * When the event ends, in Asia/Manila. Uses the last event day and the end
     * time; a single-day booking whose end time is not after its start time
     * runs past midnight. With no end time, the event ends at the end of the day.
     */
    public function eventEndsAt(): \Carbon\Carbon
    {
        $day = ($this->event_end_date ?? $this->event_date)->format('Y-m-d');

        if (! $this->end_time) {
            return \Carbon\Carbon::parse($day, 'Asia/Manila')->endOfDay();
        }

        $end = \Carbon\Carbon::parse($day.' '.$this->end_time, 'Asia/Manila');
        if (! $this->event_end_date && $this->start_time && $this->end_time <= $this->start_time) {
            $end->addDay();
        }

        return $end;
    }

    public function eventHasEnded(): bool
    {
        return $this->eventEndsAt()->isPast();
    }

    public function getDeliveryCompletionBlockReason(): ?string
    {
        // Null delivery_type preserves pre-migration bookings while still enforcing their orders.
        $orders = \Illuminate\Support\Facades\Schema::hasTable('tbl_booking_hardcopy_orders')
            ? HardcopyOrderModel::where('booking_id', $this->id)->where('status', '!=', 'cancelled')->get()
            : collect();
        if ($orders->contains(fn ($order) => !$order->accepted_at || $order->status !== 'delivered')) {
            return 'All hardcopy orders must be accepted and delivered before completion.';
        }
        if (in_array($this->delivery_type, ['hardcopy', 'both'], true) && $orders->isEmpty()) {
            return 'Add and deliver a hardcopy order before selecting this delivery type.';
        }
        return $this->getGalleryCompletionBlockReason();
    }

    /**
     * Deadline by which a cancellation recovery must be resolved: the earlier of
     * one day from now and two hours before the event starts.
     */
    public function cancellationRecoveryDeadline(): \Carbon\Carbon
    {
        $now = \Carbon\Carbon::now('Asia/Manila');
        $eventStart = \Carbon\Carbon::parse($this->event_date->format('Y-m-d').' '.$this->start_time, 'Asia/Manila');

        return $now->copy()->addDay()->min($eventStart->subHours(2));
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

        if (! in_array($newStatus, $allowed)) {
            return false;
        }

        if ($newStatus === self::STATUS_CONFIRMED && ! $this->canBeConfirmed()) {
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
            if ($status === self::STATUS_CONFIRMED && ! $this->canBeConfirmed()) {
                continue; // Long-term bookings need both contract signatures first
            }

            if ($status === self::STATUS_COMPLETED && ! $this->canMarkAsCompleted()) {
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
            self::STATUS_CANCELLED => 'badge-soft-danger',
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
            self::PAYMENT_REFUND_PENDING => 'badge-soft-warning',
            self::PAYMENT_REFUNDED => 'badge-soft-secondary',
        ];

        return $classes[$this->payment_status] ?? 'badge-soft-secondary';
    }

    /**
     * Check if booking has multiple locations
     */
    public function hasMultipleLocations(): bool
    {
        return ! empty($this->multiple_locations) && count($this->multiple_locations) > 1;
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
        if (! empty($this->multiple_locations)) {
            return count($this->multiple_locations);
        }

        // If using single location fields
        return ! empty($this->city) ? 1 : 0;
    }
}
