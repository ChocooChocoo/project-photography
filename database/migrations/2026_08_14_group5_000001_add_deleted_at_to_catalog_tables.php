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
        Schema::table('tbl_services', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });

        Schema::table('tbl_categories', function (Blueprint $table) {
            $table->dropUnique('tbl_categories_category_name_unique');
            $table->softDeletes()->after('updated_at');
            $table->unique(['category_name', 'deleted_at'], 'tbl_categories_category_name_deleted_at_unique');
        });

        Schema::table('tbl_locations', function (Blueprint $table) {
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_locations', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tbl_categories', function (Blueprint $table) {
            $table->dropUnique('tbl_categories_category_name_deleted_at_unique');
            $table->dropSoftDeletes();
            $table->unique('category_name');
        });

        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('tbl_services', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
