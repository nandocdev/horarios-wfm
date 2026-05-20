<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\DTOs\RoleDTO;
use App\Modules\CoreModule\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Acción para registrar un nuevo rol institucional.
 */
class CreateRoleAction
{
    /**
     * Ejecuta la creación del rol dentro de una transacción.
     */
    public function execute(RoleDTO $dto): Role
    {
        return DB::transaction(function () use ($dto) {
            return Role::create([
                'name' => $dto->name,
                'code' => $dto->code,
                'hierarchy_level' => $dto->hierarchy_level,
                'guard_name' => $dto->guard_name,
            ]);
        });
    }

    /**
     * [RIESGOS]
     * - Duplicidad de código: El código del rol es único en base de datos.
     * - Conflictos de jerarquía: Múltiples roles en el mismo nivel pueden causar ambigüedad en la lógica de herencia.
     */
}
