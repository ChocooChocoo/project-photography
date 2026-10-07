<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the studio-facing online-gallery permissions so the owner's permission
 * picker can grant gallery access to HR employees.
 *
 * Idempotent: rows are upserted on permission_string, matching the convention
 * used by StudioOwnerPermissionsSeeder.
 */
class StudioOnlineGalleryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $permissions = [
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.online-gallery.view', 'resource' => 'online-gallery', 'action' => 'view', 'description' => 'View online galleries for the assigned studio.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.online-gallery.manage', 'resource' => 'online-gallery', 'action' => 'manage', 'description' => 'Create, upload, update, and publish online galleries for the assigned studio.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.online-gallery.create', 'resource' => 'online-gallery', 'action' => 'create', 'description' => 'Create galleries and upload images for the assigned studio.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.online-gallery.update', 'resource' => 'online-gallery', 'action' => 'update', 'description' => 'Update, publish, and submit online galleries for the assigned studio.'],
            ['portal' => 'studio-hr', 'permission_string' => 'studio-hr.online-gallery.delete', 'resource' => 'online-gallery', 'action' => 'delete', 'description' => 'Delete online galleries and gallery images for the assigned studio.'],
        ];

        foreach ($permissions as $permission) {
            $portal = $permission['portal'];
            $resource = $this->normalizeSegment($permission['resource']);
            $action = $this->normalizeSegment($permission['action']);
            $name = $this->buildUniquePermissionName($portal, $action, $resource);

            DB::table('tbl_permissions')->updateOrInsert(
                ['permission_string' => $permission['permission_string']],
                [
                    'name' => $name,
                    'portal' => $portal,
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $permission['description'],
                    'status' => 'active',
                    'updated_at' => $now,
                    'created_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
                ]
            );

            $this->command?->info("Seeded permission: {$permission['permission_string']}");
        }
    }

    /**
     * Normalize a permission resource or action segment.
     */
    private function normalizeSegment(string $value): string
    {
        $normalizedValue = strtolower(trim($value));
        $normalizedValue = preg_replace('/[^a-z0-9]+/', '_', $normalizedValue) ?? '';

        return trim($normalizedValue, '_');
    }

    /**
     * Build a globally unique permission name across portals.
     */
    private function buildUniquePermissionName(string $portal, string $action, string $resource): string
    {
        return $this->normalizeSegment($portal).'_'.$action.'_'.$resource;
    }
}
