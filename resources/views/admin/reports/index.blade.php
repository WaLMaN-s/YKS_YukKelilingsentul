@extends('layouts.admin')
@section('title', 'Laporan')
@section('content')
@php $q = ['type' => $type, 'range' => $range, 'from' => $from, 'to' => $to]; @endphp
<h1 class="text-2xl font-bold">Laporan Transaksi</h1>
<p class="text-sm text-slate-500">Periode {{ tgl($start) }} – {{ tgl($end) }}</p>

<form method="GET" class="card card-pad mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end" x-data="{ range: '{{ $range }}' }">
    <div><label class="label" for="type">Jenis laporan</label>
        <select id="type" name="type" class="input">@foreach (\App\Services\ReportService::TYPES as $k => $v)<option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>@endforeach</select></div>
    <div><label class="label" for="range">Periode</label>
        <select id="range" name="range" class="input" x-model="range">@foreach (\App\Services\ReportService::RANGES as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div x-show="range === 'custom'" x-cloak><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $from }}" class="input"></div>
    <div x-show="range === 'custom'" x-cloak><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $to }}" class="input"></div>
    <button class="btn btn-primary"><x-icon name="filter" class="size-4" /> Tampilkan</button>
</form>

<div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ([['Pendapatan', rupiah($summary['revenue']), 'wallet'], ['Jumlah rental', $summary['rentals'], 'receipt'], ['Denda & kerusakan', rupiah($summary['fines']), 'triangle-alert'], ['Customer baru', $summary['customers'], 'users']] as [$l, $v, $i])
        <div class="card card-pad flex items-center gap-4"><span class="flex size-11 items-center justify-center rounded-xl bg-brand/15 text-brand-ink"><x-icon :name="$i" /></span><div><p class="text-xs text-slate-500">{{ $l }}</p><p class="text-xl font-bold">{{ $v }}</p></div></div>
    @endforeach
</div>

<div class="mt-6 flex flex-wrap items-center justify-between gap-3">
    <h2 class="text-lg font-bold">{{ \App\Services\ReportService::TYPES[$type] }} <span class="text-sm font-normal text-slate-500">({{ count($report['rows']) }} baris)</span></h2>
    <div class="flex gap-2">
        <a href="{{ route('admin.laporan.export', $q + ['format' => 'csv']) }}" class="btn btn-dark btn-sm"><x-icon name="download" class="size-4" /> CSV / Excel</a>
        <a href="{{ route('admin.laporan.export', $q + ['format' => 'print']) }}" target="_blank" class="btn btn-dark btn-sm"><x-icon name="printer" class="size-4" /> Cetak / PDF</a>
    </div>
</div>
<div class="table-wrap mt-3"><table class="table">
    <thead><tr>@foreach ($report['headers'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($report['rows'] as $row)
        <tr>@foreach ($row as $cell)<td>{{ is_numeric($cell) && $cell > 9999 ? number_format($cell, 0, ',', '.') : $cell }}</td>@endforeach</tr>
    @empty<tr><td colspan="{{ count($report['headers']) }}" class="py-12 text-center text-slate-500">Tidak ada data pada periode ini.</td></tr>@endforelse
    </tbody>
</table></div>
@endsection
