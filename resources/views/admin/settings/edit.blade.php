@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('content')
<h1 class="text-2xl font-bold">Pengaturan Website</h1>
<form method="POST" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" class="mt-6 max-w-4xl space-y-6">@csrf @method('PUT')
    @foreach ([
        'Identitas' => ['site_name', 'tagline', 'logo', 'favicon'],
        'Kontak & Sosial Media' => ['whatsapp', 'email', 'address', 'basecamp_facilities', 'instagram', 'facebook', 'tiktok', 'operating_hours'],
        'Aturan Rental' => ['min_age', 'min_rental_days', 'reschedule_min_days', 'default_deposit', 'dp_percent', 'late_fee_per_hour', 'return_time'],
    ] as $group => $keys)
        <section class="card card-pad grid gap-4 sm:grid-cols-2">
            <h2 class="text-lg font-bold sm:col-span-2">{{ $group }}</h2>
            @foreach ($keys as $key)
                @php $f = $fields[$key]; @endphp
                @if ($f['type'] === 'file')
                    <div>
                        <x-input :name="$key" type="file" :label="$f['label']" :value="$values[$key] ?? null" accept="image/png,image/jpeg,image/webp,image/svg+xml,image/x-icon" />
                        @if (! empty($values[$key]))<img src="{{ media_url($values[$key]) }}" alt="" class="mt-2 h-14 rounded-lg border border-slate-200 bg-navy-900 object-contain p-1">@endif
                    </div>
                @else
                    <x-input :name="$key" :type="$f['type']" :label="$f['label']" :value="$values[$key] ?? null" :hint="$f['hint'] ?? null" :class="$f['type'] === 'textarea' ? 'sm:col-span-2' : ''" />
                @endif
            @endforeach
        </section>
    @endforeach
    <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan Pengaturan</button>
</form>
@endsection
