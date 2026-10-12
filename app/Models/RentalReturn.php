<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalReturn extends Model
{
    public const CONDITIONS = ['baik' => 'Baik', 'ringan' => 'Lecet / rusak ringan', 'berat' => 'Rusak berat'];

    public const FUEL = ['full' => 'Penuh', '3/4' => '3/4', '1/2' => '1/2', '1/4' => '1/4', 'kosong' => 'Kosong'];

    protected $fillable = [
        'rental_id', 'returned_at', 'condition', 'fuel_level', 'late_hours', 'late_fee',
        'damage_description', 'damage_fee', 'notes', 'processed_by',
    ];

    protected function casts(): array
    {
        return ['returned_at' => 'datetime'];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getTotalExtraAttribute(): int
    {
        return $this->late_fee + $this->damage_fee;
    }
}
