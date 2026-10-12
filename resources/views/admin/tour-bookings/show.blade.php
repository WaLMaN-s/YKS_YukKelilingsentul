@extends('layouts.admin')
@section('title', 'Booking Wisata #'.$booking->invoice_no)
@section('content')
@php $s = $booking->status; $method = $booking->method; @endphp
<a href="{{ route('admin.booking-wisata.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3"><h1 class="text-2xl font-bold">#{{ $booking->invoice_no }}</h1><span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span><span class="badge bg-slate-500/15 text-slate-600 ring-slate-200">{{ $booking->trip_label }}</span></div>
    <p class="text-xs text-slate-500">Dibuat {{ tgl($booking->created_at, 'd M Y H:i') }}</p>
</div>

<div class="mt-5 flex flex-wrap gap-2">
    @if (in_array($s, ['waiting_payment', 'waiting_verification']))
        <form method="POST" action="{{ route('admin.booking-wisata.verify', $booking->id) }}" @submit.prevent="$store.confirm.ask('Verifikasi pembayaran {{ rupiah($booking->total) }}? Booking akan dikonfirmasi.', $el)">@csrf<button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Verifikasi Pembayaran</button></form>
        <div x-data="{ open: false }" class="relative">
            <button type="button" class="btn btn-danger btn-sm" @click="open = !open">Tolak Pembayaran</button>
            <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ route('admin.booking-wisata.reject', $booking->id) }}" class="card card-pad absolute left-0 z-20 mt-2 w-72 space-y-2">@csrf
                <label class="label" for="note">Alasan penolakan</label><textarea id="note" name="note" rows="2" class="input" required></textarea>
                <button class="btn btn-danger btn-sm w-full">Tolak</button>
            </form>
        </div>
    @endif
    @if ($s === 'confirmed')
        <form method="POST" action="{{ route('admin.booking-wisata.complete', $booking->id) }}" @submit.prevent="$store.confirm.ask('Tandai trip selesai?', $el)">@csrf<button class="btn btn-success btn-sm"><x-icon name="circle-check" class="size-4" /> Tandai Selesai</button></form>
    @endif
    @if ($booking->isOpen())
        <div x-data="{ open: false }" class="relative">
            <button type="button" class="btn btn-danger btn-sm" @click="open = !open"><x-icon name="ban" class="size-4" /> Batalkan</button>
            <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ route('admin.booking-wisata.cancel', $booking->id) }}" class="card card-pad absolute left-0 z-20 mt-2 w-72 space-y-2">@csrf
                <label class="label" for="reason">Alasan (dikirim ke customer)</label><textarea id="reason" name="reason" rows="2" class="input"></textarea>
                <button class="btn btn-danger btn-sm w-full">Ya, batalkan</button>
            </form>
        </div>
    @endif
    <a href="{{ wa_link("Halo {$booking->contact_name},\n\nTerkait booking wisata #{$booking->invoice_no} ({$booking->package->name}).") }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="message-circle" class="size-4" /> WhatsApp Customer</a>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card card-pad space-y-3 text-sm lg:col-span-2">
        <h2 class="text-lg font-bold">{{ $booking->package->name }} @if ($booking->package->own_motor)<span class="badge ml-2 bg-sky-500/15 text-sky-700 ring-sky-200">Bawa motor pribadi</span>@endif</h2>
        <dl class="grid gap-4 sm:grid-cols-2">
            <div><dt class="text-slate-500">Tanggal trip</dt><dd class="font-medium text-slate-900">{{ tgl($booking->trip_date, 'l, d F Y') }}</dd></div>
            <div><dt class="text-slate-500">Peserta</dt><dd class="font-medium text-slate-900">{{ $booking->people }} orang</dd></div>
            <div><dt class="text-slate-500">Kontak</dt><dd class="font-medium text-slate-900">{{ $booking->contact_name }} · {{ $booking->contact_phone }}</dd></div>
            <div><dt class="text-slate-500">Akun</dt><dd class="font-medium text-slate-900">{{ $booking->user->name }} ({{ $booking->user->email }})</dd></div>
            @if ($booking->schedule)<div><dt class="text-slate-500">Jadwal open trip</dt><dd class="font-medium text-slate-900">{{ tgl($booking->schedule->date) }} — terisi {{ $booking->schedule->booked }}/{{ $booking->schedule->quota }}</dd></div>@endif
            <div><dt class="text-slate-500">Perubahan jadwal</dt><dd class="font-medium text-slate-900">{{ $booking->rescheduled_count }}×</dd></div>
        </dl>
        @if ($booking->notes)<p class="rounded-lg bg-navy-900 p-3 text-slate-600">{{ $booking->notes }}</p>@endif
        @if ($booking->cancelled_reason)<p class="text-rose-700">Alasan pembatalan: {{ $booking->cancelled_reason }} @if ($booking->forfeited)<span class="badge ml-1 bg-rose-500/15 text-rose-700 ring-rose-200">Pembayaran hangus</span>@endif</p>@endif
    </section>
    <section class="card card-pad space-y-2 text-sm">
        <h2 class="text-lg font-bold">Pembayaran</h2>
        <div class="flex justify-between"><span class="text-slate-500">{{ $booking->people }} × {{ rupiah($booking->price_per_person) }}</span><span>{{ rupiah($booking->subtotal) }}</span></div>
        @foreach ($booking->addons ?? [] as $ad)<div class="flex justify-between"><span class="text-slate-500">+ {{ $ad['name'] }}</span><span>{{ rupiah($ad['subtotal']) }}</span></div>@endforeach
        <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold"><span>Total</span><span class="text-brand-ink">{{ rupiah($booking->total) }}</span></div>
        <p class="text-slate-500">Metode: {{ $method?->name ?? '-' }}</p>
        @if ($booking->paid_at)<p class="text-emerald-600">Lunas {{ tgl($booking->paid_at, 'd M Y H:i') }}@if ($booking->verifier) oleh {{ $booking->verifier->name }}@endif</p>@endif
        @if ($booking->payment_note)<p class="text-rose-700">Catatan: {{ $booking->payment_note }}</p>@endif
        @if ($booking->proof_path)<a href="{{ route('files.tour-proof', $booking->id) }}" target="_blank" class="btn btn-dark btn-sm mt-2"><x-icon name="eye" class="size-4" /> Lihat bukti bayar</a>@endif
    </section>
</div>
@endsection
