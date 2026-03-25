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
        Schema::table('tbl_roles', function (Blueprint $table) {
            $table->boolean('is_system')
                ->default(false)
                ->after('status')
                ->comment('Whether the role is system-protected.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_roles', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
