<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->date('event_end_date')->nullable();
            $table->string('booking_duration_type', 20)->default('short_term');
            $table->timestamp('terms_accepted_at')->nullable();
        });

        Schema::create('tbl_booking_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->longText('terms_html');
            $table->string('studio_signature')->nullable();
            $table->string('studio_signed_by')->nullable();
            $table->unsignedBigInteger('studio_signed_user_id')->nullable();
            $table->timestamp('studio_signed_at')->nullable();
            $table->string('client_signature')->nullable();
            $table->string('client_signed_by')->nullable();
            $table->timestamp('client_signed_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->text('contract_template')->nullable();
        });

        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->text('contract_template')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tbl_freelancers', function (Blueprint $table) {
            $table->dropColumn('contract_template');
        });

        Schema::table('tbl_studios', function (Blueprint $table) {
            $table->dropColumn('contract_template');
        });

        Schema::dropIfExists('tbl_booking_contracts');

        Schema::table('tbl_bookings', function (Blueprint $table) {
            $table->dropColumn(['event_end_date', 'booking_duration_type', 'terms_accepted_at']);
        });
    }
};
