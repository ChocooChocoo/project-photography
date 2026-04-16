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
        Schema::create('tbl_procurement_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id');
            $table->foreignId('procurement_request_item_id');
            $table->foreignId('studio_id');
            $table->foreignId('recorded_by')->nullable();
            $table->string('asset_name');
            $table->string('serial_number');
            $table->date('warranty_expires_at')->nullable();
            $table->decimal('acquisition_cost', 12, 2);
            $table->string('location');
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->foreign('procurement_request_id', 'tbl_proc_assets_req_fk')
                ->references('id')
                ->on('tbl_procurement_requests')
                ->cascadeOnDelete();

            $table->foreign('procurement_request_item_id', 'tbl_proc_assets_item_fk')
                ->references('id')
                ->on('tbl_procurement_request_items')
                ->cascadeOnDelete();

            $table->foreign('studio_id', 'tbl_proc_assets_studio_fk')
                ->references('id')
                ->on('tbl_studios')
                ->cascadeOnDelete();

            $table->foreign('recorded_by', 'tbl_proc_assets_recorded_by_fk')
                ->references('id')
                ->on('tbl_users')
                ->nullOnDelete();

            $table->unique(['studio_id', 'serial_number'], 'tbl_procurement_assets_studio_serial_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_assets');
    }
};
