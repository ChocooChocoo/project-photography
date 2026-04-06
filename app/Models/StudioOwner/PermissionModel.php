<?php

namespace App\Models\StudioOwner;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionModel extends Model
{
    use HasFactory;

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
}
