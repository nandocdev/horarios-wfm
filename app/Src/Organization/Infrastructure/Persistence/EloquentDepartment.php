<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloquentDepartment extends Model
{
    protected $table = 'departments';

    protected $fillable = ['directorate_id', 'name', 'description'];

    public function directorate(): BelongsTo
    {
        return $this->belongsTo(EloquentDirectorate::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(EloquentPosition::class, 'department_id');
    }
}
