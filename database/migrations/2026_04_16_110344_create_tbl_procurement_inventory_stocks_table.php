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
        Schema::create('tbl_procurement_inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id');
            $table->foreignId('procurement_request_id')->nullable();
            $table->foreignId('procurement_request_item_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->string('item_name');
            $table->string('normalized_item_name');
            $table->text('description')->nullable();
            $table->string('unit_of_measure', 50);
            $table->decimal('stock_quantity', 10, 2)->default(0);
            $table->decimal('reorder_threshold', 10, 2)->default(0);
            $table->decimal('last_recorded_cost', 12, 2)->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamps();

            $table->foreign('studio_id', 'tbl_proc_inv_stocks_studio_fk')
                ->references('id')
                ->on('tbl_studios')
                ->cascadeOnDelete();

            $table->foreign('procurement_request_id', 'tbl_proc_inv_stocks_req_fk')
                ->references('id')
                ->on('tbl_procurement_requests')
                ->nullOnDelete();

            $table->foreign('procurement_request_item_id', 'tbl_proc_inv_stocks_item_fk')
                ->references('id')
                ->on('tbl_procurement_request_items')
                ->nullOnDelete();

            $table->foreign('created_by', 'tbl_proc_inv_stocks_created_by_fk')
                ->references('id')
                ->on('tbl_users')
                ->nullOnDelete();

            $table->foreign('updated_by', 'tbl_proc_inv_stocks_updated_by_fk')
                ->references('id')
                ->on('tbl_users')
                ->nullOnDelete();

            $table->unique(['studio_id', 'normalized_item_name'], 'tbl_procurement_inventory_stocks_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_inventory_stocks');
    }
};
