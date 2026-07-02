<?php

declare(strict_types=1);

namespace App\Src\Organization\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloquentPosition extends Model
{
    protected $table = 'positions';

    protected $fillable = ['department_id', 'name', 'description', 'position_code', 'salary', 'is_active'];

    protected $casts = ['salary' => 'decimal:2', 'is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(EloquentDepartment::class);
    }
}
