@extends('layouts.dashboard')
@section('title', 'Wisata Saya')
@section('panel')
<div class="flex items-center justify-between"><h1 class="text-2xl font-bold">Wisata Saya</h1><a href="{{ route('tours.index') }}" class="btn btn-primary btn-sm">Pesan paket wisata</a></div>
<div class="mt-6 space-y-4">
    @forelse ($bookings as $b)
        <article class="card card-pad flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold text-brand-ink">#{{ $b->invoice_no }} · {{ $b->trip_label }}</p>
                <h3 class="text-base font-semibold">{{ $b->package->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ tgl($b->trip_date) }} · {{ $b->people }} orang</p>
                <p class="mt-1 text-lg font-bold">{{ rupiah($b->total) }}</p>
            </div>
            <div class="flex flex-col items-end gap-2">
                <span class="badge {{ $b->status_color }}">{{ $b->status_label }}</span>
                <a href="{{ route('tour-booking.show', $b->id) }}" class="btn btn-ghost btn-sm">{{ $b->canUploadProof() ? 'Bayar / Detail' : 'Detail' }}</a>
            </div>
        </article>
    @empty
        <div class="card card-pad text-center text-slate-500">Belum ada booking wisata. <a href="{{ route('tours.index') }}" class="font-semibold text-brand-ink">Lihat paket wisata</a>.</div>
    @endforelse
</div>
<div class="mt-6">{{ $bookings->links() }}</div>
@endsection
