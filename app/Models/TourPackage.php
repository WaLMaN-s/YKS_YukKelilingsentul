<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourPackage extends Model
{
    protected $fillable = [
        'name', 'slug', 'price', 'duration', 'destination', 'description', 'facilities', 'excludes', 'image', 'quota', 'min_participants', 'is_active', 'own_motor',
    ];

    protected function casts(): array
    {
        return ['facilities' => 'array', 'excludes' => 'array', 'is_active' => 'boolean', 'own_motor' => 'boolean'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TourSchedule::class)->orderBy('date');
    }

    public function getImageUrlAttribute(): string
    {
        return media_url($this->image, 'images/placeholder.svg');
    }
}
