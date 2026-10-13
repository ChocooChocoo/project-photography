<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reporter_id');
            $table->enum('target_type', ['studio', 'photographer']);
            $table->unsignedBigInteger('target_id');
            $table->foreignId('booking_id')->constrained('tbl_bookings')->cascadeOnDelete();
            $table->string('reason');
            $table->text('details');
            $table->string('evidence')->nullable();
            $table->enum('status', ['open', 'reviewing', 'resolved', 'dismissed'])->default('open');
            $table->text('resolution_note')->nullable();
            $table->string('active_key', 150)->nullable()->unique();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_reports');
    }
};
