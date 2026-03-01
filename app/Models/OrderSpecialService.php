<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSpecialService extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'service_id',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function specialService(): BelongsTo
    {
        return $this->belongsTo(SpecialService::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }
}
