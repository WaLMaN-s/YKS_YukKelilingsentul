<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Models\RentalReturn;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RentalController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $rentals = Rental::with(['item.motorcycle', 'payment'])
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('invoice_no', 'like', '%'.$request->q.'%')->orWhere('renter_name', 'like', '%'.$request->q.'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('start_date', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('start_date', '<=', $request->sampai))
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin.rentals.index', ['rentals' => $rentals]);
    }

    public function show(Rental $rental)
    {
        $rental->load(['item.motorcycle.images', 'user.customer', 'payments.method', 'location', 'promo', 'returnRecord.processor', 'review']);

        return view('admin.rentals.show', ['rental' => $rental]);
    }

    public function edit(Rental $rental)
    {
        return view('admin.rentals.edit', ['rental' => $rental->load('item.motorcycle')]);
    }

    public function update(Request $request, Rental $rental): RedirectResponse
    {
        $rental->update($request->validate([
            'renter_name' => ['required', 'string', 'max:100'],
            'renter_phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{9,20}$/'],
            'renter_email' => ['required', 'email', 'max:150'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]));

        return redirect()->route('admin.penyewaan.show', $rental)->with('success', 'Data penyewaan diperbarui.');
    }

    public function confirm(Rental $rental): RedirectResponse
    {
        if (! $this->move($rental, ['pending', 'paid'], 'confirmed', ['confirmed_at' => now()])) {
            return $this->invalid($rental, 'Booking hanya bisa dikonfirmasi setelah pembayaran diverifikasi atau untuk pembayaran cash.');
        }
        $this->notifier->toUser($rental->user_id, 'Booking dikonfirmasi', 'Booking #'.$rental->invoice_no.' sudah dikonfirmasi.', route('dashboard.bookings.show', $rental->id));

        return back()->with('success', 'Booking dikonfirmasi.');
    }

    public function ready(Rental $rental): RedirectResponse
    {
        if (! $this->move($rental, ['confirmed'], 'pickup')) {
            return $this->invalid($rental, 'Hanya booking yang sudah dikonfirmasi yang bisa ditandai siap diambil.');
        }
        $this->notifier->toUser($rental->user_id, 'Motor siap diambil', 'Motor untuk #'.$rental->invoice_no.' sudah siap diambil.', route('dashboard.bookings.show', $rental->id));

        return back()->with('success', 'Ditandai siap diambil.');
    }

    public function pickedUp(Rental $rental): RedirectResponse
    {
        if (! $this->move($rental, ['confirmed', 'pickup'], 'rented', ['picked_up_at' => now()])) {
            return $this->invalid($rental, 'Booking belum dikonfirmasi.');
        }

        return back()->with('success', 'Motor ditandai sudah diambil customer.');
    }

    public function complete(Rental $rental): RedirectResponse
    {
        if (! $this->move($rental, ['returned'], 'completed', ['completed_at' => now()])) {
            return $this->invalid($rental, 'Proses pengembalian dulu sebelum menyelesaikan rental.');
        }
        $this->notifier->toUser($rental->user_id, 'Rental selesai', 'Rental #'.$rental->invoice_no.' selesai. Terima kasih! Silakan beri review.', route('dashboard.reviews'));

        return back()->with('success', 'Rental diselesaikan.');
    }

    public function cancel(Request $request, Rental $rental): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:300']]);

        if (! $this->move($rental, ['pending', 'waiting_payment', 'paid', 'confirmed', 'pickup'], 'cancelled', ['cancelled_reason' => $data['reason'] ?? 'Dibatalkan oleh admin'])) {
            return $this->invalid($rental, 'Penyewaan yang sedang berjalan atau sudah selesai tidak bisa dibatalkan.');
        }
        $this->notifier->toUser($rental->user_id, 'Booking dibatalkan', 'Booking #'.$rental->invoice_no.' dibatalkan. Alasan: '.($data['reason'] ?? 'Dibatalkan oleh admin'), route('dashboard.bookings.show', $rental->id));

        return back()->with('success', 'Booking dibatalkan.');
    }

    public function returnForm(Rental $rental)
    {
        abort_unless($rental->status === 'rented', 403, 'Pengembalian hanya untuk motor yang sedang disewa.');

        return view('admin.rentals.return', [
            'rental' => $rental->load('item.motorcycle'),
            'lateFeePerHour' => (int) setting('late_fee_per_hour', 20000),
            'dueAt' => $this->dueAt($rental),
        ]);
    }

    public function processReturn(Request $request, Rental $rental): RedirectResponse
    {
        abort_unless($rental->status === 'rented', 403, 'Pengembalian hanya untuk motor yang sedang disewa.');

        $data = $request->validate([
            'returned_at' => ['required', 'date'],
            'condition' => ['required', Rule::in(array_keys(RentalReturn::CONDITIONS))],
            'fuel_level' => ['required', Rule::in(array_keys(RentalReturn::FUEL))],
            'damage_description' => ['nullable', 'string', 'max:500'],
            'damage_fee' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $returnedAt = Carbon::parse($data['returned_at']);
        $due = $this->dueAt($rental);
        $lateHours = $returnedAt->gt($due) ? (int) ceil($due->diffInMinutes($returnedAt) / 60) : 0;
        $lateFee = $lateHours * (int) setting('late_fee_per_hour', 20000);
        $damageFee = (int) ($data['damage_fee'] ?? 0);

        DB::transaction(function () use ($rental, $data, $returnedAt, $lateHours, $lateFee, $damageFee) {
            RentalReturn::create([
                'rental_id' => $rental->id,
                'returned_at' => $returnedAt,
                'condition' => $data['condition'],
                'fuel_level' => $data['fuel_level'],
                'late_hours' => $lateHours,
                'late_fee' => $lateFee,
                'damage_description' => $data['damage_description'] ?? null,
                'damage_fee' => $damageFee,
                'notes' => $data['notes'] ?? null,
                'processed_by' => auth()->id(),
            ]);
            $rental->update(['status' => 'returned', 'returned_at' => $returnedAt, 'extra_fee' => $lateFee + $damageFee]);
        });

        $this->notifier->toUser($rental->user_id, 'Motor sudah dikembalikan', 'Pengembalian #'.$rental->invoice_no.' sudah dicatat.'.($lateFee + $damageFee > 0 ? ' Biaya tambahan: '.rupiah($lateFee + $damageFee).'.' : ''), route('dashboard.bookings.show', $rental->id));

        return redirect()->route('admin.penyewaan.show', $rental)->with('success', 'Pengembalian dicatat. Biaya tambahan: '.rupiah($lateFee + $damageFee).'.');
    }

    /** Batas waktu kembali: tanggal kembali + jam pengembalian dari pengaturan. */
    private function dueAt(Rental $rental): Carbon
    {
        return Carbon::parse($rental->end_date->toDateString().' '.setting('return_time', '17:00'));
    }

    /** @param list<string> $from */
    private function move(Rental $rental, array $from, string $to, array $extra = []): bool
    {
        if (! in_array($rental->status, $from, true)) {
            return false;
        }
        $rental->update(['status' => $to] + $extra);

        return true;
    }

    private function invalid(Rental $rental, string $message): RedirectResponse
    {
        return back()->with('error', $message.' (Status saat ini: '.$rental->status_label.')');
    }
}
