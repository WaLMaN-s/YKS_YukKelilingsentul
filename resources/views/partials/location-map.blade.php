{{--
    Kartu lokasi + peta Google. Dipakai di beranda dan halaman Tentang Kami.

    Alamat, link, dan pin peta diambil dari satu variabel ($petaAlamat) supaya
    tidak ada dua alamat berbeda di situs. Ubah di sini kalau lokasinya pindah.

    Opsional: @include('partials.location-map', ['petaAlamat' => 'Alamat lain'])
--}}
@php
    $petaAlamat = $petaAlamat ?? 'Hambalang, Sentul, Bukit Batu Kemun, Gunung Pancar, Bogor';
    $petaLink = 'https://www.google.com/maps/search/?api=1&query='.urlencode($petaAlamat);
    $petaEmbed = 'https://www.google.com/maps?q='.urlencode($petaAlamat).'&z=14&output=embed';
@endphp
<div class="card overflow-hidden">
    <div class="card-pad flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-brand/15 text-brand-ink"><x-icon name="map-pin" /></span>
            <div>
                <h2 class="text-lg font-bold">Lokasi Basecamp</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $petaAlamat }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ setting('operating_hours') }}</p>
            </div>
        </div>
        <a href="{{ $petaLink }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm"><x-icon name="map-pin" class="size-4" /> Buka di Google Maps</a>
    </div>
    <iframe src="{{ $petaEmbed }}" class="h-72 w-full sm:h-96" style="border:0" loading="lazy" title="Peta lokasi basecamp {{ setting('site_name') }}" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
</div>
