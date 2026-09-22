<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Src\Location\Infrastructure\Database\Factories\DistrictFactory;

class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
    use HasFactory;

    protected static function newFactory(): DistrictFactory
    {
        return DistrictFactory::new();
    }

    protected $fillable = [
        'province_id',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Province, $this>
     */
    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * @return HasMany<Township, $this>
     */
    public function townships(): HasMany
    {
        return $this->hasMany(Township::class);
    }
}
