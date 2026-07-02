<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use Spatie\Permission\Models\Permission as SpatiePermission;

class EloquentPermission extends SpatiePermission
{
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'guard_name',
    ];
}
