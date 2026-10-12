<?php

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            return Setting::get($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }
}

if (! function_exists('rupiah')) {
    function rupiah(int|float|string|null $value): string
    {
        return 'Rp'.number_format((float) $value, 0, ',', '.');
    }
}

if (! function_exists('tgl')) {
    function tgl(mixed $date, string $format = 'd F Y'): string
    {
        if (blank($date)) {
            return '-';
        }

        return Carbon::parse($date)->translatedFormat($format);
    }
}

if (! function_exists('media_url')) {
    /** URL untuk file publik: gambar bawaan (images/...) atau hasil upload (disk public). */
    function media_url(?string $path, string $fallback = 'images/placeholder.svg'): string
    {
        if (blank($path)) {
            return asset($fallback);
        }
        if (Str::startsWith($path, ['images/', 'http'])) {
            return Str::startsWith($path, 'http') ? $path : asset($path);
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('wa_link')) {
    function wa_link(?string $message = null): string
    {
        $number = preg_replace('/\D+/', '', (string) setting('whatsapp', config('services.whatsapp.number')));
        if (Str::startsWith($number, '0')) {
            $number = '62'.substr($number, 1);
        }
        $url = 'https://wa.me/'.$number;

        return $message ? $url.'?text='.rawurlencode($message) : $url;
    }
}
