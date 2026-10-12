@extends('layouts.dashboard')
@section('title', 'Profil Saya')
@section('panel')
@php $c = $user->customer; @endphp
<h1 class="text-2xl font-bold">Profil Saya</h1>
<form method="POST" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">@csrf @method('PUT')
    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Data Akun</h2>
        <x-input name="name" label="Nama lengkap" :value="$user->name" required />
        <x-input name="email" type="email" label="Email" :value="$user->email" required />
        <x-input name="phone" type="tel" label="Nomor HP" :value="$user->phone" required />
    </section>
    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Data Penyewa</h2>
        <x-input name="nik" label="NIK" :value="$c?->nik" />
        <x-input name="birth_date" type="date" label="Tanggal lahir" :value="$c?->birth_date?->toDateString()" />
        <x-input name="sim_number" label="Nomor SIM" :value="$c?->sim_number" />
        <x-input name="address" type="textarea" rows="3" label="Alamat" :value="$c?->address" class="sm:col-span-2" />
        <div>
            <x-input type="file" name="ktp" label="Foto KTP" accept="image/png,image/jpeg" />
            @if ($c?->ktp_path)<a href="{{ route('files.document', [$c->id, 'ktp']) }}" target="_blank" class="mt-1 inline-block text-xs text-brand-ink hover:underline">Lihat KTP tersimpan</a>@endif
        </div>
        <div>
            <x-input type="file" name="sim" label="Foto SIM" accept="image/png,image/jpeg" />
            @if ($c?->sim_path)<a href="{{ route('files.document', [$c->id, 'sim']) }}" target="_blank" class="mt-1 inline-block text-xs text-brand-ink hover:underline">Lihat SIM tersimpan</a>@endif
        </div>
    </section>
    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Ganti Password <span class="text-xs font-normal text-slate-500">(opsional)</span></h2>
        <x-input name="current_password" type="password" label="Password saat ini" autocomplete="current-password" />
        <span></span>
        <x-input name="password" type="password" label="Password baru" autocomplete="new-password" />
        <x-input name="password_confirmation" type="password" label="Ulangi password baru" autocomplete="new-password" />
    </section>
    <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan Perubahan</button>
</form>

{{-- Pintasan akun — di layar kecil menu ini tidak lagi ada di sidebar. --}}
<section class="card card-pad mt-6">
    <h2 class="text-lg font-bold">Akun</h2>
    <div class="mt-3 flex flex-wrap gap-2">
        <a href="{{ route('dashboard.reviews') }}" class="btn btn-ghost btn-sm"><x-icon name="star" class="size-4" /> Review saya</a>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="btn btn-ghost btn-sm text-rose-700"><x-icon name="log-out" class="size-4" /> Logout</button>
        </form>
    </div>
</section>
@endsection
