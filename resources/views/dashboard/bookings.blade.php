@extends('layouts.dashboard')
@section('title', 'Penyewaan Saya')
@section('panel')
<h1 class="text-2xl font-bold">Penyewaan Saya</h1>
<div class="mt-6 space-y-4">
    @forelse ($rentals as $rental)@include('partials.booking-row', ['rental' => $rental])
    @empty<div class="card card-pad text-center text-slate-500">Belum ada penyewaan. <a href="{{ route('motors.index') }}" class="font-semibold text-brand-ink">Sewa motor sekarang</a>.</div>@endforelse
</div>
<div class="mt-6">{{ $rentals->links() }}</div>
@endsection
