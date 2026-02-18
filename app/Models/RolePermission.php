<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class RolePermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_id',
        'resource_type',
        'can_view',
        'can_create',
        'can_edit',
    ];

    protected $casts = [
        'can_view' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
