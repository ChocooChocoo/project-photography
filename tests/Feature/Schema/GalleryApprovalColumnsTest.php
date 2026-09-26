<?php

namespace Tests\Feature\Schema;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GalleryApprovalColumnsTest extends TestCase
{
    use RefreshDatabase;

    private const TABLES = [
        'tbl_studio_online_gallery',
        'tbl_freelancer_online_gallery',
    ];

    private const APPROVAL_COLUMNS = [
        'approval_status',
        'rejection_reason',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    private const REQUIRED_TYPES = [
        'approval_status' => ['mysql' => 'enum', 'sqlite' => 'varchar'],
        'rejection_reason' => ['mysql' => 'text', 'sqlite' => 'text'],
        'submitted_by' => ['mysql' => 'bigint', 'sqlite' => 'integer'],
        'submitted_at' => ['mysql' => 'timestamp', 'sqlite' => 'datetime'],
        'approved_by' => ['mysql' => 'bigint', 'sqlite' => 'integer'],
        'approved_at' => ['mysql' => 'timestamp', 'sqlite' => 'datetime'],
        'rejected_by' => ['mysql' => 'bigint', 'sqlite' => 'integer'],
        'rejected_at' => ['mysql' => 'timestamp', 'sqlite' => 'datetime'],
    ];

    public function test_gallery_approval_columns_exist_on_both_gallery_tables(): void
    {
        foreach (self::TABLES as $table) {
            foreach (self::APPROVAL_COLUMNS as $column) {
                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "Expected column {$table}.{$column} to exist after migrations."
                );
            }
        }
    }

    public function test_gallery_approval_columns_are_nullable(): void
    {
        foreach (self::TABLES as $table) {
            $columns = $this->columns($table);

            foreach (self::APPROVAL_COLUMNS as $column) {
                $this->assertTrue(
                    $columns[$column]['nullable'],
                    "Expected column {$table}.{$column} to be nullable."
                );
            }
        }
    }

    public function test_gallery_approval_column_types_match_the_migration(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        foreach (self::TABLES as $table) {
            $columns = $this->columns($table);

            foreach (self::REQUIRED_TYPES as $column => $types) {
                $expected = $types[$driver] ?? $types['sqlite'];

                $this->assertSame(
                    $expected,
                    $columns[$column]['type_name'],
                    "Expected column {$table}.{$column} to be {$expected}."
                );
            }
        }
    }

    public function test_approval_status_offers_the_four_decisions(): void
    {
        foreach (self::TABLES as $table) {
            $definition = $this->approvalStatusDefinition($table);

            foreach (['pending', 'approved', 'rejected', 'cancelled'] as $value) {
                $this->assertStringContainsString(
                    $value,
                    $definition,
                    "Expected {$table}.approval_status to allow '{$value}'."
                );
            }
        }
    }

    public function test_gallery_approval_actor_columns_reference_tbl_users_with_set_null(): void
    {
        foreach (self::TABLES as $table) {
            $foreignKeys = collect(Schema::getForeignKeys($table));

            foreach (['submitted_by', 'approved_by', 'rejected_by'] as $column) {
                $foreignKey = $foreignKeys->first(
                    fn (array $key) => in_array($column, $key['columns'], true)
                );

                $this->assertNotNull($foreignKey, "Expected a foreign key on {$table}.{$column}.");
                $this->assertSame('tbl_users', $foreignKey['foreign_table']);
                $this->assertContains('id', $foreignKey['foreign_columns']);
                $this->assertSame('set null', $foreignKey['on_delete']);
            }
        }
    }

    private function columns(string $table): array
    {
        return collect(Schema::getColumns($table))->keyBy('name')->all();
    }

    private function approvalStatusDefinition(string $table): string
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $row = DB::selectOne(
                'select COLUMN_TYPE as definition from information_schema.COLUMNS '
                .'where TABLE_SCHEMA = DATABASE() and TABLE_NAME = ? and COLUMN_NAME = ?',
                [$table, 'approval_status']
            );

            return (string) ($row->definition ?? '');
        }

        if ($driver === 'sqlite') {
            $row = DB::selectOne(
                "select sql as definition from sqlite_master where type = 'table' and name = ?",
                [$table]
            );

            return (string) ($row->definition ?? '');
        }

        return '';
    }
}
