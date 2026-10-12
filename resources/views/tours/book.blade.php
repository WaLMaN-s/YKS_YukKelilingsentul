@extends('layouts.app')
@section('title', 'Pesan '.$package->name)

@section('content')
<div class="container-x py-8" x-data="{
    tripType: @js(old('trip_type', $schedules->isNotEmpty() && ! old('trip_date') ? 'open' : 'private')),
    people: {{ (int) old('people', 2) }},
    scheduleId: @js(old('tour_schedule_id', $preferred)),
    addons: @js(array_map('strval', old('addons', []))),
    price: {{ $package->price }},
    addonList: @js($addons->map(fn ($a) => ['id' => (string) $a->id, 'price' => $a->price, 'per' => $a->per])->values()),
    method: @js(old('payment_method_id')),
    get addonTotal() { return this.addonList.filter(a => this.addons.includes(a.id)).reduce((t, a) => t + (a.per === 'person' ? a.price * (this.people || 1) : a.price), 0) },
    get total() { return this.price * (this.people || 1) + this.addonTotal },
    rupiah(n) { return 'Rp' + Math.round(n).toLocaleString('id-ID') },
}">
    <a href="{{ route('tours.show', $package->slug) }}" class="text-sm text-slate-500 hover:text-brand-ink">← {{ $package->name }}</a>
    <h1 class="mt-2 text-3xl font-bold">Pesan Paket Wisata</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $package->name }} · {{ $package->duration }} · {{ rupiah($package->price) }} / orang</p>

    <form method="POST" action="{{ route('tour-booking.store') }}" class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_.6fr]">
        @csrf
        <input type="hidden" name="tour_package_id" value="{{ $package->id }}">

        <div class="space-y-6">
            <section class="card card-pad">
                <h2 class="mb-4 text-lg font-bold">1. Jenis Trip</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                        <input type="radio" name="trip_type" value="open" x-model="tripType" class="mt-1 accent-amber-600">
                        <span class="text-sm"><b class="block text-slate-900">Open Trip</b><span class="text-xs text-slate-500">Gabung jadwal yang sudah dibuka ({{ $schedules->count() }} jadwal tersedia).</span></span>
                    </label>
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                        <input type="radio" name="trip_type" value="private" x-model="tripType" class="mt-1 accent-amber-600">
                        <span class="text-sm"><b class="block text-slate-900">Private Trip</b><span class="text-xs text-slate-500">Pilih tanggal sendiri, kapan saja (tidak perlu menunggu open trip).</span></span>
                    </label>
                </div>
                @error('trip_type')<p class="field-error">{{ $message }}</p>@enderror

                <div class="mt-4" x-show="tripType === 'open'" x-cloak>
                    @if ($schedules->isEmpty())
                        <p class="rounded-xl bg-amber-500/10 p-3 text-sm text-amber-700">Belum ada jadwal open trip. Silakan pilih Private Trip.</p>
                    @else
                        <label class="label" for="tour_schedule_id">Pilih jadwal</label>
                        <select id="tour_schedule_id" name="tour_schedule_id" class="input" x-model="scheduleId" :disabled="tripType !== 'open'">
                            <option value="">Pilih tanggal…</option>
                            @foreach ($schedules as $s)<option value="{{ $s->id }}">{{ tgl($s->date, 'l, d F Y') }} — sisa {{ $s->remaining }} kursi</option>@endforeach
                        </select>
                    @endif
                    @error('tour_schedule_id')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="mt-4" x-show="tripType === 'private'" x-cloak>
                    <label class="label" for="trip_date">Tanggal trip</label>
                    <input id="trip_date" type="date" name="trip_date" class="input" min="{{ today()->addDay()->toDateString() }}" value="{{ old('trip_date') }}" :disabled="tripType !== 'private'">
                    <p class="hint">Minimal besok. Admin akan menyiapkan guide dan rute sesuai tanggal Anda.</p>
                    @error('trip_date')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="mt-4 max-w-xs">
                    <label class="label" for="people">Jumlah peserta <span class="text-brand-ink">*</span></label>
                    <input id="people" name="people" type="number" min="1" max="40" class="input" x-model.number="people" required>
                    @error('people')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </section>

            @if ($addons->isNotEmpty())
                <section class="card card-pad">
                    <h2 class="mb-3 text-lg font-bold">2. Add-on <span class="text-sm font-normal text-slate-500">(opsional)</span></h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($addons as $ad)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                                <input type="checkbox" name="addons[]" value="{{ $ad->id }}" x-model="addons" class="accent-amber-600">
                                <span class="text-sm"><b class="block text-slate-900">{{ $ad->name }}</b><span class="text-xs text-slate-500">{{ rupiah($ad->price) }} {{ $ad->per === 'person' ? '/ orang' : '/ booking' }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="card card-pad">
                <h2 class="mb-4 text-lg font-bold">Kontak Pemesan</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-input name="contact_name" label="Nama" :value="$user->name" required />
                    <x-input name="contact_phone" type="tel" label="Nomor HP / WhatsApp" :value="$user->phone" required />
                    <x-input name="notes" type="textarea" rows="2" label="Catatan (opsional)" class="sm:col-span-2" hint="Mis. level pengalaman, kebutuhan khusus." />
                </div>
            </section>

            <section class="card card-pad">
                <h2 class="mb-1 text-lg font-bold">Metode Pembayaran (lunas)</h2>
                <p class="mb-4 text-xs text-slate-500">Pembayaran di website langsung lunas, tanpa DP.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($methods as $m)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-navy-900 p-4 has-[:checked]:border-brand has-[:checked]:bg-brand/10">
                            <input type="radio" name="payment_method_id" value="{{ $m->id }}" x-model="method" class="accent-amber-600" required>
                            <span class="text-sm"><b class="block text-slate-900">{{ $m->name }}</b><span class="text-xs text-slate-500">{{ $m->type_label }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('payment_method_id')<p class="field-error">{{ $message }}</p>@enderror
            </section>

            @include('partials.policies', ['kind' => 'trip'])

            <label class="flex items-start gap-3 text-sm text-slate-600">
                <input type="checkbox" name="agree" value="1" class="mt-1 accent-amber-600" required @checked(old('agree'))>
                <span>Saya menyetujui kebijakan pemesanan di atas.</span>
            </label>
            @error('agree')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <aside>
            <div class="card card-pad space-y-4 lg:sticky lg:top-24">
                <img src="{{ $package->image_url }}" alt="" class="aspect-[16/9] w-full rounded-xl object-cover">
                <h2 class="text-lg font-bold">{{ $package->name }}</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500"><span x-text="people || 1"></span> orang × {{ rupiah($package->price) }}</span><b x-text="rupiah(price * (people || 1))"></b></div>
                    <div class="flex justify-between" x-show="addonTotal > 0" x-cloak><span class="text-slate-500">Add-on</span><b x-text="rupiah(addonTotal)"></b></div>
                    <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base"><span class="font-semibold text-slate-900">Total (lunas)</span><b class="text-xl text-brand-ink" x-text="rupiah(total)"></b></div>
                </div>
                <button class="btn btn-primary w-full">Buat Booking</button>
                <p class="text-center text-xs text-slate-500">Total final dihitung ulang oleh sistem.</p>
            </div>
        </aside>
    </form>
</div>
@endsection
