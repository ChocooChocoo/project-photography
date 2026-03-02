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
        Schema::table('tbl_packages', function (Blueprint $table) {
            // Add allow_multiple_locations boolean field with default false
            $table->boolean('allow_multiple_locations')
                  ->default(false)
                  ->after('package_location')
                  ->comment('Determines if package allows multiple shooting locations');
            
            // Add max_locations integer field with default 1, nullable
            $table->integer('max_locations')
                  ->default(1)
                  ->nullable()
                  ->after('allow_multiple_locations')
                  ->comment('Maximum number of locations allowed (1-10)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_packages', function (Blueprint $table) {
            $table->dropColumn(['allow_multiple_locations', 'max_locations']);
        });
    }
};