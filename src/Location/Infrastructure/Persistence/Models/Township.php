<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Location\Infrastructure\Database\Factories\TownshipFactory;

class Township extends Model
{
    /** @use HasFactory<TownshipFactory> */
    use HasFactory;

    protected static function newFactory(): TownshipFactory
    {
        return TownshipFactory::new();
    }

    protected $fillable = [
        'district_id',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
