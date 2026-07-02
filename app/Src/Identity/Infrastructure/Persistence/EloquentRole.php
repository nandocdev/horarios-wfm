<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use App\Src\Identity\Infrastructure\Observers\RoleObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class EloquentRole extends SpatieRole
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'guard_name',
        'code',
        'hierarchy_level',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::observe(RoleObserver::class);
    }

    public function users(): MorphToMany
    {
        $userClass = app(PermissionRegistrar::class)->getUserClass();

        return $this->morphToMany(
            $userClass,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key'),
        );
    }

    public function scopeByHierarchy(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('hierarchy_level', $direction);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
