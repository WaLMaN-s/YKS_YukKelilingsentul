<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBooking extends Model
{
    public const STATUSES = [
        'waiting_payment' => 'Menunggu Pembayaran',
        'waiting_verification' => 'Menunggu Verifikasi',
        'confirmed' => 'Dikonfirmasi',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const COLORS = [
        'waiting_payment' => 'bg-orange-400/15 text-orange-300 ring-orange-400/30',
        'waiting_verification' => 'bg-sky-400/15 text-sky-300 ring-sky-400/30',
        'confirmed' => 'bg-emerald-400/15 text-emerald-300 ring-emerald-400/30',
        'completed' => 'bg-green-500/15 text-green-300 ring-green-400/30',
        'cancelled' => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
    ];

    /** Status yang masih bisa diubah/dibatalkan customer. */
    public const OPEN = ['waiting_payment', 'waiting_verification', 'confirmed'];

    protected $fillable = [
        'invoice_no', 'user_id', 'tour_package_id', 'tour_schedule_id', 'trip_type', 'trip_date', 'people',
        'price_per_person', 'subtotal', 'addons', 'addons_total', 'total', 'contact_name', 'contact_phone', 'notes',
        'status', 'payment_method_id', 'proof_path', 'payment_note', 'paid_at', 'verified_by', 'cancelled_reason',
        'cancelled_at', 'forfeited', 'rescheduled_count', 'source',
    ];

    protected function casts(): array
    {
        return [
            'trip_date' => 'date',
            'addons' => 'array',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'forfeited' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(TourPackage::class, 'tour_package_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(TourSchedule::class, 'tour_schedule_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300 ring-slate-400/30';
    }

    public function getTripLabelAttribute(): string
    {
        return $this->trip_type === 'private' ? 'Private Trip' : 'Open Trip';
    }

    public function canUploadProof(): bool
    {
        return in_array($this->status, ['waiting_payment', 'waiting_verification'], true) && $this->method?->type !== 'cash';
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public static function generateInvoiceNo(): string
    {
        $prefix = 'TRP-'.now()->format('Ymd').'-';
        $n = static::whereDate('created_at', today())->count() + 1;
        do {
            $number = $prefix.str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
        } while (static::where('invoice_no', $number)->exists());

        return $number;
    }

    public function whatsappMessage(): string
    {
        return "Halo Admin,\n\nSaya ingin menanyakan pesanan wisata:\n\nPaket:\n".($this->package?->name ?? '-')
            ."\n\nTanggal:\n".tgl($this->trip_date).' ('.$this->trip_label.", {$this->people} orang)"
            ."\n\nBooking:\n#".$this->invoice_no;
    }
}
