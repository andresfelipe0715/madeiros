<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stages(): BelongsToMany
    {
        return $this->belongsToMany(Stage::class, 'role_stages');
    }

    public function orderPermission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoleOrderPermission::class);
    }

    public function clientPermission(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RoleClientPermission::class);
    }
}
