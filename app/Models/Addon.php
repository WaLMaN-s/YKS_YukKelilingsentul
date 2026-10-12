<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    public const TARGETS = ['rental' => 'Sewa kendaraan', 'tour' => 'Paket wisata', 'both' => 'Keduanya'];

    public const PER = ['person' => 'Per orang', 'booking' => 'Per booking'];

    protected $fillable = ['name', 'description', 'price', 'applies_to', 'per', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Add-on aktif yang berlaku untuk 'rental' atau 'tour'. */
    public function scopeFor($query, string $target)
    {
        return $query->where('is_active', true)->whereIn('applies_to', [$target, 'both'])->orderBy('id');
    }

    public function amountFor(int $people): int
    {
        return $this->per === 'person' ? $this->price * max(1, $people) : $this->price;
    }

    /** @return array{id: int, name: string, price: int, per: string, subtotal: int} */
    public function snapshot(int $people): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'price' => $this->price, 'per' => $this->per, 'subtotal' => $this->amountFor($people)];
    }
}
