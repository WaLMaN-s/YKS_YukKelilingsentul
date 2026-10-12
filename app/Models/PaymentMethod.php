<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    public const TYPES = ['bank' => 'Transfer Bank', 'qris' => 'QRIS', 'cash' => 'Cash'];

    protected $fillable = ['name', 'type', 'account_name', 'account_number', 'instructions', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
