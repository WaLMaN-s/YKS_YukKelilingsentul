<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\Location;
use App\Models\Motorcycle;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Promo;
use App\Models\PromoUsage;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalPackage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public const MAX_QTY = 10;

    public const MAX_DAYS = 90;

    public function __construct(private NotificationService $notifier) {}

    public function minDays(): int
    {
        return max(1, (int) setting('min_rental_days', 1));
    }

    /** Perubahan jadwal paling lambat H-n (default H-2). */
    public function rescheduleDays(): int
    {
        return max(0, (int) setting('reschedule_min_days', 2));
    }

    public function daysBetween(Carbon $start, Carbon $end): int
    {
        return max(1, (int) $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()));
    }

    /** Jeep = satu perjalanan sehari, jadi tanggal kembali selalu H+1. */
    public function normalizeEnd(Motorcycle $motor, Carbon $start, Carbon $end): Carbon
    {
        return $motor->isJeep() ? $start->copy()->startOfDay()->addDay() : $end->copy()->startOfDay();
    }

    /** Harga paket terbaik: kombinasi bulanan + mingguan + harian, tidak pernah lebih mahal dari harian. */
    public function priceFor(Motorcycle $motor, int $days): int
    {
        $plain = $motor->price_daily * $days;
        $total = 0;
        $rest = $days;

        if ($motor->price_monthly && $rest >= 30) {
            $n = intdiv($rest, 30);
            $total += $n * $motor->price_monthly;
            $rest -= $n * 30;
        }
        if ($motor->price_weekly && $rest >= 7) {
            $n = intdiv($rest, 7);
            $total += $n * $motor->price_weekly;
            $rest -= $n * 7;
        }
        $total += $rest * $motor->price_daily;

        return min($plain, $total);
    }

    /**
     * Sisa unit pada rentang [start, end). Dihitung per hari (puncak pemakaian), bukan sekadar jumlah booking
     * yang beririsan, supaya unit yang sama bisa dipakai bergantian.
     */
    public function unitsLeft(Motorcycle $motor, Carbon $start, Carbon $end, ?int $ignoreRentalId = null): int
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();
        if ($end->lte($start) || $start->diffInDays($end) > self::MAX_DAYS + 30) {
            return 0;
        }

        $items = RentalItem::query()
            ->where('motorcycle_id', $motor->id)
            ->whereHas('rental', function ($q) use ($start, $end, $ignoreRentalId) {
                $q->whereIn('status', Rental::ACTIVE)
                    ->where('start_date', '<', $end->toDateString())
                    ->where('end_date', '>', $start->toDateString());
                if ($ignoreRentalId) {
                    $q->where('id', '!=', $ignoreRentalId);
                }
            })
            ->with('rental:id,start_date,end_date')
            ->get();

        $peak = 0;
        for ($d = $start->copy(); $d->lt($end); $d->addDay()) {
            $iso = $d->toDateString();
            $used = $items->filter(fn ($i) => $i->rental->start_date->toDateString() <= $iso && $i->rental->end_date->toDateString() > $iso)->sum('quantity');
            $peak = max($peak, (int) $used);
        }

        return max(0, $motor->stock - $peak);
    }

    /** Tanggal (ISO) yang unitnya sudah habis, untuk kalender ketersediaan. @return list<string> */
    public function fullDays(Motorcycle $motor, int $horizon = 120): array
    {
        $from = today();
        $to = today()->addDays($horizon);
        $items = RentalItem::query()
            ->where('motorcycle_id', $motor->id)
            ->whereHas('rental', fn ($q) => $q->whereIn('status', Rental::ACTIVE)
                ->where('end_date', '>', $from->toDateString())->where('start_date', '<', $to->toDateString()))
            ->with('rental:id,start_date,end_date')->get();

        $full = [];
        for ($d = $from->copy(); $d->lt($to); $d->addDay()) {
            $iso = $d->toDateString();
            $used = $items->filter(fn ($i) => $i->rental->start_date->toDateString() <= $iso && $i->rental->end_date->toDateString() > $iso)->sum('quantity');
            if ($used >= $motor->stock) {
                $full[] = $iso;
            }
        }

        return $full;
    }

    /** Tidak ada satu pun unit tersisa pada rentang tanggal tersebut. */
    public function hasOverlap(Motorcycle $motor, Carbon $start, Carbon $end, ?int $ignoreRentalId = null): bool
    {
        return $this->unitsLeft($motor, $start, $end, $ignoreRentalId) < 1;
    }

    public function isAvailable(Motorcycle $motor, Carbon $start, Carbon $end, int $quantity = 1): bool
    {
        return $motor->status === 'available' && $this->unitsLeft($motor, $start, $end) >= $quantity;
    }

    /**
     * Hitung rincian harga. Tidak menyimpan apa pun.
     *
     * @param  list<int>  $addonIds
     * @return array<string, mixed>
     */
    public function quote(
        Motorcycle $motor,
        Carbon $start,
        Carbon $end,
        ?string $promoCode = null,
        ?int $locationId = null,
        int $quantity = 1,
        ?int $packageId = null,
        array $addonIds = [],
        ?int $people = null,
        ?int $ignoreRentalId = null,
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $this->normalizeEnd($motor, $start, $end);
        $days = $this->daysBetween($start, $end);
        $quantity = max(1, $quantity);
        $errors = [];

        // Harga satuan
        $package = null;
        if ($motor->isJeep()) {
            $package = $packageId ? RentalPackage::active()->where('vehicle_type', 'jeep')->find($packageId) : null;
            if (! $package) {
                $errors[] = 'Pilih paket jeep terlebih dahulu.';
            }
            $unitPrice = (int) ($package?->price ?? 0);
            $people = $people ?: $motor->capacity * $quantity;
            if ($people > $motor->capacity * $quantity) {
                $errors[] = 'Maksimal '.$motor->capacity.' orang per jeep (untuk '.$quantity.' jeep: '.($motor->capacity * $quantity).' orang).';
            }
        } else {
            $unitPrice = $this->priceFor($motor, $days);
            $people = $quantity; // satu pengendara per unit
        }
        $subtotal = $unitPrice * $quantity;

        // Add-on
        $addons = $addonIds === [] ? collect() : Addon::for('rental')->whereIn('id', $addonIds)->get();
        $addonLines = $addons->map(fn (Addon $a) => $a->snapshot($people))->values()->all();
        $addonsTotal = (int) collect($addonLines)->sum('subtotal');

        $delivery = $locationId ? (int) Location::where('id', $locationId)->where('is_active', true)->value('delivery_fee') : 0;
        $perUnitDeposit = $motor->isJeep() ? $motor->deposit : ($motor->deposit ?: (int) setting('default_deposit', 0));
        $deposit = (int) ($perUnitDeposit * $quantity);

        // Promo
        $promo = null;
        $discount = 0;
        $promoOk = null;
        $promoMessage = null;
        if (filled($promoCode)) {
            $promo = Promo::where('code', strtoupper(trim($promoCode)))->first();
            if (! $promo) {
                [$promoOk, $promoMessage] = [false, 'Kode promo tidak ditemukan.'];
            } else {
                [$promoOk, $promoMessage, $discount] = $promo->check($subtotal);
                if (! $promoOk) {
                    $promo = null;
                }
            }
        }

        // Validasi
        if ($end->lte($start)) {
            $errors[] = 'Tanggal kembali harus setelah tanggal mulai.';
        }
        if (! $motor->isJeep() && $days < $this->minDays()) {
            $errors[] = 'Minimal durasi sewa adalah '.$this->minDays().' hari.';
        }
        if ($days > self::MAX_DAYS) {
            $errors[] = 'Maksimal durasi sewa '.self::MAX_DAYS.' hari. Hubungi admin untuk sewa jangka panjang.';
        }
        if ($quantity > self::MAX_QTY) {
            $errors[] = 'Maksimal '.self::MAX_QTY.' unit per booking. Hubungi admin untuk jumlah lebih banyak.';
        }
        $left = 0;
        if ($motor->status !== 'available') {
            $errors[] = 'Kendaraan sedang tidak tersedia untuk disewa.';
        } elseif ($end->gt($start)) {
            $left = $this->unitsLeft($motor, $start, $end, $ignoreRentalId);
            if ($left < 1) {
                $errors[] = 'Semua unit sudah dibooking pada tanggal tersebut. Pilih tanggal lain.';
            } elseif ($left < $quantity) {
                $errors[] = 'Hanya tersisa '.$left.' unit pada tanggal tersebut.';
            }
        }

        return [
            'end_date' => $end->toDateString(),
            'days' => $days,
            'quantity' => $quantity,
            'people' => $people,
            'package' => $package,
            'price_per_day' => $unitPrice,
            'subtotal' => $subtotal,
            'addons' => $addonLines,
            'addons_total' => $addonsTotal,
            'delivery_fee' => $delivery,
            'extra_fee' => 0,
            'deposit' => $deposit,
            'discount' => $discount,
            'total' => max(0, $subtotal + $addonsTotal + $delivery + $deposit - $discount),
            'promo' => $promo,
            'promo_ok' => $promoOk,
            'promo_message' => $promoMessage,
            'units_left' => $left,
            'available' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * Buat booking di dalam transaksi + kunci baris kendaraan untuk mencegah overbooking.
     *
     * @param  array<string, mixed>  $data  start_date, end_date, quantity, package_id, addons[], people, location_id,
     *                                      promo_code, renter_*, notes, source
     */
    public function create(User $user, Motorcycle $motor, PaymentMethod $method, array $data): Rental
    {
        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = $this->normalizeEnd($motor, $start, Carbon::parse($data['end_date']));

        $rental = DB::transaction(function () use ($user, $motor, $method, $data, $start, $end) {
            $locked = Motorcycle::whereKey($motor->id)->lockForUpdate()->firstOrFail();
            $quote = $this->quote(
                $locked, $start, $end, $data['promo_code'] ?? null, $data['location_id'] ?? null,
                (int) ($data['quantity'] ?? 1), $data['package_id'] ?? null, array_map('intval', $data['addons'] ?? []),
                isset($data['people']) ? (int) $data['people'] : null,
            );

            if (! $quote['available']) {
                throw ValidationException::withMessages(['start_date' => $quote['errors'][0]]);
            }
            if (filled($data['promo_code'] ?? null) && $quote['promo_ok'] === false) {
                throw ValidationException::withMessages(['promo_code' => $quote['promo_message']]);
            }

            $rental = Rental::create([
                'invoice_no' => Rental::generateInvoiceNo(),
                'user_id' => $user->id,
                'location_id' => $data['location_id'] ?? null,
                'promo_id' => $quote['promo']?->id,
                'package_id' => $quote['package']?->id,
                'renter_name' => $data['renter_name'],
                'renter_phone' => $data['renter_phone'],
                'renter_email' => $data['renter_email'],
                'start_date' => $start,
                'end_date' => $end,
                'days' => $quote['days'],
                'quantity' => $quote['quantity'],
                'people' => $quote['people'],
                'subtotal' => $quote['subtotal'],
                'addons' => $quote['addons'] ?: null,
                'addons_total' => $quote['addons_total'],
                'delivery_fee' => $quote['delivery_fee'],
                'deposit' => $quote['deposit'],
                'discount' => $quote['discount'],
                'total' => $quote['total'],
                'status' => $method->type === 'cash' ? 'pending' : 'waiting_payment',
                'source' => $data['source'] ?? 'website',
                'notes' => $data['notes'] ?? null,
            ]);

            $rental->items()->create([
                'motorcycle_id' => $locked->id,
                'quantity' => $quote['quantity'],
                'price_per_day' => $quote['price_per_day'],
                'days' => $quote['days'],
                'subtotal' => $quote['subtotal'],
            ]);

            Payment::create([
                'rental_id' => $rental->id,
                'payment_method_id' => $method->id,
                'amount' => $quote['total'],
                'status' => 'pending',
            ]);

            if ($quote['promo']) {
                PromoUsage::create([
                    'promo_id' => $quote['promo']->id,
                    'user_id' => $user->id,
                    'rental_id' => $rental->id,
                    'discount' => $quote['discount'],
                ]);
                $quote['promo']->increment('used');
            }

            return $rental;
        });

        $this->notifier->toUser($user, 'Booking berhasil', 'Booking #'.$rental->invoice_no.' untuk '.$motor->name.' sudah dibuat.', route('dashboard.bookings.show', $rental->id));
        $this->notifier->toAdmins(['rental'], 'Booking baru', '#'.$rental->invoice_no.' - '.$rental->renter_name.' menyewa '.$motor->name.'.', route('admin.penyewaan.show', $rental->id));

        return $rental;
    }

    // ---------------- Kebijakan jadwal & pembatalan ----------------

    /** Jadwal boleh diubah paling lambat H-2 (atur di Pengaturan) dan sebelum kendaraan diambil. */
    public function canReschedule(Rental $rental): bool
    {
        return in_array($rental->status, Rental::CHANGEABLE, true)
            && today()->lte($rental->start_date->copy()->subDays($this->rescheduleDays()));
    }

    /** Ubah tanggal mulai dengan durasi tetap, sehingga harga tidak berubah. */
    public function reschedule(Rental $rental, Carbon $newStart): Rental
    {
        if (! $this->canReschedule($rental)) {
            throw ValidationException::withMessages([
                'start_date' => 'Perubahan jadwal maksimal H-'.$this->rescheduleDays().' sebelum tanggal sewa. Hubungi admin via WhatsApp.',
            ]);
        }
        $newStart = $newStart->copy()->startOfDay();
        if ($newStart->lte(today())) {
            throw ValidationException::withMessages(['start_date' => 'Tanggal baru harus setelah hari ini.']);
        }
        $newEnd = $newStart->copy()->addDays($rental->days);

        DB::transaction(function () use ($rental, $newStart, $newEnd) {
            $item = $rental->items()->firstOrFail();
            $motor = Motorcycle::whereKey($item->motorcycle_id)->lockForUpdate()->firstOrFail();

            if ($motor->status !== 'available' || $this->unitsLeft($motor, $newStart, $newEnd, $rental->id) < $item->quantity) {
                throw ValidationException::withMessages(['start_date' => 'Unit tidak tersedia pada tanggal baru. Pilih tanggal lain.']);
            }
            $rental->update([
                'start_date' => $newStart,
                'end_date' => $newEnd,
                'rescheduled_count' => $rental->rescheduled_count + 1,
            ]);
        });

        $this->notifier->toAdmins(['rental', 'pickup'], 'Jadwal diubah', '#'.$rental->invoice_no.' dipindah ke '.tgl($newStart).'.', route('admin.penyewaan.show', $rental->id));

        return $rental->fresh();
    }

    /** Pembatalan oleh customer. Pada hari H atau sesudahnya, pembayaran hangus. */
    public function cancelByCustomer(Rental $rental): bool
    {
        if (! $rental->canBeCancelledByCustomer()) {
            abort(403, 'Booking ini tidak bisa dibatalkan sendiri. Hubungi admin.');
        }
        $forfeit = $rental->wouldForfeitIfCancelled();
        $paid = $rental->payments()->where('status', 'paid')->exists();

        $rental->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'forfeited' => $forfeit,
            'cancelled_reason' => $forfeit ? 'Dibatalkan pada hari H — pembayaran hangus.' : 'Dibatalkan oleh customer',
        ]);

        $note = match (true) {
            $paid && $forfeit => 'Pembayaran HANGUS (dibatalkan pada hari H).',
            $paid => 'Sudah dibayar — perlu diproses refund.',
            default => 'Belum ada pembayaran.',
        };
        $this->notifier->toAdmins(['rental', 'payment'], 'Booking dibatalkan', '#'.$rental->invoice_no.' dibatalkan oleh '.$rental->renter_name.'. '.$note, route('admin.penyewaan.show', $rental->id));

        return $forfeit;
    }
}
