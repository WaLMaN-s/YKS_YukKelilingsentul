@php $motor = $rental->motorcycle; @endphp
<article class="card overflow-hidden sm:flex">
    <img src="{{ $motor?->image_url }}" alt="" loading="lazy" class="h-36 w-full object-cover sm:h-auto sm:w-48">
    <div class="flex flex-1 flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold text-brand-ink">#{{ $rental->invoice_no }}</p>
            <h3 class="text-base font-semibold">{{ $motor?->name }}@if ($rental->quantity > 1) <span class="text-sm font-normal text-slate-500">× {{ $rental->quantity }} unit</span>@endif</h3>
            @if ($rental->package)<p class="text-xs text-brand-ink">{{ $rental->package->name }}</p>@endif
            <p class="mt-1 text-sm text-slate-500">{{ tgl($rental->start_date) }} – {{ tgl($rental->end_date) }} · {{ $rental->days }} Hari</p>
            <p class="mt-1 text-lg font-bold">{{ rupiah($rental->total) }}</p>
        </div>
        <div class="flex flex-col items-start gap-2 sm:items-end">
            <span class="badge {{ $rental->status_color }}">{{ $rental->status_label }}</span>
            <a href="{{ route('dashboard.bookings.show', $rental->id) }}" class="btn btn-ghost btn-sm">Detail</a>
            @if (in_array($rental->status, ['waiting_payment', 'pending']) && $rental->payment?->canUploadProof())
                <a href="{{ route('payment.show', $rental->id) }}" class="btn btn-primary btn-sm">Bayar</a>
            @endif
        </div>
    </div>
</article>
