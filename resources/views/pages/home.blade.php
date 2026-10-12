@extends('layouts.app')

@section('description', 'Sewa motor trail mudah, cepat, aman, dan terpercaya di Sentul. Pilih motor, tentukan tanggal, booking online, dan nikmati jalur offroad bersama guide berpengalaman.')

@section('content')
{{-- HERO --}}
<section class="relative isolate overflow-hidden">
    <picture>
        <source type="image/webp" sizes="100vw" srcset="{{ asset('images/hero-960.webp') }} 960w, {{ asset('images/hero-1280.webp') }} 1280w, {{ asset('images/hero-1672.webp') }} 1672w">
        <img src="{{ asset('images/hero.png') }}" alt="" fetchpriority="high" decoding="async" class="absolute inset-0 -z-10 h-full w-full object-cover">
    </picture>
    <div class="absolute inset-0 -z-10 bg-gradient-to-r from-navy-950 via-navy-950/80 to-navy-950/20"></div>
    <div class="absolute inset-x-0 bottom-0 -z-10 h-32 bg-gradient-to-t from-navy-950 to-transparent"></div>

    <div class="container-x grid gap-10 py-16 sm:py-24 lg:grid-cols-[1.4fr_.6fr] lg:py-28">
        <div>
            <p class="text-sm font-semibold italic text-brand-ink">Jelajahi Alam, Rasakan Adrenalinnya</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-[1.05] sm:text-6xl">
                Jelajahi Lebih Bebas<br>
                <span class="italic text-brand-ink">Dengan Motor Pilihanmu</span>
            </h1>
            <p class="mt-5 max-w-xl text-base text-slate-600">
                Sewa motor mudah, cepat, aman, dan terpercaya.<br class="hidden sm:block">
                Siap menemani perjalananmu ke mana saja.
            </p>

            <form action="{{ route('motors.index') }}" method="GET" class="mt-8 grid max-w-3xl gap-3 rounded-3xl border border-slate-200 bg-navy-900/85 p-4 shadow-2xl backdrop-blur sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                <div>
                    <label for="home-lokasi" class="label">Lokasi</label>
                    <select id="home-lokasi" name="lokasi" class="input" aria-label="Lokasi pengambilan">
                        @foreach ($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label for="home-start" class="label">Tanggal Mulai</label>
                    <input id="home-start" name="start" type="date" class="input" min="{{ today()->toDateString() }}" value="{{ today()->addDay()->toDateString() }}" required>
                </div>
                <div>
                    <label for="home-end" class="label">Tanggal Kembali</label>
                    <input id="home-end" name="end" type="date" class="input" min="{{ today()->addDay()->toDateString() }}" value="{{ today()->addDays(2)->toDateString() }}" required>
                </div>
                <button class="btn btn-primary h-[42px] sm:col-span-2 lg:col-span-1"><x-icon name="search" class="size-4" /> Cari Motor</button>
            </form>
        </div>

        <ul class="hidden space-y-3 self-center lg:block">
            @foreach ([['shield-check', 'Motor Terawat', '& Siap Jalur'], ['user', 'Guide Profesional', 'Berpengalaman'], ['map', 'Jalur Variatif', 'Alam & Perbukitan'], ['clock', 'Proses Mudah', '& Cepat']] as [$icon, $t1, $t2])
                <li class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-navy-900/70 p-3 backdrop-blur">
                    <span class="flex size-11 items-center justify-center rounded-full border border-brand/40 bg-brand/10 text-brand-ink"><x-icon :name="$icon" /></span>
                    <span class="text-sm leading-tight"><b class="block text-slate-900">{{ $t1 }}</b><span class="text-slate-500">{{ $t2 }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- SHORTCUT --}}
<section class="border-y border-slate-200 bg-navy-900">
    <div class="container-x grid grid-cols-2 gap-px py-2 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['bike', 'Sewa Motor', $unitCounts['trail'].' unit motor trail', route('motors.index', ['jenis' => 'trail'])],
            ['truck', 'Sewa Jeep', $unitCounts['jeep'].' unit, maks 4 orang', route('motors.index', ['jenis' => 'jeep'])],
            ['map', 'Paket Wisata', 'Jelajahi rute terbaik Sentul', route('tours.index')],
            ['image', 'Galeri', 'Lihat keseruan kami', route('gallery')],
            ['map-pin', 'Basecamp', 'Gunung Pancar', route('contact')],
            ['circle-check', 'FAQ & Kebijakan', 'Jadwal, batal, bayar', route('faq')],
        ] as [$icon, $title, $sub, $url])
            <a href="{{ $url }}" class="group flex flex-col items-center gap-1 rounded-2xl px-3 py-5 text-center transition hover:bg-slate-900/5">
                <x-icon :name="$icon" class="size-8 text-brand-ink transition group-hover:scale-110" />
                <span class="mt-1 text-sm font-semibold text-slate-900">{{ $title }}</span>
                <span class="text-xs text-slate-500">{{ $sub }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- DUA LAYANAN UTAMA --}}
<section class="bg-slate-100 py-10 text-slate-900">
    <div class="container-x grid gap-6 md:grid-cols-2">
        @php $firstTour = $tours->first(); @endphp
        <article class="flex overflow-hidden rounded-2xl bg-white shadow-lg">
            <img src="{{ $motors->first()?->image_url ?? asset('images/placeholder.svg') }}" alt="" class="hidden w-2/5 object-cover sm:block">
            <div class="p-6">
                <span class="badge bg-brand/25 text-amber-800 ring-brand/40">RENTAL MOTOR</span>
                <h2 class="mt-3 text-2xl font-bold !text-slate-900">Sewa Motor Trail</h2>
                <p class="mt-2 text-sm text-slate-600">Pilih motor favoritmu dan jelajahi jalur offroad Sentul dengan bebas.</p>
                <a href="{{ route('motors.index') }}" class="btn btn-primary btn-sm mt-4">Lihat Motor <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </article>
        <article class="flex overflow-hidden rounded-2xl bg-white shadow-lg">
            <img src="{{ $firstTour?->image_url ?? asset('images/placeholder.svg') }}" alt="" class="hidden w-2/5 object-cover sm:block">
            <div class="p-6">
                <span class="badge bg-brand/25 text-amber-800 ring-brand/40">PAKET WISATA</span>
                <h2 class="mt-3 text-2xl font-bold !text-slate-900">Paket Adventure</h2>
                <p class="mt-2 text-sm text-slate-600">Nikmati paket wisata lengkap dengan motor, guide, dan rute menarik.</p>
                <a href="{{ route('tours.index') }}" class="btn btn-primary btn-sm mt-4">Lihat Paket <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </article>
    </div>
</section>

{{-- MOTOR --}}
<section class="py-14">
    <div class="container-x">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold sm:text-3xl">Motor Trail Tersedia <span class="text-base font-normal text-slate-500">· {{ $unitCounts['trail'] }} unit</span></h2>
                <p class="mt-1 text-sm text-slate-500">Motor terawat, performa maksimal, siap menemani petualanganmu.</p>
            </div>
            <a href="{{ route('motors.index', ['jenis' => 'trail']) }}" class="hidden items-center gap-1 text-sm font-semibold text-brand-ink hover:underline sm:flex">Lihat Semua Motor <x-icon name="arrow-right" class="size-4" /></a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @forelse ($motors as $motor)
                <x-motor-card :motor="$motor" />
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-10 text-center text-slate-500">Belum ada motor. Tambahkan dari dashboard admin.</p>
            @endforelse
        </div>
        <a href="{{ route('motors.index') }}" class="btn btn-ghost mt-6 w-full sm:hidden">Lihat Semua Motor</a>
    </div>
</section>

{{-- JEEP --}}
@if ($jeeps->isNotEmpty())
<section class="bg-navy-900 py-14">
    <div class="container-x">
        <div class="mb-6 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold sm:text-3xl">Jeep Adventure</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $unitCounts['jeep'] }} unit siap jalan · maks 4 orang per jeep · pilih paket Jeep saja atau Jeep + Trekking.</p>
            </div>
            <a href="{{ route('motors.index', ['jenis' => 'jeep']) }}" class="hidden items-center gap-1 text-sm font-semibold text-brand-ink hover:underline sm:flex">Lihat Semua Jeep <x-icon name="arrow-right" class="size-4" /></a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">@foreach ($jeeps as $jeep)<x-motor-card :motor="$jeep" />@endforeach</div>
    </div>
</section>
@endif

{{-- PAKET WISATA --}}
@if ($tours->isNotEmpty())
<section class="bg-navy-900 py-14">
    <div class="container-x">
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold sm:text-3xl">Paket Wisata Populer</h2>
                <p class="mt-1 text-sm text-slate-500">Rute pilihan lengkap dengan motor, guide, dan dokumentasi.</p>
            </div>
            <a href="{{ route('tours.index') }}" class="hidden text-sm font-semibold text-brand-ink hover:underline sm:block">Semua paket →</a>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            @foreach ($tours as $tour)
                <a href="{{ route('tours.show', $tour->slug) }}" class="group card overflow-hidden transition hover:-translate-y-1 hover:border-brand/40">
                    <img src="{{ $tour->image_url }}" alt="{{ $tour->name }}" loading="lazy" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="p-5">
                        <h3 class="font-semibold">{{ $tour->name }}</h3>
                        <p class="mt-1 flex items-center gap-1 text-xs text-slate-500"><x-icon name="clock" class="size-3.5" /> {{ $tour->duration }} · <x-icon name="map-pin" class="size-3.5" /> {{ $tour->destination }}</p>
                        <p class="mt-3 text-lg font-bold text-brand-ink">{{ rupiah($tour->price) }} <span class="text-xs font-normal text-slate-500">/ orang</span></p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- GALERI --}}
@if ($galleries->isNotEmpty())
<section class="bg-slate-100 py-10 text-slate-900">
    <div class="container-x">
        <div class="mb-4 flex items-end justify-between">
            <div>
                <h2 class="text-xl font-bold !text-slate-900">Galeri Kegiatan</h2>
                <p class="text-sm text-slate-600">Lihat keseruan perjalanan bersama kami.</p>
            </div>
            <a href="{{ route('gallery') }}" class="text-sm font-semibold text-amber-700 hover:underline">Lihat semua →</a>
        </div>
        <div class="flex snap-x gap-3 overflow-x-auto pb-2">
            @foreach ($galleries as $g)
                <img src="{{ $g->image_url }}" alt="{{ $g->title }}" loading="lazy" class="h-32 w-48 shrink-0 snap-start rounded-xl object-cover shadow sm:h-36 sm:w-56">
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- BASECAMP --}}
<section class="py-14">
    <div class="container-x grid items-center gap-8 lg:grid-cols-2">
        <div>
            <p class="text-sm font-semibold italic text-brand-ink">Basecamp</p>
            <h2 class="mt-1 text-2xl font-bold sm:text-3xl">Gunung Pancar</h2>
            <p class="mt-2 max-w-md text-sm text-slate-500">Titik kumpul dan pengambilan kendaraan. Datang lebih awal, siapkan diri, dan istirahat dengan nyaman.</p>
            <a href="{{ route('contact') }}" class="btn btn-ghost btn-sm mt-4">Lihat lokasi & kontak</a>
        </div>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($facilities as $f)
                <li class="card flex items-center gap-3 p-4 text-sm"><x-icon name="circle-check" class="size-5 text-brand-ink" /> {{ $f }}</li>
            @endforeach
        </ul>
    </div>
</section>

{{-- PETA LOKASI --}}
<section class="container-x py-14">
    @include('partials.location-map')
</section>

{{-- REVIEW --}}
<section class="py-14">
    <div class="container-x">
        <h2 class="text-2xl font-bold sm:text-3xl">Apa Kata Mereka?</h2>
        <p class="mt-1 text-sm text-slate-500">Pengalaman nyata dari pelanggan kami.</p>
        <div class="mt-6 grid gap-5 md:grid-cols-3">
            @forelse ($reviews as $r)
                <figure class="card card-pad">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-full bg-brand font-bold text-ink">{{ \Illuminate\Support\Str::of($r->user->name)->substr(0, 1)->upper() }}</span>
                        <div>
                            <figcaption class="text-sm font-semibold text-slate-900">{{ $r->user->name }}</figcaption>
                            <x-stars :rating="$r->rating" />
                        </div>
                    </div>
                    <blockquote class="mt-3 text-sm text-slate-600">"{{ $r->comment }}"</blockquote>
                    <p class="mt-3 text-xs text-slate-500">Menyewa {{ $r->motorcycle->name }}</p>
                </figure>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada ulasan. Jadilah yang pertama setelah menyelesaikan rental!</p>
            @endforelse
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="pb-16">
    <div class="container-x">
        <div class="relative overflow-hidden rounded-3xl border border-brand/30 bg-gradient-to-r from-navy-800 to-navy-700 p-8 sm:p-12">
            <p class="text-sm font-semibold italic text-brand-ink">More Ride, More Story</p>
            <h2 class="mt-2 max-w-xl text-2xl font-bold sm:text-3xl">Siap berpetualang di jalur Sentul?</h2>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="{{ route('motors.index') }}" class="btn btn-primary">Pesan Sekarang</a>
                <a href="{{ wa_link('Halo Admin, saya ingin tanya paket sewa motor.') }}" target="_blank" rel="noopener" class="btn btn-ghost"><x-icon name="message-circle" class="size-4" /> Tanya via WhatsApp</a>
            </div>
        </div>
    </div>
</section>
@endsection
