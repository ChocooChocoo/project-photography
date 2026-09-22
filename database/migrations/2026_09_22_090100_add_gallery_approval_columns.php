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
        $this->addApprovalColumns('tbl_studio_online_gallery');
        $this->addApprovalColumns('tbl_freelancer_online_gallery');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->dropApprovalColumns('tbl_studio_online_gallery');
        $this->dropApprovalColumns('tbl_freelancer_online_gallery');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Add the owner approval columns to one gallery table.
     */
    private function addApprovalColumns(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->enum('approval_status', ['pending', 'approved', 'rejected', 'cancelled'])->nullable()->after('gallery_status');
            $table->text('rejection_reason')->nullable()->after('approval_status');
            $table->foreignId('submitted_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('tbl_users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
        });
    }

    /**
     * Remove the owner approval columns from one gallery table.
     */
    private function dropApprovalColumns(string $tableName): void
    {
        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'rejection_reason',
                'submitted_by',
                'submitted_at',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
            ]);
        });
    }
};
