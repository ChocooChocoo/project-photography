<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', fn (Blueprint $table) => $table->string('delivery_type', 20)->nullable());
        Schema::create('tbl_booking_hardcopy_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('tbl_bookings')->cascadeOnDelete();
            $table->enum('fulfillment_method', ['pickup', 'courier']);
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('contact_number', 40)->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'preparing', 'ready', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });
        Schema::create('tbl_booking_hardcopy_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('tbl_booking_hardcopy_orders')->cascadeOnDelete();
            $table->string('name');
            $table->string('size', 100)->nullable();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_booking_hardcopy_items');
        Schema::dropIfExists('tbl_booking_hardcopy_orders');
        Schema::table('tbl_bookings', fn (Blueprint $table) => $table->dropColumn('delivery_type'));
    }
};
