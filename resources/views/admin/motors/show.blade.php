@extends('layouts.admin')
@section('title', $motor->name)
@section('content')
<a href="{{ route('admin.motor.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke Motor</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">{{ $motor->name }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('motors.show', $motor->slug) }}" target="_blank" class="btn btn-ghost btn-sm"><x-icon name="external-link" class="size-4" /> Lihat di website</a>
        <a href="{{ route('admin.motor.edit', $motor) }}" class="btn btn-primary btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
    </div>
</div>
<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1fr]">
    <section class="card overflow-hidden"><img src="{{ $motor->image_url }}" alt="" class="aspect-[16/10] w-full object-cover">
        <div class="grid grid-cols-5 gap-2 p-3">@foreach ($motor->images as $img)<img src="{{ $img->url }}" alt="" class="aspect-[4/3] rounded-lg object-cover">@endforeach</div></section>
    <section class="card card-pad">
        <dl class="divide-y divide-slate-200 text-sm">
            @foreach ([
                'Status' => $motor->status_label, 'Kategori' => $motor->category?->name, 'Merek / Model' => $motor->brand.' '.$motor->model, 'Plat' => $motor->plate_number,
                'Tahun' => $motor->year, 'Mesin' => $motor->cc.' cc · '.$motor->transmission, 'Warna' => $motor->color,
                'Harga harian' => rupiah($motor->price_daily), 'Harga mingguan' => $motor->price_weekly ? rupiah($motor->price_weekly) : '-',
                'Harga bulanan' => $motor->price_monthly ? rupiah($motor->price_monthly) : '-', 'Deposit' => rupiah($motor->deposit), 'Total disewa' => $motor->rental_items_count.'×',
            ] as $k => $v)<div class="flex justify-between py-2"><dt class="text-slate-500">{{ $k }}</dt><dd class="font-medium text-slate-900">{{ $v ?: '-' }}</dd></div>@endforeach
        </dl>
    </section>
</div>
<section class="card mt-6 overflow-hidden"><h2 class="p-5 font-semibold">Riwayat Penyewaan</h2>
    <div class="table-wrap rounded-none border-x-0 border-b-0"><table class="table"><thead><tr><th>Invoice</th><th>Penyewa</th><th>Tanggal</th><th>Status</th></tr></thead><tbody>
    @forelse ($history as $h)
        <tr><td><a class="text-brand-ink hover:underline" href="{{ route('admin.penyewaan.show', $h->rental_id) }}">{{ $h->rental->invoice_no }}</a></td><td>{{ $h->rental->renter_name }}</td><td>{{ tgl($h->rental->start_date, 'd M Y') }} – {{ tgl($h->rental->end_date, 'd M Y') }}</td><td><span class="badge {{ $h->rental->status_color }}">{{ $h->rental->status_label }}</span></td></tr>
    @empty<tr><td colspan="4" class="py-8 text-center text-slate-500">Belum pernah disewa.</td></tr>@endforelse
    </tbody></table></div></section>
@endsection
