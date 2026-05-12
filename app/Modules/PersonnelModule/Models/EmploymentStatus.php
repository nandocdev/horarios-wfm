<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentStatus extends Model
{
    protected $table = 'employment_statuses';

    protected $fillable = ['name', 'description', 'code', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query): mixed
    {
        return $query->where('is_active', false);
    }
}
