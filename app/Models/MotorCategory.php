<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MotorCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

    public function motorcycles(): HasMany
    {
        return $this->hasMany(Motorcycle::class);
    }
}
