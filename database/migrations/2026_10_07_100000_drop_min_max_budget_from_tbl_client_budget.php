<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasMin = Schema::hasColumn('tbl_client_budget', 'minimum_budget');
        $hasMax = Schema::hasColumn('tbl_client_budget', 'maximum_budget');

        // Rows without a preferred amount fall back to the old max/min so the column can become required.
        $sources = array_filter([$hasMax ? 'maximum_budget' : null, $hasMin ? 'minimum_budget' : null]);
        $fallback = $sources ? 'COALESCE(' . implode(', ', $sources) . ', 0)' : '0';
        DB::table('tbl_client_budget')->whereNull('preferred_budget')->update(['preferred_budget' => DB::raw($fallback)]);

        if ($hasMin && $hasMax) {
            Schema::table('tbl_client_budget', fn (Blueprint $t) => $t->dropIndex(['minimum_budget', 'maximum_budget']));
        }

        foreach (['minimum_budget' => $hasMin, 'maximum_budget' => $hasMax] as $column => $exists) {
            if ($exists) {
                Schema::table('tbl_client_budget', fn (Blueprint $t) => $t->dropColumn($column));
            }
        }

        Schema::table('tbl_client_budget', fn (Blueprint $t) => $t->decimal('preferred_budget', 10, 2)->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('tbl_client_budget', function (Blueprint $table) {
            $table->decimal('preferred_budget', 10, 2)->nullable()->change();
            if (! Schema::hasColumn('tbl_client_budget', 'minimum_budget')) {
                $table->decimal('minimum_budget', 10, 2)->nullable()->after('description');
            }
            if (! Schema::hasColumn('tbl_client_budget', 'maximum_budget')) {
                $table->decimal('maximum_budget', 10, 2)->nullable()->after('minimum_budget');
            }
        });

        Schema::table('tbl_client_budget', fn (Blueprint $t) => $t->index(['minimum_budget', 'maximum_budget']));
    }
};
