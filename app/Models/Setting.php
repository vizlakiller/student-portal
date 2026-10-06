<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value settings chosen by the super admin (branding).
 * Read with Setting::get('portal_name'), save with Setting::put('portal_name', '...').
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;


    public static function get(string $key, mixed $default = null): mixed
    {
        return self::values()[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::flush();
    }

    public static function forgetMany(array $keys): void
    {
        self::whereIn('key', $keys)->delete();
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget('settings');
        app()->forgetInstance('portal.settings');
    }

    /**
     * All settings as [key => value], cached so each page load doesn't query the database.
     */
    private static function values(): array
    {
        // Already loaded during this request
        if (app()->bound('portal.settings')) {
            return app('portal.settings');
        }

        // Before "php artisan migrate" has created the table, just use the defaults.
        if (! Schema::hasTable('settings')) {
            return [];
        }

        $values = Cache::rememberForever('settings', fn () => self::pluck('value', 'key')->all());
        app()->instance('portal.settings', $values);

        return $values;
    }
}
