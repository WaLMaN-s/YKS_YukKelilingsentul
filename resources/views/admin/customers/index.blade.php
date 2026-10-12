@extends('layouts.admin')
@section('title', 'Customer')
@section('content')
<h1 class="text-2xl font-bold">Customer</h1>
<form method="GET" class="mt-5 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Nama / email / HP" class="input !w-64">
    <select name="status" class="input !w-44" onchange="this.form.submit()"><option value="">Semua</option><option value="blocked" @selected(request('status') === 'blocked')>Diblokir</option></select>
    <button class="btn btn-dark">Cari</button>
</form>
<div class="table-wrap mt-5"><table class="table">
    <thead><tr><th>Nama</th><th>Kontak</th><th>Total rental</th><th>Total transaksi</th><th>Terdaftar</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
    <tbody>
    @forelse ($customers as $c)
        <tr>
            <td class="font-medium text-slate-900">{{ $c->name }}</td>
            <td>{{ $c->email }}<p class="text-xs text-slate-500">{{ $c->phone }}</p></td>
            <td>{{ $c->rentals_count }}</td><td>{{ rupiah($c->spent) }}</td><td>{{ tgl($c->created_at, 'd M Y') }}</td>
            <td><span class="badge {{ $c->is_blocked ? 'bg-rose-500/15 text-rose-700 ring-rose-200' : 'bg-emerald-400/15 text-emerald-700 ring-emerald-200' }}">{{ $c->is_blocked ? 'Diblokir' : 'Aktif' }}</span></td>
            <td><div class="flex justify-end gap-2">
                <a href="{{ route('admin.customer.show', $c) }}" class="btn btn-dark btn-sm">Detail</a>
                <form method="POST" action="{{ route('admin.customer.block', $c) }}" @submit.prevent="$store.confirm.ask('{{ $c->is_blocked ? 'Buka blokir' : 'Blokir' }} {{ addslashes($c->name) }}?', $el)">@csrf
                    <button class="btn {{ $c->is_blocked ? 'btn-success' : 'btn-danger' }} btn-sm">{{ $c->is_blocked ? 'Unblock' : 'Block' }}</button></form>
            </div></td>
        </tr>
    @empty<tr><td colspan="7" class="py-12 text-center text-slate-500">Belum ada customer.</td></tr>@endforelse
    </tbody></table></div>
<div class="mt-6">{{ $customers->links() }}</div>
@endsection
