@extends('layouts.admin')
@section('title', $motor->exists ? 'Edit Motor' : 'Tambah Motor')
@section('content')
<a href="{{ route('admin.motor.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke Motor</a>
<h1 class="mt-2 text-2xl font-bold">{{ $motor->exists ? 'Edit Motor' : 'Tambah Motor' }}</h1>

<form method="POST" enctype="multipart/form-data" class="mt-6 max-w-4xl space-y-6"
      action="{{ $motor->exists ? route('admin.motor.update', $motor) : route('admin.motor.store') }}">
    @csrf @if ($motor->exists) @method('PUT') @endif

    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Informasi Dasar</h2>
        <x-input name="vehicle_type" type="select" label="Jenis kendaraan" :value="$motor->vehicle_type" :options="\App\Models\Motorcycle::TYPES" required />
        <x-input name="stock" type="number" label="Jumlah unit (stok)" :value="$motor->stock" required min="0" hint="Jumlah unit fisik untuk model ini. Booking otomatis ditolak jika semua unit sudah terpakai di tanggal tersebut." />
        <x-input name="name" label="Nama kendaraan" :value="$motor->name" required />
        <x-input name="slug" label="Slug" :value="$motor->slug" hint="Kosongkan untuk dibuat otomatis." />
        <x-input name="brand" label="Merek" :value="$motor->brand" required />
        <x-input name="model" label="Model" :value="$motor->model" />
        <x-input name="motor_category_id" type="select" label="Kategori" :value="$motor->motor_category_id" :options="$categories->pluck('name', 'id')->all()" placeholder-option="Tanpa kategori" />
        <x-input name="plate_number" label="Nomor polisi" :value="$motor->plate_number" />
        <x-input name="year" type="number" label="Tahun" :value="$motor->year" min="1990" />
        <x-input name="color" label="Warna" :value="$motor->color" />
        <x-input name="cc" type="number" label="Kapasitas mesin (CC)" :value="$motor->cc" required min="50" />
        <x-input name="transmission" type="select" label="Transmisi" :value="$motor->transmission" :options="['Manual' => 'Manual', 'Automatic' => 'Automatic']" required />
        <x-input name="capacity" type="number" label="Kapasitas penumpang (jeep: maks orang)" :value="$motor->capacity" required min="1" />
        <x-input name="status" type="select" label="Status" :value="$motor->status" :options="\App\Models\Motorcycle::STATUSES" required hint="Maintenance / Nonaktif = tidak bisa dibooking." />
    </section>

    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Harga</h2>
        <x-input name="price_daily" type="number" label="Harga harian / harga mulai (Rp)" hint="Jeep: harga per jeep yang tampil sebagai ‘Mulai’. Harga final jeep mengikuti paket (menu Paket Jeep)." :value="$motor->price_daily" required min="1000" step="1000" />
        <x-input name="deposit" type="number" label="Deposit (Rp)" :value="$motor->deposit" min="0" step="10000" hint="Kosong/0 = pakai deposit default dari Pengaturan." />
        <x-input name="price_weekly" type="number" label="Harga mingguan (Rp)" :value="$motor->price_weekly" min="0" step="10000" hint="Khusus motor. Dipakai otomatis bila durasi ≥ 7 hari dan lebih hemat." />
        <x-input name="price_monthly" type="number" label="Harga bulanan (Rp)" :value="$motor->price_monthly" min="0" step="10000" hint="Dipakai otomatis bila durasi ≥ 30 hari dan lebih hemat." />
    </section>

    <section class="card card-pad space-y-4">
        <h2 class="text-lg font-bold">Detail</h2>
        <x-input name="description" type="textarea" rows="4" label="Deskripsi" :value="$motor->description" />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-input name="facilities" type="lines" rows="6" label="Fasilitas" :value="$motor->facilities" hint="Satu fasilitas per baris (Helm, Jas hujan, …)." />
            <x-input name="terms" type="textarea" rows="6" label="Syarat sewa" :value="$motor->terms" hint="Satu syarat per baris." />
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <x-input name="is_popular" type="checkbox" label="Tandai POPULER" :value="$motor->is_popular" />
            <x-input name="is_promo" type="checkbox" label="Tandai PROMO" :value="$motor->is_promo" />
        </div>
    </section>

    <section class="card card-pad space-y-4">
        <h2 class="text-lg font-bold">Foto</h2>
        @if ($motor->exists && $motor->images->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($motor->images as $img)
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <img src="{{ $img->url }}" alt="" class="aspect-[4/3] w-full object-cover">
                        <div class="flex items-center justify-between gap-1 bg-navy-900 p-2">
                            @if ($img->is_primary)<span class="badge bg-brand/90 text-ink ring-brand">Utama</span>
                            @else<button type="submit" form="img-primary-{{ $img->id }}" class="text-xs text-brand-ink hover:underline">Jadikan utama</button>@endif
                            <button type="submit" form="img-del-{{ $img->id }}" class="text-xs text-rose-700 hover:underline">Hapus</button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
        <x-input name="images[]" type="file" label="Upload foto (bisa lebih dari satu)" multiple accept="image/png,image/jpeg,image/webp" hint="JPG/PNG/WebP, maks 4 MB per foto, hingga 8 foto sekali upload. Foto pertama otomatis menjadi foto utama." />
    </section>

    <div class="flex gap-3">
        <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        <a href="{{ route('admin.motor.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>

{{-- Form aksi foto harus di luar form utama (HTML tidak boleh bersarang) --}}
@if ($motor->exists)
    @foreach ($motor->images as $img)
        <form id="img-primary-{{ $img->id }}" method="POST" action="{{ route('admin.motor.image.primary', $img->id) }}">@csrf</form>
        <form id="img-del-{{ $img->id }}" method="POST" action="{{ route('admin.motor.image.destroy', $img->id) }}" @submit.prevent="$store.confirm.ask('Hapus foto ini?', $el)">@csrf @method('DELETE')</form>
    @endforeach
@endif
@endsection
