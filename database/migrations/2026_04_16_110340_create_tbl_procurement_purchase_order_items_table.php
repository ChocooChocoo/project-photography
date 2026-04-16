<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tbl_procurement_purchase_order_items')) {
            Schema::create('tbl_procurement_purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id');
                $table->foreignId('procurement_request_item_id');
                $table->string('item_name');
                $table->decimal('quantity', 10, 2);
                $table->string('unit_of_measure', 50);
                $table->decimal('unit_price', 12, 2);
                $table->decimal('total_price', 12, 2);
                $table->timestamps();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_purchase_order_items', 'tbl_proc_po_items_po_fk')) {
            Schema::table('tbl_procurement_purchase_order_items', function (Blueprint $table) {
                $table->foreign('purchase_order_id', 'tbl_proc_po_items_po_fk')
                    ->references('id')
                    ->on('tbl_procurement_purchase_orders')
                    ->cascadeOnDelete();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_purchase_order_items', 'tbl_proc_po_items_req_item_fk')) {
            Schema::table('tbl_procurement_purchase_order_items', function (Blueprint $table) {
                $table->foreign('procurement_request_item_id', 'tbl_proc_po_items_req_item_fk')
                    ->references('id')
                    ->on('tbl_procurement_request_items')
                    ->cascadeOnDelete();
            });
        }

        if (!$this->indexExists('tbl_procurement_purchase_order_items', 'tbl_procurement_po_items_unique')) {
            Schema::table('tbl_procurement_purchase_order_items', function (Blueprint $table) {
                $table->unique(['purchase_order_id', 'procurement_request_item_id'], 'tbl_procurement_po_items_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_purchase_order_items');
    }

    /**
     * Determine whether a foreign key already exists.
     */
    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('CONSTRAINT_NAME', $constraintName)
            ->exists();
    }

    /**
     * Determine whether an index already exists.
     */
    private function indexExists(string $tableName, string $indexName): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $tableName)
            ->where('INDEX_NAME', $indexName)
            ->exists();
    }
};
