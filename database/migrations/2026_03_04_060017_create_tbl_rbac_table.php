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
        Schema::create('tbl_rbac', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique(); // One-to-one relationship with users
            $table->unsignedBigInteger('studio_id'); // Which studio this RBAC belongs to
            $table->string('role'); // Store the role value (studio-hr, studio-finance, studio-photographer)
            $table->string('role_type')->nullable(); // Manager, Staff, Photographer
            
            // Granular permissions
            $table->boolean('can_create')->default(false);
            $table->boolean('can_read')->default(false);
            $table->boolean('can_update')->default(false);
            $table->boolean('can_delete')->default(false);
            
            // Future extensibility - JSON for module-specific permissions
            $table->json('module_permissions')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
                  
            $table->foreign('studio_id')
                  ->references('id')
                  ->on('tbl_studios')
                  ->onDelete('cascade');
                  
            // Indexes for better performance
            $table->index('user_id');
            $table->index('studio_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_rbac');
    }
};