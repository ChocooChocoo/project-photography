<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BookingModel;
use App\Models\StudioOwner\StudioOnlineGalleryModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

$database = DB::connection()->getDatabaseName();
if (! preg_match('/^photo_phase[89]_test_[a-f0-9]{16}$/', $database) || DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('Refusing to change a non-disposable database.');
}
if (! Schema::hasColumn('tbl_studio_online_gallery', 'drive_link')) {
    (require database_path('migrations/2026_10_09_140000_add_gallery_drive_links.php'))->up();
}
if (! Schema::hasTable('tbl_booking_hardcopy_orders')) {
    (require database_path('migrations/2026_10_09_150000_add_hardcopy_orders.php'))->up();
}
$source = BookingModel::where('booking_reference', 'PHASE8-PRIMARY')->firstOrFail();
if (($argv[1] ?? '') === '--inspect') {
    $bookings = BookingModel::where('booking_reference', 'like', 'PHASE9-%')->get();
    $ids = $bookings->pluck('id');
    $notifications = \App\Models\NotificationModel::where('user_id', $source->client_id)
        ->whereIn('type', ['hardcopy_ready', 'hardcopy_shipped', 'hardcopy_delivered'])->get()
        ->filter(fn ($notification) => $ids->contains($notification->data['booking_id'] ?? null));
    echo json_encode([
        'bookings' => $bookings->map(fn ($booking) => ['id' => $booking->id, 'status' => $booking->status, 'delivery_type' => $booking->delivery_type, 'remaining_balance' => $booking->remaining_balance]),
        'orders' => \App\Models\HardcopyOrderModel::whereIn('booking_id', $ids)->get(['booking_id', 'fulfillment_method', 'status', 'courier_name', 'tracking_number']),
        'notifications' => $notifications->groupBy('type')->map->count(),
        'timeline_events' => DB::table('tbl_booking_events')->whereIn('booking_id', $ids)->whereIn('type', ['hardcopy_ready', 'hardcopy_shipped', 'hardcopy_delivered'])->count(),
        'gateway_payments' => \App\Models\PaymentModel::whereIn('booking_id', $ids)->whereNotNull('stripe_session_id')->get()
            ->map(fn ($payment) => ['booking_id' => $payment->booking_id, 'amount' => $payment->amount,
                'status' => $payment->status, 'test_session' => str_starts_with($payment->stripe_session_id, 'cs_test_'),
                'stripe_status' => $payment->payment_details['stripe_status'] ?? null,
                'is_balance_payment' => $payment->payment_details['is_balance_payment'] ?? null]),
    ], JSON_PRETTY_PRINT).PHP_EOL;
    exit;
}
if (($argv[1] ?? '') === '--fund-fixtures') {
    foreach (BookingModel::where('booking_reference', 'like', 'PHASE9-%')->get() as $booking) {
        if ($booking->payments()->exists()) {
            continue;
        }
        foreach ($source->payments as $payment) {
            $copy = $payment->replicate();
            $copy->booking_id = $booking->id;
            $copy->payment_reference = 'PHASE9-PAY-'.$booking->id;
            $copy->save();
        }
    }
    echo "Restored source payment snapshots in disposable fixtures only.\n";
    exit;
}
if (($argv[1] ?? '') === '--settle') {
    $booking = BookingModel::findOrFail($argv[2] ?? 0);
    if (! str_starts_with($booking->booking_reference, 'PHASE9-')) {
        throw new RuntimeException('Not a Phase 9 fixture.');
    }
    foreach ($booking->payments()->where('status', 'pending')->get() as $payment) {
        $payment->markAsPaid();
    }
    echo json_encode(['simulated_settlement' => true, 'remaining_balance' => $booking->fresh()->remaining_balance]).PHP_EOL;
    exit;
}
$package = $source->packages()->firstOrFail()->studioPackage;
$package->update(['online_gallery' => true]);
$results = [];
foreach (['APPROVED', 'PENDING', 'DRAFT', 'DRIVE', 'DRIVE_NEW', 'HARDCOPY', 'BOTH'] as $kind) {
    $booking = $source->replicate();
    $booking->booking_reference = 'PHASE9-'.$kind.'-'.bin2hex(random_bytes(3));
    $booking->status = 'in_progress';
    $booking->save();
    foreach ($source->payments as $payment) {
        $copy = $payment->replicate();
        $copy->booking_id = $booking->id;
        $copy->payment_reference = 'PHASE9-PAY-'.$booking->id;
        $copy->save();
    }
    foreach ($source->packages as $snapshot) {
        $copy = $snapshot->replicate();
        $copy->booking_id = $booking->id;
        $copy->save();
    }
    \App\Models\StudioOwner\BookingAssignedPhotographerModel::create([
        'booking_id' => $booking->id, 'studio_id' => $booking->provider_id,
        'photographer_id' => \App\Models\UserModel::where('email', 'daniela.villaflor@gmail.com')->value('id'),
        'assigned_by' => $booking->studio->user_id, 'status' => 'completed', 'assigned_at' => now(), 'completed_at' => now(),
    ]);
    if (in_array($kind, ['DRIVE_NEW', 'HARDCOPY'], true)) {
        $results[$kind] = ['booking' => $booking->id, 'gallery' => null, 'reference' => $booking->booking_reference];

        continue;
    }
    $path = 'studio-online-galleries/phase9-browser-'.$booking->id.'/photo.png';
    if ($kind !== 'DRIVE') {
        Storage::disk('public')->put($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWf8AAAAASUVORK5CYII='));
    }
    $gallery = StudioOnlineGalleryModel::create([
        'booking_id' => $booking->id, 'studio_id' => $booking->provider_id, 'client_id' => $booking->client_id,
        'gallery_reference' => StudioOnlineGalleryModel::generateGalleryReference(),
        'gallery_name' => 'Phase 9 '.$kind, 'images' => $kind === 'DRIVE' ? [] : [$path],
        'total_photos' => $kind === 'DRIVE' ? 0 : 1, 'status' => 'active',
        'gallery_status' => $kind === 'DRAFT' ? 'draft' : 'published',
        'approval_status' => $kind === 'PENDING' ? 'pending' : 'approved',
        'drive_link' => $kind === 'DRIVE' ? 'https://drive.google.com/file/d/phase9-browser-fixture/view' : null,
    ]);
    $results[$kind] = ['booking' => $booking->id, 'gallery' => $gallery->id, 'reference' => $booking->booking_reference];
}
echo json_encode(['database' => $database, 'fixtures' => $results], JSON_PRETTY_PRINT).PHP_EOL;
