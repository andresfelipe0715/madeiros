<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['name', 'default_sequence', 'active', 'can_remit', 'is_delivery_stage'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'can_remit' => 'boolean',
            'is_delivery_stage' => 'boolean',
        ];
    }

    public function orderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_stages');
    }
}
