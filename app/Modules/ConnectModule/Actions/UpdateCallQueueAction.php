<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\DB;

final class UpdateCallQueueAction
{
    public function execute(CallQueue $queue, CallQueueDTO $dto): CallQueue
    {
        $result = DB::transaction(function () use ($queue, $dto) {
            $queue->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            return $queue->refresh();
        });

        app(CallQueueCache::class)->refresh();

        return $result;
    }
}
