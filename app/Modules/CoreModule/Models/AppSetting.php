<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Helper para obtener un valor de configuración.
     */
    public static function get(string $key, mixed $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper para establecer un valor de configuración.
     */
    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );
    }
}
