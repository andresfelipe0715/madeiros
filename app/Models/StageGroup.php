<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageGroup extends Model
{
    public $timestamps = false; // Disable timestamps as they are not in the schema

    protected $fillable = ['name'];

    public function stages()
    {
        return $this->hasMany(Stage::class);
    }
}
