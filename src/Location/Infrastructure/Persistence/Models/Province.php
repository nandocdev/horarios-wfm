<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Src\Location\Infrastructure\Database\Factories\ProvinceFactory;

class Province extends Model
{
    /** @use HasFactory<ProvinceFactory> */
    use HasFactory;

    protected static function newFactory(): ProvinceFactory
    {
        return ProvinceFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    /**
     * @return HasManyThrough<Township, District, $this>
     */
    public function townships(): HasManyThrough
    {
        return $this->hasManyThrough(Township::class, District::class);
    }
}
