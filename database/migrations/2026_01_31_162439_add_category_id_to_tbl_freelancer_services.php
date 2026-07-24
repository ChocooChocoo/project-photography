<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Redundant on a fresh database: create_tbl_freelancer_services_table already
 * creates category_id together with its foreign key and the
 * unique_user_category index. Guarded so `migrate:fresh` does not fail with a
 * duplicate column, while still repairing an older database that lacks it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tbl_freelancer_services', 'category_id')) {
            return;
        }

        Schema::table('tbl_freelancer_services', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->after('user_id')->nullable();

            $table->foreign('category_id')
                ->references('id')
                ->on('tbl_categories')
                ->onDelete('set null');

            $table->unique(['user_id', 'category_id'], 'unique_user_category');
        });
    }

    public function down(): void
    {
        // Left to create_tbl_freelancer_services_table, which owns the column.
    }
};
