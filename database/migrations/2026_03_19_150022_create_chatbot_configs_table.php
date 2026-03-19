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
        Schema::create('tbl_chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('config_name')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable(); // REMOVED DEFAULT VALUE
            $table->boolean('is_active')->default(true);
            $table->string('bot_name')->default('Support Assistant');
            $table->string('bot_avatar')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('owner_id');
            $table->index('is_active');
            
            // Foreign key
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_chatbot_configs');
    }
};