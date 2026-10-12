@extends('layouts.admin')
@section('title', 'Edit Customer')
@section('content')
@php $c = $customer->customer; @endphp
<a href="{{ route('admin.customer.show', $customer) }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali</a>
<h1 class="mt-2 text-2xl font-bold">Edit {{ $customer->name }}</h1>
<form method="POST" action="{{ route('admin.customer.update', $customer) }}" class="card card-pad mt-6 grid max-w-3xl gap-4 sm:grid-cols-2">@csrf @method('PUT')
    <x-input name="name" label="Nama" :value="$customer->name" required />
    <x-input name="email" type="email" label="Email" :value="$customer->email" required />
    <x-input name="phone" label="No. HP" :value="$customer->phone" />
    <x-input name="nik" label="NIK" :value="$c?->nik" />
    <x-input name="birth_date" type="date" label="Tanggal lahir" :value="$c?->birth_date" />
    <x-input name="sim_number" label="Nomor SIM" :value="$c?->sim_number" />
    <x-input name="address" type="textarea" rows="3" label="Alamat" :value="$c?->address" class="sm:col-span-2" />
    <div class="flex gap-3 sm:col-span-2"><button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button><a href="{{ route('admin.customer.show', $customer) }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
