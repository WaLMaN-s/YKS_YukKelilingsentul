<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promo extends Model
{
    public const TYPES = ['percent' => 'Persen (%)', 'fixed' => 'Nominal (Rp)'];

    protected $fillable = [
        'code', 'type', 'value', 'min_transaction', 'max_discount', 'starts_at', 'ends_at', 'quota', 'used', 'is_active',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'is_active' => 'boolean'];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromoUsage::class);
    }

    /** @return array{0: bool, 1: string, 2: int} [valid, pesan, diskon] */
    public function check(int $subtotal): array
    {
        if (! $this->is_active) {
            return [false, 'Kode promo tidak aktif.', 0];
        }
        if ($this->starts_at && $this->starts_at->isFuture()) {
            return [false, 'Promo belum berlaku.', 0];
        }
        if ($this->ends_at && $this->ends_at->endOfDay()->isPast()) {
            return [false, 'Kode promo sudah kedaluwarsa.', 0];
        }
        if ($this->quota !== null && $this->used >= $this->quota) {
            return [false, 'Kuota promo sudah habis.', 0];
        }
        if ($subtotal < $this->min_transaction) {
            return [false, 'Minimal transaksi '.rupiah($this->min_transaction).' belum terpenuhi.', 0];
        }

        $discount = $this->type === 'percent'
            ? (int) floor($subtotal * $this->value / 100)
            : min($this->value, $subtotal);

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return [true, 'Promo '.$this->code.' berhasil dipakai.', $discount];
    }
}
