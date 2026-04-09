<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionModel extends Model
{
    use HasFactory;

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
        'inquiries' => 'Inquiries',
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
     * Build possible legacy and protocol permission identifiers.
     *
     * @param string $permissionIdentifier
     * @return array<int, string>
     */
    public static function buildPermissionIdentifiers(string $permissionIdentifier): array
    {
        $trimmedPermissionIdentifier = trim(strtolower($permissionIdentifier));

        if ($trimmedPermissionIdentifier === '') {
            return [];
        }

        $identifiers = [$trimmedPermissionIdentifier];

        if (str_contains($trimmedPermissionIdentifier, '.')) {
            $segments = array_values(array_filter(explode('.', $trimmedPermissionIdentifier), static fn ($segment) => $segment !== ''));

            if (count($segments) >= 2) {
                $action = array_pop($segments);
                $resource = array_pop($segments);

                if ($resource && $action) {
                    $identifiers[] = $action . '_' . $resource;
                    $identifiers[] = $resource . ':' . $action;
                }
            }
        } elseif (str_contains($trimmedPermissionIdentifier, ':')) {
            [$resource, $action] = array_pad(explode(':', $trimmedPermissionIdentifier, 2), 2, '');
            $identifiers[] = $action . '_' . $resource;
        } elseif (str_contains($trimmedPermissionIdentifier, '_')) {
            [$action, $resource] = array_pad(explode('_', $trimmedPermissionIdentifier, 2), 2, '');
            $identifiers[] = $resource . ':' . $action;
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

        return trim($actionLabel . ' ' . $resourceLabel);
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
     * Convert a technical segment to a readable label.
     */
    private static function humanizeSegment(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
