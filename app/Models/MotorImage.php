<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MotorImage extends Model
{
    protected $fillable = ['motorcycle_id', 'path', 'is_primary', 'sort_order'];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function motorcycle(): BelongsTo
    {
        return $this->belongsTo(Motorcycle::class);
    }

    public function getUrlAttribute(): string
    {
        return media_url($this->path);
    }
}
