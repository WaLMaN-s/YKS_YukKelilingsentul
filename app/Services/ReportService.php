<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Rental;
use App\Models\RentalItem;
use App\Models\RentalReturn;
use App\Models\TourBooking;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReportService
{
    public const TYPES = [
        'pendapatan' => 'Pendapatan',
        'rental' => 'Rental',
        'motor' => 'Motor',
        'customer' => 'Customer',
        'pembayaran' => 'Pembayaran',
        'denda' => 'Denda',
    ];

    public const RANGES = [
        'today' => 'Hari ini',
        'week' => 'Minggu ini',
        'month' => 'Bulan ini',
        'year' => 'Tahun ini',
        'custom' => 'Custom',
    ];

    /** @return array{0: Carbon, 1: Carbon} */
    public function range(string $key, ?string $from = null, ?string $to = null): array
    {
        return match ($key) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::parse($from ?: now()->startOfMonth())->startOfDay(),
                Carbon::parse($to ?: now())->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    /** @return array{headers: list<string>, rows: list<list<mixed>>} */
    public function build(string $type, Carbon $from, Carbon $to): array
    {
        return match ($type) {
            'rental' => $this->rentals($from, $to),
            'motor' => $this->motors($from, $to),
            'customer' => $this->customers($from, $to),
            'pembayaran' => $this->payments($from, $to),
            'denda' => $this->fines($from, $to),
            default => $this->revenue($from, $to),
        };
    }

    /** @return array<string, int> */
    public function summary(Carbon $from, Carbon $to): array
    {
        return [
            'revenue' => (int) Payment::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('amount')
                + (int) TourBooking::whereNotNull('paid_at')->where('status', '!=', 'cancelled')->whereBetween('paid_at', [$from, $to])->sum('total'),
            'rentals' => Rental::whereBetween('created_at', [$from, $to])->count(),
            'fines' => (int) RentalReturn::whereBetween('returned_at', [$from, $to])->get()->sum(fn (RentalReturn $r) => $r->total_extra),
            'customers' => User::customers()->whereBetween('created_at', [$from, $to])->count(),
        ];
    }

    private function revenue(Carbon $from, Carbon $to): array
    {
        $rows = Payment::with(['rental', 'method'])
            ->where('status', 'paid')->whereBetween('paid_at', [$from, $to])->orderBy('paid_at')->get()
            ->map(fn (Payment $p) => [
                $p->rental?->invoice_no, $p->rental?->renter_name, $p->method?->name, $p->amount, tgl($p->paid_at, 'd/m/Y H:i'),
            ])->all();

        $tourRows = TourBooking::with('method')->whereNotNull('paid_at')->where('status', '!=', 'cancelled')
            ->whereBetween('paid_at', [$from, $to])->orderBy('paid_at')->get()
            ->map(fn (TourBooking $t) => [$t->invoice_no, $t->contact_name, $t->method?->name, $t->total, tgl($t->paid_at, 'd/m/Y H:i')])->all();

        return ['headers' => ['Invoice', 'Customer', 'Metode', 'Jumlah (Rp)', 'Tanggal Bayar'], 'rows' => array_merge($rows, $tourRows)];
    }

    private function rentals(Carbon $from, Carbon $to): array
    {
        $rows = Rental::with('item.motorcycle')->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn (Rental $r) => [
                $r->invoice_no, $r->renter_name, $r->motorcycle?->name, tgl($r->start_date, 'd/m/Y'), tgl($r->end_date, 'd/m/Y'),
                $r->days, $r->total, $r->status_label,
            ])->all();

        return ['headers' => ['Invoice', 'Customer', 'Motor', 'Mulai', 'Kembali', 'Durasi (hari)', 'Total (Rp)', 'Status'], 'rows' => $rows];
    }

    private function motors(Carbon $from, Carbon $to): array
    {
        $rows = RentalItem::with('motorcycle')
            ->whereHas('rental', fn ($q) => $q->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled'))
            ->get()->groupBy('motorcycle_id')
            ->map(fn ($items) => [
                $items->first()->motorcycle?->name, $items->first()->motorcycle?->plate_number,
                $items->count(), $items->sum('days'), $items->sum('subtotal'),
            ])->sortByDesc(fn ($r) => $r[2])->values()->all();

        return ['headers' => ['Motor', 'Plat Nomor', 'Jumlah Rental', 'Total Hari', 'Pendapatan Sewa (Rp)'], 'rows' => $rows];
    }

    private function customers(Carbon $from, Carbon $to): array
    {
        $rows = User::customers()
            ->withCount(['rentals as rentals_in_range' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->withSum(['rentals as spent_in_range' => fn ($q) => $q->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled')], 'total')
            ->get()
            ->filter(fn (User $u) => $u->rentals_in_range > 0)
            ->sortByDesc('spent_in_range')
            ->map(fn (User $u) => [$u->name, $u->email, $u->phone, $u->rentals_in_range, (int) $u->spent_in_range])
            ->values()->all();

        return ['headers' => ['Nama', 'Email', 'No. HP', 'Jumlah Rental', 'Total Transaksi (Rp)'], 'rows' => $rows];
    }

    private function payments(Carbon $from, Carbon $to): array
    {
        $rows = Payment::with(['rental', 'method'])->whereBetween('created_at', [$from, $to])->latest()->get()
            ->map(fn (Payment $p) => [
                $p->rental?->invoice_no, $p->rental?->renter_name, $p->method?->name, $p->amount, $p->status_label, tgl($p->created_at, 'd/m/Y H:i'),
            ])->all();

        return ['headers' => ['Invoice', 'Customer', 'Metode', 'Jumlah (Rp)', 'Status', 'Dibuat'], 'rows' => $rows];
    }

    private function fines(Carbon $from, Carbon $to): array
    {
        $rows = RentalReturn::with('rental')->whereBetween('returned_at', [$from, $to])->latest('returned_at')->get()
            ->filter(fn (RentalReturn $r) => $r->total_extra > 0)
            ->map(fn (RentalReturn $r) => [
                $r->rental?->invoice_no, $r->rental?->renter_name, $r->late_hours, $r->late_fee, $r->damage_fee, $r->total_extra,
            ])->values()->all();

        return ['headers' => ['Invoice', 'Customer', 'Terlambat (jam)', 'Denda Keterlambatan (Rp)', 'Biaya Kerusakan (Rp)', 'Total (Rp)'], 'rows' => $rows];
    }
}
