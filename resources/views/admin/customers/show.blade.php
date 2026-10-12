@extends('layouts.admin')
@section('title', $customer->name)
@section('content')
@php $c = $customer->customer; @endphp
<a href="{{ route('admin.customer.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke Customer</a>
<div class="mt-2 flex flex-wrap items-center justify-between gap-3">
    <div class="flex items-center gap-3"><h1 class="text-2xl font-bold">{{ $customer->name }}</h1>
        <span class="badge {{ $customer->is_blocked ? 'bg-rose-500/15 text-rose-700 ring-rose-200' : 'bg-emerald-400/15 text-emerald-700 ring-emerald-200' }}">{{ $customer->is_blocked ? 'Diblokir' : 'Aktif' }}</span></div>
    <div class="flex gap-2">
        <a href="{{ route('admin.customer.edit', $customer) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-4" /> Edit</a>
        <form method="POST" action="{{ route('admin.customer.block', $customer) }}" @submit.prevent="$store.confirm.ask('{{ $customer->is_blocked ? 'Buka blokir' : 'Blokir' }} customer ini?', $el)">@csrf
            <button class="btn {{ $customer->is_blocked ? 'btn-success' : 'btn-danger' }} btn-sm">{{ $customer->is_blocked ? 'Unblock' : 'Block' }}</button></form>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_2fr]">
    <section class="card card-pad text-sm">
        <dl class="divide-y divide-slate-200">
            @foreach (['Email' => $customer->email, 'No. HP' => $customer->phone, 'NIK' => $c?->nik, 'Nomor SIM' => $c?->sim_number, 'Tanggal lahir' => $c?->birth_date ? tgl($c->birth_date) : null, 'Alamat' => $c?->address,
                'Total rental' => $rentals->count(), 'Rental aktif' => $rentals->whereIn('status', \App\Models\Rental::ACTIVE)->count(),
                'Total transaksi' => rupiah($payments->where('status', 'paid')->sum('amount'))] as $k => $v)
                <div class="flex justify-between gap-4 py-2"><dt class="shrink-0 text-slate-500">{{ $k }}</dt><dd class="text-right font-medium text-slate-900">{{ $v ?: '-' }}</dd></div>
            @endforeach
        </dl>
        <p class="mt-4 flex gap-3">
            @if ($c?->ktp_path)<a class="text-brand-ink hover:underline" target="_blank" href="{{ route('files.document', [$c->id, 'ktp']) }}">Lihat KTP</a>@endif
            @if ($c?->sim_path)<a class="text-brand-ink hover:underline" target="_blank" href="{{ route('files.document', [$c->id, 'sim']) }}">Lihat SIM</a>@endif
        </p>
    </section>
    <div class="space-y-6">
        <section class="card overflow-hidden"><h2 class="p-5 font-semibold">Riwayat Transaksi</h2>
            <div class="table-wrap rounded-none border-x-0 border-b-0"><table class="table"><thead><tr><th>Invoice</th><th>Motor</th><th>Tanggal</th><th>Total</th><th>Status</th></tr></thead><tbody>
            @forelse ($rentals as $r)
                <tr><td><a class="text-brand-ink hover:underline" href="{{ route('admin.penyewaan.show', $r) }}">#{{ $r->invoice_no }}</a></td><td>{{ $r->motorcycle?->name }}</td><td>{{ tgl($r->start_date, 'd M Y') }}</td><td>{{ rupiah($r->total) }}</td><td><span class="badge {{ $r->status_color }}">{{ $r->status_label }}</span></td></tr>
            @empty<tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada transaksi.</td></tr>@endforelse
            </tbody></table></div></section>
        <section class="card overflow-hidden"><h2 class="p-5 font-semibold">Riwayat Pembayaran</h2>
            <div class="table-wrap rounded-none border-x-0 border-b-0"><table class="table"><thead><tr><th>Invoice</th><th>Metode</th><th>Jumlah</th><th>Status</th></tr></thead><tbody>
            @forelse ($payments as $p)
                <tr><td>#{{ $p->rental->invoice_no }}</td><td>{{ $p->method?->name }}</td><td>{{ rupiah($p->amount) }}</td><td><span class="badge {{ $p->status_color }}">{{ $p->status_label }}</span></td></tr>
            @empty<tr><td colspan="4" class="py-8 text-center text-slate-500">Belum ada pembayaran.</td></tr>@endforelse
            </tbody></table></div></section>
    </div>
</div>
@endsection
