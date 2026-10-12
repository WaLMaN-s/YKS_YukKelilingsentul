@props(['rating' => 0])
<span class="inline-flex items-center gap-0.5 text-brand-ink" aria-label="Rating {{ $rating }} dari 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="size-4 {{ $i <= round($rating) ? 'fill-current' : 'fill-none stroke-current opacity-40' }}" viewBox="0 0 24 24" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    @endfor
</span>
