<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Console\Commands;

use App\Modules\WfmModule\Models\TemporalAssignment;
use Illuminate\Console\Command;

class CleanExpiredTemporalAssignments extends Command
{
    protected $signature = 'wfm:clean-temporal-assignments';

    protected $description = 'Elimina asignaciones temporales cuya fecha fin ya paso';

    public function handle(): int
    {
        $deleted = TemporalAssignment::where('end_date', '<', now()->subDay()->toDateString())
            ->delete();

        $this->info("{$deleted} asignaciones temporales expiradas eliminadas.");

        return self::SUCCESS;
    }
}
