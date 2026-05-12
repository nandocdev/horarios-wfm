<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\ShoutoutDTO;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\DB;

/**
 * Acción para actualizar un shoutout existente.
 */
class UpdateShoutoutAction
{
    /**
     * Actualiza el shoutout con los nuevos datos.
     */
    public function execute(Shoutout $shoutout, ShoutoutDTO $dto): Shoutout
    {
        return DB::transaction(function () use ($shoutout, $dto) {
            $shoutout->update([
                'employee_id' => $dto->employee_id,
                'message' => $dto->message,
                'is_active' => $dto->is_active,
            ]);

            return $shoutout->fresh();
        });
    }
}
