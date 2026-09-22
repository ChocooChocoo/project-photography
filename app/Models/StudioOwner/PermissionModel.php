<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PermissionModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Friendly portal labels keyed by stored portal names.
     *
     * @var array<string, string>
     */
    protected const PORTAL_LABELS = [
        'owner' => 'Owner Portal',
        'studio-hr' => 'HR Portal',
        'studio-finance' => 'Finance Portal',
        'studio-photographer' => 'Photographer Portal',
    ];

    /**
     * Portal names that the canonical format accepts as the first segment.
     *
     * The list is the portal part of the stored permission string. A value whose
     * first segment is not in this list gets the placeholder prefix "portal.".
     *
     * @var array<int, string>
     */
    protected const KNOWN_PORTALS = [
        'owner',
        'studio-hr',
        'studio-finance',
        'studio-photographer',
        'admin',
        'client',
        'freelancer',
    ];

    /**
     * Friendly resource labels keyed by stored resource names.
     *
     * @var array<string, string>
     */
    protected const RESOURCE_LABELS = [
        'assignment' => 'Assignment',
        'attendance' => 'Attendance',
        'bookings' => 'Bookings',
        'chatbot' => 'Chatbot',
        'dashboard' => 'Dashboard',
        'employee' => 'Employee',
        'employees' => 'Employees',
        'generate_payroll' => 'Payroll Generation',
        'leave_requests' => 'Leave Requests',
        'members' => 'Members',
        'online_gallery' => 'Online Gallery',
        'overtime_requests' => 'Overtime Requests',
        'packages' => 'Packages',
        'payroll' => 'Payroll',
        'permissions' => 'Permissions',
        'photographers' => 'Photographers',
        'roles' => 'Roles',
        'schedules' => 'Schedules',
        'services' => 'Services',
        'studio' => 'Studio',
        'studios' => 'Studios',
        'subscription' => 'Subscription',
    ];

    /**
     * Friendly action labels keyed by stored action names.
     *
     * @var array<string, string>
     */
    protected const ACTION_LABELS = [
        'approve' => 'Approve',
        'create' => 'Create',
        'delete' => 'Delete',
        'edit' => 'Edit',
        'manage' => 'Manage',
        'reject' => 'Reject',
        'update' => 'Update',
        'update_status' => 'Update Status',
        'view' => 'View',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tbl_permissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'portal',
        'resource',
        'action',
        'permission_string',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Build the canonical permission string from its three parts.
     *
     * The result is lowercase, uses hyphens inside the portal, resource, and
     * action segments, and joins the parts with dots. Example for the parts
     * "studio-hr", "online gallery", and "manage": "studio-hr.online-gallery.manage".
     */
    public static function canonicalString(string $portal, string $resource, string $action): string
    {
        $segments = array_filter([
            static::normalizeCanonicalSegment($portal),
            static::normalizeCanonicalSegment($resource),
            static::normalizeCanonicalSegment($action),
        ], static fn (string $segment) => $segment !== '');

        return implode('.', $segments);
    }

    /**
     * Normalize one permission identifier into the canonical match form.
     *
     * The value is lowercased, a colon becomes a dot, and an underscore and a
     * hyphen both become a hyphen. When the first segment is not a known portal
     * name, the placeholder prefix "portal." is added. A value that already
     * carries the placeholder prefix is left alone.
     */
    public static function normalizeIdentifier(string $value): string
    {
        $normalizedValue = strtolower(trim($value));

        if ($normalizedValue === '') {
            return '';
        }

        $normalizedValue = str_replace(':', '.', $normalizedValue);
        $normalizedValue = preg_replace('/[^a-z0-9.]+/', '-', $normalizedValue) ?? '';
        $normalizedValue = preg_replace('/-+/', '-', $normalizedValue) ?? '';
        $normalizedValue = preg_replace('/\.+/', '.', $normalizedValue) ?? '';
        $normalizedValue = trim($normalizedValue, '.-');

        if ($normalizedValue === '') {
            return '';
        }

        $segments = explode('.', $normalizedValue);
        $firstSegment = $segments[0] ?? '';

        if ($firstSegment !== 'portal' && !in_array($firstSegment, static::KNOWN_PORTALS, true)) {
            array_unshift($segments, 'portal');
        }

        return implode('.', $segments);
    }

    /**
     * Decide whether a requested permission identifier grants a stored value.
     *
     * The compare accepts a missing portal prefix on either side and treats an
     * underscore and a hyphen as the same character. Two values that both carry
     * a real portal must carry the same portal to match.
     */
    public static function identifierMatches(string $requested, ?string $stored): bool
    {
        if ($stored === null || $stored === '') {
            return false;
        }

        $requestedIdentifier = static::normalizeIdentifier($requested);
        $storedIdentifier = static::normalizeIdentifier($stored);

        if ($requestedIdentifier === '' || $storedIdentifier === '') {
            return false;
        }

        if ($requestedIdentifier === $storedIdentifier) {
            return true;
        }

        $requestedHasPortal = static::hasKnownPortalPrefix($requestedIdentifier);
        $storedHasPortal = static::hasKnownPortalPrefix($storedIdentifier);

        if ($requestedHasPortal && $storedHasPortal) {
            return false;
        }

        if (static::resourceActionKey($requestedIdentifier) === static::resourceActionKey($storedIdentifier)) {
            return true;
        }

        // Legacy grants store the action before the resource, for example
        // "manage_employees". Accept that order when at least one side has no
        // real portal.
        return static::tokenKey($requestedIdentifier) === static::tokenKey($storedIdentifier);
    }

    /**
     * Build possible identifiers for a requested permission.
     *
     * The list holds the canonical form, the resource and action part, and the
     * original value. Role checks use the list in one query.
     *
     * @return array<int, string>
     */
    public static function buildPermissionIdentifiers(string $permissionIdentifier): array
    {
        $canonical = static::normalizeIdentifier($permissionIdentifier);

        if ($canonical === '') {
            return [];
        }

        $identifiers = [$canonical];
        $resourceActionKey = static::resourceActionKey($canonical);

        if ($resourceActionKey !== '' && $resourceActionKey !== $canonical) {
            $identifiers[] = $resourceActionKey;
        }

        $originalValue = strtolower(trim($permissionIdentifier));

        if ($originalValue !== '') {
            $identifiers[] = $originalValue;
        }

        return array_values(array_unique(array_filter($identifiers)));
    }

    /**
     * Get the roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(RoleModel::class, 'tbl_role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }

    /**
     * Scope to filter active permissions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if permission is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the user-friendly label for this permission.
     */
    public function getDisplayLabelAttribute(): string
    {
        $resourceLabel = static::getResourceLabel($this->resource);
        $actionLabel = static::getActionLabel($this->action);

        return trim($actionLabel.' '.$resourceLabel);
    }

    /**
     * Get the portal display label for this permission.
     */
    public function getPortalDisplayAttribute(): string
    {
        return static::getPortalLabel($this->portal);
    }

    /**
     * Get the resource display label for this permission.
     */
    public function getResourceDisplayAttribute(): string
    {
        return static::getResourceLabel($this->resource);
    }

    /**
     * Get the action display label for this permission.
     */
    public function getActionDisplayAttribute(): string
    {
        return static::getActionLabel($this->action);
    }

    /**
     * Resolve a user-friendly portal label.
     */
    public static function getPortalLabel(?string $portal): string
    {
        $normalizedPortal = static::normalizeSegment($portal);

        if ($normalizedPortal === '') {
            return 'System';
        }

        return static::PORTAL_LABELS[$normalizedPortal] ?? static::humanizeSegment($normalizedPortal);
    }

    /**
     * Resolve a user-friendly resource label.
     */
    public static function getResourceLabel(?string $resource): string
    {
        $normalizedResource = static::normalizeSegment($resource);

        if ($normalizedResource === '') {
            return 'Access';
        }

        return static::RESOURCE_LABELS[$normalizedResource] ?? static::humanizeSegment($normalizedResource);
    }

    /**
     * Resolve a user-friendly action label.
     */
    public static function getActionLabel(?string $action): string
    {
        $normalizedAction = static::normalizeSegment($action);

        if ($normalizedAction === '') {
            return 'Access';
        }

        return static::ACTION_LABELS[$normalizedAction] ?? static::humanizeSegment($normalizedAction);
    }

    /**
     * Normalize a permission segment for display lookups.
     */
    private static function normalizeSegment(?string $value): string
    {
        return strtolower(trim(str_replace('-', '_', (string) $value)));
    }

    /**
     * Normalize a permission segment for the canonical string.
     */
    private static function normalizeCanonicalSegment(?string $value): string
    {
        $normalizedValue = strtolower(trim((string) $value));
        $normalizedValue = preg_replace('/[^a-z0-9]+/', '-', $normalizedValue) ?? '';

        return trim($normalizedValue, '-');
    }

    /**
     * Ask whether the identifier starts with a real portal name.
     */
    private static function hasKnownPortalPrefix(string $identifier): bool
    {
        $firstSegment = explode('.', $identifier)[0] ?? '';

        return in_array($firstSegment, static::KNOWN_PORTALS, true);
    }

    /**
     * Drop the portal segment from an identifier and keep the resource and action.
     */
    private static function resourceActionKey(string $identifier): string
    {
        $segments = explode('.', $identifier);
        $firstSegment = $segments[0] ?? '';

        if ($firstSegment === 'portal' || in_array($firstSegment, static::KNOWN_PORTALS, true)) {
            array_shift($segments);
        }

        return implode('.', $segments);
    }

    /**
     * Order the resource and action tokens so the old action-first form can match.
     */
    private static function tokenKey(string $identifier): string
    {
        $tokens = preg_split('/[.\-]+/', static::resourceActionKey($identifier)) ?: [];
        $tokens = array_values(array_filter($tokens));
        sort($tokens);

        return implode('.', $tokens);
    }

    /**
     * Convert a technical segment to a readable label.
     */
    private static function humanizeSegment(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
