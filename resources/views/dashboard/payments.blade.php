@extends('layouts.dashboard')
@section('title', 'Pembayaran')
@section('panel')
<h1 class="text-2xl font-bold">Riwayat Pembayaran</h1>
<div class="table-wrap mt-6">
    <table class="table">
        <thead><tr><th>Invoice</th><th>Motor</th><th>Metode</th><th>Jumlah</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse ($payments as $p)
            <tr>
                <td class="font-medium text-slate-900">#{{ $p->rental->invoice_no }}</td>
                <td>{{ $p->rental->motorcycle?->name }}</td>
                <td>{{ $p->method?->name }}</td>
                <td>{{ rupiah($p->amount) }}</td>
                <td><span class="badge {{ $p->status_color }}">{{ $p->status_label }}</span></td>
                <td class="text-right"><a class="btn btn-ghost btn-sm" href="{{ route('dashboard.bookings.show', $p->rental_id) }}">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="py-10 text-center text-slate-500">Belum ada pembayaran.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-6">{{ $payments->links() }}</div>
@endsection
