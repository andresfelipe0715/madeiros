<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FileType extends Model
{
    public $timestamps = false;

    public function orderFiles(): HasMany
    {
        return $this->hasMany(OrderFile::class);
    }
}
