@extends('layouts.dashboard')
@section('title', 'Review')
@section('panel')
<h1 class="text-2xl font-bold">Review</h1>

<h2 class="mt-6 text-lg font-bold">Menunggu review Anda</h2>
<div class="mt-3 space-y-4">
    @forelse ($pending as $rental)
        <form method="POST" action="{{ route('dashboard.reviews.store', $rental->id) }}" class="card card-pad space-y-3" x-data="{ rating: {{ (int) old('rating', 5) }} }">@csrf
            <p class="font-semibold">{{ $rental->motorcycle?->name }} <span class="text-xs font-normal text-slate-500">· #{{ $rental->invoice_no }}</span></p>
            <div class="flex gap-1" role="radiogroup" aria-label="Rating">
                @for ($i = 1; $i <= 5; $i++)
                    <button type="button" @click="rating = {{ $i }}" class="text-2xl leading-none transition" :class="rating >= {{ $i }} ? 'text-brand-ink' : 'text-slate-600'" aria-label="{{ $i }} bintang">★</button>
                @endfor
                <input type="hidden" name="rating" :value="rating">
            </div>
            <x-input name="comment" type="textarea" rows="3" placeholder="Ceritakan pengalaman Anda…" />
            <button class="btn btn-primary btn-sm">Kirim Review</button>
        </form>
    @empty
        <p class="card card-pad text-sm text-slate-500">Tidak ada rental yang menunggu review.</p>
    @endforelse
</div>

<h2 class="mt-8 text-lg font-bold">Review saya</h2>
<div class="mt-3 grid gap-4 md:grid-cols-2">
    @forelse ($reviews as $r)
        <div class="card card-pad">
            <div class="flex items-center justify-between"><p class="font-semibold">{{ $r->motorcycle->name }}</p><x-stars :rating="$r->rating" /></div>
            <p class="mt-2 text-sm text-slate-600">{{ $r->comment }}</p>
            <span class="badge mt-3 {{ ['pending' => 'bg-amber-400/15 text-amber-700 ring-amber-200', 'approved' => 'bg-emerald-400/15 text-emerald-700 ring-emerald-200', 'rejected' => 'bg-rose-500/15 text-rose-700 ring-rose-200'][$r->status] }}">{{ \App\Models\Review::STATUSES[$r->status] }}</span>
        </div>
    @empty
        <p class="text-sm text-slate-500">Belum ada review.</p>
    @endforelse
</div>
@endsection
