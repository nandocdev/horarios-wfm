<?php

declare(strict_types=1);

namespace App\Modules\GeoModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    protected $fillable = ['name', 'code'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function townships(): HasMany
    {
        return $this->hasManyThrough(Township::class, District::class);
    }
}
