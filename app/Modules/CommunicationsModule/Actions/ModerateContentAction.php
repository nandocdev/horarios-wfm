<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\ModerationDTO;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Modera contenido del módulo Communications.
 *
 * Maneja aprobación, rechazo y cambios de estado para News, Polls y Shoutouts.
 */
class ModerateContentAction
{
    /**
     * Ejecuta la moderación del contenido.
     *
     * @param  Model  $content  Instancia de News, Poll o Shoutout
     * @param  ModerationDTO  $dto  Datos de moderación
     * @return Model Contenido moderado
     */
    public function execute(Model $content, ModerationDTO $dto): Model
    {
        return DB::transaction(function () use ($content, $dto) {
            $updates = ['status' => $dto->status];

            if ($dto->approvedBy) {
                $updates['approved_by'] = $dto->approvedBy;
                $updates['approved_at'] = now();
            }

            if ($dto->moderationNotes) {
                $updates['moderation_notes'] = $dto->moderationNotes;
            }

            $content->update($updates);

            return $content->fresh();
        });
    }

    /**
     * Aprueba contenido.
     */
    public function approve(Model $content, ?string $notes = null): Model
    {
        return $this->execute($content, ModerationDTO::approve($notes));
    }

    /**
     * Rechaza contenido.
     */
    public function reject(Model $content, string $notes): Model
    {
        return $this->execute($content, ModerationDTO::reject($notes));
    }

    /**
     * Envía contenido a revisión.
     */
    public function submitForReview(Model $content): Model
    {
        return $this->execute($content, ModerationDTO::submitForReview());
    }

    /**
     * Archiva contenido.
     */
    public function archive(Model $content): Model
    {
        return $this->execute($content, ModerationDTO::archive());
    }
}
