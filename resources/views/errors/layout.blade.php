@extends('layouts.app')
@section('title', $code.' – '.$title)
@section('content')
<div class="container-x flex min-h-[65vh] flex-col items-center justify-center py-16 text-center">
    <p class="text-8xl font-extrabold text-brand-ink sm:text-9xl">{{ $code }}</p>
    <h1 class="mt-4 text-2xl font-bold">{{ $title }}</h1>
    <p class="mt-2 max-w-md text-slate-500">{{ $message }}</p>
    <div class="mt-6 flex gap-3">
        <a href="{{ route('home') }}" class="btn btn-primary">Ke Beranda</a>
        <button type="button" onclick="history.back()" class="btn btn-ghost">Kembali</button>
    </div>
</div>
@endsection
