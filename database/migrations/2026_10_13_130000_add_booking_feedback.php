<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_feedback_aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 20);
            $table->string('target_scope', 20);
            $table->unsignedBigInteger('target_id');
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unique(['target_type', 'target_scope', 'target_id'], 'feedback_aggregate_target');
        });
        foreach (['service', 'photographer', 'photo'] as $type) {
            Schema::create('tbl_'.$type.'_ratings', function (Blueprint $table) use ($type) {
                $table->id();
                $table->foreignId('booking_id')->constrained('tbl_bookings')->cascadeOnDelete();
                $table->unsignedBigInteger('client_id');
                if ($type === 'service') {
                    $table->unsignedBigInteger('package_id');
                    $table->string('package_type', 20);
                    $table->unique(['booking_id', 'package_type', 'package_id']);
                } elseif ($type === 'photographer') {
                    $table->unsignedBigInteger('photographer_id');
                    $table->unique(['booking_id', 'photographer_id']);
                } else {
                    $table->unsignedBigInteger('gallery_id');
                    $table->string('gallery_type', 20);
                    $table->unique(['booking_id', 'gallery_type', 'gallery_id']);
                }
                $table->unsignedTinyInteger('rating')->nullable();
                $table->text('review_text')->nullable();
                $table->enum('status', ['published', 'flagged', 'removed'])->default('published');
                $table->timestamps();
                $table->index(['status', 'booking_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['photo', 'photographer', 'service'] as $type) {
            Schema::dropIfExists('tbl_'.$type.'_ratings');
        }
        Schema::dropIfExists('tbl_feedback_aggregates');
    }
};
