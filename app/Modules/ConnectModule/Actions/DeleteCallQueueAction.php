<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\DB;

final class DeleteCallQueueAction
{
    public function execute(CallQueue $queue): void
    {
        DB::transaction(function () use ($queue) {
            if ($queue->subtypes()->exists()) {
                throw new \LogicException('No se puede eliminar una cola con tipos de consulta asociados.');
            }

            $queue->delete();
        });

        app(CallQueueCache::class)->refresh();
    }
}
