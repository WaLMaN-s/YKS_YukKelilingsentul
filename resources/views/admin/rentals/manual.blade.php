@extends('layouts.admin')
@section('title', 'Catat Pesanan Manual')
@section('content')
<a href="{{ route('admin.penyewaan.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke Penyewaan</a>
<h1 class="mt-2 text-2xl font-bold">Catat Pesanan Manual</h1>
<p class="text-sm text-slate-500">Untuk pesanan yang masuk lewat WhatsApp, Instagram, atau datang langsung, agar semua data tercatat di satu tempat dan stok unit ikut terpotong.</p>

<form method="POST" action="{{ route('admin.penyewaan.manual.store') }}" class="mt-6 max-w-4xl space-y-6"
      x-data="{ vehicle: @js((string) old('motor_id', '')), types: @js($vehicles->mapWithKeys(fn ($v) => [(string) $v->id => $v->vehicle_type])), get isJeep() { return this.types[this.vehicle] === 'jeep' } }">@csrf

    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Customer</h2>
        <x-input name="customer_name" label="Nama" required />
        <x-input name="customer_phone" type="tel" label="Nomor HP / WhatsApp" required />
        <x-input name="customer_email" type="email" label="Email (opsional)" hint="Kosong = dibuatkan akun tamu otomatis. Jika HP/email sudah terdaftar, akun yang ada dipakai." />
        <x-input name="source" type="select" label="Sumber pesanan" :options="['whatsapp' => 'WhatsApp', 'instagram' => 'Instagram', 'walk_in' => 'Datang langsung']" value="whatsapp" required />
    </section>

    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Pesanan</h2>
        <div class="sm:col-span-2">
            <label class="label" for="f-motor_id">Kendaraan <span class="text-brand-ink">*</span></label>
            <select id="f-motor_id" name="motor_id" class="input" x-model="vehicle" required>
                <option value="">Pilih kendaraan…</option>
                @foreach ($vehicles as $v)<option value="{{ $v->id }}">{{ $v->type_label }} — {{ $v->name }} ({{ $v->stock }} unit)</option>@endforeach
            </select>
            @error('motor_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <x-input name="start_date" type="date" :label="'Tanggal mulai / perjalanan'" :value="today()->addDay()->toDateString()" required />
        <div x-show="!isJeep"><x-input name="end_date" type="date" label="Tanggal kembali" :value="today()->addDays(2)->toDateString()" /></div>
        <x-input name="quantity" type="number" label="Jumlah unit" value="1" min="1" max="10" required />
        <div x-show="isJeep" x-cloak><x-input name="people" type="number" label="Jumlah orang (jeep)" min="1" /></div>
        <div class="sm:col-span-2" x-show="isJeep" x-cloak>
            <label class="label" for="f-package_id">Paket jeep</label>
            <select id="f-package_id" name="package_id" class="input">
                <option value="">Pilih paket…</option>
                @foreach ($packages as $pk)<option value="{{ $pk->id }}" @selected((string) old('package_id') === (string) $pk->id)>{{ $pk->name }} — {{ rupiah($pk->price) }}</option>@endforeach
            </select>
            @error('package_id')<p class="field-error">{{ $message }}</p>@enderror
        </div>
        <x-input name="location_id" type="select" label="Lokasi antar/ambil" :options="$locations->pluck('name', 'id')->all()" placeholder-option="Basecamp / tanpa antar" />
        <x-input name="promo_code" label="Kode promo (opsional)" />
        @if ($addons->isNotEmpty())
            <div class="sm:col-span-2"><p class="label">Add-on</p>
                <div class="grid gap-2 sm:grid-cols-3">@foreach ($addons as $ad)
                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 bg-navy-900 px-3 py-2.5 text-sm"><input type="checkbox" name="addons[]" value="{{ $ad->id }}" class="accent-amber-600" @checked(in_array($ad->id, old('addons', [])))> {{ $ad->name }} <span class="text-xs text-slate-500">{{ rupiah($ad->price) }}</span></label>
                @endforeach</div></div>
        @endif
        <x-input name="notes" type="textarea" rows="2" label="Catatan" class="sm:col-span-2" />
    </section>

    <section class="card card-pad grid gap-4 sm:grid-cols-2">
        <h2 class="text-lg font-bold sm:col-span-2">Pembayaran</h2>
        <x-input name="payment_mode" type="select" label="Status pembayaran" value="dp" required
                 :options="['unpaid' => 'Belum bayar (menunggu)', 'dp' => 'DP '.$dpPercent.'% sudah diterima', 'full' => 'Sudah lunas']"
                 :hint="'DP '.$dpPercent.'% dihitung dari total. Sisanya dicatat sebagai pelunasan yang menunggu dibayar.'" />
        <x-input name="payment_method_id" type="select" label="Metode" :options="$methods->pluck('name', 'id')->all()" required />
    </section>

    <div class="flex gap-3"><button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan Pesanan</button><a href="{{ route('admin.penyewaan.index') }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
