<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tbl_stripe_subscription_webhook_events')) {
            Schema::create('tbl_stripe_subscription_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event_id')->unique();
                $table->string('event_type');
                $table->string('stripe_subscription_id')->nullable();
                $table->string('stripe_customer_id')->nullable();
                $table->string('stripe_invoice_id')->nullable();
                $table->json('payload');
                $table->timestamps();
                $table->index(['stripe_subscription_id', 'stripe_customer_id'], 'stripe_sub_customer_idx');
            });
        } elseif (! Schema::hasIndex('tbl_stripe_subscription_webhook_events', ['stripe_subscription_id', 'stripe_customer_id'])) {
            Schema::table('tbl_stripe_subscription_webhook_events', fn (Blueprint $table) => $table->index(['stripe_subscription_id', 'stripe_customer_id'], 'stripe_sub_customer_idx'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_stripe_subscription_webhook_events');
    }
};
