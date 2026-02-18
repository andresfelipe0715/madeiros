<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleVisibilityPermission extends Model
{
    protected $fillable = [
        'role_id',
        'can_view_files',
        'can_view_order_file',
        'can_view_machine_file',
        'can_view_performance',
    ];

    protected function casts(): array
    {
        return [
            'can_view_files' => 'boolean',
            'can_view_order_file' => 'boolean',
            'can_view_machine_file' => 'boolean',
            'can_view_performance' => 'boolean',
        ];
    }

    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
