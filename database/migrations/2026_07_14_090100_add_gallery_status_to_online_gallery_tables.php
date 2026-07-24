<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->enum('gallery_status', ['draft', 'published'])->default('draft')->after('published_at');
        });

        Schema::table('tbl_freelancer_online_gallery', function (Blueprint $table) {
            $table->enum('gallery_status', ['draft', 'published'])->default('draft')->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->dropColumn('gallery_status');
        });

        Schema::table('tbl_freelancer_online_gallery', function (Blueprint $table) {
            $table->dropColumn('gallery_status');
        });
    }
};
