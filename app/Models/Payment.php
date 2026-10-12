<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUSES = [
        'pending' => 'Pending',
        'waiting_verification' => 'Menunggu Verifikasi',
        'paid' => 'Dibayar',
        'rejected' => 'Ditolak',
        'refund' => 'Refund',
    ];

    public const COLORS = [
        'pending' => 'bg-amber-400/15 text-amber-300 ring-amber-400/30',
        'waiting_verification' => 'bg-sky-400/15 text-sky-300 ring-sky-400/30',
        'paid' => 'bg-emerald-400/15 text-emerald-300 ring-emerald-400/30',
        'rejected' => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
        'refund' => 'bg-violet-400/15 text-violet-300 ring-violet-400/30',
    ];

    protected $fillable = [
        'rental_id', 'payment_method_id', 'amount', 'proof_path', 'status', 'paid_at', 'verified_by', 'verified_at', 'note',
    ];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'verified_at' => 'datetime'];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300 ring-slate-400/30';
    }

    public function canUploadProof(): bool
    {
        return in_array($this->status, ['pending', 'rejected'], true)
            && $this->method?->type !== 'cash';
    }
}
