<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'is_secret'];

    protected $casts = ['is_secret' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting:$key", function () use ($key) {
            return static::where('key', $key)->value('value');
        });

        return $value !== null && $value !== '' ? $value : $default;
    }

    public static function put(string $key, ?string $value, string $group = 'general', bool $isSecret = false): self
    {
        $row = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'is_secret' => $isSecret]
        );
        Cache::forget("setting:$key");
        return $row;
    }
}
