<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills a migration that was never written: location_id, street, and
 * barangay were added to tbl_studios directly on the live database, which left
 * `migrate:fresh` broken because the next migration positions its columns
 * `after('barangay')`. Dated just before that migration so ordering resolves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studios', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_studios', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('category_id');
                $table->foreign('location_id')
                    ->references('id')
                    ->on('tbl_locations')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            }

            if (! Schema::hasColumn('tbl_studios', 'street')) {
                $table->string('street')->nullable()->after('location_id');
            }

            if (! Schema::hasColumn('tbl_studios', 'barangay')) {
                $table->string('barangay')->nullable()->after('street');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn(['location_id', 'street', 'barangay']);
        });
    }
};
