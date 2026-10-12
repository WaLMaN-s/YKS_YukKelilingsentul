@extends('layouts.admin')
@section('title', 'Pembayaran')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">Pembayaran</h1>
    <a href="{{ route('admin.metode-pembayaran.index') }}" class="btn btn-ghost btn-sm"><x-icon name="credit-card" class="size-4" /> Kelola metode</a></div>
<form method="GET" class="mt-5 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Invoice / nama" class="input !w-56">
    <select name="status" class="input !w-52" onchange="this.form.submit()"><option value="">Semua status</option>@foreach (\App\Models\Payment::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
    <button class="btn btn-dark">Cari</button>
</form>
<div class="table-wrap mt-5">
    <table class="table">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Jumlah</th><th>Metode</th><th>Bukti</th><th>Tanggal</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($payments as $p)
            <tr>
                <td><a href="{{ route('admin.penyewaan.show', $p->rental_id) }}" class="font-medium text-slate-900 hover:text-brand-ink">#{{ $p->rental->invoice_no }}</a></td>
                <td>{{ $p->rental->renter_name }}</td><td>{{ rupiah($p->amount) }}</td><td>{{ $p->method?->name }}</td>
                <td>@if ($p->proof_path)<a href="{{ route('files.proof', $p->id) }}" target="_blank" class="text-brand-ink hover:underline">Lihat</a>@else<span class="text-slate-500">-</span>@endif</td>
                <td>{{ tgl($p->created_at, 'd M Y') }}</td>
                <td><span class="badge {{ $p->status_color }}">{{ $p->status_label }}</span>@if ($p->note)<p class="mt-1 max-w-48 text-xs text-slate-500">{{ $p->note }}</p>@endif</td>
                <td>
                    <div class="flex flex-wrap justify-end gap-1.5">
                        @if (in_array($p->status, ['pending', 'waiting_verification']))
                            <form method="POST" action="{{ route('admin.pembayaran.verify', $p) }}" @submit.prevent="$store.confirm.ask('Verifikasi pembayaran {{ rupiah($p->amount) }} ini?', $el)">@csrf<button class="btn btn-success btn-sm">Verifikasi</button></form>
                            <div x-data="{ open: false }" class="relative">
                                <button type="button" class="btn btn-danger btn-sm" @click="open = !open">Tolak</button>
                                <form x-show="open" x-cloak @click.outside="open = false" method="POST" action="{{ route('admin.pembayaran.reject', $p) }}" class="card card-pad absolute right-0 z-20 mt-2 w-64 space-y-2 text-left">@csrf
                                    <label class="label" for="note-{{ $p->id }}">Alasan penolakan</label>
                                    <textarea id="note-{{ $p->id }}" name="note" rows="2" class="input" required></textarea>
                                    <button class="btn btn-danger btn-sm w-full">Tolak pembayaran</button>
                                </form>
                            </div>
                        @elseif ($p->status === 'paid')
                            <form method="POST" action="{{ route('admin.pembayaran.refund', $p) }}" @submit.prevent="$store.confirm.ask('Tandai pembayaran ini sebagai refund? Booking yang belum berjalan akan dibatalkan.', $el)">@csrf<button class="btn btn-dark btn-sm">Refund</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-12 text-center text-slate-500">Tidak ada pembayaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $payments->links() }}</div>
@endsection
