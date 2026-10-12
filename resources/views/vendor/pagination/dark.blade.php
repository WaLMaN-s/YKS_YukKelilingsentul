@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination" class="flex flex-wrap items-center justify-center gap-1.5">
        @if ($paginator->onFirstPage())
            <span class="rounded-full px-3.5 py-2 text-sm text-slate-600">‹</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-full px-3.5 py-2 text-sm text-slate-700 hover:bg-slate-900/5" aria-label="Sebelumnya">‹</a>
        @endif

        @if (method_exists($paginator, 'elements'))
            @foreach ($paginator->elements() as $element)
                @if (is_string($element))
                    <span class="px-2 text-slate-600">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="rounded-full bg-brand px-3.5 py-2 text-sm font-semibold text-ink">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="rounded-full px-3.5 py-2 text-sm text-slate-700 hover:bg-slate-900/5">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-full px-3.5 py-2 text-sm text-slate-700 hover:bg-slate-900/5" aria-label="Berikutnya">›</a>
        @else
            <span class="rounded-full px-3.5 py-2 text-sm text-slate-600">›</span>
        @endif
    </nav>
@endif
