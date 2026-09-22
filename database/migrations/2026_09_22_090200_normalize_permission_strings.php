<?php

use App\Models\StudioOwner\PermissionModel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrite every stored permission string into the canonical
 * "portal.resource.action" form and merge the rows that collide after the
 * rewrite.
 *
 * The migration is idempotent. A second run finds one row per canonical string
 * and changes nothing.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tbl_permissions') || !Schema::hasColumn('tbl_permissions', 'permission_string')) {
            return;
        }

        $hasPortal = Schema::hasColumn('tbl_permissions', 'portal');
        $hasResource = Schema::hasColumn('tbl_permissions', 'resource');
        $hasAction = Schema::hasColumn('tbl_permissions', 'action');

        $rows = DB::table('tbl_permissions')->orderBy('id')->get();

        // Group the rows by the canonical string they resolve to.
        $groups = [];

        foreach ($rows as $row) {
            $canonical = $this->canonicalStringForRow($row, $hasPortal, $hasResource, $hasAction);

            if ($canonical === '') {
                continue;
            }

            $groups[$canonical][] = $row;
        }

        $mergedCount = 0;
        $rewrittenCount = 0;

        foreach ($groups as $canonical => $groupRows) {
            $keeper = array_shift($groupRows);

            foreach ($groupRows as $duplicate) {
                $this->moveRoleLinks((int) $duplicate->id, (int) $keeper->id);
                DB::table('tbl_permissions')->where('id', $duplicate->id)->delete();
                $mergedCount++;
            }

            if ((string) $keeper->permission_string !== $canonical) {
                // Delete the duplicates first so the unique index stays free.
                DB::table('tbl_permissions')
                    ->where('id', $keeper->id)
                    ->update(['permission_string' => $canonical]);
                $rewrittenCount++;
            }
        }

        Log::info('Permission string normalization complete.', [
            'rewritten' => $rewrittenCount,
            'merged' => $mergedCount,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * The repair rewrites data, so there is nothing to reverse.
     */
    public function down(): void
    {
        //
    }

    /**
     * Resolve the canonical string for one stored row.
     */
    private function canonicalStringForRow(object $row, bool $hasPortal, bool $hasResource, bool $hasAction): string
    {
        $portal = $hasPortal ? $this->canonicalSegment((string) ($row->portal ?? '')) : '';
        $resource = $hasResource ? $this->canonicalSegment((string) ($row->resource ?? '')) : '';
        $action = $hasAction ? $this->canonicalSegment((string) ($row->action ?? '')) : '';
        $storedValue = (string) ($row->permission_string ?? '');

        if ($resource !== '' && $action !== '') {
            if ($portal === '') {
                $portal = $this->portalFromStoredString($storedValue) ?: 'owner';
            }

            return PermissionModel::canonicalString($portal, $resource, $action);
        }

        $normalizedValue = PermissionModel::normalizeIdentifier($storedValue);

        if ($normalizedValue === '') {
            return '';
        }

        $segments = explode('.', $normalizedValue);

        // The normalizer adds the placeholder prefix when the stored value has
        // no portal. Replace the placeholder with the row portal.
        if (($segments[0] ?? '') === 'portal') {
            array_shift($segments);
            $portalPrefix = $portal !== '' ? $portal : 'owner';
            $normalizedValue = $portalPrefix.'.'.implode('.', $segments);
        }

        return $normalizedValue;
    }

    /**
     * Read the portal name out of a stored permission string when one is present.
     */
    private function portalFromStoredString(string $storedValue): string
    {
        $normalizedValue = PermissionModel::normalizeIdentifier($storedValue);

        if ($normalizedValue === '') {
            return '';
        }

        $firstSegment = explode('.', $normalizedValue)[0] ?? '';

        if (in_array($firstSegment, ['owner', 'studio-hr', 'studio-finance', 'studio-photographer'], true)) {
            return $firstSegment;
        }

        return '';
    }

    /**
     * Move every role link from the duplicate row to the row that stays.
     */
    private function moveRoleLinks(int $fromId, int $toId): void
    {
        if (!Schema::hasTable('tbl_role_permissions')) {
            return;
        }

        $roleIds = DB::table('tbl_role_permissions')
            ->where('permission_id', $fromId)
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $alreadyLinked = DB::table('tbl_role_permissions')
                ->where('permission_id', $toId)
                ->where('role_id', $roleId)
                ->exists();

            if ($alreadyLinked) {
                DB::table('tbl_role_permissions')
                    ->where('permission_id', $fromId)
                    ->where('role_id', $roleId)
                    ->delete();
                continue;
            }

            DB::table('tbl_role_permissions')
                ->where('permission_id', $fromId)
                ->where('role_id', $roleId)
                ->update(['permission_id' => $toId]);
        }
    }

    /**
     * Normalize one segment for the canonical string. An underscore becomes a hyphen.
     */
    private function canonicalSegment(string $value): string
    {
        $normalizedValue = strtolower(trim($value));
        $normalizedValue = preg_replace('/[^a-z0-9]+/', '-', $normalizedValue) ?? '';
        $normalizedValue = preg_replace('/-+/', '-', $normalizedValue) ?? '';

        return trim($normalizedValue, '-');
    }
};
