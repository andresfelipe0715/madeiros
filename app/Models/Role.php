<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'active'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'role_stages');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    public function visibilityPermission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoleVisibilityPermission::class);
    }

    /**
     * Check if the role has a specific permission for a resource.
     */
    public function hasPermission(string $resource, string $action): bool
    {
        $permission = $this->permissions->firstWhere('resource_type', $resource);

        if (!$permission) {
            return false;
        }

        $field = 'can_' . $action;
        return (bool) ($permission->{$field} ?? false);
    }

    protected static function booted(): void
    {
        static::created(function (Role $role) {
            $role->visibilityPermission()->create([
                'can_view_files' => true,
                'can_view_order_file' => true,
                'can_view_machine_file' => true,
            ]);
        });
    }
}
