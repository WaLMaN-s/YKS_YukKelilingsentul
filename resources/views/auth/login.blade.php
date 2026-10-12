@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="container-x flex min-h-[70vh] items-center justify-center py-12">
    <div class="card card-pad w-full max-w-md">
        <h1 class="text-2xl font-bold">Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">Masuk untuk booking dan melihat riwayat sewa.</p>
        <form method="POST" action="{{ route('login.attempt') }}" class="mt-6 space-y-4">@csrf
            <x-input name="email" type="email" label="Email" autocomplete="email" required />
            <x-input name="password" type="password" label="Password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm text-slate-500"><input type="checkbox" name="remember" class="accent-amber-600"> Ingat saya</label>
            <button class="btn btn-primary w-full">Login</button>
        </form>
        <p class="mt-5 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-brand-ink hover:underline">Daftar</a></p>
    </div>
</div>
@endsection
