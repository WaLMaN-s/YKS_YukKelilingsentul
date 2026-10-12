@extends('layouts.app')
@section('title', 'Galeri')
@section('description', 'Galeri foto motor, perjalanan, wisata, dan pelanggan Yuk Keliling Sentul.')
@section('content')
<section class="border-b border-slate-200 bg-navy-900 py-10"><div class="container-x"><h1 class="text-3xl font-bold">Galeri</h1><p class="mt-1 text-sm text-slate-500">Keseruan perjalanan bersama kami.</p></div></section>
<div class="container-x py-10" x-data="galleryFilter()" @keydown.escape.window="lightbox = null">
    <div class="mb-6 flex flex-wrap gap-2">
        <button type="button" class="btn btn-sm" :class="cat === 'semua' ? 'btn-primary' : 'btn-dark'" @click="cat = 'semua'">Semua</button>
        @foreach (\App\Models\Gallery::CATEGORIES as $key => $label)
            <button type="button" class="btn btn-sm" :class="cat === '{{ $key }}' ? 'btn-primary' : 'btn-dark'" @click="cat = '{{ $key }}'">{{ $label }}</button>
        @endforeach
    </div>
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        @forelse ($items as $item)
            <button type="button" x-show="cat === 'semua' || cat === '{{ $item->category }}'" x-transition @click="lightbox = { src: @js($item->image_url), title: @js($item->title) }"
                    class="group relative overflow-hidden rounded-2xl border border-slate-200 text-left">
                <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105">
                <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3 text-xs font-medium text-white">{{ $item->title }}</span>
            </button>
        @empty
            <p class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center text-slate-500">Belum ada foto.</p>
        @endforelse
    </div>
    <div x-show="lightbox" x-cloak x-transition.opacity class="fixed inset-0 z-[75] flex items-center justify-center bg-black/90 p-4" @click="lightbox = null">
        <figure class="max-w-4xl" @click.stop>
            <img :src="lightbox && lightbox.src" :alt="lightbox && lightbox.title" class="max-h-[80vh] rounded-2xl">
            <figcaption class="mt-3 text-center text-sm text-slate-200" x-text="lightbox && lightbox.title"></figcaption>
        </figure>
        <button type="button" class="absolute right-4 top-4 flex size-10 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20" @click="lightbox = null" aria-label="Tutup">&times;</button>
    </div>
</div>
@endsection
