<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\DTOs\ShoutoutDTO;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Support\Facades\DB;

/**
 * Acción para crear un nuevo shoutout.
 */
class CreateShoutoutAction
{
    /**
     * Ejecuta la creación del shoutout.
     */
    public function execute(ShoutoutDTO $dto): Shoutout
    {
        return DB::transaction(function () use ($dto) {
            $shoutout = Shoutout::create([
                'employee_id' => $dto->employee_id,
                'message' => $dto->message,
                'is_active' => $dto->is_active,
                'scheduled_at' => $dto->scheduled_at,
                'archive_at' => $dto->archive_at,
                'status' => $dto->workflow_action === 'submit_review' ? 'pending_review' : 'draft',
            ]);

            // Sincronizar categorías y tags si existen métodos de relación en el modelo
            if (! empty($dto->category_ids)) {
                $shoutout->categories()->sync($dto->category_ids);
            }

            if (! empty($dto->tag_ids)) {
                $shoutout->tags()->sync($dto->tag_ids);
            }

            // Almacenar imagen si existe
            if ($dto->image) {
                $shoutout->addMedia($dto->image)
                    ->toMediaCollection('banner');
            }

            return $shoutout;
        });
    }
}
