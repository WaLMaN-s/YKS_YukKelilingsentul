<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Rental extends Model
{
    public const STATUSES = [
        'pending' => 'Menunggu Konfirmasi',
        'waiting_payment' => 'Menunggu Pembayaran',
        'paid' => 'Dibayar',
        'confirmed' => 'Dikonfirmasi',
        'pickup' => 'Siap Diambil',
        'rented' => 'Sedang Disewa',
        'returned' => 'Dikembalikan',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const COLORS = [
        'pending' => 'bg-amber-400/15 text-amber-300 ring-amber-400/30',
        'waiting_payment' => 'bg-orange-400/15 text-orange-300 ring-orange-400/30',
        'paid' => 'bg-sky-400/15 text-sky-300 ring-sky-400/30',
        'confirmed' => 'bg-emerald-400/15 text-emerald-300 ring-emerald-400/30',
        'pickup' => 'bg-teal-400/15 text-teal-300 ring-teal-400/30',
        'rented' => 'bg-blue-400/15 text-blue-300 ring-blue-400/30',
        'returned' => 'bg-violet-400/15 text-violet-300 ring-violet-400/30',
        'completed' => 'bg-green-500/15 text-green-300 ring-green-400/30',
        'cancelled' => 'bg-rose-500/15 text-rose-300 ring-rose-400/30',
    ];

    /** Status yang mengunci tanggal motor (mencegah double booking). */
    public const ACTIVE = ['pending', 'waiting_payment', 'paid', 'confirmed', 'pickup', 'rented'];

    protected $fillable = [
        'invoice_no', 'user_id', 'location_id', 'promo_id', 'renter_name', 'renter_phone', 'renter_email',
        'start_date', 'end_date', 'days', 'subtotal', 'delivery_fee', 'extra_fee', 'deposit', 'discount', 'total',
        'status', 'notes', 'cancelled_reason', 'confirmed_at', 'picked_up_at', 'returned_at', 'completed_at',
        'package_id', 'quantity', 'people', 'addons', 'addons_total', 'source', 'cancelled_at', 'forfeited', 'rescheduled_count',
    ];

    public const SOURCES = ['website' => 'Website', 'whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'walk_in' => 'Datang langsung'];

    /** Status yang masih boleh dijadwal ulang / dibatalkan customer (sebelum motor diambil). */
    public const CHANGEABLE = ['pending', 'waiting_payment', 'paid', 'confirmed', 'pickup'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'confirmed_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'addons' => 'array',
            'forfeited' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(RentalPackage::class, 'package_id');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    public function item(): HasOne
    {
        return $this->hasOne(RentalItem::class);
    }

    public function returnRecord(): HasOne
    {
        return $this->hasOne(RentalReturn::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function getMotorcycleAttribute(): ?Motorcycle
    {
        return $this->item?->motorcycle;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::COLORS[$this->status] ?? 'bg-slate-500/15 text-slate-300 ring-slate-400/30';
    }

    /** Sisa deposit yang dikembalikan setelah dipotong biaya tambahan. */
    public function getDepositRefundAttribute(): int
    {
        return max(0, $this->deposit - $this->extra_fee);
    }

    /** Kekurangan bayar jika biaya tambahan melebihi deposit. */
    public function getAdditionalDueAttribute(): int
    {
        return max(0, $this->extra_fee - $this->deposit);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return in_array($this->status, self::CHANGEABLE, true);
    }

    /** Membatalkan pada hari H (atau sesudahnya) = pembayaran hangus. */
    public function wouldForfeitIfCancelled(): bool
    {
        return today()->gte($this->start_date);
    }

    public static function generateInvoiceNo(): string
    {
        $prefix = 'INV-'.now()->format('Ymd').'-';
        $n = static::whereDate('created_at', today())->count() + 1;
        do {
            $number = $prefix.str_pad((string) $n++, 3, '0', STR_PAD_LEFT);
        } while (static::where('invoice_no', $number)->exists());

        return $number;
    }

    public function whatsappMessage(): string
    {
        return "Halo Admin,\n\nSaya ingin menyewa:\n\nMotor:\n".($this->motorcycle?->name ?? '-')
            ."\n\nTanggal:\n".tgl($this->start_date).' - '.tgl($this->end_date)
            ."\n\nBooking:\n#".$this->invoice_no;
    }
}
