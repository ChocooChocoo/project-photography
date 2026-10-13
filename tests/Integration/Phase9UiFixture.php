<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:', 'APP_URL' => 'http://127.0.0.1:8767', 'SESSION_DRIVER' => 'array'] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite' || Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== ':memory:') {
    throw new RuntimeException('UI fixtures must use an in-memory database.');
}

$directory = $argv[1] ?? throw new RuntimeException('Pass an output directory.');
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}
$client = new App\Models\UserModel(['first_name' => 'Fixture', 'last_name' => 'Client', 'email' => 'fixture@example.test']);
$booking = new App\Models\BookingModel(['id' => 1, 'booking_reference' => 'UI-FIXTURE', 'status' => 'in_progress', 'event_name' => 'Fixture session', 'event_date' => '2026-10-20']);
$booking->id = 1;
$booking->formatted_event_date = 'Oct 20, 2026';
$booking->has_gallery = false;
$booking->setRelation('client', $client);
$booking->setRelation('packages', collect());
$booking->setRelation('category', null);
file_put_contents($directory.'/owner-gallery.html', view('owner.view-online-gallery', ['bookings' => collect([$booking])])->render());
file_put_contents($directory.'/client-gallery.html', view('client.view-online-gallery', ['galleries' => collect([(object) [
    'id' => 1, 'type' => 'studio', 'thumbnail' => null, 'total_photos' => 1, 'gallery_name' => 'Fixture gallery',
    'provider_name' => 'Fixture Studio', 'booking_reference' => 'UI-FIXTURE', 'event_date' => 'Oct 20, 2026',
    'description' => 'Isolated browser fixture', 'created_at' => 'Oct 13, 2026',
]])])->render());

$order = new App\Models\HardcopyOrderModel(['booking_id' => 1, 'fulfillment_method' => 'pickup', 'status' => 'pending', 'amount' => 300]);
$order->id = 1;
$order->setRelation('items', collect([new App\Models\HardcopyItemModel(['name' => 'Frame', 'size' => '8x10', 'quantity' => 2, 'unit_price' => 150])]));
foreach (['owner' => true, 'client' => false] as $name => $owner) {
    $html = '<!doctype html><html lang="en"><head><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="fixture"><link rel="stylesheet" href="/assets/css/app.min.css"></head><body><main class="container p-3"><div id="panel"></div></main>';
    $html .= view('partials.hardcopy-script')->render();
    $html .= '<script>window.mountHardcopy(document.getElementById("panel"),1,'.($owner ? 'true' : 'false').');</script></body></html>';
    file_put_contents($directory.'/hardcopy-'.$name.'.html', $html);
    file_put_contents($directory.'/hardcopy-'.$name.'-panel.html', view('partials.hardcopy-orders', ['booking' => $booking, 'orders' => $owner ? collect() : collect([$order]), 'owner' => $owner])->render());
}
echo 'Rendered isolated UI fixtures in '.$directory.PHP_EOL;
