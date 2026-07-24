<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * tbl_online_gallery was an early, provider-agnostic gallery table that was
 * superseded by tbl_studio_online_gallery and tbl_freelancer_online_gallery.
 * It has no Eloquent model and no reference anywhere in app/, routes/,
 * resources/views/, or the seeder chain, so it is dropped here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('tbl_online_gallery');
    }

    public function down(): void
    {
        Schema::create('tbl_online_gallery', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('studio_id');
            $table->unsignedBigInteger('client_id');
            $table->string('gallery_reference')->unique();
            $table->string('gallery_name')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('total_photos')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('tbl_bookings')->onDelete('cascade');
            $table->foreign('studio_id')->references('id')->on('tbl_studios')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('tbl_users')->onDelete('cascade');

            $table->index('booking_id');
            $table->index('studio_id');
            $table->index('client_id');
            $table->index('status');
        });
    }
};
