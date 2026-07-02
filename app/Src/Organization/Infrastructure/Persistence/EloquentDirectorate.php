<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentDirectorate extends Model
{
    protected $table = 'directorates';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function departments(): HasMany
    {
        return $this->hasMany(EloquentDepartment::class, 'directorate_id');
    }
}
