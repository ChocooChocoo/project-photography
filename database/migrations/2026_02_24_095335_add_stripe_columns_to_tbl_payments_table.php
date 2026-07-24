<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills a migration that was never written: the Stripe identifiers were
 * added to tbl_payments directly on the live database when Stripe was added
 * alongside PayMongo. PaymentModel and BookingController use both gateways'
 * columns, so both pairs are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_payments', 'stripe_payment_intent_id')) {
                $table->string('stripe_payment_intent_id')->nullable()->after('payment_reference');
            }

            if (! Schema::hasColumn('tbl_payments', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable()->after('stripe_payment_intent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_intent_id', 'stripe_session_id']);
        });
    }
};
