<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\ReactionDTO;
use App\Modules\CommunicationsModule\Events\ReactionAdded;
use App\Modules\CommunicationsModule\Events\ReactionRemoved;
use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\DB;

/**
 * Agrega o remueve una reacción en un shoutout.
 */
class ToggleReactionAction
{
    /**
     * Ejecuta el toggle de la reacción.
     *
     * @param  ReactionDTO  $dto  Datos validados de la reacción
     * @param  Shoutout  $shoutout  El shoutout donde se reacciona
     * @param  int  $userId  ID del usuario que reacciona
     * @return Reaction|null La reacción creada, o null si se removió
     */
    public function execute(ReactionDTO $dto, Shoutout $shoutout, int $userId): ?Reaction
    {
        return DB::transaction(function () use ($dto, $shoutout, $userId) {
            $existingReaction = Reaction::where('shoutout_id', $shoutout->id)
                ->where('user_id', $userId)
                ->where('type', $dto->type)
                ->first();

            if ($existingReaction) {
                // Remover reacción existente
                $existingReaction->delete();
                event(new ReactionRemoved($existingReaction));

                return null;
            } else {
                // Crear nueva reacción
                $reaction = Reaction::create([
                    'shoutout_id' => $shoutout->id,
                    'user_id' => $userId,
                    'type' => $dto->type,
                    'is_active' => true,
                ]);

                event(new ReactionAdded($reaction));

                return $reaction;
            }
        });
    }
}
