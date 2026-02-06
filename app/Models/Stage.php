<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    public $timestamps = false;

    public function orderStages(): HasMany
    {
        return $this->hasMany(OrderStage::class);
    }
}
