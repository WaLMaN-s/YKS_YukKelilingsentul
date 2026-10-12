@extends('layouts.dashboard')
@section('title', 'Dashboard')
@section('panel')
<h1 class="text-2xl font-bold">Halo, {{ auth()->user()->name }} 👋</h1>
<p class="mt-1 text-sm text-slate-500">Ringkasan aktivitas sewa Anda.</p>

<div class="mt-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
    @foreach ([
        ['Total Penyewaan', $stats['total'], 'bike', 'text-sky-700 bg-sky-500/15'],
        ['Penyewaan Aktif', $stats['active'], 'clock', 'text-amber-700 bg-amber-500/15'],
        ['Selesai', $stats['completed'], 'circle-check', 'text-emerald-700 bg-emerald-500/15'],
        ['Total Pengeluaran', rupiah($stats['spent']), 'wallet', 'text-brand-ink bg-brand/15'],
    ] as [$label, $value, $icon, $color])
        <div class="card p-4 sm:p-6">
            <span class="flex size-10 items-center justify-center rounded-xl {{ $color }}"><x-icon :name="$icon" /></span>
            <p class="mt-3 text-xs text-slate-500">{{ $label }}</p>
            <p class="text-lg font-bold text-slate-900 sm:text-2xl">{{ $value }}</p>
        </div>
    @endforeach
</div>

<div class="mt-8 flex items-center justify-between">
    <h2 class="text-lg font-bold">Penyewaan Terbaru</h2>
    <a href="{{ route('dashboard.bookings') }}" class="text-sm font-semibold text-brand-ink hover:underline">Lihat semua →</a>
</div>
<div class="mt-4 space-y-4">
    @forelse ($recent as $rental)
        @include('partials.booking-row', ['rental' => $rental])
    @empty
        <div class="card card-pad text-center">
            <x-icon name="bike" class="mx-auto size-10 text-slate-500" />
            <p class="mt-3 font-semibold">Belum ada penyewaan</p>
            <a href="{{ route('motors.index') }}" class="btn btn-primary mt-4">Cari Motor</a>
        </div>
    @endforelse
</div>
@endsection
