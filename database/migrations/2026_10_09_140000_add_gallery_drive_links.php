<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            Schema::table($table, fn (Blueprint $schema) => $schema->text('drive_link')->nullable());
        }
    }

    public function down(): void
    {
        foreach (['tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            Schema::table($table, fn (Blueprint $schema) => $schema->dropColumn('drive_link'));
        }
    }
};
