<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Support\Facades\DB;

/**
 * Elimina un tag del sistema.
 */
class DeleteTagAction
{
    /**
     * Ejecuta la eliminación del tag.
     */
    public function execute(Tag $tag): bool
    {
        return DB::transaction(function () use ($tag) {
            // Las relaciones polimórficas se eliminan automáticamente por cascade
            return $tag->delete();
        });
    }
}
