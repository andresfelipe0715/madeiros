<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StageGroup extends Model
{
    use HasFactory;
    public $timestamps = false; // Disable timestamps as they are not in the schema

    protected $fillable = ['name', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function stages()
    {
        return $this->hasMany(Stage::class);
    }
}
