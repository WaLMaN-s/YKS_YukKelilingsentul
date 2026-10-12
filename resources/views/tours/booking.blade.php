@extends('layouts.app')
@section('title', 'Booking Wisata #'.$booking->invoice_no)

@section('content')
@php $method = $booking->method; @endphp
<div class="container-x max-w-4xl py-10">
    <a href="{{ route('dashboard.tours') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Wisata Saya</a>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-3xl font-bold">#{{ $booking->invoice_no }}</h1>
        <div class="flex gap-2"><span class="badge bg-slate-500/15 text-slate-600 ring-slate-200">{{ $booking->trip_label }}</span><span class="badge {{ $booking->status_color }}">{{ $booking->status_label }}</span></div>
    </div>

    @if ($booking->status === 'cancelled')
        <p class="mt-4 rounded-xl border border-rose-200 bg-rose-500/10 p-3 text-sm text-rose-700">Booking dibatalkan. {{ $booking->cancelled_reason }}</p>
    @endif
    @if ($booking->payment_note && $booking->status === 'waiting_payment')
        <p class="mt-4 rounded-xl border border-rose-200 bg-rose-500/10 p-3 text-sm text-rose-700">Pembayaran ditolak: {{ $booking->payment_note }}</p>
    @endif
    @if ($booking->status === 'confirmed')
        <p class="mt-4 rounded-xl border border-emerald-200 bg-emerald-500/10 p-3 text-sm text-emerald-700">Pembayaran lunas dan trip terkonfirmasi. Sampai jumpa di basecamp!</p>
    @endif

    <div class="mt-6 grid gap-6 md:grid-cols-[1fr_.8fr]">
        <div class="space-y-6">
            <section class="card card-pad space-y-3 text-sm">
                <h2 class="text-lg font-bold">{{ $booking->package->name }}</h2>
                <dl class="grid grid-cols-2 gap-3">
                    <div><dt class="text-slate-500">Tanggal trip</dt><dd class="font-medium text-slate-900">{{ tgl($booking->trip_date, 'l, d F Y') }}</dd></div>
                    <div><dt class="text-slate-500">Peserta</dt><dd class="font-medium text-slate-900">{{ $booking->people }} orang</dd></div>
                    <div><dt class="text-slate-500">Kontak</dt><dd class="font-medium text-slate-900">{{ $booking->contact_name }}<br>{{ $booking->contact_phone }}</dd></div>
                    <div><dt class="text-slate-500">Perubahan jadwal</dt><dd class="font-medium text-slate-900">{{ $booking->rescheduled_count }}×</dd></div>
                </dl>
                @if ($booking->notes)<p class="rounded-lg bg-navy-900 p-3 text-slate-600">{{ $booking->notes }}</p>@endif
            </section>

            @if ($method && in_array($booking->status, ['waiting_payment', 'waiting_verification']))
                <section class="card card-pad">
                    <h2 class="text-lg font-bold">Pembayaran — {{ $method->name }}</h2>
                    @if ($method->type !== 'cash')
                        <dl class="mt-3 divide-y divide-slate-200 rounded-xl bg-navy-900 px-4 text-sm">
                            <div class="flex justify-between py-3"><dt class="text-slate-500">{{ $method->type === 'qris' ? 'ID QRIS' : 'No. rekening' }}</dt><dd class="font-mono text-base font-bold text-brand-ink">{{ $method->account_number }}</dd></div>
                            <div class="flex justify-between py-3"><dt class="text-slate-500">Atas nama</dt><dd class="font-semibold">{{ $method->account_name }}</dd></div>
                            <div class="flex justify-between py-3"><dt class="text-slate-500">Jumlah (lunas)</dt><dd class="text-lg font-bold">{{ rupiah($booking->total) }}</dd></div>
                        </dl>
                    @endif
                    <p class="mt-3 text-sm text-slate-600">{{ $method->instructions }}</p>
                    @if ($booking->canUploadProof())
                        @if ($booking->status === 'waiting_verification')<p class="mt-3 text-sm text-sky-700">Bukti sudah dikirim dan menunggu verifikasi. Anda bisa mengunggah ulang jika salah.</p>@endif
                        <form method="POST" action="{{ route('tour-booking.proof', $booking->id) }}" enctype="multipart/form-data" class="mt-4 space-y-4">@csrf
                            <x-input type="file" name="proof" label="Bukti transfer / screenshot" accept="image/png,image/jpeg,application/pdf" required hint="JPG, PNG, atau PDF. Maks 4 MB." />
                            <button class="btn btn-primary"><x-icon name="upload" class="size-4" /> Kirim Bukti</button>
                        </form>
                    @endif
                </section>
            @endif

            @if ($booking->isOpen())
                <section class="card card-pad text-sm">
                    <h2 class="text-lg font-bold">Ubah Jadwal</h2>
                    @if ($canReschedule)
                        <form method="POST" action="{{ route('tour-booking.reschedule', $booking->id) }}" class="mt-3 flex flex-wrap items-end gap-3">@csrf
                            @if ($booking->trip_type === 'open')
                                <div><label class="label" for="new_schedule">Jadwal open trip lain</label>
                                    <select id="new_schedule" name="tour_schedule_id" class="input" required><option value="">Pilih…</option>@foreach ($schedules as $s)<option value="{{ $s->id }}">{{ tgl($s->date, 'd F Y') }} — sisa {{ $s->remaining }}</option>@endforeach</select></div>
                            @else
                                <div><label class="label" for="new_date">Tanggal baru</label>
                                    <input id="new_date" type="date" name="trip_date" class="input" min="{{ today()->addDay()->toDateString() }}" required></div>
                            @endif
                            <button class="btn btn-dark btn-sm">Simpan jadwal</button>
                        </form>
                        <p class="mt-2 text-xs text-slate-500">Maksimal H-{{ $rescheduleDays }} dari tanggal trip.</p>
                        @error('tour_schedule_id')<p class="field-error">{{ $message }}</p>@enderror
                        @error('trip_date')<p class="field-error">{{ $message }}</p>@enderror
                    @else
                        <p class="mt-1 text-xs text-slate-500">Batas perubahan jadwal (H-{{ $rescheduleDays }}) sudah lewat. Hubungi admin via WhatsApp jika mendesak.</p>
                    @endif
                </section>
            @endif
        </div>

        <aside class="card card-pad h-fit space-y-4">
            <img src="{{ $booking->package->image_url }}" alt="" class="aspect-[16/9] w-full rounded-xl object-cover">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">{{ $booking->people }} orang × {{ rupiah($booking->price_per_person) }}</dt><dd>{{ rupiah($booking->subtotal) }}</dd></div>
                @foreach ($booking->addons ?? [] as $ad)<div class="flex justify-between"><dt class="text-slate-500">+ {{ $ad['name'] }}</dt><dd>{{ rupiah($ad['subtotal']) }}</dd></div>@endforeach
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-semibold">Total</dt><dd class="font-bold text-brand-ink">{{ rupiah($booking->total) }}</dd></div>
            </dl>
            @if ($booking->proof_path)<a href="{{ route('files.tour-proof', $booking->id) }}" target="_blank" class="btn btn-dark w-full"><x-icon name="eye" class="size-4" /> Lihat bukti bayar</a>@endif
            <a href="{{ wa_link($booking->whatsappMessage()) }}" target="_blank" rel="noopener" class="btn btn-ghost w-full"><x-icon name="message-circle" class="size-4" /> Hubungi Admin</a>
            @if ($booking->isOpen())
                <form method="POST" action="{{ route('tour-booking.cancel', $booking->id) }}"
                      @submit.prevent="$store.confirm.ask({{ \Illuminate\Support\Js::from(today()->gte($booking->trip_date) ? 'Hari H sudah tiba: membatalkan sekarang membuat pembayaran HANGUS. Lanjutkan?' : 'Batalkan booking ini? Jika sudah membayar, admin akan menghubungi Anda untuk refund.') }}, $el)">@csrf
                    <button class="btn btn-danger w-full">Batalkan Booking</button>
                </form>
            @endif
        </aside>
    </div>
</div>
@endsection
