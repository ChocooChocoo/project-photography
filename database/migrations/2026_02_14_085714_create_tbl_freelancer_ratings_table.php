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
        Schema::create('tbl_freelancer_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('freelancer_id'); // References user_id in tbl_freelancers
            $table->unsignedTinyInteger('rating')->comment('1-5 stars');
            $table->string('title')->nullable();
            $table->text('review_text');
            $table->enum('review_type', ['positive', 'neutral', 'negative'])->nullable();
            $table->string('preset_used')->nullable()->comment('The preset review template used');
            $table->boolean('is_recommend')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('booking_id')->references('id')->on('tbl_bookings')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('tbl_users')->onDelete('cascade');
            // freelancer_id holds a user id (bookings store the freelancer's
            // user id in provider_id), and tbl_freelancers.user_id has no
            // unique key, so this points at tbl_users directly.
            $table->foreign('freelancer_id')->references('id')->on('tbl_users')->onDelete('cascade');

            // Ensure one review per booking
            $table->unique('booking_id', 'unique_freelancer_booking_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_freelancer_ratings');
    }
};
