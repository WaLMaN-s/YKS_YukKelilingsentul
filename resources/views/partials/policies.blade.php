@php $rd = (int) setting('reschedule_min_days', 2); @endphp
<div class="rounded-2xl border border-brand/25 bg-brand/5 p-4 text-sm">
    <p class="mb-2 flex items-center gap-2 font-semibold text-brand-ink"><x-icon name="info" class="size-4" /> Kebijakan pemesanan</p>
    <ul class="list-disc space-y-1 pl-5 text-slate-600">
        <li>Pembayaran di website <b class="text-slate-900">langsung lunas</b> (tanpa DP).</li>
        <li>Perubahan jadwal maksimal <b class="text-slate-900">H-{{ $rd }}</b> dari tanggal {{ $kind ?? 'sewa' }}, selama unit/kuota tersedia.</li>
        <li>Pembatalan pada <b class="text-slate-900">hari H</b>: pembayaran <b class="text-slate-900">hangus</b>.</li>
        <li>Kerusakan yang terjadi selama pemakaian <b class="text-slate-900">ditanggung penyewa</b>.</li>
    </ul>
</div>
