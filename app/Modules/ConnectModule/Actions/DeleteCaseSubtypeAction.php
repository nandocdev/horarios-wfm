<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CaseSubtype;
use Illuminate\Support\Facades\DB;

final class DeleteCaseSubtypeAction
{
    public function execute(CaseSubtype $subtype): void
    {
        DB::transaction(function () use ($subtype) {
            $subtype->delete();
        });
    }
}
