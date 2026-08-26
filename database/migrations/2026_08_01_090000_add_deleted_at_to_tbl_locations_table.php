<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tbl_locations') && ! Schema::hasColumn('tbl_locations', 'deleted_at')) {
            Schema::table('tbl_locations', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tbl_locations') && Schema::hasColumn('tbl_locations', 'deleted_at')) {
            Schema::table('tbl_locations', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
