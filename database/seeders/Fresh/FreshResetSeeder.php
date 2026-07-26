<?php

namespace Database\Seeders\Fresh;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Clears every table the fresh seed owns.
 *
 * tbl_users and tbl_locations are preserved: they are absent from the truncate
 * list, the constructor refuses to run if they ever appear in it, and run()
 * compares their row counts before and after so a mistake aborts loudly rather
 * than destroying data.
 *
 * The list is explicit rather than derived from the live schema. Introspecting
 * "every table minus a preserve list" means a drifting preserve list silently
 * wipes something like `migrations`; an explicit constant shows the blast
 * radius in a diff. Tables that exist but are not listed are reported as a
 * warning and left untouched.
 */
class FreshResetSeeder
{
    /**
     * Never cleared. `migrations` is Laravel's own bookkeeping.
     *
     * @var array<int, string>
     */
    public const PRESERVED = [
        'tbl_users',
        'tbl_locations',
        'migrations',
    ];

    /**
     * Cleared, then deliberately left empty: every row in these tables is a
     * media record. tbl_procurement_documents cannot even be seeded without
     * media, since file_name and file_path are NOT NULL.
     *
     * @var array<int, string>
     */
    public const SKIPPED_MEDIA = [
        'tbl_studio_online_gallery',
        'tbl_freelancer_online_gallery',
        'tbl_procurement_documents',
    ];

    /**
     * Ordered children-before-parents. Foreign key checks are disabled during
     * the sweep, but SQLite ignores `PRAGMA foreign_keys = OFF` inside an open
     * transaction, so the order has to be correct on its own.
     *
     * @var array<int, string>
     */
    public const TRUNCATE_TABLES = [
        // Procurement, deepest first.
        'tbl_procurement_defect_returns',
        'tbl_procurement_inventory_stocks',
        'tbl_procurement_assets',
        'tbl_procurement_audit_trails',
        'tbl_procurement_documents',
        'tbl_procurement_purchase_order_items',
        'tbl_procurement_purchase_orders',
        'tbl_procurement_request_items',
        'tbl_procurement_requests',

        // Chatbot.
        'tbl_chatbot_messages',
        'tbl_chatbot_conversations',
        'tbl_chatbot_intents',
        'tbl_chatbot_configs',

        // HR, payroll, attendance.
        'tbl_generated_payrolls',
        'tbl_employee_attendance',
        'tbl_employee_payroll',
        'tbl_leave_requests',
        'tbl_overtime_requests',
        'tbl_studio_employee_schedule',

        // Bookings, payments, revenue, reviews, galleries.
        'tbl_system_revenue',
        'tbl_studio_ratings',
        'tbl_freelancer_ratings',
        'tbl_studio_online_gallery',
        'tbl_freelancer_online_gallery',
        'tbl_booking_assigned_photographers',
        'tbl_booking_packages',
        'tbl_payments',
        'tbl_bookings',

        // Subscriptions. Both plan tables hold a RESTRICT foreign key into
        // tbl_subscription_plans, so they must be emptied first.
        'tbl_studio_plans',
        'tbl_freelancer_plans',
        'tbl_subscription_plans',

        // Freelancer profiles and catalogue.
        'tbl_freelancer_packages',
        'tbl_freelancer_services',
        'tbl_freelancer_schedules',
        'pvt_freelancer_categories',
        'tbl_freelancers',

        // Studio catalogue, staffing, and the studios themselves.
        'tbl_studio_members',
        'tbl_studio_photographers',
        'tbl_studio_schedules',
        'pvt_studio_categories',
        'tbl_packages',
        'tbl_services',
        'tbl_client_budget',
        'tbl_notifications',
        'tbl_user_roles',
        'tbl_studios',

        // RBAC and reference data, rebuilt immediately afterwards.
        'tbl_role_permissions',
        'tbl_permissions',
        'tbl_roles',
        'tbl_categories',

        // Queue tables: stale payloads would reference deleted model ids.
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function __construct(private ?Command $command = null) {}

    public function run(): void
    {
        $this->assertPreservedTablesAreNotListed();
        $this->warnAboutUnlistedTables();

        $before = $this->preservedCounts();

        Schema::withoutForeignKeyConstraints(function (): void {
            foreach (self::TRUNCATE_TABLES as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
            }
        });

        $this->assertPreservedCountsUnchanged($before);

        $this->command?->info(sprintf(
            'Reset %d tables. Preserved tbl_users (%d rows) and tbl_locations (%d rows).',
            count(self::TRUNCATE_TABLES),
            $before['tbl_users'],
            $before['tbl_locations']
        ));
    }

    /**
     * Guard (b): a preserved table must never appear in the truncate list.
     */
    private function assertPreservedTablesAreNotListed(): void
    {
        foreach (self::PRESERVED as $table) {
            if (in_array($table, self::TRUNCATE_TABLES, true)) {
                throw new RuntimeException("Preserved table [{$table}] is listed for truncation. Refusing to run.");
            }
        }
    }

    /**
     * A table that exists but is not listed is reported, never truncated.
     * Adding a new table to the reset has to be a deliberate edit.
     */
    private function warnAboutUnlistedTables(): void
    {
        $unlisted = collect(Schema::getTableListing())
            ->map(fn (string $table): string => Str::afterLast($table, '.'))
            ->reject(fn (string $table): bool => in_array($table, self::PRESERVED, true))
            ->diff(self::TRUNCATE_TABLES)
            ->values();

        if ($unlisted->isNotEmpty()) {
            $this->command?->warn('Tables present but not reset: '.$unlisted->implode(', '));
        }
    }

    /**
     * @return array{tbl_users: int, tbl_locations: int}
     */
    private function preservedCounts(): array
    {
        return [
            'tbl_users' => DB::table('tbl_users')->count(),
            'tbl_locations' => DB::table('tbl_locations')->count(),
        ];
    }

    /**
     * Guard (c): the reset must not have changed either preserved table.
     *
     * @param  array{tbl_users: int, tbl_locations: int}  $before
     */
    private function assertPreservedCountsUnchanged(array $before): void
    {
        $after = $this->preservedCounts();

        foreach ($before as $table => $count) {
            if ($after[$table] !== $count) {
                throw new RuntimeException(sprintf(
                    'Reset changed preserved table [%s]: %d rows before, %d after.',
                    $table,
                    $count,
                    $after[$table]
                ));
            }
        }
    }
}
