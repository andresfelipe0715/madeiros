<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'material',
        'invoice_number',
        'notes',
        'created_by',
        'delivered_by',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function orderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class);
    }

    public function orderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }

    public function trackingLinks(): HasMany
    {
        return $this->hasMany(OrderTrackingLink::class);
    }

    public function currentStageName(): string
    {
        if ($this->delivered_at) {
            return 'Entregada';
        }

        if ($this->orderStages->isEmpty()) {
            return 'Sin etapa';
        }

        $currentStage = $this->orderStages
            ->whereNull('completed_at')
            ->sortBy('sequence')
            ->first();

        if ($currentStage) {
            return $currentStage->stage->name;
        }

        return 'Entregada';
    }
}
