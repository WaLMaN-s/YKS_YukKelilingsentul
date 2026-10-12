@props([
    'name', 'label' => null, 'type' => 'text', 'value' => null, 'options' => [], 'hint' => null,
    'required' => false, 'placeholder' => null, 'rows' => 4, 'placeholderOption' => null,
])
@php
    $dotName = str_replace(['[', ']'], ['.', ''], $name);
    $current = old($dotName, $value);
    if ($current instanceof \DateTimeInterface) {
        $current = $current->format($type === 'datetime-local' ? 'Y-m-d\TH:i' : 'Y-m-d');
    }
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label && $type !== 'checkbox')
        <label for="f-{{ $name }}" class="label">{{ $label }}@if ($required)<span class="text-brand-ink"> *</span>@endif</label>
    @endif

    @if ($type === 'textarea' || $type === 'lines')
        <textarea id="f-{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" placeholder="{{ $placeholder }}" class="input" @required($required)>{{ is_array($current) ? implode("\n", $current) : $current }}</textarea>
    @elseif ($type === 'select')
        <select id="f-{{ $name }}" name="{{ $name }}" class="input" @required($required) {{ $attributes->only(['x-model', '@change']) }}>
            @if ($placeholderOption !== null)<option value="">{{ $placeholderOption }}</option>@endif
            @foreach ($options as $key => $text)
                <option value="{{ $key }}" @selected((string) $current === (string) $key)>{{ $text }}</option>
            @endforeach
        </select>
    @elseif ($type === 'checkbox')
        <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-navy-900 px-4 py-3 text-sm text-slate-700">
            <input type="hidden" name="{{ $name }}" value="0">
            <input type="checkbox" id="f-{{ $name }}" name="{{ $name }}" value="1" class="size-4 rounded border-slate-300 bg-white accent-amber-600" @checked((bool) $current)>
            {{ $label }}
        </label>
    @elseif ($type === 'file' || $type === 'image')
        <input id="f-{{ $name }}" type="file" name="{{ $name }}" @if($attributes->get('multiple')) multiple @endif accept="{{ $attributes->get('accept', 'image/*') }}" @required($required)
               class="block w-full rounded-xl border border-dashed border-slate-300 bg-navy-900 px-3 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-full file:border-0 file:bg-brand file:px-4 file:py-1.5 file:text-xs file:font-semibold file:text-ink">
        @if ($type === 'image' && $value)
            <img src="{{ media_url($value) }}" alt="" class="mt-2 h-20 rounded-lg border border-slate-200 object-cover">
        @endif
    @else
        <input id="f-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $type === 'password' ? '' : $current }}" placeholder="{{ $placeholder }}" class="input" @required($required)
               {{ $attributes->only(['min', 'max', 'step', 'autocomplete', 'x-model', 'x-model.number', '@input', ':min']) }}>
    @endif

    @if ($hint)<p class="hint">{{ $hint }}</p>@endif
    @error($dotName)<p class="field-error">{{ $message }}</p>@enderror
</div>
