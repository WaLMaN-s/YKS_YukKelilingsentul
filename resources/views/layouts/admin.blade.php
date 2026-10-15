@php
    $siteName = setting('site_name', config('app.name'));
    $u = auth()->user();
    $can = fn (...$p) => $u->hasAnyPermission($p);
    $groups = [
        [null, [['Dashboard', 'admin.dashboard', 'admin.dashboard', 'layout-dashboard', ['dashboard']]]],
        ['Data Master', [
            ['Motor', 'admin.motor.index', 'admin.motor.*', 'bike', ['motor']],
            ['Kategori Motor', 'admin.kategori-motor.index', 'admin.kategori-motor.*', 'layers', ['motor']],
            ['Lokasi', 'admin.lokasi.index', 'admin.lokasi.*', 'map-pin', ['motor']],
            ['Paket Jeep', 'admin.paket-kendaraan.index', 'admin.paket-kendaraan.*', 'truck', ['motor']],
            ['Add-on', 'admin.addon.index', 'admin.addon.*', 'package', ['motor']],
            ['Paket Wisata', 'admin.paket-wisata.index', 'admin.paket-wisata.*', 'map', ['tour']],
            ['Jadwal Wisata', 'admin.jadwal-wisata.index', 'admin.jadwal-wisata.*', 'calendar-days', ['tour']],
            ['Customer', 'admin.customer.index', 'admin.customer.*', 'users', ['customer']],
        ]],
        ['Transaksi', [
            ['Penyewaan', 'admin.penyewaan.index', 'admin.penyewaan.*', 'receipt', ['rental', 'pickup', 'return']],
            ['Booking Wisata', 'admin.booking-wisata.index', 'admin.booking-wisata.*', 'flag', ['tour']],
            ['Pembayaran', 'admin.pembayaran.index', 'admin.pembayaran.*', 'wallet', ['payment']],
            ['Metode Pembayaran', 'admin.metode-pembayaran.index', 'admin.metode-pembayaran.*', 'credit-card', ['payment']],
            ['Promo', 'admin.promo.index', 'admin.promo.*', 'percent', ['promo']],
            ['Review', 'admin.review.index', 'admin.review.*', 'star', ['review']],
            ['Galeri', 'admin.galeri.index', 'admin.galeri.*', 'image', ['gallery']],
        ]],
        ['Laporan', [['Laporan Transaksi', 'admin.laporan.index', 'admin.laporan.*', 'chart-column', ['report']]]],
        ['Sistem', [
            ['Admin Users', 'admin.admin-users.index', 'admin.admin-users.*', 'user-cog', ['users']],
            ['Pengaturan', 'admin.pengaturan.edit', 'admin.pengaturan.*', 'settings', ['settings']],
        ]],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') | Admin {{ $siteName }}</title>
    <link rel="icon" href="{{ media_url(setting('favicon'), 'images/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen" x-data="{ drawer: false }" @keydown.escape.window="drawer = false">

{{-- Sidebar --}}
<div x-show="drawer" x-cloak class="fixed inset-0 z-40 bg-black/70 lg:hidden" @click="drawer = false"></div>
<aside class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col overflow-y-auto border-r border-slate-200 bg-navy-900 p-4 transition-transform duration-200 lg:translate-x-0"
       :class="drawer && '!translate-x-0'" aria-label="Sidebar admin">
    <a href="{{ route('admin.dashboard') }}" class="mb-2 flex items-center gap-3 px-2 py-2">
        <img src="{{ media_url(setting('logo'), 'images/logo.svg') }}" alt="" class="size-10 rounded-full">
        <span class="leading-tight"><b class="block text-sm uppercase text-slate-900">{{ $siteName }}</b><span class="text-[10px] text-slate-500">Dashboard Admin</span></span>
    </a>
    <nav class="flex-1">
        @foreach ($groups as [$title, $items])
            @php $visible = collect($items)->filter(fn ($i) => $can(...$i[4])); @endphp
            @if ($visible->isNotEmpty())
                @if ($title)<p class="side-title">{{ $title }}</p>@endif
                @foreach ($visible as [$label, $route, $pattern, $icon])
                    <a href="{{ route($route) }}" class="side-link {{ request()->routeIs($pattern) ? 'active' : '' }}"><x-icon :name="$icon" class="size-[18px]" /> {{ $label }}</a>
                @endforeach
            @endif
        @endforeach
    </nav>
    <div class="mt-4 space-y-1 border-t border-slate-200 pt-4">
        <a href="{{ route('home') }}" class="side-link"><x-icon name="external-link" class="size-[18px]" /> Lihat Website</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="side-link w-full text-rose-700"><x-icon name="log-out" class="size-[18px]" /> Logout</button></form>
    </div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-navy-950/90 px-4 backdrop-blur sm:px-6">
        <div class="flex items-center gap-2">
            <button type="button" class="flex size-10 items-center justify-center rounded-full hover:bg-slate-900/5 lg:hidden" @click="drawer = true" aria-label="Buka menu"><x-icon name="menu" /></button>
            <h2 class="hidden text-sm font-semibold text-slate-600 sm:block">@yield('title', 'Dashboard')</h2>
        </div>
        <div class="flex items-center gap-2">
            @include('partials.notifications')
            <div class="flex items-center gap-2 rounded-full border border-slate-200 py-1 pl-1 pr-3">
                <span class="flex size-8 items-center justify-center rounded-full bg-brand text-sm font-bold text-ink">{{ \Illuminate\Support\Str::of($u->name)->substr(0, 1)->upper() }}</span>
                <span class="hidden leading-tight sm:block"><b class="block text-xs text-slate-900">{{ $u->name }}</b><span class="text-[10px] text-slate-500">{{ $u->role?->name }}</span></span>
            </div>
        </div>
    </header>

    <main class="p-4 sm:p-6">
        @if ($errors->any() && ! request()->routeIs('admin.dashboard'))
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-500/10 p-4 text-sm text-rose-700" role="alert">
                <p class="font-semibold">Periksa kembali isian Anda:</p>
                <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="px-6 pb-6 text-xs text-slate-500">{{ $siteName }} · Dashboard Admin · © {{ date('Y') }}</footer>
</div>

@include('partials.toast')
@include('partials.confirm-modal')
@stack('scripts')
</body>
</html>
