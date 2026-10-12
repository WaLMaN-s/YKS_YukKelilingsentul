@extends('layouts.admin')
@section('title', ($item->exists ? 'Edit ' : 'Tambah ').$ctl->singular())
@section('content')
<a href="{{ route($ctl->routeBase().'.index') }}" class="text-sm text-slate-500 hover:text-brand-ink">← Kembali ke {{ $ctl->title() }}</a>
<h1 class="mt-2 text-2xl font-bold">{{ $item->exists ? 'Edit' : 'Tambah' }} {{ $ctl->singular() }}</h1>

<form method="POST" enctype="multipart/form-data"
      action="{{ $item->exists ? route($ctl->routeBase().'.update', $item->getKey()) : route($ctl->routeBase().'.store') }}"
      class="card card-pad mt-6 max-w-3xl space-y-5">
    @csrf
    @if ($item->exists) @method('PUT') @endif

    @foreach ($fields as $name => $f)
        @php
            $type = $f['type'];
            $value = $type === 'checkbox' ? ($item->exists ? $item->{$name} : true) : $item->{$name};
        @endphp
        <x-input :name="$name" :label="$f['label']" :type="$type" :value="$value" :options="$f['options'] ?? []"
                 :hint="$f['hint'] ?? null" :required="str_contains(implode('|', (array) ($f['rules'] ?? [])), 'required') || (($f['required_on_create'] ?? false) && ! $item->exists)"
                 :placeholder-option="$type === 'select' ? 'Pilih...' : null" />
    @endforeach

    <div class="flex gap-3 border-t border-slate-200 pt-5">
        <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        <a href="{{ route($ctl->routeBase().'.index') }}" class="btn btn-ghost">Batal</a>
    </div>
</form>
@endsection
