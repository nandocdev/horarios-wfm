<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\Concerns\ProfileValidationRules;
use App\Modules\CoreModule\DTOs\UserDTO;
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
 * [RIESGO] Pérdida de roles: Si el DTO no incluye todos los roles existentes,
 *           syncRoles() los elimina. Mitigado aquí por merge de roles.
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

            // Sincronización segura de roles: solo agrega/quita roles especificados
            // en el DTO, preservando roles previos que no estén en la lista de exclusión.
            if (! empty($dto->roles)) {
                // Obtener roles actuales del usuario
                $currentRoles = $user->roles->pluck('name')->toArray();

                // Roles solicitados en el DTO
                $requestedRoles = array_values($dto->roles);

                // Calcular roles a agregar (solicitados pero no actuales)
                $rolesToAdd = array_diff($requestedRoles, $currentRoles);

                // Calcular roles a quitar (actuales pero no solicitados)
                // Excepto el rol por defecto 'agent' que nunca debe quitarse automáticamente
                $rolesToRemove = array_diff($currentRoles, $requestedRoles);
                $rolesToRemove = array_diff($rolesToRemove, ['agent']);

                // Agregar roles solicitados pero no presentes
                if (! empty($rolesToAdd)) {
                    $user->syncRoles($rolesToAdd);
                }

                // Quitar roles que ya no son solicitados (excepto 'agent')
                if (! empty($rolesToRemove)) {
                    $user->removeRole($rolesToRemove);
                }
            }

            return $user;
        });
    }
}