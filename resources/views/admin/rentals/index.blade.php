@extends('layouts.admin')
@section('title', 'Penyewaan')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">Penyewaan</h1>
    @if (auth()->user()->hasPermission('rental'))<a href="{{ route('admin.penyewaan.manual') }}" class="btn btn-primary btn-sm"><x-icon name="plus" class="size-4" /> Catat Pesanan Manual (WA/IG)</a>@endif</div>
<form method="GET" class="mt-5 flex flex-wrap items-end gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Invoice / nama penyewa" class="input !w-56">
    <select name="status" class="input !w-52"><option value="">Semua status</option>@foreach (\App\Models\Rental::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
    <div><label class="sr-only" for="dari">Dari</label><input id="dari" type="date" name="dari" value="{{ request('dari') }}" class="input !w-44" title="Mulai sewa dari"></div>
    <div><label class="sr-only" for="sampai">Sampai</label><input id="sampai" type="date" name="sampai" value="{{ request('sampai') }}" class="input !w-44" title="Mulai sewa sampai"></div>
    <button class="btn btn-dark">Filter</button>
    @if (request()->hasAny(['q', 'status', 'dari', 'sampai']))<a href="{{ route('admin.penyewaan.index') }}" class="btn btn-ghost">Reset</a>@endif
</form>
<div class="table-wrap mt-5">
    <table class="table">
        <thead><tr><th>ID</th><th>Customer</th><th>Motor</th><th>Tanggal Sewa</th><th>Tanggal Kembali</th><th>Durasi</th><th>Total</th><th>Pembayaran</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($rentals as $r)
            <tr>
                <td class="font-medium text-slate-900">#{{ $r->invoice_no }}</td>
                <td>{{ $r->renter_name }}</td>
                <td>{{ $r->motorcycle?->name }}@if ($r->quantity > 1) <span class="text-xs text-slate-500">× {{ $r->quantity }}</span>@endif<p class="text-[11px] text-slate-500">{{ \App\Models\Rental::SOURCES[$r->source] ?? $r->source }}</p></td>
                <td>{{ tgl($r->start_date, 'd M Y') }}</td><td>{{ tgl($r->end_date, 'd M Y') }}</td>
                <td>{{ $r->days }} hari</td><td>{{ rupiah($r->total) }}</td>
                <td>@if ($r->payment)<span class="badge {{ $r->payment->status_color }}">{{ $r->payment->status_label }}</span>@endif</td>
                <td><span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span></td>
                <td class="text-right"><a href="{{ route('admin.penyewaan.show', $r) }}" class="btn btn-dark btn-sm">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="10" class="py-12 text-center text-slate-500">Tidak ada data penyewaan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $rentals->links() }}</div>
@endsection
