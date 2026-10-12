<div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" class="relative flex size-10 items-center justify-center rounded-full text-slate-700 transition hover:bg-slate-900/5" aria-label="Notifikasi" :aria-expanded="open">
        <x-icon name="bell" />
        @if ($unreadCount > 0)
            <span class="absolute right-1 top-1 flex size-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>
    <div x-show="open" x-cloak x-transition class="absolute right-0 z-50 mt-2 w-80 max-w-[90vw] overflow-hidden rounded-2xl border border-slate-200 bg-navy-800 shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Notifikasi</p>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
                    <button class="text-xs text-brand-ink hover:underline">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
        <ul class="max-h-80 divide-y divide-slate-200 overflow-y-auto">
            @forelse ($latestNotifications as $n)
                <li>
                    <a href="{{ route('notifications.open', $n->id) }}" class="block px-4 py-3 transition hover:bg-slate-900/5 {{ $n->read_at ? 'opacity-60' : '' }}">
                        <p class="flex items-center gap-2 text-sm font-medium text-slate-900">@unless($n->read_at)<span class="size-2 rounded-full bg-brand"></span>@endunless{{ $n->title }}</p>
                        @if ($n->body)<p class="mt-0.5 text-xs text-slate-500">{{ $n->body }}</p>@endif
                        <p class="mt-1 text-[11px] text-slate-500">{{ $n->created_at->diffForHumans() }}</p>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-500">Belum ada notifikasi.</li>
            @endforelse
        </ul>
    </div>
</div>
