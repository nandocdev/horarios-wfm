<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Facades\DB;

final class CreateCallQueueAction
{
    public function execute(CallQueueDTO $dto): CallQueue
    {
        return DB::transaction(function () use ($dto) {
            return CallQueue::create([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);
        });
    }
}
