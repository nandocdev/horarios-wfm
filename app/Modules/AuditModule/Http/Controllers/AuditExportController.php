<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuditModule\Actions\ExportAuditLogsAction;
use App\Modules\AuditModule\DTOs\AuditLogExportDTO;
use App\Modules\AuditModule\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExportController extends Controller
{
    public function export(Request $request, ExportAuditLogsAction $action): Response|StreamedResponse|JsonResponse
    {
        $this->authorize('export', AuditLog::class);

        $dto = AuditLogExportDTO::fromRequest($request);
        $logs = $action->execute($dto);

        if ($dto->format === 'json') {
            return response()->json($logs->toArray());
        }

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['id', 'entity_type', 'entity_id', 'action', 'before', 'after', 'user', 'ip_address', 'created_at']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log->id,
                    $log->entity_type,
                    $log->entity_id,
                    $log->action,
                    json_encode($log->before, JSON_UNESCAPED_UNICODE),
                    json_encode($log->after, JSON_UNESCAPED_UNICODE),
                    optional($log->user)->name,
                    $log->ip_address,
                    $log->created_at?->toDateTimeString(),
                ]);
            }

            fclose($output);
        }, 'audit-logs-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
