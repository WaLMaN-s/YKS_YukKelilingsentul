@extends('layouts.app')
@section('title', $motor->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags($motor->description ?? 'Sewa '.$motor->name.' di Sentul.'), 155))
@section('og_image', $motor->image_url)

@section('content')
@php
    $isAvailable = $motor->status === 'available';
    $waText = "Halo Admin,\n\nSaya ingin menyewa:\n\nMotor:\n".$motor->name;
@endphp
<div class="container-x py-8">
    <nav class="mb-5 text-xs text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('home') }}" class="hover:text-brand-ink">Home</a> / <a href="{{ route('motors.index') }}" class="hover:text-brand-ink">Motor</a> / <span class="text-slate-700">{{ $motor->name }}</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-[1.3fr_.7fr]">
        <div class="space-y-8">
            {{-- Galeri --}}
            <div x-data="{ active: @js(media_url($motor->images->first()?->path)) }">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-navy-800">
                    <img :src="active" alt="{{ $motor->name }}" class="aspect-[16/10] w-full object-cover">
                </div>
                @if ($motor->images->count() > 1)
                    <div class="mt-3 grid grid-cols-5 gap-2">
                        @foreach ($motor->images as $img)
                            <button type="button" @click="active = @js($img->url)" class="overflow-hidden rounded-xl border-2 border-transparent transition hover:border-brand" :class="active === @js($img->url) && '!border-brand'">
                                <img src="{{ $img->url }}" alt="Foto {{ $loop->iteration }} {{ $motor->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <section>
                <h2 class="text-xl font-bold">Deskripsi</h2>
                <p class="mt-2 leading-relaxed text-slate-600">{{ $motor->description }}</p>
            </section>

            <section class="grid gap-6 md:grid-cols-2">
                <div class="card card-pad">
                    <h2 class="text-lg font-bold">Spesifikasi</h2>
                    <dl class="mt-3 divide-y divide-slate-200 text-sm">
                        @foreach ([
                            'Jenis' => $motor->type_label, 'Merek' => $motor->brand, 'Model' => $motor->model,
                            'Mesin' => $motor->isJeep() ? null : $motor->cc.' CC', 'Transmisi' => $motor->isJeep() ? null : $motor->transmission,
                            'Kapasitas' => $motor->isJeep() ? 'Maks '.$motor->capacity.' orang / jeep' : $motor->capacity.' Orang',
                            'Jumlah unit' => $motor->stock.' unit', 'Tahun' => $motor->year, 'Warna' => $motor->color, 'Kategori' => $motor->category?->name,
                        ] as $k => $v)
                            @if ($v)<div class="flex justify-between py-2"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-medium text-slate-900">{{ $v }}</dd></div>@endif
                        @endforeach
                    </dl>
                </div>
                <div class="card card-pad">
                    <h2 class="text-lg font-bold">Fasilitas</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @forelse ($motor->facilities_list as $f)
                            <li class="flex items-center gap-2 text-slate-700"><x-icon name="check" class="size-4 text-emerald-600" /> {{ $f }}</li>
                        @empty
                            <li class="text-slate-500">Belum ada data fasilitas.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            @if ($motor->terms)
                <section class="card card-pad">
                    <h2 class="text-lg font-bold">Syarat Sewa</h2>
                    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-600">
                        @foreach (preg_split('/\r\n|\r|\n/', $motor->terms) as $line)@if (trim($line))<li>{{ $line }}</li>@endif @endforeach
                    </ul>
                </section>
            @endif

            {{-- Kalender --}}
            <section class="card card-pad" x-data="availabilityCalendar(@js($booked))">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">Kalender Ketersediaan</h2>
                    <div class="flex items-center gap-1">
                        <button type="button" class="flex size-8 items-center justify-center rounded-full hover:bg-slate-900/5" @click="move(-1)" aria-label="Bulan sebelumnya"><x-icon name="chevron-left" class="size-4" /></button>
                        <span class="min-w-32 text-center text-sm font-medium capitalize" x-text="title"></span>
                        <button type="button" class="flex size-8 items-center justify-center rounded-full hover:bg-slate-900/5" @click="move(1)" aria-label="Bulan berikutnya"><x-icon name="chevron-right" class="size-4" /></button>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs text-slate-500">
                    @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $d)<div class="py-1">{{ $d }}</div>@endforeach
                </div>
                <div class="grid grid-cols-7 gap-1 text-center text-sm">
                    <template x-for="(c, i) in cells" :key="i">
                        <div class="aspect-square" >
                            <template x-if="c">
                                <div class="flex size-full items-center justify-center rounded-lg"
                                     :class="c.booked ? 'bg-rose-500/20 text-rose-700 line-through' : (c.past ? 'text-slate-600' : 'bg-emerald-500/10 text-emerald-700')"
                                     x-text="c.d"></div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="mt-3 flex gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-500/30"></span> Tersedia</span>
                    <span class="flex items-center gap-1.5"><span class="size-3 rounded bg-rose-500/40"></span> Penuh (semua unit terbooking)</span>
                </div>
            </section>

            @if ($reviews->isNotEmpty())
                <section>
                    <h2 class="text-xl font-bold">Ulasan Penyewa <span class="ml-2 text-sm font-normal text-slate-500">★ {{ $avgRating }}</span></h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        @foreach ($reviews as $r)
                            <figure class="card card-pad">
                                <div class="flex items-center justify-between"><figcaption class="text-sm font-semibold">{{ $r->user->name }}</figcaption><x-stars :rating="$r->rating" /></div>
                                <blockquote class="mt-2 text-sm text-slate-600">{{ $r->comment }}</blockquote>
                            </figure>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        {{-- Panel harga --}}
        <aside>
            <div class="card card-pad space-y-5 lg:sticky lg:top-24">
                <div>
                    <div class="flex flex-wrap gap-1.5">
                        @if ($isAvailable)<span class="badge bg-emerald-500/20 text-emerald-700 ring-emerald-200">TERSEDIA</span>@else<span class="badge bg-amber-500/20 text-amber-700 ring-amber-200">{{ strtoupper($motor->status_label) }}</span>@endif
                        @if ($motor->is_popular)<span class="badge bg-brand/90 text-ink ring-brand">POPULER</span>@endif
                        @if ($motor->is_promo)<span class="badge bg-rose-500/90 text-white ring-rose-400">PROMO</span>@endif
                    </div>
                    <h1 class="mt-3 text-2xl font-bold">{{ $motor->name }}</h1>
                    @if ($motor->isJeep())
                    <p class="mt-2 text-sm text-slate-500">Harga per jeep · maks {{ $motor->capacity }} orang</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($packages as $pk)
                            <a href="{{ $isAvailable ? route('booking.create', ['motor' => $motor->slug, 'paket' => $pk->id]) : '#' }}" class="block rounded-xl border border-slate-200 bg-navy-900 p-3 transition hover:border-brand/50">
                                <span class="flex items-center justify-between gap-3"><b class="text-sm text-slate-900">{{ $pk->name }}</b><b class="text-brand-ink">{{ rupiah($pk->price) }}</b></span>
                                @if ($pk->includes)<span class="mt-1 block text-xs text-slate-500">{{ implode(' · ', $pk->includes) }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                    <dl class="mt-3 divide-y divide-slate-200 rounded-xl bg-navy-900 px-4 text-sm">
                        <div class="flex justify-between py-2.5"><dt class="text-slate-500">Deposit / jeep</dt><dd class="font-semibold">{{ rupiah($motor->deposit ?: setting('default_deposit', 0)) }}</dd></div>
                    </dl>
                @else
                    <p class="mt-2 text-3xl font-extrabold text-brand-ink">{{ rupiah($motor->price_daily) }} <span class="text-sm font-normal text-slate-500">/ hari</span></p>
                    <dl class="mt-3 divide-y divide-slate-200 rounded-xl bg-navy-900 px-4 text-sm">
                        <div class="flex justify-between py-2.5"><dt class="text-slate-500">Harga mingguan</dt><dd class="font-semibold">{{ $motor->price_weekly ? rupiah($motor->price_weekly) : '-' }}</dd></div>
                        <div class="flex justify-between py-2.5"><dt class="text-slate-500">Harga bulanan</dt><dd class="font-semibold">{{ $motor->price_monthly ? rupiah($motor->price_monthly) : '-' }}</dd></div>
                        <div class="flex justify-between py-2.5"><dt class="text-slate-500">Deposit</dt><dd class="font-semibold">{{ rupiah($motor->deposit ?: setting('default_deposit', 0)) }}</dd></div>
                    </dl>
                @endif
                @if ($addons->isNotEmpty())
                    <div class="rounded-xl bg-navy-900 p-3 text-xs text-slate-500">
                        <p class="mb-1 font-semibold text-slate-700">Add-on tersedia</p>
                        @foreach ($addons as $ad)<p class="flex justify-between"><span>{{ $ad->name }}</span><span>{{ rupiah($ad->price) }} {{ $ad->per === 'person' ? '/ orang' : '' }}</span></p>@endforeach
                    </div>
                @endif
                @if ($isAvailable)
                    <a href="{{ route('booking.create', ['motor' => $motor->slug]) }}" class="btn btn-primary w-full">Sewa Sekarang</a>
                @else
                    <p class="rounded-xl bg-amber-500/10 p-3 text-sm text-amber-700">Motor ini sedang tidak tersedia untuk disewa.</p>
                @endif
                <a href="{{ wa_link($waText) }}" target="_blank" rel="noopener" class="btn btn-ghost w-full"><x-icon name="message-circle" class="size-4" /> Tanya via WhatsApp</a>
                @include('partials.policies', ['kind' => 'sewa'])
            </div>
        </aside>
    </div>

    @if ($related->isNotEmpty())
        <section class="mt-14">
            <h2 class="mb-5 text-xl font-bold">Motor Lainnya</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach ($related as $m)<x-motor-card :motor="$m" />@endforeach</div>
        </section>
    @endif
</div>
@endsection
