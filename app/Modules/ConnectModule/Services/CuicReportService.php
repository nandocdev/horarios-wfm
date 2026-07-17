<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Integración con Cisco Unified Intelligence Center (CUIC) REST API.
 *
 * ── FLUJO CORRECTO DE CUIC (Ingeniería Inversa del gadget) ──────────────────
 *
 * CUIC usa un proceso asíncrono de DOS PASOS para generar reportes:
 *
 *   PASO 1 — Iniciar ejecución:
 *     POST /cuic/rest/{locale}/reports/execute/newRest/?reportExecutionType=historical&reportExecutionMode=DEFAULT
 *     Body: { "reportId": "ID", "hardRefresh": true, "filter": { ...filtros... } }
 *     Respuesta: { "dataSetId": "DADEDC511000019D...", "filterId": "287407..." }
 *
 *   PASO 2 — Polling hasta READY:
 *     GET /cuic/rest/{locale}/reports/execute/{dataSetId}?reportExecutionType=historical&reportExecutionMode=DEFAULT
 *     Respuesta: { "executionResult": { "status": "RUNNING|READY|FAILED", "jsonData": "[...]" } }
 *
 * IMPORTANTE: El filtro va EMBEBIDO en el body del POST inicial. No es un paso separado.
 * El endpoint /filter se usa solo para guardar preferencias de usuario en la UI — no para la API.
 *
 * [RIESGOS]
 * - `jsonData` en la respuesta es un STRING con JSON embebido → doble decode.
 * - El campo `transition_time` viene en epoch MILISEGUNDOS → Carbon::createFromTimestampMs().
 * - Si el servidor CUIC está bajo carga, el status puede quedar en RUNNING mucho tiempo.
 * - Basic Auth con formato CCX\username es requerido en este servidor CUIC on-premise.
 * - SSL auto-firmado → CUIC_VERIFY_SSL=false en el .env.
 */
final class CuicReportService {
    private string $baseUrl;

    private string $username;

    private string $password;

    private bool $verifySsl;

    private int $timeout;

    /** Intervalo de polling en segundos (CUIC usa 3s internamente) */
    private int $pollInterval = 3;

    /** Máximo de intentos de polling antes de abortar */
    private int $maxPollAttempts = 20;

    /** @var array<string, array{id: string, locale: string, params: array<string, string>}> */
    private array $reports;

    public function __construct() {
        $cfg = config('contact-center.cuic');

        $domain = (string) ($cfg['domain'] ?? 'CCX');

        $this->baseUrl = rtrim((string) $cfg['base_url'], '/');
        $this->username = $domain . '\\' . (string) $cfg['username']; // CCX\username
        $this->password = (string) $cfg['password'];
        $this->verifySsl = (bool) $cfg['verify_ssl'];
        $this->timeout = (int) $cfg['timeout'];
        $this->reports = (array) $cfg['reports'];
    }

    // -------------------------------------------------------------------------
    // API Pública
    // -------------------------------------------------------------------------

    /**
     * Ejecuta un reporte SIN filtros de rango temporal.
     *
     * Usa el filtro por defecto configurado en CUIC para el reporte.
     *
     * @param  string  $reportKey  Clave semántica en config (ej. 'agent_state_transitions')
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    public function executeReport(string $reportKey): Collection {
        $report = $this->resolveReport($reportKey);

        Log::info("[CUIC] Iniciando '{$reportKey}' sin filtros (ID: {$report['id']})");

        $initBody = [
            'reportId' => $report['id'],
            'hardRefresh' => true,
        ];

        return $this->runWithPolling($report, $initBody, $reportKey);
    }

    /**
     * Ejecuta un reporte con filtro de rango temporal y lista opcional de agentes.
     *
     * El filtro va embebido en el POST inicial — es un único request, no dos pasos.
     *
     * Ejemplo:
     *   $rows = $service->executeReportWithFilter(
     *       'agent_detail',
     *       Carbon::yesterday()->setTime(6, 0, 0),
     *       Carbon::yesterday()->setTime(7, 0, 0),
     *       ['Amalia Renteria', 'Fernando Castillo Valdez']
     *   );
     *
     * @param  array<int, string>  $agentNames  Nombres completos tal como aparecen en CUIC
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    public function executeReportWithFilter(
        string $reportKey,
        CarbonInterface $startDateTime,
        CarbonInterface $endDateTime,
        array $agentNames = []
    ): Collection {
        $report = $this->resolveReport($reportKey);
        $params = $report['params'];

        $this->assertParamIds($params, ['start_datetime', 'end_datetime', 'current_user']);

        // Fechas pasadas → relativeDate=false para evitar que CUIC reescriba al día actual.
        // Fechas de hoy → relativeDate=true + value=THISDAY (CUIC resuelve la fecha del servidor).
        $isToday = $startDateTime->isToday();
        $relativeDate = $isToday;
        $startValue = $isToday ? 'THISDAY' : $startDateTime->format('m/d/Y H:i:s');
        $endValue = $isToday ? 'THISDAY' : $endDateTime->format('m/d/Y H:i:s');

        $filterParams = [
            [
                'paramId' => $params['start_datetime'],
                'paramType' => 'DATETIME',
                'relativeDate' => $relativeDate,
                'date' => $startDateTime->format('m/d/Y H:i:s'),
                'value' => $startValue,
            ],
            [
                'paramId' => $params['end_datetime'],
                'paramType' => 'DATETIME',
                'relativeDate' => $relativeDate,
                'date' => $endDateTime->format('m/d/Y H:i:s'),
                'value' => $endValue,
            ],
        ];

        if (!empty($agentNames) && isset($params['agent_names'])) {
            $filterParams[] = [
                'paramId' => $params['agent_names'],
                'paramType' => 'VALUELIST',
                'value' => $agentNames,
            ];
        }

        $filterParams[] = [
            'paramId' => $params['current_user'],
            'paramType' => 'STRING',
            'value' => $this->username, // CCX\username
        ];

        $initBody = [
            'reportId' => $report['id'],
            'hardRefresh' => true,
            'filter' => [
                'repType' => 'STPROC',
                'filterParams' => $filterParams,
            ],
        ];

        Log::info("[CUIC] Iniciando '{$reportKey}' con filtros", [
            'start' => $startDateTime->toDateTimeString(),
            'end' => $endDateTime->toDateTimeString(),
            'agents' => count($agentNames),
            'isToday' => $isToday,
        ]);

        return $this->runWithPolling($report, $initBody, $reportKey);
    }

    /**
     * Ejecuta un reporte de tiempo real (Snapshot inicial).
     *
     * @param  array<int, string>  $csqNames
     * @return Collection<int, array<string, mixed>>
     */
    public function executeRealtimeSnapshot(string $reportKey, array $csqNames = []): Collection {
        $report = $this->resolveReport($reportKey);
        $params = $report['params'];

        $filters = [];
        if (!empty($csqNames) && isset($params['csq_names'])) {
            $filters[] = [
                'isKeyField' => true,
                'name' => 'VoiceIAQStats.esdName', // Nombre técnico en CUIC para CSQ
                'fieldId' => $params['csq_names'],
                'fieldType' => 'VALUELIST',
                'operator' => 'SetValues',
                'value' => array_map(fn($name) => ['key' => $name, 'desc' => $name], $csqNames),
            ];
        }

        return $this->fetchInitialData($report, $filters, 'STPROC', $reportKey);
    }

    /**
     * Ejecuta un reporte de estado de agentes en tiempo real (REALTIMESTREAM).
     *
     * @param  array<int, string>  $agentUsernames
     * @return Collection<int, array<string, mixed>>
     */
    public function executeAgentRealtimeSnapshot(string $reportKey, array $agentUsernames = []): Collection {
        $report = $this->resolveReport($reportKey);
        $params = $report['params'];

        $filters = [];
        if (!empty($agentUsernames) && isset($params['agent_login_id'])) {
            $filters[] = [
                'isKeyField' => true,
                'name' => 'AgentStateDetailRealtime.loginID', // Nombre técnico para Login ID
                'fieldId' => $params['agent_login_id'],
                'fieldType' => 'VALUELIST',
                'operator' => 'SetValues',
                'value' => $agentUsernames,
            ];
        }

        return $this->fetchInitialData($report, $filters, 'REALTIMESTREAM', $reportKey);
    }

    /**
     * Lógica compartida para obtener snapshot inicial (initialData).
     */
    private function fetchInitialData(array $report, array $filters, string $repType, string $reportKey): Collection {
        $url = "{$this->baseUrl}/cuic/rest/{$report['locale']}/initialData/";

        $query = [
            'repType' => $repType,
        ];

        // En esta versión del servidor CUIC, 'filters' DEBE ser un string de Array JSON ([...]).
        // Si se envía un objeto (repType envolviendo filters), el servidor falla con 500.
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

        return collect((array) $rows);
    }

    /**
     * Retorna el ID interno de CUIC para una clave semántica.
     *
     * @throws RuntimeException
     */
    public function getReportId(string $reportKey): string {
        return $this->resolveReport($reportKey)['id'];
    }

    /**
     * Lista todos los reportes registrados con sus IDs.
     *
     * @return array<string, string>
     */
    public function listReports(): array {
        return array_map(fn(array $r) => $r['id'], $this->reports);
    }

    // -------------------------------------------------------------------------
    // Motor de ejecución con polling
    // -------------------------------------------------------------------------

    /**
     * Ejecuta el flujo completo: POST /newRest/ → polling GET hasta READY.
     *
     * @param  array{id: string, locale: string, params: array<string, string>}  $report
     * @param  array<string, mixed>  $initBody
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private function runWithPolling(array $report, array $initBody, string $reportKey): Collection {
        // PASO 1: Iniciar la ejecución del reporte
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

        // PASO 2: Polling hasta que el reporte esté READY
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

            match ($status) {
                'READY' => null,  // continúa abajo
                'RUNNING' => null,  // continúa el bucle
                'FAILED',
                'QUERY_TIMEOUT' => throw new RuntimeException(
                    "[CUIC] Reporte '{$reportKey}' falló. errorType: " . ($result['errorType'] ?? 'UNKNOWN')
                ),
                default => throw new RuntimeException(
                    "[CUIC] Reporte '{$reportKey}' estado inesperado: {$status}"
                ),
            };

            if ($status === 'READY') {
                return $this->parseResult($result, $reportKey);
            }
        }

        throw new RuntimeException(
            "[CUIC] Timeout: el reporte '{$reportKey}' no completó en "
            . ($this->maxPollAttempts * $this->pollInterval) . 's.'
        );
    }

    // -------------------------------------------------------------------------
    // Parseo de respuesta
    // -------------------------------------------------------------------------

    /**
     * Parsea el bloque `executionResult` con READY status.
     *
     * CUIC devuelve `jsonData` como STRING JSON embebido → doble decode.
     *
     * @param  array<string, mixed>  $result
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private function parseResult(array $result, string $reportKey): Collection {
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

        return collect((array) $rows);
    }

    // -------------------------------------------------------------------------
    // Construcción de URLs
    // -------------------------------------------------------------------------

    /**
     * @return array{id: string, locale: string, params: array<string, string>}
     *
     * @throws RuntimeException
     */
    private function resolveReport(string $key): array {
        if (!isset($this->reports[$key])) {
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

    /** @param array{locale: string} $report */
    private function buildNewRestUrl(array $report): string {
        return "{$this->baseUrl}/cuic/rest/{$report['locale']}/reports/execute/newRest/";
    }

    /** @param array{locale: string} $report */
    private function buildPollUrl(array $report, string $dataSetId): string {
        return "{$this->baseUrl}/cuic/rest/{$report['locale']}/reports/execute/{$dataSetId}";
    }

    // -------------------------------------------------------------------------
    // HTTP
    // -------------------------------------------------------------------------

    /**
     * GET con Basic Auth.
     *
     * @param  array<string, string>  $query
     *
     * @throws RuntimeException
     */
    private function get(string $url, array $query = []): Response {
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

    /**
     * POST con Basic Auth.
     *
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $query
     *
     * @throws RuntimeException
     */
    private function post(string $url, array $body = [], array $query = []): Response {
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

    // -------------------------------------------------------------------------
    // Validaciones
    // -------------------------------------------------------------------------

    /**
     * Valida que los paramIds requeridos estén en la config del reporte.
     *
     * @param  array<string, string>  $params
     * @param  array<int, string>  $required
     *
     * @throws RuntimeException
     */
    private function assertParamIds(array $params, array $required): void {
        $missing = array_filter($required, fn(string $k) => empty($params[$k]));

        if (!empty($missing)) {
            throw new RuntimeException(
                '[CUIC] Faltan paramIds en config: ' . implode(', ', $missing)
                . '. Agrégalos en contact-center.cuic.reports.{key}.params'
            );
        }
    }
}
