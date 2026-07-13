<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_studio_ratings', function (Blueprint $table) {
            $table->enum('status', ['published', 'flagged', 'removed'])->default('published')->after('review_type');
        });

        Schema::table('tbl_freelancer_ratings', function (Blueprint $table) {
            $table->enum('status', ['published', 'flagged', 'removed'])->default('published')->after('review_type');
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0)->after('studio_name');
            $table->unsignedInteger('total_reviews')->default(0)->after('avg_rating');
        });

        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studio_ratings', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('tbl_freelancer_ratings', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropColumn(['avg_rating', 'total_reviews']);
        });

        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->dropColumn(['avg_rating', 'total_reviews']);
        });
    }
};
