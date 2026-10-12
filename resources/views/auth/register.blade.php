@extends('layouts.app')
@section('title', 'Daftar')
@section('content')
<div class="container-x flex min-h-[70vh] items-center justify-center py-12">
    <div class="card card-pad w-full max-w-md">
        <h1 class="text-2xl font-bold">Buat Akun</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar gratis untuk mulai booking motor.</p>
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-4">@csrf
            <x-input name="name" label="Nama lengkap" autocomplete="name" required />
            <x-input name="email" type="email" label="Email" autocomplete="email" required />
            <x-input name="phone" type="tel" label="Nomor HP / WhatsApp" autocomplete="tel" required placeholder="08xxxxxxxxxx" />
            <x-input name="password" type="password" label="Password" hint="Minimal 8 karakter." autocomplete="new-password" required />
            <x-input name="password_confirmation" type="password" label="Ulangi password" autocomplete="new-password" required />
            <button class="btn btn-primary w-full">Daftar</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-brand-ink hover:underline">Login</a></p>
    </div>
</div>
@endsection
