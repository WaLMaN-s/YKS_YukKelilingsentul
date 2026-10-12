@extends('layouts.app')
@section('title', 'Booking '.$motor->name)

@section('content')
@php $isJeep = $motor->isJeep(); @endphp
<div class="container-x py-8" x-data="bookingForm(@js([
    'isJeep' => $isJeep,
    'start' => old('start_date', $start),
    'end' => old('end_date', $end),
    'location' => old('location_id', $selectedLocation),
    'promo' => old('promo_code'),
    'method' => old('payment_method_id'),
    'quantity' => (int) old('quantity', 1),
    'people' => old('people'),
    'packageId' => old('package_id', $selectedPackage),
    'addons' => old('addons', []),
    'motorId' => $motor->id,
    'quoteUrl' => route('booking.quote'),
    'methods' => $methods->map(fn ($m) => ['id' => $m->id, 'type' => $m->type])->values(),
]))">
    <h1 class="text-3xl font-bold">Booking {{ $isJeep ? 'Jeep' : 'Motor' }}</h1>
    <p class="mt-1 text-sm text-slate-500">Lengkapi data berikut. Total dihitung otomatis dan divalidasi ulang oleh sistem.</p>

    <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_.6fr]">
        @csrf
        <input type="hidden" name="motor_id" value="{{ $motor->id }}">
        @if ($isJeep)<input type="hidden" name="end_date" :value="end">@endif

        <div class="space-y-6">
            {{-- 1. Jadwal --}}
            <section class="card card-pad">
                <h2 class="mb-4 text-lg font-bold">1. {{ $isJeep ? 'Tanggal Perjalanan' : 'Tanggal, Jumlah & Lokasi' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="label" for="start_date">{{ $isJeep ? 'Tanggal perjalanan' : 'Tanggal mulai' }} <span class="text-brand-ink">*</span></label>
                        <input id="start_date" name="start_date" type="date" class="input" x-model="start" :min="today" required>
                        @error('start_date')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @unless ($isJeep)
                        <div>
                            <label class="label" for="end_date">Tanggal kembali <span class="text-brand-ink">*</span></label>
                            <input id="end_date" name="end_date" type="date" class="input" x-model="end" :min="start" required>
                            @error('end_date')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endunless
                    <div>
                        <label class="label" for="quantity">Jumlah {{ $isJeep ? 'jeep' : 'unit motor' }} <span class="text-brand-ink">*</span></label>
                        <input id="quantity" name="quantity" type="number" min="1" max="{{ $maxQty }}" class="input" x-model="quantity" required>
                        <p class="hint">Maks {{ $maxQty }} unit per booking. Untuk rombongan lebih besar, hubungi admin.</p>
                        @error('quantity')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    @if ($isJeep)
                        <div>
                            <label class="label" for="people">Jumlah orang</label>
                            <input id="people" name="people" type="number" min="1" max="{{ $motor->capacity * $maxQty }}" class="input" x-model="people" placeholder="Maks {{ $motor->capacity }} orang / jeep">
                            @error('people')<p class="field-error">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="label" for="location_id">Lokasi pengambilan / pengantaran</label>
                        <select id="location_id" name="location_id" class="input" x-model="location">
                            <option value="">Ambil di basecamp (opsional pilih lokasi lain)</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }} — {{ $loc->delivery_fee ? 'biaya antar '.rupiah($loc->delivery_fee) : 'gratis' }}</option>
                            @endforeach
                        </select>
                        @error('location_id')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                @unless ($isJeep)<p class="hint">Minimal durasi sewa {{ $minDays }} hari. Durasi dihitung dari selisih tanggal mulai dan tanggal kembali.</p>@endunless
            </section>

            @if ($isJeep)
                <section class="card card-pad">
                    <h2 class="mb-1 text-lg font-bold">2. Pilih Paket Jeep</h2>
                    <p class="mb-4 text-xs text-slate-500">Harga per jeep (maks {{ $motor->capacity }} orang). Tiket curug sudah termasuk pada paket yang bertanda “+ Curug”.</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($packages as $pk)
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 transition hover:border-brand/50 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                                <input type="radio" name="package_id" value="{{ $pk->id }}" x-model="packageId" class="mt-1 accent-amber-600" required>
                                <span class="text-sm">
                                    <b class="block text-slate-900">{{ $pk->name }}</b>
                                    <span class="font-bold text-brand-ink">{{ rupiah($pk->price) }}</span>
                                    <ul class="mt-1 space-y-0.5 text-xs text-slate-500">@foreach ($pk->includes ?? [] as $inc)<li>✓ {{ $inc }}</li>@endforeach</ul>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('package_id')<p class="field-error">{{ $message }}</p>@enderror
                </section>
            @endif

            @if ($addons->isNotEmpty())
                <section class="card card-pad">
                    <h2 class="mb-1 text-lg font-bold">{{ $isJeep ? '3' : '2' }}. Add-on <span class="text-sm font-normal text-slate-500">(opsional)</span></h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach ($addons as $ad)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 transition hover:border-brand/50 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                                <input type="checkbox" name="addons[]" value="{{ $ad->id }}" x-model="addons" class="accent-amber-600">
                                <span class="text-sm"><b class="block text-slate-900">{{ $ad->name }}</b><span class="text-xs text-slate-500">{{ rupiah($ad->price) }} {{ $ad->per === 'person' ? '/ orang' : '/ booking' }}</span>@if ($ad->description)<span class="block text-xs text-slate-500">{{ $ad->description }}</span>@endif</span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Data penyewa --}}
            <section class="card card-pad">
                <h2 class="mb-4 text-lg font-bold">{{ 2 + ($isJeep ? 1 : 0) + ($addons->isNotEmpty() ? 1 : 0) }}. Data Penyewa</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="renter_name" label="Nama lengkap" :value="$user->name" required />
                    <x-input name="renter_phone" type="tel" label="Nomor HP" :value="$user->phone" required placeholder="08xxxxxxxxxx" />
                    <x-input name="renter_email" type="email" label="Email" :value="$user->email" required />
                    <x-input name="nik" label="NIK (16 digit)" :value="$customer?->nik" required placeholder="3201xxxxxxxxxxxx" />
                    <x-input name="birth_date" type="date" label="Tanggal lahir" :value="$customer?->birth_date?->toDateString()" required :hint="'Usia minimal '.$minAge.' tahun.'" />
                    <x-input name="sim_number" label="Nomor SIM C" :value="$customer?->sim_number" required />
                    <x-input name="address" type="textarea" rows="3" label="Alamat" :value="$customer?->address" required class="sm:col-span-2" />
                </div>
            </section>

            <section class="card card-pad">
                <h2 class="mb-1 text-lg font-bold">Dokumen</h2>
                <p class="mb-4 text-xs text-slate-500"><x-icon name="lock" class="mr-1 inline size-3.5" />Dokumen disimpan privat dan hanya bisa dilihat oleh Anda dan admin yang berwenang.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input type="file" name="ktp" label="Foto KTP" accept="image/png,image/jpeg" :required="! $customer?->ktp_path" :hint="$customer?->ktp_path ? 'Sudah tersimpan. Unggah ulang hanya jika ingin mengganti.' : 'JPG/PNG, maks 3 MB.'" />
                    <x-input type="file" name="sim" label="Foto SIM C" accept="image/png,image/jpeg" :required="! $customer?->sim_path" :hint="$customer?->sim_path ? 'Sudah tersimpan. Unggah ulang hanya jika ingin mengganti.' : 'JPG/PNG, maks 3 MB.'" />
                </div>
            </section>

            <section class="card card-pad">
                <h2 class="mb-4 text-lg font-bold">Kode Promo</h2>
                <div class="flex gap-2">
                    <input name="promo_code" class="input uppercase" x-model="promo" placeholder="Contoh: HEMAT20" maxlength="40" @keydown.enter.prevent="fetchQuote()">
                    <button type="button" class="btn btn-dark" @click="fetchQuote()">Terapkan</button>
                </div>
                <template x-if="quote && quote.promo_message">
                    <p class="mt-2 text-sm" :class="quote.promo_ok ? 'text-emerald-600' : 'text-rose-600'" x-text="quote.promo_message"></p>
                </template>
                @error('promo_code')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            <section class="card card-pad">
                <h2 class="mb-1 text-lg font-bold">Metode Pembayaran (lunas)</h2>
                <p class="mb-4 text-xs text-slate-500">Pembayaran di website langsung lunas, tanpa DP.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($methods as $m)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 transition hover:border-brand/50 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                            <input type="radio" name="payment_method_id" value="{{ $m->id }}" x-model="method" class="accent-amber-600" required>
                            <span class="text-sm"><b class="block text-slate-900">{{ $m->name }}</b><span class="text-xs text-slate-500">{{ $m->type_label }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method_id')<p class="field-error">{{ $message }}</p>@enderror
                <p class="hint" x-show="methodType === 'cash'">Cash dibayar saat pengambilan di basecamp. Booking menunggu konfirmasi admin.</p>
                <div class="mt-4"><x-input name="notes" type="textarea" rows="2" label="Catatan untuk admin (opsional)" /></div>
            </section>

            @include('partials.policies', ['kind' => 'sewa'])

            <label class="flex items-start gap-3 text-sm text-slate-600">
                <input type="checkbox" name="agree" value="1" class="mt-1 accent-amber-600" required @checked(old('agree'))>
                <span>Saya menyetujui syarat sewa &amp; kebijakan pemesanan di atas, dan menyatakan data yang saya isi benar.</span>
            </label>
            @error('agree')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        {{-- Ringkasan --}}
        <aside>
            <div class="card card-pad space-y-4 lg:sticky lg:top-24">
                <img src="{{ $motor->image_url }}" alt="{{ $motor->name }}" class="aspect-[16/10] w-full rounded-xl object-cover">
                <div>
                    <h2 class="text-lg font-bold">{{ $motor->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $isJeep ? 'Harga sesuai paket yang dipilih' : rupiah($motor->price_daily).' / hari' }} · {{ $motor->stock }} unit</p>
                </div>

                <div class="space-y-2 text-sm" x-show="quote && quote.subtotal !== undefined" x-cloak>
                    <div class="flex justify-between"><span class="text-slate-500">{{ $isJeep ? 'Perjalanan' : 'Durasi' }}</span><b x-text="quote && quote.days + ' hari'"></b></div>
                    <div class="flex justify-between"><span class="text-slate-500">Jumlah unit</span><b x-text="quote && quote.quantity"></b></div>
                    <div class="flex justify-between"><span class="text-slate-500">Harga sewa</span><b x-text="quote && rupiah(quote.subtotal)"></b></div>
                    <template x-for="a in (quote && quote.addons) || []" :key="a.id">
                        <div class="flex justify-between"><span class="text-slate-500" x-text="'+ ' + a.name"></span><b x-text="rupiah(a.subtotal)"></b></div>
                    </template>
                    <div class="flex justify-between"><span class="text-slate-500">Biaya antar</span><b x-text="quote && rupiah(quote.delivery_fee)"></b></div>
                    <div class="flex justify-between"><span class="text-slate-500">Deposit</span><b x-text="quote && rupiah(quote.deposit)"></b></div>
                    <div class="flex justify-between text-emerald-600" x-show="quote && quote.discount > 0"><span>Diskon</span><b x-text="quote && '- ' + rupiah(quote.discount)"></b></div>
                    <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base"><span class="font-semibold text-slate-900">Total (lunas)</span><b class="text-xl text-brand-ink" x-text="quote && rupiah(quote.total)"></b></div>
                </div>
                <p class="text-xs text-slate-500" x-show="loading" x-cloak>Menghitung...</p>

                <template x-if="quote && !quote.available">
                    <ul class="space-y-1 rounded-xl border border-rose-200 bg-rose-500/10 p-3 text-sm text-rose-700">
                        <template x-for="e in quote.errors" :key="e"><li x-text="e"></li></template>
                    </ul>
                </template>
                @error('start_date')<p class="field-error">{{ $message }}</p>@enderror

                <button class="btn btn-primary w-full" :disabled="!quote || !quote.available">Buat Booking</button>
                <p class="text-center text-xs text-slate-500">Deposit dikembalikan setelah kendaraan kembali dalam kondisi baik.</p>
            </div>
        </aside>
    </form>
</div>
@endsection
