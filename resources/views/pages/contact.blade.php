@extends('layouts.app')
@section('title', 'Kontak')
@section('description', 'Hubungi Yuk Keliling Sentul via WhatsApp, email, atau kunjungi basecamp kami di Sentul, Bogor.')
@section('content')
<section class="border-b border-slate-200 bg-navy-900 py-10"><div class="container-x"><h1 class="text-3xl font-bold">Kontak</h1><p class="mt-1 text-sm text-slate-500">Kami siap membantu menentukan motor dan rute yang pas.</p></div></section>
<div class="container-x grid gap-8 py-12 lg:grid-cols-2">
    <div class="card card-pad space-y-5">
        @if (setting('whatsapp'))
        <div class="flex gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-700"><x-icon name="message-circle" /></span>
            <div><p class="font-semibold">WhatsApp</p><a class="text-brand-ink hover:underline" href="{{ wa_link('Halo Admin, saya ingin bertanya.') }}" target="_blank" rel="noopener">{{ setting('whatsapp') }}</a></div></div>
        @endif
        @if (setting('email'))
        <div class="flex gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-sky-500/15 text-sky-700"><x-icon name="mail" /></span>
            <div><p class="font-semibold">Email</p><a class="text-brand-ink hover:underline" href="mailto:{{ setting('email') }}">{{ setting('email') }}</a></div></div>
        @endif
        <div class="flex gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand/15 text-brand-ink"><x-icon name="map-pin" /></span>
            <div><p class="font-semibold">Basecamp Gunung Pancar</p><p class="text-slate-600">{{ setting('address') }}</p>
                @if ($facilities)<ul class="mt-2 flex flex-wrap gap-2">@foreach ($facilities as $f)<li class="badge bg-slate-900/5 text-slate-600 ring-slate-200">{{ $f }}</li>@endforeach</ul>@endif</div></div>
        <div class="flex gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-violet-500/15 text-violet-700"><x-icon name="clock" /></span>
            <div><p class="font-semibold">Jam Operasional</p><p class="text-slate-600">{{ setting('operating_hours') }}</p></div></div>
        @if (setting('instagram'))
        <div class="flex gap-4"><span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-pink-500/15 text-pink-700"><x-icon name="instagram" /></span>
            <div><p class="font-semibold">Instagram</p><a class="text-brand-ink hover:underline" href="https://instagram.com/{{ setting('instagram') }}" target="_blank" rel="noopener">{{ '@'.setting('instagram') }}</a></div></div>
        @endif
    </div>
    <div class="card card-pad flex flex-col justify-center text-center">
        <x-icon name="message-circle" class="mx-auto size-12 text-emerald-600" />
        <h2 class="mt-3 text-xl font-bold">Tanya langsung via WhatsApp</h2>
        <p class="mt-2 text-sm text-slate-500">Respons tercepat untuk cek ketersediaan motor, paket wisata, dan promo.</p>
        <a href="{{ wa_link("Halo Admin,\n\nSaya ingin bertanya tentang sewa motor.") }}" target="_blank" rel="noopener" class="btn btn-primary mx-auto mt-5">Chat Sekarang</a>
    </div>
</div>
@endsection
