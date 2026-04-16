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
        Schema::create('tbl_procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_reference', 30)->unique();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('tbl_users')->cascadeOnDelete();
            $table->string('requester_role', 50);
            $table->string('status', 50)->default('draft');
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_high_value')->default(false);
            $table->date('required_date')->nullable();
            $table->text('purpose')->nullable();
            $table->text('inventory_bypass_reason')->nullable();
            $table->text('finance_review_note')->nullable();
            $table->text('owner_review_note')->nullable();
            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('approved_total', 12, 2)->default(0);
            $table->string('invoice_reference', 100)->nullable();
            $table->decimal('invoice_amount', 12, 2)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('payment_note')->nullable();
            $table->foreignId('finance_reviewed_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('finance_reviewed_at')->nullable();
            $table->foreignId('owner_reviewed_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('owner_reviewed_at')->nullable();
            $table->foreignId('receipt_confirmed_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('receipt_confirmed_at')->nullable();
            $table->timestamp('payment_processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['studio_id', 'status'], 'tbl_procurement_requests_studio_status_index');
            $table->index(['requester_id', 'status'], 'tbl_procurement_requests_requester_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_requests');
    }
};
