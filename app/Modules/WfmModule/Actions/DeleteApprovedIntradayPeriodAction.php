<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Actions;

use App\Modules\WfmModule\Models\ApprovedIntradayPeriod;
use Illuminate\Support\Facades\DB;

final class DeleteApprovedIntradayPeriodAction
{
    /**
     * Elimina un periodo aprobado de forma transaccional.
     */
    public function execute(int $periodId): void
    {
        DB::transaction(function () use ($periodId) {
            $period = ApprovedIntradayPeriod::findOrFail($periodId);
            $period->delete();
        });
    }
}
