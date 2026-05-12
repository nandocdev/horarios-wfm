<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\DB;

/**
 * Acción para eliminar un shoutout.
 */
class DeleteShoutoutAction
{
    /**
     * Elimina el shoutout.
     */
    public function execute(Shoutout $shoutout): bool
    {
        return DB::transaction(function () use ($shoutout) {
            return $shoutout->delete();
        });
    }
}
