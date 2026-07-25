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
        Schema::create('tbl_chatbot_intents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('config_id');
            $table->string('intent_name');
            $table->text('response_text');
            $table->integer('priority')->default(0); // Higher priority is injected first
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index('config_id');
            $table->index('is_active');
            $table->index('priority');

            // Foreign key
            $table->foreign('config_id')
                ->references('id')
                ->on('tbl_chatbot_configs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_chatbot_intents');
    }
};
