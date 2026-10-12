@php
    $siteName = setting('site_name', config('app.name'));
    $pageTitle = trim($__env->yieldContent('title'));
    $fullTitle = $pageTitle ? $pageTitle.' | '.$siteName : $siteName.' – '.setting('tagline', 'Sewa Motor Trail & Paket Adventure');
    $desc = trim($__env->yieldContent('description')) ?: 'Sewa motor trail, paket wisata offroad, dan guide berpengalaman di Sentul. Booking online mudah, cepat, dan aman.';
    $ogImage = trim($__env->yieldContent('og_image')) ?: asset('images/hero.png');
    $nav = [
        ['Home', 'home', 'home'], ['Motor', 'motors.index', 'motors.*'], ['Paket Wisata', 'tours.index', 'tours.*'],
        ['Galeri', 'gallery', 'gallery'], ['Tentang Kami', 'about', 'about'], ['Kontak', 'contact', 'contact'],
    ];

    // Navigasi bawah untuk pelanggan (mobile) — gaya aplikasi.
    if (! auth()->check()) {
        $accountTab = ['Masuk', 'login', 'login', 'user'];
    } elseif (auth()->user()->isAdmin()) {
        $accountTab = ['Admin', 'admin.dashboard', 'admin.*', 'layout-dashboard'];
    } else {
        $accountTab = ['Akun', 'dashboard', 'dashboard*', 'user'];
    }
    $bottomNav = [
        ['Beranda', 'home', 'home', 'home'],
        ['Motor', 'motors.index', 'motors.*', 'bike'],
        ['Wisata', 'tours.index', 'tours.*', 'map'],
        ['Galeri', 'gallery', 'gallery', 'image'],
        ['Tentang', 'about', 'about', 'map-pin'],
        $accountTab,
    ];
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $desc }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $desc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ media_url(setting('favicon'), 'images/favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen">
<a href="#konten" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[90] focus:rounded-lg focus:bg-brand focus:px-4 focus:py-2 focus:text-ink">Lewati ke konten</a>

<header class="sticky top-0 z-50 border-b border-slate-200 bg-navy-950/90 backdrop-blur-md">
    <div class="container-x flex h-16 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $siteName }}">
            <img src="{{ media_url(setting('logo'), 'images/logo.svg') }}" alt="" class="size-11 rounded-full object-cover">
            <span class="hidden leading-tight sm:block">
                <span class="block text-sm font-extrabold uppercase tracking-wide text-slate-900">{{ $siteName }}</span>
                <span class="block text-[10px] text-slate-500">{{ setting('tagline', 'Sewa Motor Trail & Paket Adventure') }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex" aria-label="Menu utama">
            @foreach ($nav as [$label, $route, $pattern])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            @auth
                @include('partials.notifications')
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">Dashboard</a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">Dashboard</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">@csrf
                    <button class="btn btn-ghost btn-sm">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm hidden sm:inline-flex">Daftar</a>
            @endauth
        </div>
    </div>
</header>

<main id="konten">
    @yield('content')
</main>

<footer class="border-t border-slate-200 bg-navy-900 pb-20 lg:pb-0">
    <div class="container-x grid gap-10 py-12 md:grid-cols-4">
        <div class="md:col-span-2">
            <div class="flex items-center gap-3">
                <img src="{{ media_url(setting('logo'), 'images/logo.svg') }}" alt="" class="size-12 rounded-full">
                <div>
                    <p class="font-extrabold uppercase text-slate-900">{{ $siteName }}</p>
                    <p class="text-xs text-slate-500">{{ setting('tagline') }}</p>
                </div>
            </div>
            <p class="mt-4 max-w-md text-sm text-slate-500">{{ setting('address') }}<br>{{ setting('operating_hours') }}</p>
        </div>
        <div>
            <p class="mb-3 text-sm font-semibold text-slate-900">Menu</p>
            <ul class="space-y-2 text-sm text-slate-500">
                @foreach ($nav as [$label, $route])<li><a class="hover:text-brand-ink" href="{{ route($route) }}">{{ $label }}</a></li>@endforeach
                <li><a class="hover:text-brand-ink" href="{{ route('faq') }}">FAQ</a></li>
            </ul>
        </div>
        <div>
            <p class="mb-3 text-sm font-semibold text-slate-900">Hubungi Kami</p>
            <ul class="space-y-2 text-sm text-slate-500">
                @if (setting('whatsapp'))<li class="flex items-center gap-2"><x-icon name="phone" class="size-4" /><a class="hover:text-brand-ink" href="{{ wa_link() }}" target="_blank" rel="noopener">{{ setting('whatsapp') }}</a></li>@endif
                @if (setting('email'))<li class="flex items-center gap-2"><x-icon name="mail" class="size-4" /><a class="hover:text-brand-ink" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></li>@endif
                @if (setting('instagram'))<li class="flex items-center gap-2"><x-icon name="instagram" class="size-4" /><a class="hover:text-brand-ink" href="https://instagram.com/{{ setting('instagram') }}" target="_blank" rel="noopener">@ {{ setting('instagram') }}</a></li>@endif
                @if (setting('facebook'))<li class="flex items-center gap-2"><x-icon name="facebook" class="size-4" /><a class="hover:text-brand-ink" href="https://facebook.com/{{ setting('facebook') }}" target="_blank" rel="noopener">{{ setting('facebook') }}</a></li>@endif
                @if (setting('tiktok'))<li class="flex items-center gap-2"><x-icon name="store" class="size-4" /><a class="hover:text-brand-ink" href="https://tiktok.com/{{ '@'.setting('tiktok') }}" target="_blank" rel="noopener">TikTok @ {{ setting('tiktok') }}</a></li>@endif
            </ul>
        </div>
    </div>
    <div class="border-t border-slate-100 py-4 text-center text-xs text-slate-500">© {{ date('Y') }} {{ $siteName }}. Semua hak dilindungi.</div>
</footer>

@hasSection('bottom_nav')
    @yield('bottom_nav')
@else
    @include('partials.bottom-nav', ['items' => $bottomNav])
@endif

@if (setting('whatsapp'))
    <a href="{{ wa_link('Halo Admin, saya ingin bertanya tentang sewa motor.') }}" target="_blank" rel="noopener"
       class="fixed bottom-24 right-5 z-30 flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-3 text-sm font-semibold text-white shadow-xl shadow-emerald-900/40 transition hover:scale-105 lg:bottom-5">
        <x-icon name="message-circle" /> <span class="hidden sm:inline">Chat WhatsApp</span>
    </a>
@endif

@include('partials.toast')
@include('partials.confirm-modal')
@stack('scripts')
</body>
</html>
