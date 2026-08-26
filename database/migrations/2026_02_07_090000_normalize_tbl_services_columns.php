<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills a migration that was never written. On the live database
 * service_description and status were dropped and service_name was widened to
 * text, because ServicesModel stores a JSON array of service names in it.
 * See resources/views/owner/view-services.blade.php, which documents the
 * removal of the two columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_services', function (Blueprint $table) {
            $table->text('service_name')->change();
        });

        Schema::table('tbl_services', function (Blueprint $table) {
            // SQLite cannot rebuild the table while an index still references a
            // column being removed. The original migration dropped `status`
            // without first removing its generated index.
            if (Schema::hasIndex('tbl_services', 'tbl_services_status_index')) {
                $table->dropIndex('tbl_services_status_index');
            }

            foreach (['service_description', 'status'] as $column) {
                if (Schema::hasColumn('tbl_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_services', function (Blueprint $table) {
            $table->text('service_description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('service_name')->change();
        });
    }
};
