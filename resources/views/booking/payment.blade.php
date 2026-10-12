@extends('layouts.app')
@section('title', 'Pembayaran #'.$rental->invoice_no)

@section('content')
@php $method = $payment?->method; @endphp
<div class="container-x max-w-4xl py-10">
    <h1 class="text-3xl font-bold">Pembayaran</h1>
    <p class="mt-1 text-sm text-slate-500">Invoice <b class="text-slate-900">#{{ $rental->invoice_no }}</b></p>

    <div class="mt-8 grid gap-6 md:grid-cols-[1fr_.8fr]">
        <div class="space-y-6">
            <section class="card card-pad">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Status</h2>
                    <div class="flex gap-2">
                        <span class="badge {{ $rental->status_color }}">{{ $rental->status_label }}</span>
                        @if ($payment)<span class="badge {{ $payment->status_color }}">{{ $payment->status_label }}</span>@endif
                    </div>
                </div>
                @if ($payment?->status === 'rejected' && $payment->note)
                    <p class="mt-3 rounded-xl border border-rose-200 bg-rose-500/10 p-3 text-sm text-rose-700">Pembayaran ditolak: {{ $payment->note }}</p>
                @endif
                @if ($payment?->status === 'paid')
                    <p class="mt-3 rounded-xl border border-emerald-200 bg-emerald-500/10 p-3 text-sm text-emerald-700">Pembayaran sudah diterima. Admin akan mengonfirmasi booking Anda.</p>
                @endif
            </section>

            @if ($method && $payment?->status !== 'paid')
                <section class="card card-pad">
                    <h2 class="text-lg font-bold">Tujuan Pembayaran — {{ $method->name }}</h2>
                    @if ($method->type !== 'cash')
                        <dl class="mt-3 divide-y divide-slate-200 rounded-xl bg-navy-900 px-4 text-sm">
                            <div class="flex justify-between py-3"><dt class="text-slate-500">{{ $method->type === 'qris' ? 'ID QRIS' : 'No. rekening' }}</dt><dd class="font-mono text-base font-bold text-brand-ink">{{ $method->account_number }}</dd></div>
                            <div class="flex justify-between py-3"><dt class="text-slate-500">Atas nama</dt><dd class="font-semibold">{{ $method->account_name }}</dd></div>
                            <div class="flex justify-between py-3"><dt class="text-slate-500">Jumlah transfer</dt><dd class="text-lg font-bold">{{ rupiah($payment->amount) }}</dd></div>
                        </dl>
                    @endif
                    <p class="mt-3 text-sm text-slate-600">{{ $method->instructions }}</p>
                </section>

                @if ($payment->canUploadProof() && ! in_array($rental->status, ['cancelled', 'completed']))
                    <section class="card card-pad">
                        <h2 class="text-lg font-bold">Upload Bukti Pembayaran</h2>
                        @if ($payment->status === 'waiting_verification')
                            <p class="mt-2 text-sm text-sky-700">Bukti sudah dikirim dan menunggu verifikasi. Anda bisa mengunggah ulang jika salah.</p>
                        @endif
                        <form method="POST" action="{{ route('payment.proof', $rental->id) }}" enctype="multipart/form-data" class="mt-4 space-y-4">@csrf
                            <x-input type="file" name="proof" label="Bukti transfer / screenshot" accept="image/png,image/jpeg,application/pdf" required hint="JPG, PNG, atau PDF. Maks 4 MB." />
                            <button class="btn btn-primary"><x-icon name="upload" class="size-4" /> Kirim Bukti</button>
                        </form>
                    </section>
                @endif
            @endif
        </div>

        <aside class="card card-pad h-fit space-y-4">
            <img src="{{ $rental->motorcycle->image_url }}" alt="" class="aspect-[16/10] w-full rounded-xl object-cover">
            <h2 class="font-bold">{{ $rental->motorcycle->name }}</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Tanggal</dt><dd class="text-right">{{ tgl($rental->start_date) }} –<br>{{ tgl($rental->end_date) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Durasi</dt><dd>{{ $rental->days }} hari</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Unit</dt><dd>{{ $rental->quantity }}{{ $rental->package ? ' · '.$rental->package->name : '' }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Harga sewa</dt><dd>{{ rupiah($rental->subtotal) }}</dd></div>
                @foreach ($rental->addons ?? [] as $ad)<div class="flex justify-between"><dt class="text-slate-500">+ {{ $ad['name'] }}</dt><dd>{{ rupiah($ad['subtotal']) }}</dd></div>@endforeach
                <div class="flex justify-between"><dt class="text-slate-500">Biaya antar</dt><dd>{{ rupiah($rental->delivery_fee) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Deposit</dt><dd>{{ rupiah($rental->deposit) }}</dd></div>
                @if ($rental->discount)<div class="flex justify-between text-emerald-600"><dt>Diskon</dt><dd>- {{ rupiah($rental->discount) }}</dd></div>@endif
                <div class="flex justify-between border-t border-slate-200 pt-3 text-base"><dt class="font-semibold">Total</dt><dd class="font-bold text-brand-ink">{{ rupiah($rental->total) }}</dd></div>
            </dl>
            <a href="{{ wa_link($rental->whatsappMessage()) }}" target="_blank" rel="noopener" class="btn btn-ghost w-full"><x-icon name="message-circle" class="size-4" /> Hubungi Admin</a>
            <a href="{{ route('dashboard.bookings.show', $rental->id) }}" class="btn btn-dark w-full">Lihat Detail Booking</a>
        </aside>
    </div>
</div>
@endsection
