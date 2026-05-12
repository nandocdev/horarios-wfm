<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\DTOs\UserDTO;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Actualiza los datos de un usuario y sincroniza sus roles.
 */
class UpdateUserAction
{
    public function execute(User $user, UserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto) {
            $data = [
                'name' => $dto->name,
                'email' => $dto->email,
                'is_active' => $dto->is_active,
                'force_password_change' => $dto->force_password_change,
            ];

            if (! empty($dto->password)) {
                $data['password'] = Hash::make($dto->password);
            }

            $user->update($data);

            // Sincronización de roles Spatie (reemplaza los anteriores)
            if (! empty($dto->roles)) {
                $user->syncRoles($dto->roles);
            }

            return $user;
        });
    }

    /**
     * [RIESGOS]
     * - Escalada de privilegios: El cambio de roles debe estar estrictamente protegido por políticas para evitar que un usuario se asigne permisos de administrador.
     * - Inconsistencia de sesión: Al cambiar roles, las sesiones activas del usuario podrían tardar en reflejar los nuevos permisos si la caché no se limpia correctamente.
     */
}
