<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_booking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('tbl_bookings')->cascadeOnDelete();
            $table->string('type', 80);
            $table->string('title');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignId('actor_id')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->string('event_key')->nullable();
            $table->unique(['booking_id', 'event_key']);
            $table->index(['booking_id', 'occurred_at']);
        });
        Schema::create('tbl_studio_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('studio_id')->constrained('tbl_studios')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 80);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('status', 30)->default('available');
            $table->timestamps();
        });
        Schema::table('tbl_booking_equipment', function (Blueprint $table) {
            $table->foreignId('equipment_id')->nullable()->constrained('tbl_studio_equipment')->nullOnDelete();
            $table->string('brand')->nullable();
        });
        Schema::table('tbl_bookings', fn (Blueprint $table) => $table->string('preferred_camera_brand', 100)->nullable());
        Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
            $table->string('on_site_proof_image')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_booking_assigned_photographers', fn (Blueprint $table) => $table->dropColumn(['on_site_proof_image', 'latitude', 'longitude']));
        Schema::table('tbl_bookings', fn (Blueprint $table) => $table->dropColumn('preferred_camera_brand'));
        Schema::table('tbl_booking_equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipment_id');
            $table->dropColumn('brand');
        });
        Schema::dropIfExists('tbl_studio_equipment');
        Schema::dropIfExists('tbl_booking_events');
    }
};
