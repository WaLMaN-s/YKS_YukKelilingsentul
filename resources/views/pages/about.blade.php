@extends('layouts.app')
@section('title', 'Tentang Kami')
@section('description', 'Tentang Yuk Keliling Sentul: penyedia sewa motor trail dan paket wisata adventure di Sentul, Bogor.')
@section('content')
<section class="border-b border-slate-200 bg-navy-900 py-10"><div class="container-x"><h1 class="text-3xl font-bold">Tentang Kami</h1></div></section>
<div class="container-x grid gap-10 py-12 lg:grid-cols-2">
    <div class="prose-lite">
        <h2 class="mb-3 text-2xl font-bold">{{ setting('site_name') }}</h2>
        <p>Kami menyediakan sewa motor trail dan paket wisata offroad di kawasan Sentul, Bogor. Semua motor dirawat rutin dan diperiksa sebelum setiap perjalanan, sehingga Anda bisa fokus menikmati jalur dan pemandangan.</p>
        <p>Tim guide kami hafal jalur hutan pinus, perbukitan, hingga aliran sungai kecil. Baik pemula maupun rider berpengalaman, selalu ada rute yang sesuai.</p>
        <p class="font-semibold italic text-brand-ink">More Ride, More Story.</p>
        <p>Basecamp kami di <b class="text-slate-900">Gunung Pancar</b> dilengkapi {{ strtolower(implode(', ', $facilities)) }}.</p>
        <a href="{{ route('contact') }}" class="btn btn-primary mt-3">Hubungi Kami</a>
    </div>
    <div class="grid grid-cols-2 gap-4">
        @foreach ([[$unitCounts['trail'].' unit', 'Motor trail'], [$unitCounts['jeep'].' unit', 'Jeep (maks 4 orang)'], ['Guide', 'Berpengalaman'], ['Gunung Pancar', 'Basecamp kami']] as [$n, $t])
            <div class="card card-pad text-center"><p class="text-3xl font-extrabold text-brand-ink">{{ $n }}</p><p class="mt-1 text-sm text-slate-500">{{ $t }}</p></div>
        @endforeach
    </div>
</div>

{{-- LOKASI & PETA --}}
<section class="container-x pb-16">
    @include('partials.location-map')
</section>
@endsection
