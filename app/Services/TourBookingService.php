<?php

namespace App\Services;

use App\Models\Addon;
use App\Models\PaymentMethod;
use App\Models\TourBooking;
use App\Models\TourPackage;
use App\Models\TourSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TourBookingService
{
    public function __construct(private NotificationService $notifier, private BookingService $booking) {}

    /**
     * @param  list<int>  $addonIds
     * @return array{price_per_person: int, subtotal: int, addons: list<array<string, mixed>>, addons_total: int, total: int}
     */
    public function quote(TourPackage $package, int $people, array $addonIds = []): array
    {
        $people = max(1, $people);
        $lines = $addonIds === []
            ? []
            : Addon::for('tour')->whereIn('id', $addonIds)->get()->map(fn (Addon $a) => $a->snapshot($people))->values()->all();
        $addonsTotal = (int) collect($lines)->sum('subtotal');
        $subtotal = $package->price * $people;

        return [
            'price_per_person' => $package->price,
            'subtotal' => $subtotal,
            'addons' => $lines,
            'addons_total' => $addonsTotal,
            'total' => $subtotal + $addonsTotal,
        ];
    }

    /**
     * Open trip: pilih jadwal yang ada (kuota dikurangi). Private trip: pilih tanggal bebas, kapan saja.
     *
     * @param  array<string, mixed>  $data  trip_type, tour_schedule_id, trip_date, people, addons[], contact_name, contact_phone, notes
     */
    public function create(User $user, TourPackage $package, PaymentMethod $method, array $data): TourBooking
    {
        $people = (int) $data['people'];

        $booking = DB::transaction(function () use ($user, $package, $method, $data, $people) {
            $schedule = null;
            if ($data['trip_type'] === 'open') {
                $schedule = TourSchedule::whereKey($data['tour_schedule_id'] ?? 0)
                    ->where('tour_package_id', $package->id)->lockForUpdate()->first();
                if (! $schedule || $schedule->date->lt(today())) {
                    throw ValidationException::withMessages(['tour_schedule_id' => 'Jadwal open trip tidak ditemukan atau sudah lewat.']);
                }
                if ($schedule->remaining < $people) {
                    throw ValidationException::withMessages(['people' => 'Sisa kuota jadwal ini hanya '.$schedule->remaining.' orang. Kurangi peserta atau pilih Private Trip.']);
                }
                $schedule->increment('booked', $people);
                $date = $schedule->date;
            } else {
                $date = Carbon::parse($data['trip_date'])->startOfDay();
                if ($date->lte(today())) {
                    throw ValidationException::withMessages(['trip_date' => 'Tanggal private trip minimal besok.']);
                }
            }

            $q = $this->quote($package, $people, array_map('intval', $data['addons'] ?? []));

            return TourBooking::create([
                'invoice_no' => TourBooking::generateInvoiceNo(),
                'user_id' => $user->id,
                'tour_package_id' => $package->id,
                'tour_schedule_id' => $schedule?->id,
                'trip_type' => $data['trip_type'],
                'trip_date' => $date,
                'people' => $people,
                'price_per_person' => $q['price_per_person'],
                'subtotal' => $q['subtotal'],
                'addons' => $q['addons'] ?: null,
                'addons_total' => $q['addons_total'],
                'total' => $q['total'],
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'notes' => $data['notes'] ?? null,
                'status' => 'waiting_payment',
                'payment_method_id' => $method->id,
                'source' => $data['source'] ?? 'website',
            ]);
        });

        $this->notifier->toUser($user, 'Booking wisata berhasil', 'Booking #'.$booking->invoice_no.' ('.$package->name.') dibuat. Selesaikan pembayaran.', route('tour-booking.show', $booking->id));
        $this->notifier->toAdmins(['tour'], 'Booking wisata baru', '#'.$booking->invoice_no.' - '.$booking->contact_name.' ('.$booking->trip_label.', '.$people.' orang).', route('admin.booking-wisata.show', $booking->id));

        return $booking;
    }

    public function canReschedule(TourBooking $booking): bool
    {
        return $booking->isOpen()
            && today()->lte($booking->trip_date->copy()->subDays($this->booking->rescheduleDays()));
    }

    /** @param  array<string, mixed>  $data  tour_schedule_id (open trip) atau trip_date (private trip) */
    public function reschedule(TourBooking $booking, array $data): TourBooking
    {
        if (! $this->canReschedule($booking)) {
            throw ValidationException::withMessages(['trip_date' => 'Perubahan jadwal maksimal H-'.$this->booking->rescheduleDays().' sebelum tanggal trip. Hubungi admin via WhatsApp.']);
        }

        DB::transaction(function () use ($booking, $data) {
            if ($booking->trip_type === 'open') {
                $new = TourSchedule::whereKey($data['tour_schedule_id'] ?? 0)
                    ->where('tour_package_id', $booking->tour_package_id)->lockForUpdate()->first();
                if (! $new || $new->date->lte(today()) || $new->id === $booking->tour_schedule_id) {
                    throw ValidationException::withMessages(['tour_schedule_id' => 'Pilih jadwal lain yang masih akan datang.']);
                }
                if ($new->remaining < $booking->people) {
                    throw ValidationException::withMessages(['tour_schedule_id' => 'Sisa kuota jadwal itu hanya '.$new->remaining.' orang.']);
                }
                $this->releaseSeats($booking);
                $new->increment('booked', $booking->people);
                $booking->update(['tour_schedule_id' => $new->id, 'trip_date' => $new->date]);
            } else {
                $date = Carbon::parse($data['trip_date'] ?? null)->startOfDay();
                if ($date->lte(today())) {
                    throw ValidationException::withMessages(['trip_date' => 'Tanggal baru minimal besok.']);
                }
                $booking->update(['trip_date' => $date]);
            }
            $booking->increment('rescheduled_count');
        });

        $this->notifier->toAdmins(['tour'], 'Jadwal wisata diubah', '#'.$booking->invoice_no.' dipindah ke '.tgl($booking->fresh()->trip_date).'.', route('admin.booking-wisata.show', $booking->id));

        return $booking->fresh();
    }

    /** Pembatalan customer: pada hari H atau sesudahnya pembayaran hangus. */
    public function cancelByCustomer(TourBooking $booking): bool
    {
        if (! $booking->isOpen()) {
            abort(403, 'Booking ini tidak bisa dibatalkan sendiri. Hubungi admin.');
        }
        $forfeit = today()->gte($booking->trip_date);
        $paid = $booking->paid_at !== null;

        $this->cancel($booking, $forfeit ? 'Dibatalkan pada hari H — pembayaran hangus.' : 'Dibatalkan oleh customer', $forfeit);

        $note = match (true) {
            $paid && $forfeit => 'Pembayaran HANGUS (dibatalkan pada hari H).',
            $paid => 'Sudah dibayar — perlu diproses refund.',
            default => 'Belum ada pembayaran.',
        };
        $this->notifier->toAdmins(['tour', 'payment'], 'Booking wisata dibatalkan', '#'.$booking->invoice_no.' dibatalkan. '.$note, route('admin.booking-wisata.show', $booking->id));

        return $forfeit;
    }

    public function cancel(TourBooking $booking, string $reason, bool $forfeited = false): void
    {
        DB::transaction(function () use ($booking, $reason, $forfeited) {
            $this->releaseSeats($booking);
            $booking->update(['status' => 'cancelled', 'cancelled_reason' => $reason, 'cancelled_at' => now(), 'forfeited' => $forfeited]);
        });
    }

    private function releaseSeats(TourBooking $booking): void
    {
        if ($booking->trip_type === 'open' && $booking->tour_schedule_id) {
            $schedule = TourSchedule::whereKey($booking->tour_schedule_id)->lockForUpdate()->first();
            $schedule?->update(['booked' => max(0, $schedule->booked - $booking->people)]);
        }
    }
}
