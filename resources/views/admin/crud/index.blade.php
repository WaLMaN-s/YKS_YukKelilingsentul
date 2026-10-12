@extends('layouts.admin')
@section('title', $ctl->title())
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-bold">{{ $ctl->title() }}</h1><p class="text-sm text-slate-500">{{ $items->total() }} data</p></div>
    <a href="{{ route($ctl->routeBase().'.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah {{ $ctl->singular() }}</a>
</div>

<div class="table-wrap mt-6">
    <table class="table">
        <thead>
            <tr>@foreach ($columns as $name => $f)<th>{{ $f['label'] }}</th>@endforeach<th class="text-right">Aksi</th></tr>
        </thead>
        <tbody>
        @forelse ($items as $item)
            <tr>
                @foreach ($columns as $name => $f)
                    <td>
                        @if (($f['type'] ?? '') === 'image')
                            <img src="{{ media_url($item->{$name}) }}" alt="" class="h-12 w-16 rounded-lg object-cover">
                        @elseif (($f['type'] ?? '') === 'checkbox')
                            <span class="badge {{ $item->{$name} ? 'bg-emerald-400/15 text-emerald-700 ring-emerald-200' : 'bg-slate-500/15 text-slate-500 ring-slate-200' }}">{{ $ctl->display($item, $name, $f) }}</span>
                        @elseif ($name === 'is_blocked')
                            <span class="badge {{ $item->{$name} ? 'bg-rose-500/15 text-rose-700 ring-rose-200' : 'bg-emerald-400/15 text-emerald-700 ring-emerald-200' }}">{{ $ctl->display($item, $name, $f) }}</span>
                        @else
                            {{ $ctl->display($item, $name, $f) }}
                        @endif
                    </td>
                @endforeach
                <td>
                    <div class="flex justify-end gap-2">
                        <a href="{{ route($ctl->routeBase().'.edit', $item->getKey()) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-3.5" /> Edit</a>
                        <form method="POST" action="{{ route($ctl->routeBase().'.destroy', $item->getKey()) }}" @submit.prevent="$store.confirm.ask('Hapus {{ $ctl->singular() }} ini? Tindakan tidak bisa dibatalkan.', $el)">@csrf @method('DELETE')
                            <button class="btn btn-danger btn-sm"><x-icon name="trash-2" class="size-3.5" /> Hapus</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $columns->count() + 1 }}" class="py-12 text-center text-slate-500">Belum ada data. Klik “Tambah {{ $ctl->singular() }}”.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
