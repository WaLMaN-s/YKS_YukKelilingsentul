@extends('layouts.admin')
@section('title', 'Review')
@section('content')
<h1 class="text-2xl font-bold">Review</h1>
<form method="GET" class="mt-5"><select name="status" class="input !w-52" onchange="this.form.submit()"><option value="">Semua status</option>@foreach (\App\Models\Review::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach</select></form>
<div class="mt-5 grid gap-4 lg:grid-cols-2">
    @forelse ($reviews as $r)
        <article class="card card-pad">
            <div class="flex items-start justify-between gap-3">
                <div><p class="font-semibold">{{ $r->user->name }} <span class="text-xs font-normal text-slate-500">· {{ $r->motorcycle->name }}</span></p><x-stars :rating="$r->rating" /></div>
                <span class="badge {{ ['pending' => 'bg-amber-400/15 text-amber-700 ring-amber-200', 'approved' => 'bg-emerald-400/15 text-emerald-700 ring-emerald-200', 'rejected' => 'bg-rose-500/15 text-rose-700 ring-rose-200'][$r->status] }}">{{ \App\Models\Review::STATUSES[$r->status] }}</span>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ $r->comment ?: '(tanpa komentar)' }}</p>
            <p class="mt-2 text-xs text-slate-500">#{{ $r->rental->invoice_no }} · {{ $r->created_at->diffForHumans() }}</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($r->status !== 'approved')<form method="POST" action="{{ route('admin.review.approve', $r) }}">@csrf<button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" /> Setujui</button></form>@endif
                @if ($r->status !== 'rejected')<form method="POST" action="{{ route('admin.review.reject', $r) }}">@csrf<button class="btn btn-dark btn-sm">Tolak</button></form>@endif
                <form method="POST" action="{{ route('admin.review.destroy', $r) }}" @submit.prevent="$store.confirm.ask('Hapus review ini?', $el)">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><x-icon name="trash-2" class="size-4" /></button></form>
            </div>
        </article>
    @empty
        <p class="card card-pad text-center text-slate-500 lg:col-span-2">Belum ada review.</p>
    @endforelse
</div>
<div class="mt-6">{{ $reviews->links() }}</div>
@endsection
