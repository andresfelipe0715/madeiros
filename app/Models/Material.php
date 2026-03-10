<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'stock_quantity',
        'reserved_quantity',
        'bodega_quantity',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
            'bodega_quantity' => 'decimal:2',
        ];
    }

    public function orderMaterials(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OrderMaterial::class);
    }

    public function availableQuantity(): float
    {
        return (float) ($this->stock_quantity - $this->reserved_quantity);
    }

    public function inventoryLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }
}
