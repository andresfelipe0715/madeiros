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
        'lleva_herrajeria',
        'lleva_manual_armado',
        'invoice_number',
        'notes',
        'created_by',
        'delivered_by',
        'delivered_at',
        'herrajeria_delivered_at',
        'herrajeria_delivered_by',
        'manual_armado_delivered_at',
        'manual_armado_delivered_by',
    ];

    public function getCreatorNameAttribute(): string
    {
        return $this->createdBy?->name ?? 'Sistema';
    }

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'herrajeria_delivered_at' => 'datetime',
            'manual_armado_delivered_at' => 'datetime',
            'lleva_herrajeria' => 'boolean',
            'lleva_manual_armado' => 'boolean',
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

    public function herrajeriaDeliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'herrajeria_delivered_by');
    }

    public function manualArmadoDeliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_armado_delivered_by');
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

        // Logic for persistent delivery stage when extras are pending
        $entregaStage = $this->orderStages->first(fn($os) => $os->stage->is_delivery_stage);
        if (
            $entregaStage && $entregaStage->completed_at && (
                ($this->lleva_herrajeria && !$this->herrajeria_delivered_at) ||
                ($this->lleva_manual_armado && !$this->manual_armado_delivered_at)
            )
        ) {
            return $entregaStage->stage->name;
        }

        return 'Entregada';
    }
}
