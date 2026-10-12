@extends('layouts.admin')
@section('title', 'Penyewaan #'.$rental->invoice_no)
@section('content')
@php
    $u = auth()->user();
    $motor = $rental->motorcycle;
    $payment = $rental->payment;
    $customer = $rental->user->customer;
    $s = $rental->status;
@endphp
<a href="{{ route('admin.penyewaan.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke Penyewaan</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3"><h1 class="text-2xl font-bold">#{{ $rental->invoice_no }}</h1><span class="badge {{ $rental->status_color }}">{{ $rental->status_label }}</span></div>
    <p class="text-xs text-slate-500">Dibuat {{ tgl($rental->created_at, 'd M Y H:i') }}</p>
</div>

{{-- Aksi sesuai status & izin --}}
<div class="mt-5 flex flex-wrap gap-2">
    @if ($u->hasPermission('rental'))
        @if (in_array($s, ['pending', 'paid']))
            <form method="POST" action="{{ route('admin.penyewaan.confirm', $rental) }}">@csrf<button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Konfirmasi</button></form>
        @endif
        @if ($s === 'waiting_payment')<span class="badge bg-orange-400/15 text-orange-700 ring-orange-200">Menunggu pembayaran — verifikasi di menu Pembayaran</span>@endif
        <a href="{{ route('admin.penyewaan.edit', $rental) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
    @endif
    @if ($u->hasPermission('pickup'))
        @if ($s === 'confirmed')
            <form method="POST" action="{{ route('admin.penyewaan.ready', $rental) }}">@csrf<button class="btn btn-dark btn-sm"><x-icon name="clipboard-check" class="size-4" /> Siap Diambil</button></form>
        @endif
        @if (in_array($s, ['confirmed', 'pickup']))
            <form method="POST" action="{{ route('admin.penyewaan.picked-up', $rental) }}" @submit.prevent="$store.confirm.ask('Tandai motor sudah diambil customer?', $el)">@csrf<button class="btn btn-primary btn-sm"><x-icon name="bike" class="size-4" /> Tandai Diambil</button></form>
        @endif
    @endif
    @if ($u->hasPermission('return') && $s === 'rented')
        <a href="{{ route('admin.penyewaan.return', $rental) }}" class="btn btn-primary btn-sm"><x-icon name="undo-2" class="size-4" /> Tandai Dikembalikan</a>
    @endif
    @if ($u->hasAnyPermission(['rental', 'return']) && $s === 'returned')
        <form method="POST" action="{{ route('admin.penyewaan.complete', $rental) }}" @submit.prevent="$store.confirm.ask('Selesaikan rental ini?', $el)">@csrf<button class="btn btn-success btn-sm"><x-icon name="circle-check" class="size-4" /> Selesaikan</button></form>
    @endif
    @if ($u->hasPermission('rental') && in_array($s, ['pending', 'waiting_payment', 'paid', 'confirmed', 'pickup']))
        <div x-data="{ open: false }" class="relative">
            <button type="button" class="btn btn-danger btn-sm" @click="open = !open"><x-icon name="ban" class="size-4" /> {{ in_array($s, ['pending', 'waiting_payment']) ? 'Tolak' : 'Batalkan' }}</button>
            <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ route('admin.penyewaan.cancel', $rental) }}" class="card card-pad absolute left-0 z-20 mt-2 w-72 space-y-3">@csrf
                <x-input name="reason" type="textarea" rows="2" label="Alasan (dikirim ke customer)" />
                <button class="btn btn-danger btn-sm w-full">Ya, {{ in_array($s, ['pending', 'waiting_payment']) ? 'tolak' : 'batalkan' }} booking</button>
            </form>
        </div>
    @endif
    <a href="{{ wa_link("Halo {$rental->renter_name},\n\nTerkait booking #{$rental->invoice_no} ({$motor?->name}).") }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="message-circle" class="size-4" /> WhatsApp Customer</a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card card-pad xl:col-span-2 space-y-5">
        <div class="flex gap-4"><img src="{{ $motor?->image_url }}" alt="" class="h-24 w-36 rounded-xl object-cover">
            <div><h2 class="text-lg font-bold">{{ $motor?->name }}</h2><p class="text-sm text-slate-500">{{ $motor?->plate_number }} · {{ $motor?->cc }}cc</p>
                <p class="mt-2 text-sm">{{ tgl($rental->start_date) }} → {{ tgl($rental->end_date) }} <span class="text-slate-500">({{ $rental->days }} hari)</span></p>
                <p class="text-xs text-slate-500">Lokasi: {{ $rental->location?->name ?? '-' }}</p>
                <p class="mt-1 flex flex-wrap gap-1.5 text-xs">
                    <span class="badge bg-slate-500/15 text-slate-600 ring-slate-200">{{ $rental->quantity }} unit</span>
                    @if ($rental->people)<span class="badge bg-slate-500/15 text-slate-600 ring-slate-200">{{ $rental->people }} orang</span>@endif
                    @if ($rental->package)<span class="badge bg-brand/15 text-brand-ink ring-brand/30">{{ $rental->package->name }}</span>@endif
                    <span class="badge bg-sky-500/15 text-sky-700 ring-sky-200">Sumber: {{ \App\Models\Rental::SOURCES[$rental->source] ?? $rental->source }}</span>
                    @if ($rental->rescheduled_count)<span class="badge bg-amber-400/15 text-amber-700 ring-amber-200">Jadwal diubah {{ $rental->rescheduled_count }}×</span>@endif
                </p></div></div>
        <div class="grid gap-4 text-sm sm:grid-cols-2">
            <div><p class="text-slate-500">Penyewa</p><p class="font-medium text-slate-900">{{ $rental->renter_name }}</p><p>{{ $rental->renter_phone }} · {{ $rental->renter_email }}</p></div>
            <div><p class="text-slate-500">Akun customer</p><a href="{{ route('admin.customer.show', $rental->user_id) }}" class="font-medium text-brand-ink hover:underline">{{ $rental->user->name }}</a></div>
            @if ($customer)
                <div><p class="text-slate-500">NIK / SIM</p><p class="font-medium text-slate-900">{{ $customer->nik ?: '-' }} / {{ $customer->sim_number ?: '-' }}</p></div>
                <div><p class="text-slate-500">Dokumen</p>
                    <p class="flex gap-3">
                        @if ($customer->ktp_path)<a class="text-brand-ink hover:underline" target="_blank" href="{{ route('files.document', [$customer->id, 'ktp']) }}">Lihat KTP</a>@else<span class="text-slate-500">KTP belum ada</span>@endif
                        @if ($customer->sim_path)<a class="text-brand-ink hover:underline" target="_blank" href="{{ route('files.document', [$customer->id, 'sim']) }}">Lihat SIM</a>@else<span class="text-slate-500">SIM belum ada</span>@endif
                    </p></div>
            @endif
            @if ($rental->notes)<div class="sm:col-span-2"><p class="text-slate-500">Catatan</p><p>{{ $rental->notes }}</p></div>@endif
            @if ($rental->cancelled_reason)<div class="sm:col-span-2"><p class="text-slate-500">Alasan pembatalan</p><p class="text-rose-700">{{ $rental->cancelled_reason }}</p>@if ($rental->forfeited)<p class="mt-1"><span class="badge bg-rose-500/15 text-rose-700 ring-rose-200">Pembayaran hangus — jangan direfund</span></p>@endif</div>@endif
        </div>
    </section>

    <section class="card card-pad space-y-2 text-sm">
        <h2 class="text-lg font-bold">Rincian Biaya</h2>
        <div class="flex justify-between"><span class="text-slate-500">Harga sewa{{ $rental->quantity > 1 ? ' ('.$rental->quantity.' unit)' : '' }}</span><span>{{ rupiah($rental->subtotal) }}</span></div>
        @foreach ($rental->addons ?? [] as $ad)<div class="flex justify-between"><span class="text-slate-500">+ {{ $ad['name'] }}</span><span>{{ rupiah($ad['subtotal']) }}</span></div>@endforeach
        <div class="flex justify-between"><span class="text-slate-500">Biaya antar</span><span>{{ rupiah($rental->delivery_fee) }}</span></div>
        <div class="flex justify-between"><span class="text-slate-500">Deposit</span><span>{{ rupiah($rental->deposit) }}</span></div>
        @if ($rental->discount)<div class="flex justify-between text-emerald-600"><span>Diskon {{ $rental->promo?->code }}</span><span>- {{ rupiah($rental->discount) }}</span></div>@endif
        <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-bold"><span>Total</span><span class="text-brand-ink">{{ rupiah($rental->total) }}</span></div>
        @if ($rental->returnRecord)
            <div class="mt-3 border-t border-slate-200 pt-3">
                <div class="flex justify-between"><span class="text-slate-500">Biaya tambahan</span><span class="font-semibold text-amber-700">{{ rupiah($rental->extra_fee) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Deposit dikembalikan</span><span class="font-semibold text-emerald-600">{{ rupiah($rental->deposit_refund) }}</span></div>
                @if ($rental->additional_due)<div class="flex justify-between"><span class="text-slate-500">Kekurangan bayar</span><span class="font-semibold text-rose-700">{{ rupiah($rental->additional_due) }}</span></div>@endif
            </div>
        @endif
    </section>

    <section class="card card-pad xl:col-span-2">
        <h2 class="text-lg font-bold">Pembayaran</h2>
        <div class="table-wrap mt-3"><table class="table"><thead><tr><th>Metode</th><th>Jumlah</th><th>Status</th><th>Bukti</th><th>Dibayar</th></tr></thead><tbody>
        @foreach ($rental->payments as $p)
            <tr><td>{{ $p->method?->name }}@if ($p->note)<p class="text-xs text-slate-500">{{ $p->note }}</p>@endif</td><td>{{ rupiah($p->amount) }}</td><td><span class="badge {{ $p->status_color }}">{{ $p->status_label }}</span></td>
                <td>@if ($p->proof_path)<a href="{{ route('files.proof', $p->id) }}" target="_blank" class="text-brand-ink hover:underline">Lihat bukti</a>@else<span class="text-slate-500">-</span>@endif</td>
                <td>{{ $p->paid_at ? tgl($p->paid_at, 'd M Y H:i') : '-' }}</td></tr>
        @endforeach
        </tbody></table></div>
        @if (! in_array($s, ['cancelled']) && $rental->payments->where('status', '!=', 'paid')->where('status', '!=', 'refund')->isNotEmpty() && in_array($s, ['confirmed', 'pickup', 'rented', 'returned']))
            <p class="mt-3 rounded-xl border border-amber-200 bg-amber-500/10 p-3 text-sm text-amber-700">Masih ada pembayaran yang belum lunas (mis. pelunasan setelah DP). Pastikan lunas sebelum kendaraan diserahkan.</p>
        @endif
        @if ($u->hasPermission('payment'))<a href="{{ route('admin.pembayaran.index', ['q' => $rental->invoice_no]) }}" class="mt-3 inline-block text-sm text-brand-ink hover:underline">Kelola pembayaran →</a>@endif
    </section>

    @if ($rental->returnRecord)
        @php $ret = $rental->returnRecord; @endphp
        <section class="card card-pad space-y-1 text-sm">
            <h2 class="text-lg font-bold">Pengembalian</h2>
            <p><span class="text-slate-500">Waktu:</span> {{ tgl($ret->returned_at, 'd M Y H:i') }}</p>
            <p><span class="text-slate-500">Kondisi:</span> {{ \App\Models\RentalReturn::CONDITIONS[$ret->condition] ?? $ret->condition }}</p>
            <p><span class="text-slate-500">BBM:</span> {{ \App\Models\RentalReturn::FUEL[$ret->fuel_level] ?? $ret->fuel_level }}</p>
            <p><span class="text-slate-500">Terlambat:</span> {{ $ret->late_hours }} jam ({{ rupiah($ret->late_fee) }})</p>
            <p><span class="text-slate-500">Kerusakan:</span> {{ rupiah($ret->damage_fee) }} {{ $ret->damage_description }}</p>
            @if ($ret->notes)<p><span class="text-slate-500">Catatan:</span> {{ $ret->notes }}</p>@endif
            <p class="text-xs text-slate-500">Diproses oleh {{ $ret->processor?->name ?? '-' }}</p>
        </section>
    @endif

    @if ($rental->review)
        <section class="card card-pad xl:col-span-3"><h2 class="font-bold">Review customer</h2><div class="mt-2"><x-stars :rating="$rental->review->rating" /> <span class="badge ml-2 bg-slate-500/15 text-slate-600 ring-slate-200">{{ \App\Models\Review::STATUSES[$rental->review->status] }}</span></div><p class="mt-2 text-sm text-slate-600">{{ $rental->review->comment }}</p></section>
    @endif
</div>
@endsection
