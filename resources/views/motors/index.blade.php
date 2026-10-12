@extends('layouts.app')
@section('title', 'Sewa Motor Trail')
@section('description', 'Daftar motor trail untuk disewa: filter kategori, merek, harga, CC, dan transmisi. Cek ketersediaan sesuai tanggal sewa Anda.')

@section('content')
@php $carry = request()->only(['start', 'end', 'lokasi']); $jenis = request('jenis'); @endphp
<section class="border-b border-slate-200 bg-navy-900 py-10">
    <div class="container-x">
        <h1 class="text-3xl font-bold">{{ $jenis === 'jeep' ? 'Sewa Jeep' : ($jenis === 'trail' ? 'Sewa Motor Trail' : 'Motor Trail & Jeep') }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            @if (request('start') && request('end'))
                Menampilkan motor yang tersedia pada <b class="text-brand-ink">{{ tgl(request('start')) }}</b> – <b class="text-brand-ink">{{ tgl(request('end')) }}</b>.
            @else
                Pilih motor trail favoritmu, lalu tentukan tanggal sewa.
            @endif
        </p>
    </div>
</section>

<div class="container-x pt-8">
    <div class="flex flex-wrap gap-2">
        @foreach ([null => 'Semua', 'trail' => 'Motor Trail', 'jeep' => 'Jeep'] as $k => $label)
            <a href="{{ route('motors.index', array_filter($carry + ['jenis' => $k])) }}" class="btn btn-sm {{ ($jenis ?? null) == $k ? 'btn-primary' : 'btn-dark' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>
<div class="container-x grid gap-8 py-8 lg:grid-cols-[18rem_1fr]" x-data="{ filters: false }">
    <button type="button" class="btn btn-dark lg:hidden" @click="filters = !filters"><x-icon name="filter" class="size-4" /> Filter</button>

    <aside class="lg:block" :class="filters ? 'block' : 'hidden'">
        <form method="GET" action="{{ route('motors.index') }}" class="card card-pad space-y-4 lg:sticky lg:top-24">
            @foreach ($carry as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
            <input type="hidden" name="urut" value="{{ request('urut') }}">
            <input type="hidden" name="jenis" value="{{ request('jenis') }}">
            <x-input name="q" label="Cari motor" :value="request('q')" placeholder="Nama atau merek" />
            <div>
                <label class="label" for="f-kategori">Kategori</label>
                <select id="f-kategori" name="kategori" class="input">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)<option value="{{ $c->slug }}" @selected(request('kategori') === $c->slug)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label" for="f-merek">Merek</label>
                <select id="f-merek" name="merek" class="input">
                    <option value="">Semua merek</option>
                    @foreach ($brands as $b)<option @selected(request('merek') === $b)>{{ $b }}</option>@endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <x-input name="harga_min" type="number" label="Harga min" :value="request('harga_min')" min="0" step="10000" placeholder="0" />
                <x-input name="harga_max" type="number" label="Harga maks" :value="request('harga_max')" min="0" step="10000" placeholder="500000" />
            </div>
            <div>
                <label class="label" for="f-cc">Kapasitas mesin</label>
                <select id="f-cc" name="cc" class="input">
                    <option value="">Semua CC</option>
                    <option value="sd150" @selected(request('cc') === 'sd150')>≤ 150 cc</option>
                    <option value="151-200" @selected(request('cc') === '151-200')>151 – 200 cc</option>
                    <option value="gt200" @selected(request('cc') === 'gt200')>&gt; 200 cc</option>
                </select>
            </div>
            <div>
                <label class="label" for="f-transmisi">Transmisi</label>
                <select id="f-transmisi" name="transmisi" class="input">
                    <option value="">Semua</option>
                    <option @selected(request('transmisi') === 'Manual')>Manual</option>
                    <option @selected(request('transmisi') === 'Automatic')>Automatic</option>
                </select>
            </div>
            <div>
                <label class="label" for="f-status">Status</label>
                <select id="f-status" name="status" class="input">
                    <option value="">Semua status</option>
                    <option value="available" @selected(request('status') === 'available')>Tersedia</option>
                    <option value="maintenance" @selected(request('status') === 'maintenance')>Maintenance</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <button class="btn btn-primary btn-sm">Terapkan</button>
                <a href="{{ route('motors.index', $carry) }}" class="btn btn-ghost btn-sm">Reset</a>
            </div>
        </form>
    </aside>

    <div>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-500">{{ $motors->total() }} motor ditemukan</p>
            <form method="GET" action="{{ route('motors.index') }}" class="flex items-center gap-2">
                @foreach (request()->except(['urut', 'page']) as $k => $v)
                    @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <label for="urut" class="text-sm text-slate-500">Urutkan</label>
                <select id="urut" name="urut" class="input !w-auto !py-2" onchange="this.form.submit()">
                    @foreach (['populer' => 'Paling populer', 'terbaru' => 'Terbaru', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi'] as $k => $label)
                        <option value="{{ $k }}" @selected(request('urut', 'populer') === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
            @forelse ($motors as $motor)
                <x-motor-card :motor="$motor" :start="request('start')" :end="request('end')" :location="request('lokasi')" />
            @empty
                <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                    <x-icon name="bike" class="mx-auto size-10 text-slate-500" />
                    <p class="mt-3 font-semibold text-slate-900">Tidak ada motor yang cocok</p>
                    <p class="mt-1 text-sm text-slate-500">Coba ubah filter atau pilih tanggal lain.</p>
                </div>
            @endforelse
        </div>
        <div class="mt-8">{{ $motors->links() }}</div>
    </div>
</div>
@endsection
