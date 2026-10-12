@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="flex flex-wrap items-end justify-between gap-2">
    <div>
        <h1 class="text-2xl font-bold">Selamat datang, {{ auth()->user()->name }} 👋</h1>
        <p class="text-sm text-slate-500">Berikut ringkasan aktivitas {{ setting('site_name') }} hari ini.</p>
    </div>
    <p class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="calendar-days" class="size-4" /> {{ tgl(now(), 'l, d F Y') }}</p>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([
        ['Unit Motor Trail', $stats['trail'], 'bike', 'bg-blue-500/15 text-blue-700'],
        ['Unit Jeep', $stats['jeep'], 'truck', 'bg-emerald-500/15 text-emerald-700'],
        ['Unit Sedang Disewa', $stats['rented'], 'clock', 'bg-amber-500/15 text-amber-700'],
        ['Total Customer', number_format($stats['customers'], 0, ',', '.'), 'users', 'bg-violet-500/15 text-violet-700'],
        ['Booking Hari Ini', $stats['today'], 'calendar-days', 'bg-sky-500/15 text-sky-700'],
        ['Booking Pending', $stats['pending'], 'receipt', 'bg-orange-500/15 text-orange-700'],
        ['Pendapatan Bulan Ini', rupiah($stats['revenue']), 'wallet', 'bg-brand/15 text-brand-ink'],
        ['Wisata Perlu Verifikasi', $stats['tour_pending'], 'map', 'bg-teal-500/15 text-teal-700'],
    ] as [$label, $value, $icon, $color])
        <div class="card card-pad flex items-center gap-4">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl {{ $color }}"><x-icon :name="$icon" class="size-6" /></span>
            <div class="min-w-0"><p class="text-xs text-slate-500">{{ $label }}</p><p class="truncate text-xl font-bold text-slate-900">{{ $value }}</p></div>
        </div>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card card-pad xl:col-span-2">
        <h2 class="font-semibold">Pendapatan & Rental 7 Hari Terakhir</h2>
        <div class="mt-4 h-72"><canvas id="chartWeek"></canvas></div>
    </section>
    <section class="card card-pad">
        <h2 class="font-semibold">Motor Paling Banyak Disewa</h2>
        <div class="mt-4 h-72">
            @if ($topMotors->isEmpty())<p class="py-16 text-center text-sm text-slate-500">Belum ada data.</p>@else<canvas id="chartTop"></canvas>@endif
        </div>
    </section>
    <section class="card card-pad xl:col-span-2">
        <h2 class="font-semibold">Pendapatan Bulanan</h2>
        <div class="mt-4 h-64"><canvas id="chartMonth"></canvas></div>
    </section>
    <section class="card card-pad">
        <div class="flex items-center justify-between"><h2 class="font-semibold">Aktivitas Terbaru</h2></div>
        <ul class="mt-3 divide-y divide-slate-200">
            @forelse ($activities as $a)
                <li class="py-3"><a href="{{ route('notifications.open', $a->id) }}" class="block hover:text-brand-ink"><p class="text-sm font-medium">{{ $a->title }}</p><p class="text-xs text-slate-500">{{ $a->body }}</p><p class="mt-0.5 text-[11px] text-slate-500">{{ $a->created_at->diffForHumans() }}</p></a></li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">Belum ada aktivitas.</li>
            @endforelse
        </ul>
    </section>
</div>

<section class="card mt-6 overflow-hidden">
    <div class="flex items-center justify-between p-5"><h2 class="font-semibold">Pemesanan Terbaru</h2>
        @if (auth()->user()->hasAnyPermission(['rental', 'pickup', 'return']))<a href="{{ route('admin.penyewaan.index') }}" class="text-sm text-brand-ink hover:underline">Lihat semua →</a>@endif</div>
    <div class="table-wrap rounded-none border-x-0 border-b-0">
        <table class="table">
            <thead><tr><th>No. Invoice</th><th>Nama</th><th>Motor</th><th>Tanggal</th><th>Durasi</th><th>Total</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($recent as $r)
                <tr>
                    <td class="font-medium text-slate-900">{{ $r->invoice_no }}</td><td>{{ $r->renter_name }}</td><td>{{ $r->motorcycle?->name }}</td>
                    <td>{{ tgl($r->start_date, 'd M Y') }}</td><td>{{ $r->days }} hari</td><td>{{ rupiah($r->total) }}</td>
                    <td><span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">Belum ada pemesanan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.renderAdminCharts(@js([
            'week' => $week,
            'monthly' => $monthly,
            'top' => ['labels' => $topMotors->map(fn ($t) => $t->motorcycle?->name ?? '-')->values(), 'values' => $topMotors->pluck('total')->values()],
        ]));
    });
</script>
@endpush
