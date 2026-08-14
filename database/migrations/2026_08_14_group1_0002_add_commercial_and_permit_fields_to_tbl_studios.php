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
        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->decimal('maximum_price', 10, 2)->nullable()->after('starting_price');
            $table->string('linkedin_url', 255)->nullable()->after('website_url');
            $table->boolean('requires_downpayment')->default(true)->after('downpayment_percentage');
            $table->date('permit_expiry_date')->nullable()->after('status');
            $table->unsignedInteger('resubmission_count')->default(0)->after('permit_expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropColumn([
                'maximum_price',
                'linkedin_url',
                'requires_downpayment',
                'permit_expiry_date',
                'resubmission_count',
            ]);
        });
    }
};
