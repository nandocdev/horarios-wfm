<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\DTOs\AuditLogExportDTO;
use App\Src\Platform\Application\Handlers\ExportAuditLogsHandler;
use App\Src\Platform\Presentation\Policies\AuditLogPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditExportController extends Controller {
    public function export(Request $request, ExportAuditLogsHandler $handler): Response|StreamedResponse|JsonResponse {
        $this->authorize('export', AuditLogPolicy::class);

        $dto = new AuditLogExportDTO(
            search: $request->query('search'),
            action: $request->query('action'),
            entityType: $request->query('entityType'),
            dateFrom: $request->query('dateFrom'),
            dateTo: $request->query('dateTo'),
            format: strtolower($request->query('format', 'csv')),
        );

        $logs = $handler->execute($dto);

        if ($dto->format === 'json') {
            return response()->json($logs);
        }

        return response()->streamDownload(function () use ($logs) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['id', 'entity_type', 'entity_id', 'action', 'before', 'after', 'user', 'ip_address', 'created_at']);

            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['id'] ?? '',
                    $log['entity_type'] ?? '',
                    $log['entity_id'] ?? '',
                    $log['action'] ?? '',
                    isset($log['before']) ? json_encode($log['before'], JSON_UNESCAPED_UNICODE) : '',
                    isset($log['after']) ? json_encode($log['after'], JSON_UNESCAPED_UNICODE) : '',
                    $log['user_name'] ?? '',
                    $log['ip_address'] ?? '',
                    $log['created_at'] ?? '',
                ]);
            }

            fclose($output);
        }, 'audit-logs-' . now()->format('Ymd_His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
