@extends('layouts.admin')
@section('title', 'Pengembalian Motor')
@section('content')
<a href="{{ route('admin.penyewaan.show', $rental) }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali</a>
<h1 class="mt-2 text-2xl font-bold">Pengembalian — #{{ $rental->invoice_no }}</h1>
<p class="text-sm text-slate-500">{{ $rental->motorcycle?->name }} · {{ $rental->renter_name }} · Batas kembali {{ tgl($dueAt, 'd M Y, H:i') }}</p>

<form method="POST" action="{{ route('admin.penyewaan.return.store', $rental) }}" class="card card-pad mt-6 max-w-3xl space-y-5"
      x-data="{ returned: '{{ now()->format('Y-m-d\TH:i') }}', due: '{{ $dueAt->format('Y-m-d\TH:i') }}', perHour: {{ $lateFeePerHour }}, damage: {{ (int) old('damage_fee', 0) }},
                get lateHours() { const m = (new Date(this.returned) - new Date(this.due)) / 60000; return m > 0 ? Math.ceil(m / 60) : 0 },
                get lateFee() { return this.lateHours * this.perHour },
                fmt(n) { return 'Rp' + Math.round(n).toLocaleString('id-ID') } }">@csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="returned_at">Tanggal & jam kembali <span class="text-brand-ink">*</span></label>
            <input id="returned_at" type="datetime-local" name="returned_at" x-model="returned" class="input" required>
        </div>
        <x-input name="condition" type="select" label="Kondisi motor" :options="\App\Models\RentalReturn::CONDITIONS" value="baik" required />
        <x-input name="fuel_level" type="select" label="Level BBM" :options="\App\Models\RentalReturn::FUEL" value="full" required />
        <div>
            <label class="label" for="damage_fee">Biaya kerusakan (Rp)</label>
            <input id="damage_fee" type="number" min="0" step="1000" name="damage_fee" x-model.number="damage" class="input">
        </div>
    </div>
    <x-input name="damage_description" type="textarea" rows="2" label="Keterangan kerusakan" />
    <x-input name="notes" type="textarea" rows="2" label="Catatan" />

    <div class="rounded-xl bg-navy-900 p-4 text-sm">
        <p class="font-semibold text-slate-900">Perhitungan otomatis</p>
        <div class="mt-2 flex justify-between"><span class="text-slate-500">Keterlambatan (<span x-text="lateHours"></span> jam × {{ rupiah($lateFeePerHour) }})</span><span x-text="fmt(lateFee)"></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Biaya kerusakan</span><span x-text="fmt(damage || 0)"></span></div>
        <div class="mt-2 flex justify-between border-t border-slate-200 pt-2 font-bold"><span>Biaya tambahan</span><span class="text-brand-ink" x-text="fmt(lateFee + (damage || 0))"></span></div>
        <p class="mt-2 text-xs text-slate-500">Deposit {{ rupiah($rental->deposit) }} dipotong biaya tambahan. Angka final dihitung ulang oleh server saat disimpan.</p>
    </div>
    <div class="flex gap-3"><button class="btn btn-primary"><x-icon name="check" class="size-4" /> Simpan Pengembalian</button><a href="{{ route('admin.penyewaan.show', $rental) }}" class="btn btn-ghost">Batal</a></div>
</form>
@endsection
