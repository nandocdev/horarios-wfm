<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\PollDTO;
use App\Modules\CommunicationsModule\Models\Poll;
use Illuminate\Support\Facades\DB;

/**
 * Acción para actualizar una encuesta existente.
 */
class UpdatePollAction
{
    /**
     * Actualiza la encuesta con los nuevos datos.
     */
    public function execute(Poll $poll, PollDTO $dto): Poll
    {
        return DB::transaction(function () use ($poll, $dto) {
            $poll->update([
                'question' => $dto->question,
                'options' => $dto->options,
                'is_active' => $dto->is_active,
                'expires_at' => $dto->expires_at,
                'scheduled_at' => $dto->scheduled_at,
                'archive_at' => $dto->archive_at,
            ]);

            return $poll->fresh();
        });
    }
}
