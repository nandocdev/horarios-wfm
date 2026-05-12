<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\CaseSubtypeDTO;
use App\Modules\ConnectModule\Models\CaseSubtype;
use Illuminate\Support\Facades\DB;

final class CreateCaseSubtypeAction
{
    public function execute(CaseSubtypeDTO $dto): CaseSubtype
    {
        return DB::transaction(function () use ($dto) {
            return CaseSubtype::create([
                'queue_id' => $dto->queueId,
                'code' => $dto->code,
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->isActive,
            ]);
        });
    }
}
