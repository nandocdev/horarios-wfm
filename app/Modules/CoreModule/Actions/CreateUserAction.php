<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\DTOs\UserDTO;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea un usuario institucional con roles y permisos básicos.
 */
class CreateUserAction
{
    public function execute(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password ?? Str::random(12)),
                'is_active' => $dto->is_active,
                'force_password_change' => $dto->force_password_change,
            ]);

            // Asignación de roles de Spatie
            if (! empty($dto->roles)) {
                $user->assignRole($dto->roles);
            }

            // [UC-INT-05] Registro de auditoría (disparado vía Observer)

            return $user;
        });
    }

    /**
     * [RIESGOS]
     * - Debilidad de contraseña: Si no se proporciona una contraseña, se genera una aleatoria; el sistema debe asegurar el envío seguro de la misma.
     * - Duplicidad de Email: Aunque está validado en el Form, una condición de carrera podría causar un fallo a nivel de DB.
     */
}
