<?php
// Removes every local T-15 browser fixture (booking references starting "T15-").
use App\Models\BookingModel;
use Illuminate\Support\Facades\DB;

$ids = BookingModel::withTrashed()->where('booking_reference', 'like', 'T15-%')->pluck('id');
DB::table('tbl_booking_cancellation_recoveries')->whereIn('booking_id', $ids)->delete();
DB::table('tbl_studio_online_gallery')->whereIn('booking_id', $ids)->delete();
$orderIds = DB::table('tbl_booking_hardcopy_orders')->whereIn('booking_id', $ids)->pluck('id');
DB::table('tbl_booking_hardcopy_items')->whereIn('order_id', $orderIds)->delete();
DB::table('tbl_booking_hardcopy_orders')->whereIn('booking_id', $ids)->delete();
DB::table('tbl_booking_assigned_photographers')->whereIn('booking_id', $ids)->delete();
DB::table('tbl_payments')->whereIn('booking_id', $ids)->delete();
DB::table('tbl_booking_packages')->whereIn('booking_id', $ids)->delete();
foreach (['tbl_booking_events', 'tbl_notifications'] as $table) {
    if (\Illuminate\Support\Facades\Schema::hasTable($table) && \Illuminate\Support\Facades\Schema::hasColumn($table, 'booking_id')) {
        DB::table($table)->whereIn('booking_id', $ids)->delete();
    }
}
DB::table('tbl_bookings')->whereIn('id', $ids)->delete();
echo 'removed '.$ids->count().' T15 bookings; left: '.BookingModel::withTrashed()->where('booking_reference', 'like', 'T15-%')->count().PHP_EOL;
