<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\DB;

final class CreateCallQueueAction
{
    public function execute(CallQueueDTO $dto): CallQueue
    {
        $result = DB::transaction(function () use ($dto) {
            return CallQueue::create([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);
        });

        app(CallQueueCache::class)->refresh();

        return $result;
    }
}
