<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_booking_cancellation_recoveries')) {
            Schema::table('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_percentage')) {
                    $table->decimal('refund_percentage', 5, 2)->nullable()->after('status');
                }
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'refund_amount')) {
                    $table->decimal('refund_amount', 12, 2)->nullable()->after('refund_percentage');
                }
            });
        }

        if (Schema::hasTable('tbl_payments') && ! Schema::hasColumn('tbl_payments', 'refunded_amount')) {
            Schema::table('tbl_payments', function (Blueprint $table) {
                $table->decimal('refunded_amount', 12, 2)->default(0)->after('refund_reference');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_booking_cancellation_recoveries')) {
            Schema::table('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
                $table->dropColumn(['refund_percentage', 'refund_amount']);
            });
        }
        if (Schema::hasTable('tbl_payments') && Schema::hasColumn('tbl_payments', 'refunded_amount')) {
            Schema::table('tbl_payments', fn (Blueprint $table) => $table->dropColumn('refunded_amount'));
        }
    }
};
