<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\Poll;
use Illuminate\Support\Facades\DB;

/**
 * Acción para eliminar una encuesta.
 */
class DeletePollAction
{
    /**
     * Elimina la encuesta y sus respuestas asociadas.
     */
    public function execute(Poll $poll): bool
    {
        return DB::transaction(function () use ($poll) {
            // Las respuestas se eliminan automáticamente por cascade
            return $poll->delete();
        });
    }
}
