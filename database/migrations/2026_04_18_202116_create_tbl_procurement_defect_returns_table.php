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
        if (!Schema::hasTable('tbl_procurement_defect_returns')) {
            Schema::create('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('procurement_request_id');
                $table->foreignId('procurement_request_item_id');
                $table->foreignId('reported_by')->nullable();
                $table->foreignId('processed_by')->nullable();
                $table->decimal('reported_quantity', 10, 2);
                $table->string('reason_code', 50);
                $table->text('reason_other')->nullable();
                $table->text('requester_note')->nullable();
                $table->text('finance_note')->nullable();
                $table->string('status', 50)->default('reported');
                $table->timestamp('reported_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamp('replacement_delivered_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_req_fk')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->foreign('procurement_request_id', 'tbl_proc_def_returns_req_fk')
                    ->references('id')
                    ->on('tbl_procurement_requests')
                    ->cascadeOnDelete();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_item_fk')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->foreign('procurement_request_item_id', 'tbl_proc_def_returns_item_fk')
                    ->references('id')
                    ->on('tbl_procurement_request_items')
                    ->cascadeOnDelete();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_reported_by_fk')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->foreign('reported_by', 'tbl_proc_def_returns_reported_by_fk')
                    ->references('id')
                    ->on('tbl_users')
                    ->nullOnDelete();
            });
        }

        if (!$this->foreignKeyExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_processed_by_fk')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->foreign('processed_by', 'tbl_proc_def_returns_processed_by_fk')
                    ->references('id')
                    ->on('tbl_users')
                    ->nullOnDelete();
            });
        }

        if (!$this->indexExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_request_status_idx')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->index(['procurement_request_id', 'status'], 'tbl_proc_def_returns_request_status_idx');
            });
        }

        if (!$this->indexExists('tbl_procurement_defect_returns', 'tbl_proc_def_returns_item_status_idx')) {
            Schema::table('tbl_procurement_defect_returns', function (Blueprint $table) {
                $table->index(['procurement_request_item_id', 'status'], 'tbl_proc_def_returns_item_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_defect_returns');
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
