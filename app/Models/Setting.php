<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Simple key/value store for site-wide settings (name, tagline, footer
 * text, etc). Deliberately minimal — this is the kind of table a real
 * project usually grows in its own direction, so it's kept as plain
 * key/value rather than a rigid single-row "Settings" table.
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    // The table is keyed by its string `key` column (there is no `id`),
    // so Eloquent must be told, otherwise UPDATE/DELETE would target a
    // non-existent `id` column.
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * The public site name: the admin-editable setting when present,
     * otherwise APP_NAME from .env.
     */
    public static function siteName(): string
    {
        return static::get('site_name') ?: (string) config('app.name');
    }

    /**
     * @return array<string, string|null>
     */
    public static function allAsArray(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }
}
