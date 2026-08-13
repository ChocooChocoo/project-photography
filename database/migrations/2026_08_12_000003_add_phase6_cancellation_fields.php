<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_bookings', 'cancelled_by')) {
                $table->string('cancelled_by')->nullable();
            }
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tbl_bookings` MODIFY COLUMN `cancelled_by` ENUM('client', 'studio', 'system', 'photographer') NULL");
        }

        Schema::table('tbl_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('tbl_payments', 'refund_reference')) {
                $table->string('refund_reference')->nullable();
            }
            if (! Schema::hasColumn('tbl_payments', 'refund_notes')) {
                $table->text('refund_notes')->nullable();
            }
            if (! Schema::hasColumn('tbl_payments', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
        });
        if (! Schema::hasIndex('tbl_payments', ['refund_reference'], 'unique')) {
            Schema::table('tbl_payments', fn (Blueprint $table) => $table->unique('refund_reference', 'pmt_ref_unique'));
        }

        if (! Schema::hasColumn('tbl_booking_assigned_photographers', 'recovery_id')) {
            Schema::table('tbl_booking_assigned_photographers', function (Blueprint $table) {
                $table->unsignedBigInteger('recovery_id')->nullable();
            });
        }
        if (! Schema::hasIndex('tbl_booking_assigned_photographers', ['recovery_id'])) {
            Schema::table('tbl_booking_assigned_photographers', fn (Blueprint $table) => $table->index('recovery_id', 'assign_recovery_idx'));
        }

        if (! Schema::hasTable('tbl_booking_cancellation_recoveries')) {
            Schema::create('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('booking_id');
                $table->unsignedBigInteger('studio_id');
                $table->unsignedBigInteger('original_assignment_id');
                $table->unsignedBigInteger('replacement_assignment_id')->nullable();
                $table->string('status');
                $table->timestamp('deadline');
                $table->timestamp('replacement_proposed_at')->nullable();
                $table->timestamp('replacement_confirmed_at')->nullable();
                $table->timestamp('client_responded_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->string('outcome_reason')->nullable();
                $table->text('photographer_reason')->nullable();
                $table->timestamps();
                $table->unique('booking_id', 'bcr_booking_unique');
                $table->index('status', 'bcr_status_idx');
                $table->index('deadline', 'bcr_deadline_idx');
            });
        } else {
            Schema::table('tbl_booking_cancellation_recoveries', function (Blueprint $table) {
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'booking_id')) {
                    $table->unsignedBigInteger('booking_id');
                }
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'studio_id')) {
                    $table->unsignedBigInteger('studio_id');
                }
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'original_assignment_id')) {
                    $table->unsignedBigInteger('original_assignment_id');
                }
                if (! Schema::hasColumn('tbl_booking_cancellation_recoveries', 'replacement_assignment_id')) {
                    $table->unsignedBigInteger('replacement_assignment_id')->nullable();
                }
            });
        }

        $this->ensureForeignKey('tbl_booking_cancellation_recoveries', 'booking_id', 'tbl_bookings', 'bcr_booking_fk', true);
        $this->ensureForeignKey('tbl_booking_cancellation_recoveries', 'studio_id', 'tbl_studios', 'bcr_studio_fk', true);
        $this->ensureForeignKey('tbl_booking_cancellation_recoveries', 'original_assignment_id', 'tbl_booking_assigned_photographers', 'bcr_orig_assign_fk', true);
        $this->ensureForeignKey('tbl_booking_cancellation_recoveries', 'replacement_assignment_id', 'tbl_booking_assigned_photographers', 'bcr_repl_assign_fk', false);
        if (! Schema::hasIndex('tbl_booking_cancellation_recoveries', ['booking_id'], 'unique')) {
            Schema::table('tbl_booking_cancellation_recoveries', fn (Blueprint $table) => $table->unique('booking_id', 'bcr_booking_unique'));
        }
        if (! Schema::hasIndex('tbl_booking_cancellation_recoveries', ['status'])) {
            Schema::table('tbl_booking_cancellation_recoveries', fn (Blueprint $table) => $table->index('status', 'bcr_status_idx'));
        }
        if (! Schema::hasIndex('tbl_booking_cancellation_recoveries', ['deadline'])) {
            Schema::table('tbl_booking_cancellation_recoveries', fn (Blueprint $table) => $table->index('deadline', 'bcr_deadline_idx'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_booking_cancellation_recoveries');
        Schema::table('tbl_booking_assigned_photographers', fn (Blueprint $table) => $table->dropColumn('recovery_id'));
        Schema::table('tbl_payments', fn (Blueprint $table) => $table->dropColumn(['refund_reference', 'refund_notes', 'refunded_at']));
    }

    private function ensureForeignKey(string $tableName, string $column, string $referencedTable, string $constraintName, bool $cascade): void
    {
        $exists = collect(Schema::getForeignKeys($tableName))->contains(fn (array $foreignKey) =>
            $foreignKey['columns'] === [$column] && $foreignKey['foreign_table'] === $referencedTable
        );

        if ($exists) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($column, $referencedTable, $constraintName, $cascade) {
            $foreign = $table->foreign($column, $constraintName)
                ->references('id')
                ->on($referencedTable);
            $cascade ? $foreign->cascadeOnDelete() : $foreign->nullOnDelete();
        });
    }
};
