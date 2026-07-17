<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CallQueueDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use Illuminate\Support\Facades\DB;

final class UpdateCallQueueAction {
    public function execute(CallQueue $queue, CallQueueDTO $dto): CallQueue {
        return DB::transaction(function () use ($queue, $dto) {
            $previousName = $queue->name;

            $queue->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);

            // No-op: CaseSubtypes reference `queue_id` now, so renaming a queue does not require migrating subtypes.

            return $queue->refresh();
        });
    }
}
