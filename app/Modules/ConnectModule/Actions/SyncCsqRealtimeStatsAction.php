<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CsqRealtimeStat;
use App\Modules\ConnectModule\Services\CuicReportService;
use Illuminate\Support\Facades\Log;

class SyncCsqRealtimeStatsAction
{
    public function __construct(
        private readonly CuicReportService $cuic
    ) {}

    public function execute(): int
    {
        $queues = CallQueue::activeNames();

        if (empty($queues)) {
            Log::warning('[CUIC-REALTIME] No hay colas activas configuradas para sincronizar.');

            return 0;
        }

        $rows = $this->cuic->executeRealtimeSnapshot('voice_csq_summary', $queues);

        $upserts = $rows->map(function ($row) {
            // CUIC a veces devuelve las filas como JSON strings dentro del array 'data'
            if (is_string($row)) {
                $decoded = json_decode($row, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row = $decoded;
                }
            }

            if (! is_array($row)) {
                Log::warning('[CUIC-REALTIME] Fila inválida recibida (no es array ni JSON): '.gettype($row));

                return null;
            }

            $stats = $row['VoiceIAQStats'] ?? [];

            return [
                'csq_name' => $stats['esdName'] ?? $row['id'] ?? 'Unknown',
                'calls_waiting' => (int) ($stats['nWaitingContacts'] ?? 0),
                'longest_call_in_queue' => (int) (($stats['longestWaitDuration'] ?? 0) / 1000), // Milisegundos a segundos
                'agents_logged_on' => (int) ($stats['nResourcesLoggedIn'] ?? 0),
                'agents_talking' => (int) ($stats['nInSessionResources'] ?? 0),
                'agents_ready' => (int) ($stats['nAvailResources'] ?? 0),
                'agents_not_ready' => (int) ($stats['nUnavailResources'] ?? 0),
                'agents_after_call_work' => (int) ($stats['nWorkResources'] ?? 0),
                'agents_reserved' => (int) ($stats['nSelectedResources'] ?? 0),

                // SL y acumulados
                'service_level_short_term' => (float) ($stats['nSLAPercentageHighThreshold'] ?? 0),
                'service_level_long_term' => (float) ($stats['nSLAPercentageHighThreshold'] ?? 0),
                'calls_abandoned_since_midnight' => (int) ($stats['nAbandonedContacts'] ?? 0),
                'calls_handled_since_midnight' => (int) ($stats['nHandledContacts'] ?? 0),
                'total_calls_since_midnight' => (int) ($stats['nTotalContacts'] ?? 0),

                'metadata' => json_encode($row),
                'updated_at' => now(),
            ];
        })->filter() // Eliminar nulos
            ->toArray();

        if (empty($upserts)) {
            return 0;
        }

        CsqRealtimeStat::upsert(
            $upserts,
            ['csq_name'],
            [
                'calls_waiting', 'longest_call_in_queue', 'agents_logged_on', 'agents_talking',
                'agents_ready', 'agents_not_ready', 'agents_after_call_work', 'agents_reserved',
                'service_level_short_term', 'service_level_long_term', 'calls_abandoned_since_midnight',
                'calls_handled_since_midnight', 'total_calls_since_midnight', 'metadata', 'updated_at',
            ]
        );

        return count($upserts);

    }
}
