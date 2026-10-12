@extends('layouts.admin')
@section('title', 'Edit Penyewaan')
@section('content')
<a href="{{ route('admin.penyewaan.show', $rental) }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali</a>
<h1 class="mt-2 text-2xl font-bold">Edit #{{ $rental->invoice_no }}</h1>
<p class="text-sm text-slate-500">Tanggal dan harga tidak bisa diubah agar aman dari bentrok jadwal. Batalkan lalu buat booking baru jika perlu.</p>
<form method="POST" action="{{ route('admin.penyewaan.update', $rental) }}" class="card card-pad mt-6 max-w-2xl space-y-4">@csrf @method('PUT')
    <x-input name="renter_name" label="Nama penyewa" :value="$rental->renter_name" required />
    <x-input name="renter_phone" label="Nomor HP" :value="$rental->renter_phone" required />
    <x-input name="renter_email" type="email" label="Email" :value="$rental->renter_email" required />
    <x-input name="notes" type="textarea" rows="3" label="Catatan" :value="$rental->notes" />
    <div class="flex gap-3"><button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button><a href="{{ route('admin.penyewaan.show', $rental) }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
