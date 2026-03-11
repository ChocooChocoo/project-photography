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
        Schema::create('tbl_client_budget', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to client (user)
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
            
            // Budget Information
            $table->string('budget_name')->nullable()->comment('Name/Title for this budget (e.g., Wedding Budget, Event Budget)');
            $table->text('description')->nullable()->comment('Description of the budget purpose');
            
            // Budget Amount Fields
            $table->decimal('minimum_budget', 10, 2)->nullable()->comment('Minimum budget amount');
            $table->decimal('maximum_budget', 10, 2)->nullable()->comment('Maximum budget amount');
            $table->decimal('preferred_budget', 10, 2)->nullable()->comment('Preferred/ideal budget amount');
            
            // Categorization (for filtering later)
            $table->unsignedBigInteger('category_id')->nullable()->comment('Associated service category');
            $table->foreign('category_id')
                  ->references('id')
                  ->on('tbl_categories')
                  ->onDelete('set null');
            
            $table->string('budget_type')->nullable()->comment('Type: service, package, equipment, etc.');
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('Budget status');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes()->comment('Soft delete support');
            
            // Indexes for performance
            $table->index('client_id');
            $table->index('category_id');
            $table->index('status');
            $table->index(['client_id', 'status']);
            $table->index(['minimum_budget', 'maximum_budget']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_client_budget');
    }
};