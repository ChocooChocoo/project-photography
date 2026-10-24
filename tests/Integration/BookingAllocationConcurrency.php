<?php

require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Http\Controllers\Client\BookingController;
use App\Models\BookingModel;
use App\Models\UserModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function allocationGuard(): void
{
    if (!app()->environment('testing') || DB::getDriverName() !== 'mysql'
        || !preg_match('/^photo_allocation_test_[a-f0-9]{16}$/', DB::connection()->getDatabaseName())) {
        throw new RuntimeException('Refusing a non-disposable allocation database.');
    }
}

function allocationSignal(string $file, ?Process $process = null): void
{
    $deadline = microtime(true) + 25;
    while (!is_file($file)) {
        if ($process && !$process->isRunning()) throw new RuntimeException('Worker stopped before barrier: '.$process->getOutput().$process->getErrorOutput());
        if (microtime(true) > $deadline) throw new RuntimeException('Barrier timed out: '.basename($file));
        usleep(10000);
        clearstatcache(true, $file);
    }
}

function allocationPayload(string $scenario, string $worker, string $type): array
{
    $date = now()->addDays(30)->startOfDay();
    if ($scenario === 'recurrence' && $worker === 'a') $date->addWeeks(2);
    return [
        'type' => $type, 'provider_id' => $type === 'studio' ? 1 : 2, 'category_id' => 1, 'package_id' => 1,
        'event_date' => $date->format('Y-m-d'), 'event_timezone' => 'Asia/Manila',
        'start_time' => $scenario === 'capacity' && $worker === 'b' ? '12:00' : '09:00',
        'end_time' => $scenario === 'capacity' && $worker === 'b' ? '13:00' : '10:00',
        'location_type' => $type === 'studio' ? 'in-studio' : 'on-location',
        'city' => 'Fixture city', 'barangay' => 'Fixture barangay',
        'full_name' => 'Allocation Client', 'email' => 'allocation@example.test', 'contact_number' => '09170000000',
        'payment_type' => 'full_payment', 'terms_agree' => 1,
        'booking_frequency' => $scenario === 'recurrence' && $worker === 'b' ? 'recurring' : 'one_time',
        'recurrence_pattern' => ['frequency' => 'weekly', 'interval' => 1, 'sessions' => 3],
    ];
}

if (($argv[1] ?? '') === 'worker') {
    [$script, $mode, $database, $barrier, $worker, $scenario, $type] = $argv;
    allocationGuard();
    if (DB::connection()->getDatabaseName() !== $database) throw new RuntimeException('Worker database mismatch.');
    $providerTable = $type === 'studio' ? 'tbl_studios' : 'tbl_users';
    DB::connection()->beforeExecuting(function ($sql) use ($barrier, $worker, $providerTable) {
        if (str_contains($sql, $providerTable) && str_contains(strtolower($sql), 'for update')) touch($barrier.'/attempt-'.$worker);
    });
    $held = false;
    DB::listen(function ($query) use ($barrier, $worker, $providerTable, &$held) {
        if ($worker === 'a' && !$held && str_contains($query->sql, $providerTable)
            && str_contains(strtolower($query->sql), 'for update')) {
            $held = true;
            touch($barrier.'/locked-a');
            allocationSignal($barrier.'/release-a');
        }
    });
    Auth::setUser(UserModel::findOrFail(1));
    $request = Request::create('/client/bookings', 'POST', allocationPayload($scenario, $worker, $type));
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => Auth::user());
    try {
        $response = app(BookingController::class)->store($request);
        echo json_encode(['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true)]);
    } catch (ValidationException $exception) {
        echo json_encode(['status' => 422, 'errors' => $exception->errors()]);
    }
    exit;
}

if (($argv[1] ?? '') !== '--run') {
    fwrite(STDERR, "Usage: php tests/Integration/BookingAllocationConcurrency.php --run\nFresh disposable MySQL only; no gateway calls or live migrations.\n");
    exit(2);
}

function allocationFixture(string $type, string $scenario): void
{
    allocationGuard();
    Schema::dropAllTables();
    // Reuse the minimal booking schema, not the PHPUnit lifecycle or its database configuration.
    $test = new Tests\Feature\Booking\BookingTermsAndTimeslotTest('test_store_accepts_booking_when_terms_agreed');
    (new ReflectionMethod($test, 'createSchema'))->invoke($test);
    (require dirname(__DIR__, 2).'/database/migrations/2026_09_30_100000_add_booking_duration_and_contracts.php')->up();
    foreach (['client', $type === 'studio' ? 'owner' : 'freelancer'] as $i => $role) {
        DB::table('tbl_users')->insert(['id' => $i + 1, 'role' => $role, 'first_name' => 'Allocation', 'last_name' => $role,
            'email' => $role.'@example.test', 'mobile_number' => '09170000000', 'password' => 'unused', 'status' => 'active']);
    }
    $days = json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
    $limit = $scenario === 'capacity' ? 1 : 5;
    DB::table('tbl_categories')->insert(['id' => 1, 'category_name' => 'Coverage', 'status' => 'active']);
    if ($type === 'studio') {
        Schema::table('tbl_studio_plans', fn (Blueprint $table) => $table->timestamp('grace_ends_at')->nullable());
        Schema::table('tbl_studios', fn (Blueprint $table) => $table->integer('max_clients_per_day')->default(5));
        DB::table('tbl_studios')->insert(['id' => 1, 'user_id' => 2, 'studio_name' => 'Allocation Studio', 'status' => 'verified', 'max_clients_per_day' => $limit]);
        DB::table('tbl_studio_plans')->insert(['studio_id' => 1, 'status' => 'active', 'payment_status' => 'paid', 'end_date' => now()->addYear()->format('Y-m-d')]);
        DB::table('tbl_studio_schedules')->insert(['studio_id' => 1, 'operating_days' => $days, 'booking_limit' => $limit]);
        DB::table('tbl_packages')->insert(['id' => 1, 'studio_id' => 1, 'category_id' => 1, 'package_name' => 'Coverage',
            'package_price' => 10000, 'package_location' => json_encode(['In-Studio']), 'status' => 'active']);
    } else {
        Schema::create('tbl_freelancers', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->string('brand_name'); });
        Schema::create('tbl_freelancer_schedules', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('user_id'); $table->json('operating_days'); $table->integer('booking_limit'); });
        Schema::create('tbl_freelancer_packages', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('category_id');
            $table->string('package_name'); $table->decimal('package_price', 10, 2); $table->string('status'); $table->softDeletes();
        });
        DB::table('tbl_freelancers')->insert(['user_id' => 2, 'brand_name' => 'Allocation Freelancer']);
        DB::table('tbl_freelancer_schedules')->insert(['user_id' => 2, 'operating_days' => $days, 'booking_limit' => $limit]);
        DB::table('tbl_freelancer_packages')->insert(['id' => 1, 'user_id' => 2, 'category_id' => 1, 'package_name' => 'Coverage', 'package_price' => 10000, 'status' => 'active']);
    }
}

$database = 'photo_allocation_test_'.bin2hex(random_bytes(8));
$mysql = config('database.connections.mysql');
$mysql['database'] = '';
$mysql['url'] = null;
config(['database.connections.allocation_admin' => $mysql]);
$admin = DB::connection('allocation_admin');
$created = false;
$workers = [];
$barriers = [];
$failure = null;
try {
    $admin->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created = true;
    $mysql['database'] = $database;
    config(['app.env' => 'testing', 'database.default' => 'allocation_test', 'database.connections.allocation_test' => $mysql]);
    $app->instance('env', 'testing');
    foreach (['studio', 'freelancer'] as $type) {
        foreach (['overlap', 'capacity', 'recurrence'] as $scenario) {
            allocationFixture($type, $scenario);
            $barrier = getenv('LOCALAPPDATA').'/Temp/opencode/'.$database.'_'.$type.'_'.$scenario;
            if (!mkdir($barrier, 0700)) throw new RuntimeException('Cannot create barrier.');
            $barriers[] = $barrier;
            $env = ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'mysql', 'DB_DATABASE' => $database, 'DB_URL' => false,
                'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync', 'SESSION_DRIVER' => 'array', 'CACHE_STORE' => 'array'];
            foreach (['a', 'b'] as $worker) {
                $process = new Process([PHP_BINARY, __FILE__, 'worker', $database, $barrier, $worker, $scenario, $type], dirname(__DIR__, 2), $env);
                $process->setTimeout(40); $workers[] = $process; $process->start();
                if ($worker === 'a') { $first = $process; allocationSignal($barrier.'/locked-a', $process); }
                else { $second = $process; allocationSignal($barrier.'/attempt-b', $process); }
            }
            if (!$second->isRunning()) throw new RuntimeException('Competing allocation did not wait on provider lock.');
            touch($barrier.'/release-a');
            $first->wait(); $second->wait();
            if (!$first->isSuccessful() || !$second->isSuccessful()) throw new RuntimeException('Worker failed: '.$first->getErrorOutput().$second->getErrorOutput());
            $a = json_decode($first->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            $b = json_decode($second->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            if ($a['status'] !== 200 || $b['status'] !== 422) throw new RuntimeException('Unexpected allocation responses: '.json_encode([$a, $b]));
            if (BookingModel::count() !== 1 || DB::table('tbl_booking_packages')->count() !== 1 || DB::table('tbl_payments')->count() !== 1
                || BookingModel::whereNotNull('parent_booking_id')->exists()) throw new RuntimeException('Losing allocation left partial booking/package/payment records.');
            if ($scenario === 'recurrence' && !isset($b['errors']['recurrence_pattern'])) throw new RuntimeException('Recurrence failed outside occurrence validation.');
            echo "PASS {$type} {$scenario}: independent workers (200/422), one booking/line/payment, no partial recurrence\n";
        }
    }
    echo 'Engine: '.$admin->selectOne('SELECT VERSION() AS version')->version."; fresh disposable database only; no gateway calls.\n";
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    foreach ($workers as $worker) if ($worker->isRunning()) $worker->stop();
    DB::disconnect('allocation_test');
    if ($created) $admin->statement("DROP DATABASE `{$database}`");
    foreach ($barriers as $barrier) {
        foreach (glob($barrier.'/*') as $file) unlink($file);
        rmdir($barrier);
    }
}
if ($failure) { fwrite(STDERR, $failure->getMessage()."\n"); exit(1); }
