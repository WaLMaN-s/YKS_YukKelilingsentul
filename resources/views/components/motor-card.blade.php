@props(['motor', 'start' => null, 'end' => null, 'location' => null])
@php
    $isAvailable = $motor->status === 'available';
    $params = array_filter(['motor' => $motor->slug, 'start' => $start, 'end' => $end, 'lokasi' => $location]);
@endphp
<article class="group card overflow-hidden transition duration-300 hover:-translate-y-1 hover:border-brand/40">
    <a href="{{ route('motors.show', $motor->slug) }}" class="relative block overflow-hidden">
        <img src="{{ $motor->image_url }}" alt="{{ $motor->name }}" loading="lazy" class="aspect-[16/10] w-full object-cover transition duration-500 group-hover:scale-105">
        <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
            @if ($isAvailable)
                <span class="badge bg-emerald-500/20 text-emerald-700 ring-emerald-200 backdrop-blur">TERSEDIA</span>
            @else
                <span class="badge bg-amber-500/20 text-amber-700 ring-amber-200 backdrop-blur">{{ strtoupper($motor->status_label) }}</span>
            @endif
            @if ($motor->is_popular)<span class="badge bg-brand/90 text-ink ring-brand">POPULER</span>@endif
            @if ($motor->is_promo)<span class="badge bg-rose-500/90 text-white ring-rose-400">PROMO</span>@endif
        </div>
    </a>
    <div class="p-3 sm:p-4">
        <h3 class="text-sm font-semibold sm:text-base"><a href="{{ route('motors.show', $motor->slug) }}" class="hover:text-brand-ink">{{ $motor->name }}</a></h3>
        <ul class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
            @if ($motor->isJeep())
                <li class="flex items-center gap-1"><x-icon name="users" class="size-3.5" />Maks {{ $motor->capacity }} orang</li>
                <li class="flex items-center gap-1"><x-icon name="layers" class="size-3.5" />{{ $motor->stock }} unit</li>
            @else
                <li class="flex items-center gap-1"><x-icon name="gauge" class="size-3.5" />{{ $motor->cc }}cc</li>
                <li class="flex items-center gap-1"><x-icon name="cog" class="size-3.5" />{{ $motor->transmission === 'Manual' ? 'Manual' : 'Automatic' }}</li>
                <li class="flex items-center gap-1"><x-icon name="layers" class="size-3.5" />{{ $motor->stock }} unit</li>
            @endif
            @if (($motor->avg_rating ?? null))<li class="flex items-center gap-1 text-brand-ink"><x-icon name="star" class="size-3.5" />{{ number_format($motor->avg_rating, 1) }}</li>@endif
        </ul>
        <p class="mt-2 text-base font-bold text-brand-ink sm:mt-3 sm:text-lg">
            @if ($motor->isJeep())<span class="text-xs font-normal text-slate-500">Mulai</span> {{ rupiah($motor->price_daily) }} <span class="text-xs font-normal text-slate-500">/ jeep</span>
            @else{{ rupiah($motor->price_daily) }} <span class="text-xs font-normal text-slate-500">/ hari</span>@endif
        </p>
        <div class="mt-2 grid grid-cols-2 gap-1.5 sm:mt-3 sm:gap-2">
            <a href="{{ route('motors.show', $motor->slug) }}" class="btn btn-ghost btn-sm px-2">
                <span class="sm:hidden">Detail</span><span class="hidden sm:inline">Lihat Detail</span>
            </a>
            @if ($isAvailable)
                <a href="{{ route('booking.create', $params) }}" class="btn btn-primary btn-sm px-2">
                    <span class="sm:hidden">Sewa</span><span class="hidden sm:inline">Sewa Sekarang</span>
                </a>
            @else
                <span class="btn btn-dark btn-sm cursor-not-allowed px-2 opacity-60">
                    <span class="sm:hidden">Kosong</span><span class="hidden sm:inline">Tidak tersedia</span>
                </span>
            @endif
        </div>
    </div>
</article>
