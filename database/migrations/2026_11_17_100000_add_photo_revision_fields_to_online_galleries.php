<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a client flag individual gallery photos for revision instead of
 * pulling the whole gallery back to draft.
 */
return new class extends Migration
{
    private const TABLES = ['tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'revision_photos')) {
                    $blueprint->json('revision_photos')->nullable();
                }
                if (! Schema::hasColumn($table, 'revision_note')) {
                    $blueprint->text('revision_note')->nullable();
                }
                if (! Schema::hasColumn($table, 'revision_resolved_at')) {
                    $blueprint->timestamp('revision_resolved_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $columns = array_values(array_filter(
                ['revision_photos', 'revision_note', 'revision_resolved_at'],
                fn ($column) => Schema::hasColumn($table, $column)
            ));

            if ($columns) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($columns));
            }
        }
    }
};
