<div x-data x-show="$store.confirm.open" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="$store.confirm.open = false">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="$store.confirm.open = false"></div>
    <div x-show="$store.confirm.open" x-transition class="card card-pad relative w-full max-w-sm text-center" role="alertdialog" aria-modal="true">
        <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-brand/15 text-brand-ink"><x-icon name="triangle-alert" class="size-6" /></div>
        <p class="text-sm text-slate-700" x-text="$store.confirm.message"></p>
        <div class="mt-5 grid grid-cols-2 gap-3">
            <button type="button" class="btn btn-dark" @click="$store.confirm.open = false">Batal</button>
            <button type="button" class="btn btn-primary" @click="$store.confirm.accept()">Ya, lanjutkan</button>
        </div>
    </div>
</div>
