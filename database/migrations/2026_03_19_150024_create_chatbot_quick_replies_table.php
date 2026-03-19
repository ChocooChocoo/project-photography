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
        Schema::create('tbl_chatbot_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intent_id');
            $table->string('reply_text');
            $table->string('action_value')->nullable(); // Could be another intent trigger or URL
            $table->enum('action_type', ['trigger_intent', 'open_url', 'none'])->default('trigger_intent');
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('intent_id');
            $table->index('is_active');
            $table->index('position');
            
            // Foreign key
            $table->foreign('intent_id')
                  ->references('id')
                  ->on('tbl_chatbot_intents')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_chatbot_quick_replies');
    }
};