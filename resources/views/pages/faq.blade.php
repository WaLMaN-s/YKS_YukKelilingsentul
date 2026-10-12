@extends('layouts.app')
@section('title', 'FAQ')
@section('description', 'Pertanyaan yang sering diajukan seputar sewa motor, pembayaran, deposit, dan pengembalian.')
@section('content')
<section class="border-b border-slate-200 bg-navy-900 py-10"><div class="container-x"><h1 class="text-3xl font-bold">Pertanyaan Umum</h1></div></section>
<div class="container-x max-w-3xl space-y-3 py-12" x-data="{ open: 0 }">
    @foreach ($faqs as $i => [$q, $a])
        <div class="card overflow-hidden">
            <button type="button" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-semibold" @click="open = open === {{ $i + 1 }} ? 0 : {{ $i + 1 }}" :aria-expanded="open === {{ $i + 1 }}">
                {{ $q }} <span class="transition" :class="open === {{ $i + 1 }} && 'rotate-180'"><x-icon name="chevron-down" /></span>
            </button>
            <div x-show="open === {{ $i + 1 }}" x-transition x-cloak class="px-5 pb-4 text-sm text-slate-600">{{ $a }}</div>
        </div>
    @endforeach
</div>
@endsection
