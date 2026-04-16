<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_procurement_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('tbl_procurement_requests')->cascadeOnDelete();
            $table->string('po_number', 30)->unique();
            $table->string('supplier_name');
            $table->string('supplier_email')->nullable();
            $table->string('supplier_contact_number', 50)->nullable();
            $table->text('supplier_address')->nullable();
            $table->text('delivery_address');
            $table->string('payment_terms', 150);
            $table->date('order_date');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('ordered_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamps();

            $table->index(['procurement_request_id'], 'tbl_procurement_purchase_orders_request_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_purchase_orders');
    }
};
