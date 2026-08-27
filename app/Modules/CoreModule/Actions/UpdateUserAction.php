<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\DTOs\UserDTO;
use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Actualiza los datos de un usuario y sincroniza sus roles de forma segura.
 *
 * [RIESGO] Escalada de privilegios: El cambio de roles debe estar estrictamente
 *           protegido por políticas para evitar que un usuario se asigne
 *           permisos de administrador.
 * [RIESGO] Inconsistencia de sesión: Al cambiar roles, las sesiones activas
 *           del usuario podrían tardar en reflejar los nuevos permisos si la
 *           caché no se limpia correctamente.
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
                $data['force_password_change'] = false;
            }

            $user->update($data);

            // Sincronización atómica de roles: usa el array completo solicitado.
            // Esto preserva 'agent' si viene en el DTO y elimina solo los que no están.
            if (! empty($dto->roles)) {
                $requestedRoles = array_values(array_unique($dto->roles));

                // Validar que todos los roles solicitados existan en BD
                $existingRoles = User::getPermissionsViaRoles()->pluck('name')->unique();
                // Más simple: verificar contra tabla roles
                $validRoles = Role::whereIn('name', $requestedRoles)->pluck('name')->toArray();
                $invalidRoles = array_diff($requestedRoles, $validRoles);

                if (! empty($invalidRoles)) {
                    throw new \InvalidArgumentException('Roles inválidos: '.implode(', ', $invalidRoles));
                }

                // syncRoles atómico con el conjunto completo solicitado
                $user->syncRoles($requestedRoles);
            }

            return $user;
        });
    }
}
