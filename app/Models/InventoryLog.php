<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'material_id',
        'user_id',
        'action',
        'previous_stock_quantity',
        'new_stock_quantity',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'previous_stock_quantity' => 'decimal:2',
            'new_stock_quantity' => 'decimal:2',
        ];
    }

    public function material(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
