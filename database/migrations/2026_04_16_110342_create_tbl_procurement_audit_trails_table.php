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
        Schema::create('tbl_procurement_audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('tbl_procurement_requests')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50)->nullable();
            $table->text('note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['procurement_request_id', 'created_at'], 'tbl_procurement_audit_trails_request_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_audit_trails');
    }
};
