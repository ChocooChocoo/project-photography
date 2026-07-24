<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->json('cover_images')->nullable()->after('package_location');
        });

        Schema::table('tbl_freelancer_packages', function (Blueprint $table) {
            $table->json('cover_images')->nullable()->after('coverage_scope');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->dropColumn('cover_images');
        });

        Schema::table('tbl_freelancer_packages', function (Blueprint $table) {
            $table->dropColumn('cover_images');
        });
    }
};
