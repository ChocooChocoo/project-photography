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
        Schema::table('tbl_generated_payrolls', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('notes');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('rejection_reason')->nullable()->after('reviewed_at');

            $table->index('status');
            $table->index('reviewed_by');

            $table->foreign('reviewed_by')
                ->references('id')
                ->on('tbl_users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_generated_payrolls', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['reviewed_by']);
            $table->dropColumn([
                'status',
                'reviewed_by',
                'reviewed_at',
                'rejection_reason',
            ]);
        });
    }
};
