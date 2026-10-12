@extends('layouts.app')

@section('content')
@php
    $menu = [
        ['Dashboard', 'dashboard', 'dashboard', 'layout-dashboard'],
        ['Profil Saya', 'dashboard.profile', 'dashboard.profile*', 'user'],
        ['Penyewaan Saya', 'dashboard.bookings', 'dashboard.bookings*', 'bike'],
        ['Wisata Saya', 'dashboard.tours', 'dashboard.tours', 'map'],
        ['Pembayaran', 'dashboard.payments', 'dashboard.payments', 'wallet'],
        ['Review', 'dashboard.reviews', 'dashboard.reviews', 'star'],
    ];
@endphp
<div class="container-x grid gap-8 py-8 lg:grid-cols-[16rem_1fr]">
    <aside class="min-w-0">
        <div class="card card-pad lg:sticky lg:top-24">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-full bg-brand font-bold text-ink">{{ \Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p></div>
            </div>

            {{-- HP: petak menu 3 kolom supaya tidak perlu digeser ke samping --}}
            <nav class="grid grid-cols-3 gap-2 lg:hidden" aria-label="Menu akun">
                @foreach ($menu as [$label, $route, $pattern, $icon])
                    @php $active = request()->routeIs($pattern); @endphp
                    <a href="{{ route($route) }}"
                       @if ($active) aria-current="page" @endif
                       class="flex flex-col items-center justify-center gap-1.5 rounded-2xl border px-2 py-3 text-center text-[11px] font-medium leading-tight transition {{ $active ? 'border-slate-200 bg-brand/25 text-brand-ink' : 'border-slate-200 bg-navy-900 text-slate-600 hover:border-brand/40 hover:text-slate-900' }}">
                        <x-icon :name="$icon" class="size-5" />
                        <span>{{ $label }}</span>
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="flex w-full flex-col items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-navy-900 px-2 py-3 text-center text-[11px] font-medium leading-tight text-rose-700 transition hover:border-brand/40">
                        <x-icon name="log-out" class="size-5" />
                        <span>Logout</span>
                    </button>
                </form>
            </nav>

            {{-- Desktop: daftar vertikal di sidebar --}}
            <nav class="hidden gap-1 lg:flex lg:flex-col" aria-label="Menu dashboard">
                @foreach ($menu as [$label, $route, $pattern, $icon])
                    <a href="{{ route($route) }}" class="side-link {{ request()->routeIs($pattern) ? 'active' : '' }}"><x-icon :name="$icon" class="size-4" /> {{ $label }}</a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="side-link w-full text-rose-700 hover:!text-rose-700"><x-icon name="log-out" class="size-4" /> Logout</button>
                </form>
            </nav>
        </div>
    </aside>
    <section class="min-w-0">@yield('panel')</section>
</div>
@endsection
