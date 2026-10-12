@extends('layouts.admin')
@section('title', 'Booking Wisata')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-2xl font-bold">Booking Wisata</h1>
    <a href="{{ route('admin.jadwal-wisata.index') }}" class="btn btn-ghost btn-sm"><x-icon name="calendar-days" class="size-4" /> Jadwal open trip</a></div>
<form method="GET" class="mt-5 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Invoice / nama" class="input !w-56">
    <select name="status" class="input !w-52" onchange="this.form.submit()"><option value="">Semua status</option>@foreach (\App\Models\TourBooking::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select>
    <select name="jenis" class="input !w-40" onchange="this.form.submit()"><option value="">Semua trip</option><option value="open" @selected(request('jenis') === 'open')>Open Trip</option><option value="private" @selected(request('jenis') === 'private')>Private Trip</option></select>
    <button class="btn btn-dark">Cari</button>
</form>
<div class="table-wrap mt-5"><table class="table">
    <thead><tr><th>Invoice</th><th>Paket</th><th>Customer</th><th>Tanggal</th><th>Jenis</th><th>Peserta</th><th>Total</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
    <tbody>
    @forelse ($bookings as $b)
        <tr>
            <td class="font-medium text-slate-900">#{{ $b->invoice_no }}</td><td>{{ $b->package->name }}</td><td>{{ $b->contact_name }}</td>
            <td>{{ tgl($b->trip_date, 'd M Y') }}</td>
            <td><span class="badge {{ $b->trip_type === 'private' ? 'bg-violet-400/15 text-violet-700 ring-violet-200' : 'bg-sky-400/15 text-sky-700 ring-sky-200' }}">{{ $b->trip_label }}</span></td>
            <td>{{ $b->people }}</td><td>{{ rupiah($b->total) }}</td>
            <td><span class="badge {{ $b->status_color }}">{{ $b->status_label }}</span></td>
            <td class="text-right"><a href="{{ route('admin.booking-wisata.show', $b->id) }}" class="btn btn-dark btn-sm">Detail</a></td>
        </tr>
    @empty<tr><td colspan="9" class="py-12 text-center text-slate-500">Belum ada booking wisata.</td></tr>@endforelse
    </tbody></table></div>
<div class="mt-6">{{ $bookings->links() }}</div>
@endsection
