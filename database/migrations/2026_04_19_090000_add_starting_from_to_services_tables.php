<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_services', function (Blueprint $table) {
            $table->decimal('starting_from', 10, 2)->nullable()->after('service_name');
        });

        Schema::table('tbl_freelancer_services', function (Blueprint $table) {
            $table->decimal('starting_from', 10, 2)->nullable()->after('services_name');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_services', function (Blueprint $table) {
            $table->dropColumn('starting_from');
        });

        Schema::table('tbl_freelancer_services', function (Blueprint $table) {
            $table->dropColumn('starting_from');
        });
    }
};
