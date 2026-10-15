<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Cache in-memory per request: menghindari 1 query DB untuk setiap pemanggilan setting(). */
    private static ?array $memo = null;

    public static function allCached(): array
    {
        if (static::$memo !== null) {
            return static::$memo;
        }

        return static::$memo = Cache::rememberForever('app_settings', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::allCached()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(array $data): void
    {
        foreach ($data as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('app_settings');
        static::$memo = null;
    }
}
