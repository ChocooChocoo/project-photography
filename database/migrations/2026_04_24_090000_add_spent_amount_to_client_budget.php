<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_client_budget', function (Blueprint $table) {
            $table->decimal('spent_amount', 10, 2)->default(0)->after('preferred_budget');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_client_budget', function (Blueprint $table) {
            $table->dropColumn('spent_amount');
        });
    }
};
