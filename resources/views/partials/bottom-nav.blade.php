{{--
    Navigasi bawah khusus layar kecil (gaya aplikasi, mirip Shopee).
    Hanya tampil di bawah breakpoint `lg` — di desktop menu utama/sidebar tetap dipakai.

    Pakai:
        @include('partials.bottom-nav', ['items' => [
            ['Beranda', 'home', 'home', 'home'],
            ...
        ]])
--}}
@php
    $items = $items ?? [];
    $label = $label ?? 'Navigasi utama';
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-navy-950/90 pb-1 backdrop-blur-md lg:hidden" aria-label="{{ $label }}">
    <ul class="mx-auto flex max-w-xl">
        @foreach ($items as [$text, $routeName, $pattern, $icon])
            @php $active = request()->routeIs($pattern); @endphp
            <li class="min-w-0 flex-1">
                <a href="{{ route($routeName) }}"
                   @if ($active) aria-current="page" @endif
                   class="flex flex-col items-center gap-1 px-1 pb-1.5 pt-2.5 text-[10px] font-medium transition {{ $active ? 'text-brand-ink' : 'text-slate-500 hover:text-slate-900' }}">
                    <span class="flex size-8 items-center justify-center rounded-full transition {{ $active ? 'bg-brand/25' : '' }}">
                        <x-icon :name="$icon" class="size-5" />
                    </span>
                    <span class="w-full truncate text-center leading-none">{{ $text }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
