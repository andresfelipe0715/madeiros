<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'stage_id',
        'sequence',
        'notes',
        'is_pending',
        'pending_reason',
        'pending_marked_by',
        'pending_marked_at',
        'started_at',
        'completed_at',
        'started_by',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'is_pending' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'pending_marked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function pendingMarkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pending_marked_by');
    }
}
