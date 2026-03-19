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
        Schema::create('tbl_chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->enum('sender_type', ['user', 'bot']);
            $table->text('message');
            $table->unsignedBigInteger('intent_id')->nullable(); // Which intent was matched (if bot response)
            $table->boolean('was_helpful')->nullable(); // User feedback
            $table->json('metadata')->nullable(); // Additional data (quick replies shown, etc.)
            $table->timestamps();
            
            // Indexes
            $table->index('conversation_id');
            $table->index('sender_type');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('conversation_id')
                  ->references('id')
                  ->on('tbl_chatbot_conversations')
                  ->onDelete('cascade');
                  
            $table->foreign('intent_id')
                  ->references('id')
                  ->on('tbl_chatbot_intents')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_chatbot_messages');
    }
};