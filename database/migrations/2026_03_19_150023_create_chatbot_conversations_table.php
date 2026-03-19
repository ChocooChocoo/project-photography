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
        Schema::create('tbl_chatbot_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable(); // Client/customer
            $table->unsignedBigInteger('owner_id'); // Studio owner whose chatbot is being used
            $table->unsignedBigInteger('config_id')->nullable(); // Which config was active
            $table->enum('status', ['active', 'ended', 'timeout'])->default('active');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->json('metadata')->nullable(); // Store additional context
            $table->timestamps();
            
            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('owner_id');
            $table->index('status');
            $table->index('started_at');
            
            // Foreign keys
            $table->foreign('user_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('set null');
                  
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('tbl_users')
                  ->onDelete('cascade');
                  
            $table->foreign('config_id')
                  ->references('id')
                  ->on('tbl_chatbot_configs')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_chatbot_conversations');
    }
};