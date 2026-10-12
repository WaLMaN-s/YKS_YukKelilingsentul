<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourSchedule extends Model
{
    protected $fillable = ['tour_package_id', 'date', 'quota', 'booked', 'notes'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class, 'tour_package_id');
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->quota - $this->booked);
    }
}
