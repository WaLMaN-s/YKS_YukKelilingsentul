@extends('layouts.app')
@section('title', 'Paket Wisata')
@section('description', 'Paket wisata motor trail di Sentul lengkap dengan motor, guide, BBM, dan dokumentasi.')
@section('content')
<section class="border-b border-slate-200 bg-navy-900 py-10">
    <div class="container-x"><h1 class="text-3xl font-bold">Paket Wisata</h1><p class="mt-1 text-sm text-slate-500">Rute terbaik Sentul bersama guide berpengalaman.</p></div>
</section>
<div class="container-x grid gap-6 py-10 md:grid-cols-2 lg:grid-cols-3">
    @forelse ($tours as $tour)
        <article class="group card overflow-hidden transition hover:-translate-y-1 hover:border-brand/40">
            <a href="{{ route('tours.show', $tour->slug) }}"><img src="{{ $tour->image_url }}" alt="{{ $tour->name }}" loading="lazy" class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-105"></a>
            <div class="p-5">
                <h2 class="text-lg font-semibold"><a href="{{ route('tours.show', $tour->slug) }}" class="hover:text-brand-ink">{{ $tour->name }}</a></h2>
                @if ($tour->own_motor)<span class="badge mt-1 bg-sky-500/15 text-sky-700 ring-sky-200">Bawa motor pribadi</span>@endif
                <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500"><x-icon name="clock" class="size-3.5" /> {{ $tour->duration }} · {{ $tour->upcoming_schedules ? $tour->upcoming_schedules.' jadwal open trip' : 'Private trip kapan saja' }}</p>
                <ul class="mt-3 space-y-1 text-sm text-slate-600">
                    @foreach (array_slice($tour->facilities ?? [], 0, 4) as $f)<li class="flex items-center gap-2"><x-icon name="check" class="size-4 text-emerald-600" />{{ $f }}</li>@endforeach
                </ul>
                <div class="mt-4 flex items-center justify-between">
                    <p class="text-lg font-bold text-brand-ink">{{ rupiah($tour->price) }}<span class="text-xs font-normal text-slate-500"> / orang</span></p>
                    <a href="{{ route('tours.show', $tour->slug) }}" class="btn btn-primary btn-sm">Lihat Detail</a>
                </div>
            </div>
        </article>
    @empty
        <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">Belum ada paket wisata.</p>
    @endforelse
</div>
@endsection
