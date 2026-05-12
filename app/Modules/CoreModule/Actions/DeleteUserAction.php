<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Actions;

use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Acción para eliminar un usuario mediante SoftDeletes.
 */
class DeleteUserAction
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->delete();
        });
    }

    /**
     * [RIESGOS]
     * - Huérfanos de datos: Aunque se usa SoftDelete, los registros relacionados (auditoría, comentarios) podrían perder el vínculo nominal si no se gestionan correctamente.
     * - Recuperación accidental: Restaurar un usuario eliminado podría reactivar accesos obsoletos si no se revisa su perfil previo a la restauración.
     */
}
