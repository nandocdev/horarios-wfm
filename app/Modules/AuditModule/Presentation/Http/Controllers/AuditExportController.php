<?php

declare(strict_types=1);

namespace App\Modules\AuditModule\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AuditModule\Application\ExportAuditLogs\Command;
use App\Modules\AuditModule\Application\ExportAuditLogs\Handler;
use App\Modules\AuditModule\Infrastructure\Persistence\Eloquent\AuditLogModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExportController extends Controller
{
    public function export(Request $request, Handler $handler): Response|StreamedResponse|JsonResponse
    {
        $this->authorize('export', AuditLogModel::class);

        $command = new Command(
            search: $request->query('search'),
            action: $request->query('action'),
            entityType: $request->query('entityType'),
            dateFrom: $request->query('dateFrom'),
            dateTo: $request->query('dateTo'),
            format: strtolower($request->query('format', 'csv')),
        );

        $entries = $handler($command);

        if ($command->format === 'json') {
            $data = array_map(fn ($entry) => [
                'id' => $entry->id(),
                'entity_type' => $entry->entityType()->value(),
                'entity_id' => $entry->entityId()->value(),
                'action' => $entry->action()->value(),
                'before' => $entry->before()?->data(),
                'after' => $entry->after()?->data(),
                'ip_address' => $entry->ipAddress()?->value(),
                'created_at' => $entry->createdAt()->format('Y-m-d H:i:s'),
            ], $entries);

            return response()->json($data);
        }

        return response()->streamDownload(function () use ($entries) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['id', 'entity_type', 'entity_id', 'action', 'before', 'after', 'ip_address', 'created_at']);

            foreach ($entries as $entry) {
                fputcsv($output, [
                    $entry->id(),
                    $entry->entityType()->value(),
                    $entry->entityId()->value(),
                    $entry->action()->value(),
                    $entry->before()?->toJson() ?? '',
                    $entry->after()?->toJson() ?? '',
                    $entry->ipAddress()?->value() ?? '',
                    $entry->createdAt()->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);
        }, 'audit-logs-'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
