<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'user_id',
        'action',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * Parse a remit action string into structured data.
     * remit|from:{from_id}|to:{to_id}|reason:{text}
     */
    public function getRemitDataAttribute(): ?array
    {
        if (!str_starts_with($this->action, 'remit|')) {
            return null;
        }

        $parts = explode('|', $this->action);
        $data = [];

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$key, $value] = explode(':', $part, 2);
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
