<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\PollDTO;
use App\Modules\CommunicationsModule\Models\Poll;
use Illuminate\Support\Facades\DB;

/**
 * Acción para crear una nueva encuesta.
 */
class CreatePollAction
{
    /**
     * Ejecuta la creación de la encuesta.
     */
    public function execute(PollDTO $dto): Poll
    {
        return DB::transaction(function () use ($dto) {
            return Poll::create([
                'question' => $dto->question,
                'options' => collect($dto->options)->map(fn ($opt) => array_merge($opt, ['votes' => 0]))->toArray(),
                'is_active' => $dto->is_active,
                'expires_at' => $dto->expires_at,
                'scheduled_at' => $dto->scheduled_at,
                'archive_at' => $dto->archive_at,
            ]);
        });
    }
}
