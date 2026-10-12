<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalItem extends Model
{
    protected $fillable = ['rental_id', 'motorcycle_id', 'quantity', 'price_per_day', 'days', 'subtotal'];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }
}
