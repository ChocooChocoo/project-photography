<?php
// Local-only T-15 browser fixtures. Every booking reference starts with "T15-".
// Run: php artisan tinker output/t15-fixtures.php   Remove: see t15-fixtures-cleanup.php

use App\Models\BookingCancellationRecoveryModel;
use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\HardcopyOrderModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use Illuminate\Support\Facades\DB;

$studioId = 4; $clientId = 42; $ownerId = 25; $packageId = 31; // Vows and Verses Basic, 1 photographer, ₱7,900

$make = function (string $ref, array $extra) use ($studioId, $clientId, $packageId) {
    BookingModel::withTrashed()->where('booking_reference', $ref)->forceDelete();
    $id = DB::table('tbl_bookings')->insertGetId(array_merge([
        'booking_reference' => $ref, 'client_id' => $clientId, 'booking_type' => 'studio', 'provider_id' => $studioId,
        'category_id' => 1, 'event_date' => now()->subDays(3)->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
        'location_type' => 'in-studio', 'total_amount' => 7900, 'down_payment' => 7900, 'remaining_balance' => 0,
        'deposit_policy' => '100% (Full payment)', 'payment_type' => 'full_payment', 'status' => 'in_progress',
        'payment_status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
    ], $extra));
    BookingPackageModel::create(['booking_id' => $id, 'package_id' => $packageId, 'package_type' => 'studio',
        'package_name' => 'Vows and Verses Collection - Basic', 'package_price' => 7900]);
    return BookingModel::find($id);
};
$pay = function (BookingModel $b, float $amount, string $suffix) {
    PaymentModel::create(['booking_id' => $b->id, 'payment_reference' => 'T15-PAY-'.$b->id.'-'.$suffix,
        'amount' => $amount, 'payment_method' => 'gcash', 'status' => 'succeeded']);
};
$assign = function (BookingModel $b, int $photographerId, string $status) use ($studioId, $ownerId) {
    return BookingAssignedPhotographerModel::create(['booking_id' => $b->id, 'studio_id' => $studioId,
        'photographer_id' => $photographerId, 'assigned_by' => $ownerId, 'status' => $status,
        'assigned_at' => now()->subDays(5), 'confirmed_at' => now()->subDays(5),
        'completed_at' => $status === 'completed' ? now()->subDay() : null]);
};

// A. In progress, photographer finished, prints READY, hardcopy delivery -> owner can complete; assign button hidden.
$a = $make('T15-A-HARDCOPY', ['delivery_type' => null]);
$pay($a, 7900, '1');
$assign($a, 28, 'completed');
HardcopyOrderModel::create(['booking_id' => $a->id, 'fulfillment_method' => 'pickup', 'suggested_fulfillment_method' => 'pickup',
    'amount' => 0, 'status' => 'ready', 'accepted_at' => now()->subDays(2)]);

// B. Same, but prints still PREPARING -> owner cannot complete.
$b = $make('T15-B-PREPARING', []);
$pay($b, 7900, '1');
$assign($b, 29, 'completed');
HardcopyOrderModel::create(['booking_id' => $b->id, 'fulfillment_method' => 'pickup', 'suggested_fulfillment_method' => 'pickup',
    'amount' => 0, 'status' => 'preparing', 'accepted_at' => now()->subDays(2)]);

// C. Completed booking -> photographer 30 sees "Booking Completed"; no assign button for owner.
$c = $make('T15-C-COMPLETED', ['status' => 'completed', 'completed_at' => now()->subDay(), 'delivery_type' => 'hardcopy']);
$pay($c, 7900, '1');
$assign($c, 30, 'completed');

// D. Two pending bookings on the same future day -> slot counter shows 1 of 3 left.
$day = now()->next('Wednesday')->addWeek()->toDateString();
$make('T15-D-SLOT1', ['event_date' => $day, 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'pending', 'payment_status' => 'pending']);
$make('T15-D-SLOT2', ['event_date' => $day, 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'confirmed', 'payment_status' => 'pending']);

// E. Client cancelled a fully paid booking (₱7,900, studio 30% downpayment terms -> ₱2,370 kept, max ₱5,530).
$e = $make('T15-E-CLIENT-CANCEL', ['event_date' => now()->addDays(10)->toDateString(), 'status' => 'cancelled',
    'cancelled_by' => 'client', 'cancellation_reason' => 'T15 browser check', 'payment_status' => 'refund_pending']);
$pay($e, 7900, '1');
BookingCancellationRecoveryModel::create(['booking_id' => $e->id, 'studio_id' => $studioId, 'status' => 'refund_pending',
    'deadline' => now()->addDay(), 'outcome_reason' => 'Client cancelled a fully paid booking.']);

// F. Studio cancelled a paid booking -> full refund, not editable.
$f = $make('T15-F-STUDIO-CANCEL', ['event_date' => now()->addDays(12)->toDateString(), 'status' => 'cancelled',
    'cancelled_by' => 'studio', 'cancellation_reason' => 'T15 browser check', 'payment_status' => 'refund_pending']);
$pay($f, 7900, '1');
BookingCancellationRecoveryModel::create(['booking_id' => $f->id, 'studio_id' => $studioId, 'status' => 'refund_pending',
    'deadline' => now()->addDay(), 'outcome_reason' => 'Studio cancelled.']);

echo json_encode(['A' => $a->id, 'B' => $b->id, 'C' => $c->id, 'D_day' => $day, 'E' => $e->id, 'F' => $f->id]).PHP_EOL;
