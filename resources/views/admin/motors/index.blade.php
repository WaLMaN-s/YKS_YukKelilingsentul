@extends('layouts.admin')
@section('title', 'Motor')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-bold">Motor</h1><p class="text-sm text-slate-500">{{ $motors->total() }} unit terdaftar</p></div>
    <a href="{{ route('admin.motor.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Motor</a>
</div>

<form method="GET" class="mt-5 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama, merek, plat…" class="input !w-64">
    <select name="jenis" class="input !w-40" onchange="this.form.submit()"><option value="">Semua jenis</option>@foreach (\App\Models\Motorcycle::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('jenis') === $k)>{{ $v }}</option>@endforeach</select>
    <select name="status" class="input !w-44" onchange="this.form.submit()">
        <option value="">Semua status</option>
        @foreach (\App\Models\Motorcycle::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
    </select>
    <button class="btn btn-dark">Cari</button>
</form>

<div class="table-wrap mt-5">
    <table class="table">
        <thead><tr><th>Kendaraan</th><th>Jenis</th><th>Unit</th><th>Kategori</th><th>Harga</th><th>Disewa</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        @forelse ($motors as $m)
            <tr>
                <td><div class="flex items-center gap-3"><img src="{{ $m->image_url }}" alt="" class="h-12 w-16 rounded-lg object-cover"><div><a href="{{ route('admin.motor.show', $m) }}" class="font-semibold text-slate-900 hover:text-brand-ink">{{ $m->name }}</a><p class="text-xs text-slate-500">{{ $m->isJeep() ? 'Maks '.$m->capacity.' orang' : $m->cc.'cc · '.$m->transmission }}</p></div></div></td>
                <td>{{ $m->type_label }}</td>
                <td class="font-semibold text-slate-900">{{ $m->stock }}</td>
                <td>{{ $m->category?->name ?? '-' }}</td>
                <td>{{ rupiah($m->price_daily) }}</td>
                <td>{{ $m->rental_items_count }}×</td>
                <td>
                    <form method="POST" action="{{ route('admin.motor.status', $m) }}">@csrf
                        <select name="status" onchange="this.form.submit()" class="input !w-36 !py-1.5 text-xs" aria-label="Ubah status {{ $m->name }}">
                            @foreach (\App\Models\Motorcycle::STATUSES as $k => $v)<option value="{{ $k }}" @selected($m->status === $k)>{{ $v }}</option>@endforeach
                        </select>
                    </form>
                </td>
                <td>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route('admin.motor.show', $m) }}" class="btn btn-ghost btn-sm" aria-label="Detail"><x-icon name="eye" class="size-3.5" /></a>
                        <a href="{{ route('admin.motor.edit', $m) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-3.5" /> Edit</a>
                        <form method="POST" action="{{ route('admin.motor.destroy', $m) }}" @submit.prevent="$store.confirm.ask('Hapus motor {{ addslashes($m->name) }}?', $el)">@csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm" aria-label="Hapus"><x-icon name="trash-2" class="size-3.5" /></button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-12 text-center text-slate-500">Belum ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $motors->links() }}</div>
@endsection
