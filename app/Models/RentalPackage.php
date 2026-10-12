<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalPackage extends Model
{
    protected $fillable = ['vehicle_type', 'name', 'description', 'includes', 'price', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return ['includes' => 'array', 'is_active' => 'boolean'];
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('price');
    }
}
