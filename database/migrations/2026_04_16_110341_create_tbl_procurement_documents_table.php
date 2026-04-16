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
        Schema::create('tbl_procurement_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_request_id')->constrained('tbl_procurement_requests')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('tbl_procurement_purchase_orders')->nullOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->string('document_type', 50);
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['procurement_request_id', 'document_type'], 'tbl_procurement_documents_request_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_procurement_documents');
    }
};
