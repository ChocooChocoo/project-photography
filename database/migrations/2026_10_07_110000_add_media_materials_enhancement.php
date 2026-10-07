<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('cover_videos')->nullable()->after('cover_images');
                $table->json('materials')->nullable()->after('cover_videos');
                $table->decimal('enhanced_photos_price', 10, 2)->nullable()->after('materials');
            });
        }

        foreach (['tbl_services', 'tbl_freelancer_services'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->json('images')->nullable();
                $table->json('videos')->nullable();
            });
        }

        Schema::table('tbl_booking_packages', function (Blueprint $table) {
            $table->json('materials')->nullable();
            $table->string('photo_edit_option', 20)->nullable();
            $table->decimal('enhancement_amount', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        foreach (['tbl_packages', 'tbl_freelancer_packages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['cover_videos', 'materials', 'enhanced_photos_price']);
            });
        }

        foreach (['tbl_services', 'tbl_freelancer_services'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['images', 'videos']);
            });
        }

        Schema::table('tbl_booking_packages', function (Blueprint $table) {
            $table->dropColumn(['materials', 'photo_edit_option', 'enhancement_amount']);
        });
    }
};
