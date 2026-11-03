<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links each edited gallery photo to its unedited original, so clients
 * can compare them with a before-and-after slider.
 * Shape: {"<edited path>": "<original path>"}.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->json('raw_images')->nullable()->after('images');
            });
        }
    }

    public function down(): void
    {
        foreach (['tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropColumn('raw_images'));
        }
    }
};
