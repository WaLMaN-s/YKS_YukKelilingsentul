@extends('layouts.dashboard')
@section('title', 'Booking #'.$rental->invoice_no)
@section('panel')
@php $motor = $rental->motorcycle; $payment = $rental->payment; $steps = ['pending' => 1, 'waiting_payment' => 1, 'paid' => 2, 'confirmed' => 3, 'pickup' => 3, 'rented' => 4, 'returned' => 5, 'completed' => 6]; $current = $steps[$rental->status] ?? 0; @endphp
<a href="{{ route('dashboard.bookings') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">#{{ $rental->invoice_no }}</h1>
    <span class="badge {{ $rental->status_color }}">{{ $rental->status_label }}</span>
</div>

@if ($rental->status === 'cancelled')
    <p class="mt-4 rounded-xl border border-rose-200 bg-rose-500/10 p-3 text-sm text-rose-700">Booking dibatalkan. {{ $rental->cancelled_reason }}</p>
@else
    <ol class="mt-5 grid grid-cols-3 gap-2 text-center text-[11px] sm:grid-cols-6 sm:text-xs">
        @foreach (['Dibuat', 'Dibayar', 'Dikonfirmasi', 'Disewa', 'Dikembalikan', 'Selesai'] as $i => $label)
            <li class="rounded-lg px-2 py-2 {{ $current >= $i + 1 ? 'bg-brand font-semibold text-ink' : 'bg-navy-800 text-slate-500' }}">{{ $label }}</li>
        @endforeach
    </ol>
@endif

<div class="mt-6 grid gap-6 xl:grid-cols-[1fr_.9fr]">
    <section class="card overflow-hidden">
        <img src="{{ $motor?->image_url }}" alt="" class="aspect-[16/8] w-full object-cover">
        <div class="space-y-3 p-5">
            <h2 class="text-lg font-bold">{{ $motor?->name }}</h2>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-slate-500">Mulai</dt><dd class="font-medium">{{ tgl($rental->start_date) }}</dd></div>
                <div><dt class="text-slate-500">Kembali</dt><dd class="font-medium">{{ tgl($rental->end_date) }}</dd></div>
                <div><dt class="text-slate-500">Durasi</dt><dd class="font-medium">{{ $rental->days }} hari</dd></div>
                <div><dt class="text-slate-500">Lokasi</dt><dd class="font-medium">{{ $rental->location?->name ?? '-' }}</dd></div>
            </dl>
            @if ($rental->notes)<p class="rounded-lg bg-navy-900 p-3 text-sm text-slate-600">{{ $rental->notes }}</p>@endif
        </div>
    </section>

    <section class="card card-pad space-y-3">
        <h2 class="text-lg font-bold">Rincian Biaya</h2>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Harga sewa{{ $rental->quantity > 1 ? ' ('.$rental->quantity.' unit)' : '' }}</dt><dd>{{ rupiah($rental->subtotal) }}</dd></div>
            @foreach ($rental->addons ?? [] as $ad)<div class="flex justify-between"><dt class="text-slate-500">+ {{ $ad['name'] }}</dt><dd>{{ rupiah($ad['subtotal']) }}</dd></div>@endforeach
            <div class="flex justify-between"><dt class="text-slate-500">Biaya antar</dt><dd>{{ rupiah($rental->delivery_fee) }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Deposit</dt><dd>{{ rupiah($rental->deposit) }}</dd></div>
            @if ($rental->discount)<div class="flex justify-between text-emerald-600"><dt>Diskon {{ $rental->promo?->code }}</dt><dd>- {{ rupiah($rental->discount) }}</dd></div>@endif
            <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-semibold">Total</dt><dd class="font-bold text-brand-ink">{{ rupiah($rental->total) }}</dd></div>
        </dl>

        @if ($rental->returnRecord)
            <div class="rounded-xl bg-navy-900 p-4 text-sm">
                <p class="font-semibold">Pengembalian</p>
                <p class="mt-1 text-slate-500">Dikembalikan {{ tgl($rental->returnRecord->returned_at, 'd F Y, H:i') }} · Kondisi: {{ \App\Models\RentalReturn::CONDITIONS[$rental->returnRecord->condition] ?? '-' }}</p>
                <p class="mt-1">Denda keterlambatan: {{ rupiah($rental->returnRecord->late_fee) }} · Kerusakan: {{ rupiah($rental->returnRecord->damage_fee) }}</p>
                <p class="mt-2 font-semibold text-emerald-600">Deposit dikembalikan: {{ rupiah($rental->deposit_refund) }}</p>
                @if ($rental->additional_due > 0)<p class="font-semibold text-rose-700">Kekurangan yang harus dibayar: {{ rupiah($rental->additional_due) }}</p>@endif
            </div>
        @endif

        <div class="flex items-center justify-between rounded-xl bg-navy-900 p-3 text-sm">
            <span class="text-slate-500">Pembayaran ({{ $payment?->method?->name }})</span>
            @if ($payment)<span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>@endif
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            @if ($payment?->canUploadProof() && ! in_array($rental->status, ['cancelled', 'completed']))
                <a href="{{ route('payment.show', $rental->id) }}" class="btn btn-primary btn-sm">Bayar / Upload Bukti</a>
            @endif
            @if ($payment?->proof_path)
                <a href="{{ route('files.proof', $payment->id) }}" target="_blank" class="btn btn-dark btn-sm"><x-icon name="eye" class="size-4" /> Bukti bayar</a>
            @endif
            <a href="{{ wa_link($rental->whatsappMessage()) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="message-circle" class="size-4" /> WhatsApp Admin</a>
            @if ($rental->canBeCancelledByCustomer())
                <form method="POST" action="{{ route('dashboard.bookings.cancel', $rental->id) }}"
                      @submit.prevent="$store.confirm.ask({{ \Illuminate\Support\Js::from($rental->wouldForfeitIfCancelled() ? 'Hari H sudah tiba: membatalkan sekarang membuat pembayaran HANGUS. Lanjutkan?' : 'Batalkan booking ini? Jika sudah membayar, admin akan menghubungi Anda untuk refund.') }}, $el)">@csrf
                    <button class="btn btn-danger btn-sm">Batalkan</button>
                </form>
            @endif
        </div>

        @if ($rental->canBeCancelledByCustomer())
            <div class="rounded-xl bg-navy-900 p-4 text-sm">
                <p class="font-semibold text-slate-900">Ubah jadwal</p>
                @if ($canReschedule)
                    <form method="POST" action="{{ route('dashboard.bookings.reschedule', $rental->id) }}" class="mt-2 flex flex-wrap items-end gap-2">@csrf
                        <div><label class="label" for="new_start">Tanggal mulai baru</label>
                            <input id="new_start" type="date" name="start_date" class="input" min="{{ today()->addDay()->toDateString() }}" value="{{ old('start_date') }}" required></div>
                        <button class="btn btn-dark btn-sm">Simpan jadwal</button>
                    </form>
                    <p class="mt-1 text-xs text-slate-500">Durasi {{ $rental->days }} hari tetap, harga tidak berubah. Maksimal H-{{ $rescheduleDays }} dari tanggal mulai.</p>
                    @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                @else
                    <p class="mt-1 text-xs text-slate-500">Batas perubahan jadwal (H-{{ $rescheduleDays }}) sudah lewat. Hubungi admin via WhatsApp jika ada kebutuhan mendesak.</p>
                @endif
            </div>
        @endif
    </section>
</div>

@if ($rental->status === 'completed' && ! $rental->review)
    <a href="{{ route('dashboard.reviews') }}" class="btn btn-primary mt-6"><x-icon name="star" class="size-4" /> Beri review untuk rental ini</a>
@endif
@endsection
