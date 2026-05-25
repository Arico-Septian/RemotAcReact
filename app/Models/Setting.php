<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value', 'type', 'label', 'unit'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting_{$key}", function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        return $value !== null ? $value : $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) static::get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => (string) $value]);
        Cache::forget("setting_{$key}");
    }

    public static function allKeyed(): array
    {
        return static::all()->keyBy('key')->toArray();
    }
}
