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
                ->when($dto->search, fn ($query) => $query->where(function ($sub) use ($dto) {
                    $sub->where('entity_type', 'ilike', "%{$dto->search}%")
                        ->orWhere('action', 'ilike', "%{$dto->search}%")
                        ->orWhere('ip_address', 'ilike', "%{$dto->search}%");
                }))
                ->when($dto->action, fn ($query) => $query->where('action', $dto->action))
                ->when($dto->entityType, fn ($query) => $query->where('entity_type', $dto->entityType))
                ->when($dto->dateFrom, fn ($query) => $query->whereDate('created_at', '>=', $dto->dateFrom))
                ->when($dto->dateTo, fn ($query) => $query->whereDate('created_at', '<=', $dto->dateTo))
                ->orderByDesc('created_at')
                ->get();
        });
    }
}
