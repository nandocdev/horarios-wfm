<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Actions;

use App\Modules\AuditModule\DTOs\AuditLogExportDTO;
use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

final class ExportAuditLogsAction
{
    /**
     * @return Collection<AuditLog>
     */
    public function execute(AuditLogExportDTO $dto): Collection
    {
        return DB::transaction(function () use ($dto) {
            return AuditLog::query()
                ->with('user')
                ->filter([
                    'search' => $dto->search,
                    'action' => $dto->action,
                    'entity_type' => $dto->entityType,
                    'date_from' => $dto->dateFrom,
                    'date_to' => $dto->dateTo,
                ])
                ->orderByDesc('created_at')
                ->get();
        });
    }
}
