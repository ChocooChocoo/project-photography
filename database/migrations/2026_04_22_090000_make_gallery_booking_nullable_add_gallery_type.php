<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->makeIndependent('tbl_studio_online_gallery', 'tbl_studio_online_gallery_booking_id_foreign', 'tbl_studio_online_gallery_client_id_foreign');
        $this->makeIndependent('tbl_freelancer_online_gallery', 'tbl_freelancer_online_gallery_booking_id_foreign', 'tbl_freelancer_online_gallery_client_id_foreign');
    }

    private function makeIndependent(string $table, string $bookingFk, string $clientFk): void
    {
        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$bookingFk}`");
        DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$clientFk}`");
        DB::statement("ALTER TABLE `{$table}` MODIFY `booking_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `{$table}` MODIFY `client_id` BIGINT UNSIGNED NULL");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$bookingFk}` FOREIGN KEY (`booking_id`) REFERENCES `tbl_bookings` (`id`) ON DELETE CASCADE");
        DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$clientFk}` FOREIGN KEY (`client_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE");

        Schema::table($table, function (Blueprint $tableBlueprint) {
            $tableBlueprint->enum('gallery_type', ['booking', 'portfolio'])->default('booking')->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_studio_online_gallery', function (Blueprint $table) {
            $table->dropColumn('gallery_type');
        });

        Schema::table('tbl_freelancer_online_gallery', function (Blueprint $table) {
            $table->dropColumn('gallery_type');
        });
    }
};
