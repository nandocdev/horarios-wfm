<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

class EloquentAppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public static function set(string $key, mixed $value, ?string $type = null, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'description' => $description],
        );
    }
}
