<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    public const CATEGORIES = ['motor' => 'Motor', 'perjalanan' => 'Perjalanan', 'wisata' => 'Wisata', 'customer' => 'Customer'];

    protected $fillable = ['title', 'image', 'category', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getImageUrlAttribute(): string
    {
        return media_url($this->image);
    }
}
