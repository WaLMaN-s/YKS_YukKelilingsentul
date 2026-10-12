@php
    $flash = session('success') ?? session('error') ?? session('status');
    $type = session('error') ? 'error' : 'success';
@endphp
<div x-data="toast(@js($flash), '{{ $type }}')" x-show="show" x-cloak x-transition.opacity
     class="fixed right-4 top-20 z-[70] max-w-sm" role="status" aria-live="polite">
    <div class="flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm shadow-2xl backdrop-blur"
         :class="type === 'error' ? 'border-rose-200 bg-rose-50 text-rose-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700'">
        <span x-text="message" class="flex-1"></span>
        <button type="button" @click="show = false" class="opacity-70 hover:opacity-100" aria-label="Tutup">&times;</button>
    </div>
</div>
