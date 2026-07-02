<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Integrations;

use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

final class CuicIntegrationService implements CuicIntegrationInterface
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private bool $verifySsl;
    private int $timeout;
    private int $pollInterval = 3;
    private int $maxPollAttempts = 20;
    private array $reports;

    public function __construct()
    {
        $cfg = config('contact-center.cuic');

        $domain = (string) ($cfg['domain'] ?? 'CCX');

        $this->baseUrl = rtrim((string) $cfg['base_url'], '/');
        $this->username = $domain . '\\' . (string) $cfg['username'];
        $this->password = (string) $cfg['password'];
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? false);
        $this->timeout = (int) ($cfg['timeout'] ?? 30);
        $this->reports = (array) ($cfg['reports'] ?? []);
    }

    public function executeReport(string $reportType, string $dateFrom, string $dateTo, ?int $minutes = null): array
    {
        $report = $this->resolveReport($reportType);

        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        Log::info("[CUIC] Iniciando '{$reportType}' con filtros", [
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'minutes' => $minutes,
        ]);

        $filterParams = $this->buildDateFilterParams($report, $start, $end);

        $initBody = [
            'reportId' => $report['id'],
            'hardRefresh' => true,
            'filter' => [
                'repType' => 'STPROC',
                'filterParams' => $filterParams,
            ],
        ];

        return $this->runWithPolling($report, $initBody, $reportType);
    }

    public function executeReportWithRetry(string $reportType, string $dateFrom, string $dateTo, ?int $minutes = null, int $maxRetries = 3): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $maxRetries) {
            try {
                return $this->executeReport($reportType, $dateFrom, $dateTo, $minutes);
            } catch (RuntimeException $e) {
                $lastException = $e;
                $attempt++;

                Log::warning("[CUIC] Intento {$attempt}/{$maxRetries} falló para '{$reportType}'", [
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < $maxRetries) {
                    sleep($attempt * 2);
                }
            }
        }

        throw new RuntimeException(
            "[CUIC] Reporte '{$reportType}' falló después de {$maxRetries} intentos. Último error: {$lastException->getMessage()}"
        );
    }

    public function executeRealtimeSnapshot(string $reportType): array
    {
        $report = $this->resolveReport($reportType);

        Log::info("[CUIC] Obteniendo snapshot en tiempo real para '{$reportType}'");

        return $this->fetchInitialData($report, [], 'STPROC', $reportType);
    }

    public function executeAgentRealtimeSnapshot(array $employeeIds): array
    {
        $report = $this->resolveReport('agent_realtime');
        $params = $report['params'] ?? [];

        $filters = [];
        if (! empty($employeeIds) && isset($params['agent_login_id'])) {
            $filters[] = [
                'isKeyField' => true,
                'name' => 'AgentStateDetailRealtime.loginID',
                'fieldId' => $params['agent_login_id'],
                'fieldType' => 'VALUELIST',
                'operator' => 'SetValues',
                'value' => array_map(fn ($id) => ['key' => (string) $id, 'desc' => (string) $id], $employeeIds),
            ];
        }

        return $this->fetchInitialData($report, $filters, 'REALTIMESTREAM', 'agent_realtime');
    }

    public function executeAgentDetailReport(string $loginId, string $dateFrom, string $dateTo): array
    {
        $report = $this->resolveReport('agent_detail');
        $params = $report['params'] ?? [];
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $filterParams = $this->buildDateFilterParams($report, $start, $end);

        if (isset($params['agent_names'])) {
            $filterParams[] = [
                'paramId' => $params['agent_names'],
                'paramType' => 'VALUELIST',
                'value' => [$loginId],
            ];
        }

        if (isset($params['current_user'])) {
            $filterParams[] = [
                'paramId' => $params['current_user'],
                'paramType' => 'STRING',
                'value' => $this->username,
            ];
        }

        $initBody = [
            'reportId' => $report['id'],
            'hardRefresh' => true,
            'filter' => [
                'repType' => 'STPROC',
                'filterParams' => $filterParams,
            ],
        ];

        return $this->runWithPolling($report, $initBody, 'agent_detail');
    }

    public function executeAgentStateTransitions(string $loginId, string $dateFrom, string $dateTo): array
    {
        $report = $this->resolveReport('agent_state_transitions');
        $params = $report['params'] ?? [];
        $start = Carbon::parse($dateFrom);
        $end = Carbon::parse($dateTo);

        $filterParams = $this->buildDateFilterParams($report, $start, $end);

        if (isset($params['agent_names'])) {
            $filterParams[] = [
                'paramId' => $params['agent_names'],
                'paramType' => 'VALUELIST',
                'value' => [$loginId],
            ];
        }

        if (isset($params['current_user'])) {
            $filterParams[] = [
                'paramId' => $params['current_user'],
                'paramType' => 'STRING',
                'value' => $this->username,
            ];
        }

        $initBody = [
            'reportId' => $report['id'],
            'hardRefresh' => true,
            'filter' => [
                'repType' => 'STPROC',
                'filterParams' => $filterParams,
            ],
        ];

        return $this->runWithPolling($report, $initBody, 'agent_state_transitions');
    }

    public function listReports(): array
    {
        return array_map(fn (array $r) => $r['id'], $this->reports);
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(5)
                ->withOptions(['verify' => $this->verifySsl])
                ->accept('application/json')
                ->get("{$this->baseUrl}/cuic/rest/system/platform");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveReport(string $key): array
    {
        if (! isset($this->reports[$key])) {
            throw new RuntimeException(
                "CUIC: El reporte '{$key}' no está registrado en contact-center.cuic.reports. "
                . 'Claves disponibles: ' . implode(', ', array_keys($this->reports))
            );
        }

        $report = $this->reports[$key];

        if (empty($report['id'])) {
            throw new RuntimeException("CUIC: El reporte '{$key}' no tiene ID configurado.");
        }

        return $report;
    }

    private function buildDateFilterParams(array $report, CarbonInterface $start, CarbonInterface $end): array
    {
        $params = $report['params'] ?? [];
        $this->assertParamIds($params, ['start_datetime', 'end_datetime', 'current_user']);

        $isToday = $start->isToday();
        $relativeDate = $isToday;
        $startValue = $isToday ? 'THISDAY' : $start->format('m/d/Y H:i:s');
        $endValue = $isToday ? 'THISDAY' : $end->format('m/d/Y H:i:s');

        return [
            [
                'paramId' => $params['start_datetime'],
                'paramType' => 'DATETIME',
                'relativeDate' => $relativeDate,
                'date' => $start->format('m/d/Y H:i:s'),
                'value' => $startValue,
            ],
            [
                'paramId' => $params['end_datetime'],
                'paramType' => 'DATETIME',
                'relativeDate' => $relativeDate,
                'date' => $end->format('m/d/Y H:i:s'),
                'value' => $endValue,
            ],
            [
                'paramId' => $params['current_user'],
                'paramType' => 'STRING',
                'value' => $this->username,
            ],
        ];
    }

    private function runWithPolling(array $report, array $initBody, string $reportKey): array
    {
        $initUrl = $this->buildNewRestUrl($report);
        $initResp = $this->post($initUrl, $initBody, [
            'reportExecutionType' => 'historical',
            'reportExecutionMode' => 'DEFAULT',
        ]);

        $dataSetId = $initResp->json('dataSetId');

        if (empty($dataSetId)) {
            throw new RuntimeException(
                "[CUIC] POST /newRest/ no retornó dataSetId para '{$reportKey}'. "
                . 'Body: ' . substr($initResp->body(), 0, 200)
            );
        }

        Log::info("[CUIC] '{$reportKey}' iniciado. Polling dataSetId: {$dataSetId}");

        $pollUrl = $this->buildPollUrl($report, $dataSetId);

        for ($attempt = 1; $attempt <= $this->maxPollAttempts; $attempt++) {
            sleep($this->pollInterval);

            $pollResp = $this->get($pollUrl, [
                'reportExecutionType' => 'historical',
                'reportExecutionMode' => 'DEFAULT',
            ]);

            $result = $pollResp->json('executionResult') ?? [];
            $status = (string) ($result['status'] ?? 'UNKNOWN');

            Log::debug("[CUIC] '{$reportKey}' poll #{$attempt} → status={$status}");

            if ($status === 'FAILED' || $status === 'QUERY_TIMEOUT') {
                throw new RuntimeException(
                    "[CUIC] Reporte '{$reportKey}' falló. errorType: " . ($result['errorType'] ?? 'UNKNOWN')
                );
            }

            if ($status === 'READY') {
                return $this->parseResult($result, $reportKey);
            }

            if (! in_array($status, ['RUNNING', 'READY'], true)) {
                throw new RuntimeException(
                    "[CUIC] Reporte '{$reportKey}' estado inesperado: {$status}"
                );
            }
        }

        throw new RuntimeException(
            "[CUIC] Timeout: el reporte '{$reportKey}' no completó en "
            . ($this->maxPollAttempts * $this->pollInterval) . 's.'
        );
    }

    private function fetchInitialData(array $report, array $filters, string $repType, string $reportKey): array
    {
        $url = "{$this->baseUrl}/cuic/rest/{$report['locale']}/initialData/";

        $query = ['repType' => $repType];

        $body = [
            'reportId' => $report['id'],
            'filters' => json_encode($filters),
        ];

        Log::info("[CUIC] Obteniendo snapshot {$repType} para '{$reportKey}'");

        $response = $this->post($url, $body, $query);

        $rawData = $response->json('data');
        $rows = [];

        if (is_string($rawData)) {
            $decoded = json_decode($rawData, true);
            if (is_array($decoded)) {
                $rows = count($decoded) > 0 && is_array($decoded[0]) ? $decoded[0] : $decoded;
            }
        } elseif (is_array($rawData)) {
            $rows = count($rawData) > 0 && is_array($rawData[0]) ? $rawData[0] : $rawData;
        }

        return (array) $rows;
    }

    private function parseResult(array $result, string $reportKey): array
    {
        $rawData = $result['jsonData'] ?? '[]';
        $rows = json_decode((string) $rawData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('[CUIC] Error al decodificar jsonData: ' . json_last_error_msg());
        }

        Log::info("[CUIC] '{$reportKey}' completado.", [
            'rows' => count((array) $rows),
            'startTime' => $result['startTime'] ?? null,
            'endTime' => $result['endTime'] ?? null,
        ]);

        return (array) $rows;
    }

    private function buildNewRestUrl(array $report): string
    {
        return "{$this->baseUrl}/cuic/rest/{$report['locale']}/reports/execute/newRest/";
    }

    private function buildPollUrl(array $report, string $dataSetId): string
    {
        return "{$this->baseUrl}/cuic/rest/{$report['locale']}/reports/execute/{$dataSetId}";
    }

    private function get(string $url, array $query = []): Response
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->accept('application/json')
            ->get($url, $query);

        if ($response->failed()) {
            Log::error('[CUIC] GET Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);
            throw new RuntimeException("CUIC GET HTTP {$response->status()}: " . substr($response->body(), 0, 200));
        }

        return $response;
    }

    private function post(string $url, array $body = [], array $query = []): Response
    {
        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->accept('application/json')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url . ($query ? '?' . http_build_query($query) : ''), $body);

        if ($response->failed()) {
            Log::error('[CUIC] POST Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 300),
            ]);
            throw new RuntimeException("CUIC POST HTTP {$response->status()}: " . substr($response->body(), 0, 200));
        }

        return $response;
    }

    private function assertParamIds(array $params, array $required): void
    {
        $missing = array_filter($required, fn (string $k) => empty($params[$k]));

        if (! empty($missing)) {
            throw new RuntimeException(
                '[CUIC] Faltan paramIds en config: ' . implode(', ', $missing)
                . '. Agrégalos en contact-center.cuic.reports.{key}.params'
            );
        }
    }
}
