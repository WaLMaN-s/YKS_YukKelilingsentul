<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const STATUSES = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];

    protected $fillable = ['rental_id', 'user_id', 'motorcycle_id', 'rating', 'comment', 'status'];

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
