@extends('layouts.app')
@section('title', $tour->name)
@section('description', \Illuminate\Support\Str::limit($tour->description ?? $tour->name, 155))
@section('og_image', $tour->image_url)
@section('content')
<div class="container-x grid gap-8 py-10 lg:grid-cols-[1.3fr_.7fr]">
    <div class="space-y-8">
        <img src="{{ $tour->image_url }}" alt="{{ $tour->name }}" class="aspect-[16/9] w-full rounded-3xl border border-slate-200 object-cover">
        <section>
            <h1 class="text-3xl font-bold">{{ $tour->name }}</h1>
            <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                <span class="flex items-center gap-1.5"><x-icon name="clock" class="size-4" /> Durasi {{ $tour->duration }}</span>
                @if ($tour->destination)<span class="flex items-center gap-1.5"><x-icon name="map-pin" class="size-4" /> {{ $tour->destination }}</span>@endif
                <span class="flex items-center gap-1.5"><x-icon name="users" class="size-4" /> Kuota {{ $tour->quota }} orang / jadwal</span>
            </p>
            <p class="mt-4 leading-relaxed text-slate-600">{{ $tour->description }}</p>
        </section>
        <section class="card card-pad">
            <h2 class="text-lg font-bold">Fasilitas</h2>
            @if ($tour->min_participants > 1)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-amber-500/15 px-3 py-1.5 text-sm font-medium text-amber-800 ring-1 ring-amber-200">
                    <x-icon name="users" class="size-4" /> Ketentuan: minimal {{ $tour->min_participants }} peserta
                </p>
            @endif
            <p class="mt-4 text-sm font-semibold text-slate-700">Sudah termasuk (include)</p>
            <ul class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                @foreach ($tour->facilities ?? [] as $f)<li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-emerald-600" />{{ $f }}</li>@endforeach
            </ul>
            @if (! empty($tour->excludes))
                <p class="mt-5 text-sm font-semibold text-slate-700">Belum termasuk (exclude)</p>
                <ul class="mt-2 grid gap-2 text-sm sm:grid-cols-2">
                    @foreach ($tour->excludes as $e)<li class="flex items-center gap-2 text-slate-500"><x-icon name="x" class="size-4 text-rose-500" />{{ $e }}</li>@endforeach
                </ul>
            @endif
        </section>
        <section>
            <h2 class="mb-3 text-lg font-bold">Jadwal Keberangkatan</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Tanggal</th><th>Sisa kuota</th><th>Catatan</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($schedules as $s)
                        @php $msg = "Halo Admin,\n\nSaya ingin memesan paket wisata:\n\n".$tour->name."\n\nTanggal:\n".tgl($s->date); @endphp
                        <tr>
                            <td class="font-medium text-slate-900">{{ tgl($s->date, 'l, d F Y') }}</td>
                            <td>@if ($s->remaining > 0)<span class="badge bg-emerald-500/15 text-emerald-700 ring-emerald-200">{{ $s->remaining }} tersisa</span>@else<span class="badge bg-rose-500/15 text-rose-700 ring-rose-200">Penuh</span>@endif</td>
                            <td class="text-slate-500">{{ $s->notes }}</td>
                            <td class="text-right">@if ($s->remaining > 0)<a href="{{ route('tour-booking.create', ['slug' => $tour->slug, 'jadwal' => $s->id]) }}" class="btn btn-primary btn-sm">Pesan</a>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada jadwal open trip. Tetap bisa berangkat lewat <b class="text-brand-ink">Private Trip</b> di tanggal pilihan Anda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <aside>
        <div class="card card-pad space-y-4 lg:sticky lg:top-24">
            <p class="text-3xl font-extrabold text-brand-ink">{{ rupiah($tour->price) }} <span class="text-sm font-normal text-slate-500">/ orang</span></p>
            @if ($tour->own_motor)<span class="badge bg-sky-500/15 text-sky-700 ring-sky-200">Bawa motor pribadi</span>@endif
            <a href="{{ route('tour-booking.create', ['slug' => $tour->slug]) }}" class="btn btn-primary w-full">Pesan Sekarang</a>
            <p class="text-xs text-slate-500">Tidak ada jadwal open trip? Pilih <b class="text-slate-700">Private Trip</b> dan tentukan tanggal sendiri.</p>
            <a href="{{ wa_link("Halo Admin,\n\nSaya tertarik dengan paket wisata:\n\n".$tour->name) }}" target="_blank" rel="noopener" class="btn btn-ghost w-full"><x-icon name="message-circle" class="size-4" /> Tanya via WhatsApp</a>
            @include('partials.policies', ['kind' => 'trip'])
        </div>
    </aside>
</div>
@if ($others->isNotEmpty())
    <div class="container-x pb-14"><h2 class="mb-5 text-xl font-bold">Paket Lainnya</h2>
        <div class="grid gap-5 md:grid-cols-3">@foreach ($others as $o)
            <a href="{{ route('tours.show', $o->slug) }}" class="card overflow-hidden transition hover:border-brand/40"><img src="{{ $o->image_url }}" alt="{{ $o->name }}" loading="lazy" class="aspect-[16/9] w-full object-cover"><div class="p-4"><p class="font-semibold">{{ $o->name }}</p><p class="text-sm text-brand-ink">{{ rupiah($o->price) }}</p></div></a>
        @endforeach</div></div>
@endif
@endsection
