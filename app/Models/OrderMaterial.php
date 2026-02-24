<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'material_id',
        'estimated_quantity',
        'actual_quantity',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'estimated_quantity' => 'decimal:2',
            'actual_quantity' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function material(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }
}
