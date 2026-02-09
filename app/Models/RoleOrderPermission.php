<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleOrderPermission extends Model
{
    protected $fillable = [
        'role_id',
        'can_view',
        'can_edit',
        'can_create',
    ];

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
