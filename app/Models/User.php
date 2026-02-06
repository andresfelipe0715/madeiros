<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function deliveredOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivered_by');
    }

    public function startedOrderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class, 'started_by');
    }

    public function completedOrderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class, 'completed_by');
    }

    public function uploadedOrderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class, 'uploaded_by');
    }

    public function orderLogs(): HasMany
    {
        return $this->hasMany(OrderLog::class);
    }
}
