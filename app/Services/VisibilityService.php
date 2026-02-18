<?php

namespace App\Services;

use App\Models\RoleVisibilityPermission;
use App\Models\User;

class VisibilityService
{
    protected ?RoleVisibilityPermission $permissions = null;

    public function __construct(protected ?User $user = null)
    {
        if ($this->user && $this->user->role_id) {
            $this->loadPermissions();
        }
    }

    public static function forUser(?User $user): self
    {
        return new self($user);
    }

    protected function loadPermissions(): void
    {
        $roleId = $this->user->role_id;

        // Use request-level caching or simple singleton-like property
        $this->permissions = RoleVisibilityPermission::where('role_id', $roleId)->first();
    }

    protected function check(string $field): bool
    {
        // If no record exists, default to TRUE as per requirements (fail-safe)
        if (!$this->permissions) {
            return true;
        }

        return (bool) $this->permissions->{$field};
    }

    public function canViewFiles(): bool
    {
        return $this->check('can_view_files');
    }

    public function canViewOrderFile(): bool
    {
        return $this->check('can_view_order_file');
    }

    public function canViewMachineFile(): bool
    {
        return $this->check('can_view_machine_file');
    }

    public function canViewPerformance(): bool
    {
        return $this->user->role->hasPermission('performance', 'view');
    }
}
