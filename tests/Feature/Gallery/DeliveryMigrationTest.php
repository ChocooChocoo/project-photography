<?php

namespace Tests\Feature\Gallery;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DeliveryMigrationTest extends TestCase
{
    public function test_delivery_migrations_roll_back_without_removing_bookings_or_galleries(): void
    {
        Schema::dropAllTables();
        foreach (['tbl_bookings', 'tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            Schema::create($table, function (Blueprint $schema) {
                $schema->id();
                $schema->string('reference');
            });
            DB::table($table)->insert(['reference' => 'preserved']);
        }
        $drive = require database_path('migrations/2026_10_09_140000_add_gallery_drive_links.php');
        $hardcopy = require database_path('migrations/2026_10_09_150000_add_hardcopy_orders.php');
        $drive->up();
        $hardcopy->up();
        $this->assertTrue(Schema::hasColumn('tbl_studio_online_gallery', 'drive_link'));
        $this->assertTrue(Schema::hasColumn('tbl_freelancer_online_gallery', 'drive_link'));
        $this->assertTrue(Schema::hasTable('tbl_booking_hardcopy_items'));
        $hardcopy->down();
        $drive->down();
        $this->assertFalse(Schema::hasTable('tbl_booking_hardcopy_orders'));
        $this->assertFalse(Schema::hasTable('tbl_booking_hardcopy_items'));
        $this->assertFalse(Schema::hasColumn('tbl_bookings', 'delivery_type'));
        foreach (['tbl_bookings', 'tbl_studio_online_gallery', 'tbl_freelancer_online_gallery'] as $table) {
            $this->assertDatabaseHas($table, ['reference' => 'preserved']);
        }
        $drive->up();
        $hardcopy->up();
        $this->assertTrue(Schema::hasColumn('tbl_bookings', 'delivery_type'));
    }
}
