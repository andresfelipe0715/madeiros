<?php

namespace App\Services;

use App\Models\User;

class OrderPermissionService
{
    /**
     * Check if the user can view the orders list.
     */
    public function canView(User $user): bool
    {
        return (bool) ($user->role->orderPermission->can_view ?? false);
    }

    /**
     * Check if the user can edit orders.
     */
    public function canEdit(User $user): bool
    {
        return (bool) ($user->role->orderPermission->can_edit ?? false);
    }

    /**
     * Check if the user can create orders.
     */
    public function canCreate(User $user): bool
    {
        return (bool) ($user->role->orderPermission->can_create ?? false);
    }
}
