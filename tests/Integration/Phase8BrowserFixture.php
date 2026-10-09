<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Models\BookingModel;
use App\Models\BookingPackageModel;
use App\Models\PaymentModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\PackagesModel;
use App\Models\StudioOwner\StudiosModel;
use App\Models\StudioPlanModel;
use App\Models\UserModel;
use Illuminate\Support\Facades\DB;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (DB::getDriverName() !== 'mysql') {
    throw new RuntimeException('This fixture requires MySQL.');
}

if (($argv[1] ?? '') !== '--create') {
    fwrite(STDERR, "Usage: php tests/Integration/Phase8BrowserFixture.php --create\nCreates a disposable clone. Never changes source rows.\n");
    exit(2);
}
$source = DB::connection()->getDatabaseName();
if (! preg_match('/^[a-zA-Z0-9_]+$/', $source)) throw new RuntimeException('Unexpected source database name.');
$database = 'photo_phase8_test_'.bin2hex(random_bytes(8));
DB::statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach (DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"') as $row) {
    $table = array_values((array) $row)[0];
    if (! preg_match('/^[a-zA-Z0-9_]+$/', $table)) throw new RuntimeException('Unexpected table name.');
    DB::statement("CREATE TABLE `{$database}`.`{$table}` LIKE `{$source}`.`{$table}`");
    if (! in_array($table, ['sessions', 'jobs', 'failed_jobs', 'job_batches'], true)) {
        DB::statement("INSERT INTO `{$database}`.`{$table}` SELECT * FROM `{$source}`.`{$table}`");
    }
}
$connection = config('database.default');
config(["database.connections.{$connection}.database" => $database]);
DB::purge($connection);
if (DB::connection()->getDatabaseName() !== $database) throw new RuntimeException('Clone connection mismatch.');
if (! \Illuminate\Support\Facades\Schema::hasTable('tbl_booking_events')) {
    (require dirname(__DIR__, 2).'/database/migrations/2026_10_09_100000_add_booking_preparation.php')->up();
}

$owner = UserModel::where('email', 'adrian.alcantara@gmail.com')->firstOrFail();
$client = UserModel::where('email', 'beatriz.inocencio@gmail.com')->firstOrFail();
$photographer = UserModel::where('email', 'daniela.villaflor@gmail.com')->firstOrFail();
$otherPhotographer = UserModel::where('email', 'emilio.duenas@gmail.com')->firstOrFail();
$studio = StudiosModel::where('user_id', $owner->id)->firstOrFail();
StudioPlanModel::where('studio_id', $studio->id)->update([
    'status' => 'active', 'payment_status' => 'paid', 'end_date' => now()->addMonths(2), 'trial_ends_at' => null,
]);
$package = PackagesModel::where('studio_id', $studio->id)->firstOrFail();
$package->update(['photographer_count' => 1]);
$ids = [];
foreach (['PRIMARY', 'OVERLAP'] as $kind) {
    $booking = BookingModel::create([
        'booking_reference' => 'PHASE8-'.$kind, 'client_id' => $client->id,
        'booking_type' => 'studio', 'provider_id' => $studio->id, 'category_id' => $package->category_id,
        'event_name' => 'Phase 8 browser fixture', 'event_date' => now()->addDays(10)->toDateString(),
        'start_time' => '09:00', 'end_time' => '12:00', 'location_type' => 'in-studio',
        'total_amount' => 1000, 'down_payment' => 1000, 'remaining_balance' => 0, 'payment_type' => 'full_payment',
        'status' => 'confirmed', 'payment_status' => 'pending', 'preferred_camera_brand' => 'Canon',
    ]);
    BookingPackageModel::create([
        'booking_id' => $booking->id, 'package_id' => $package->id, 'package_type' => 'studio',
        'package_name' => 'Phase 8 browser package', 'package_price' => 1000,
        'materials' => [['name' => 'Photo frame', 'quantity' => 2]],
    ]);
    PaymentModel::create([
        'booking_id' => $booking->id, 'payment_reference' => 'PHASE8-PAY-'.$kind,
        'amount' => 1000, 'payment_method' => 'card', 'status' => 'succeeded', 'paid_at' => now(),
    ]);
    $booking->updatePaymentStatus();
    if ($kind === 'OVERLAP') {
        BookingAssignedPhotographerModel::create([
            'booking_id' => $booking->id, 'studio_id' => $studio->id,
            'photographer_id' => $otherPhotographer->id, 'assigned_by' => $owner->id,
            'status' => 'assigned', 'assigned_at' => now(), 'response_deadline' => now()->addDay(),
        ]);
    }
    $ids[$kind] = $booking->id;
}
echo json_encode(['database' => $database, 'source_unchanged' => true, 'studio_id' => $studio->id, 'photographer_id' => $photographer->id, 'bookings' => $ids], JSON_PRETTY_PRINT).PHP_EOL;
