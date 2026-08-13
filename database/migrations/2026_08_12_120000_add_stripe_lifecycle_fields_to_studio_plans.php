<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('stripe_session_id');
            $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id')->index();
            $table->string('stripe_invoice_id')->nullable()->after('stripe_subscription_id')->unique();
            $table->string('stripe_first_failure_invoice_id')->nullable()->after('stripe_invoice_id');
            $table->timestamp('scheduled_cancellation_at')->nullable()->after('cancelled_at');
            $table->timestamp('first_failure_at')->nullable()->after('scheduled_cancellation_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studio_plans', function (Blueprint $table) {
            $table->dropUnique(['stripe_invoice_id']);
            $table->dropIndex(['stripe_subscription_id']);
            $table->dropColumn([
                'stripe_customer_id', 'stripe_subscription_id', 'stripe_invoice_id',
                'stripe_first_failure_invoice_id', 'scheduled_cancellation_at', 'first_failure_at',
            ]);
        });
    }
};
