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
        Schema::create('tbl_procurement_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('tbl_procurement_requests')->cascadeOnDelete();
            $table->string('item_name');
            $table->string('normalized_item_name');
            $table->text('description')->nullable();
            $table->enum('category', ['equipment', 'consumable']);
            $table->enum('expense_type', ['capex', 'opex']);
            $table->decimal('quantity', 10, 2);
            $table->string('unit_of_measure', 50);
            $table->decimal('estimated_unit_cost', 12, 2)->default(0);
            $table->decimal('estimated_total_cost', 12, 2)->default(0);
            $table->decimal('approved_unit_cost', 12, 2)->nullable();
            $table->decimal('approved_total_cost', 12, 2)->nullable();
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->text('condition_notes')->nullable();
            $table->string('preferred_supplier')->nullable();
            $table->timestamps();

            $table->index(['procurement_request_id', 'category'], 'tbl_procurement_request_items_request_category_index');
            $table->index(['normalized_item_name'], 'tbl_procurement_request_items_normalized_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_request_items');
    }
};
