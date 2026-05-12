<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Spatie\Permission\Models\Role as SpatieRole;

#[Fillable(['name', 'guard_name', 'code', 'hierarchy_level'])]
class Role extends SpatieRole
{
    /**
     * Modelo de Rol institucional extendido para soportar jerarquías operativas.
     */
}
