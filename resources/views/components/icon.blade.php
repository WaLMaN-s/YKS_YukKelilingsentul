@props(['name'])
<i data-lucide="{{ $name }}" {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} aria-hidden="true"></i>
