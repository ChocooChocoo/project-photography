<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('tbl_users');
            $table->foreignId('user_two_id')->constrained('tbl_users');
            $table->foreignId('booking_id')->nullable()->constrained('tbl_bookings');
            $table->unsignedBigInteger('booking_scope')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
            $table->unique(['user_one_id', 'user_two_id', 'booking_scope'], 'conversation_pair_scope_unique');
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });
        Schema::create('tbl_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('tbl_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('tbl_users');
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'read_at', 'sender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_messages');
        Schema::dropIfExists('tbl_conversations');
    }
};
