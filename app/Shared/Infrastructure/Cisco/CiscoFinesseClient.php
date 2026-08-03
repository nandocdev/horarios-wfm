<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cisco;

use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CiscoFinesseClient
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected int $timeout;

    protected int $batchTimeout;

    protected bool $verifySsl;

    protected int $maxRetries;

    protected int $circuitBreakerThreshold;

    protected int $circuitBreakerResetSeconds;

    public function __construct()
    {
        $this->baseUrl = config('services.uccx.url_base', env('UCCX_URL_BASE'));
        $this->username = config('services.uccx.username', env('UCCX_USERNAME'));
        $this->password = config('services.uccx.password', env('UCCX_PASSWORD'));
        $this->timeout = (int) config('services.uccx.timeout', env('UCCX_TIMEOUT', 15));
        $this->batchTimeout = (int) config('services.uccx.batch_timeout', env('UCCX_BATCH_TIMEOUT', 45));
        $this->verifySsl = (bool) config('services.uccx.verify_ssl', env('UCCX_VERIFY_SSL', false));
        $this->maxRetries = (int) config('services.uccx.max_retries', env('UCCX_MAX_RETRIES', 2));
        $this->circuitBreakerThreshold = (int) config('services.uccx.circuit_breaker_threshold', env('UCCX_CIRCUIT_BREAKER_THRESHOLD', 5));
        $this->circuitBreakerResetSeconds = (int) config('services.uccx.circuit_breaker_reset_seconds', env('UCCX_CIRCUIT_BREAKER_RESET_SECONDS', 60));
    }

    /**
     * Realiza una petición GET al API de Finesse con retry y circuit breaker.
     */
    public function get(string $endpoint, ?int $timeout = null): array
    {
        if ($this->isCircuitOpen()) {
            Log::warning("Circuit breaker abierto para Cisco Finesse. Saltando petición: {$endpoint}");
            throw new Exception('Circuit breaker abierto para Cisco Finesse. Reintentando en: '.$this->circuitBreakerResetSeconds.' segundos.');
        }

        $attempt = 0;

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($timeout ?? $this->timeout)
                ->withHeaders(['Accept' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->retry($this->maxRetries, fn (int $attempt) => $attempt * 1000, function (Exception $e) {
                    if ($e instanceof RequestException && $e->response && $e->response->status() < 500) {
                        return false;
                    }

                    Log::warning("Retry por error de conexión Cisco: {$e->getMessage()}");

                    return true;
                })
                ->get("{$this->baseUrl}/{$endpoint}");

            $attempt = $response->transferStats?->getRequest()?->getUri() ? 0 : 0;

            if ($response->failed()) {
                $this->recordFailure();
                Log::warning("Cisco Finesse respondió con error {$response->status()} en {$endpoint}");

                throw new Exception("Cisco Finesse HTTP {$response->status()} en {$endpoint}");
            }

            $this->recordSuccess();

            return $this->parseXml($response->body());
        } catch (Exception $e) {
            $this->recordFailure();
            Log::error("Error de comunicación con Cisco UCCX: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Obtiene la información de un agente por su LoginID.
     */
    public function getAgentInfo(string $loginId): array
    {
        return $this->get("User/{$loginId}");
    }

    /**
     * Obtiene los diálogos (llamadas) activos de un agente.
     */
    public function getAgentDialogs(string $loginId): array
    {
        return $this->get("User/{$loginId}/Dialogs");
    }

    /**
     * Obtiene la lista de todos los usuarios (agentes) en Finesse.
     * Usa batchTimeout (más largo) para manejar respuestas con muchos registros.
     */
    public function getAllUsers(): array
    {
        return $this->get('Users', $this->batchTimeout);
    }

    /**
     * Obtiene la lista plana de usuarios desde Finesse.
     */
    public function getUsers(): array
    {
        $data = $this->getAllUsers();

        return $data['User'] ?? [];
    }

    /**
     * Obtiene la lista de equipos desde Finesse.
     */
    public function getTeams(): array
    {
        $data = $this->get('Teams');

        return $data['Team'] ?? [];
    }

    /**
     * Verifica si el circuit breaker está abierto.
     */
    protected function isCircuitOpen(): bool
    {
        $failures = (int) Cache::get('cisco_circuit_breaker_failures', 0);

        if ($failures < $this->circuitBreakerThreshold) {
            return false;
        }

        $lastFailure = Cache::get('cisco_circuit_breaker_last_failure');
        if (! $lastFailure) {
            return false;
        }

        if (now()->diffInSeconds($lastFailure) > $this->circuitBreakerResetSeconds) {
            Cache::forget('cisco_circuit_breaker_failures');
            Cache::forget('cisco_circuit_breaker_last_failure');

            return false;
        }

        return true;
    }

    /**
     * Registra una falla en el circuit breaker.
     */
    protected function recordFailure(): void
    {
        $failures = (int) Cache::get('cisco_circuit_breaker_failures', 0) + 1;
        Cache::put('cisco_circuit_breaker_failures', $failures, 3600);
        Cache::put('cisco_circuit_breaker_last_failure', now(), 3600);
    }

    /**
     * Resetea el circuit breaker en éxito.
     */
    protected function recordSuccess(): void
    {
        $failures = (int) Cache::get('cisco_circuit_breaker_failures', 0);

        if ($failures > 0) {
            Cache::put('cisco_circuit_breaker_failures', 0, 3600);
        }
    }

    /**
     * Convierte una cadena XML en un arreglo asociativo de PHP.
     */
    protected function parseXml(string $xmlContent): array
    {
        $xmlContent = trim($xmlContent);

        if (empty($xmlContent)) {
            return [];
        }

        try {
            $xml = simplexml_load_string($xmlContent, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);

            return json_decode($json, true);
        } catch (Exception $e) {
            Log::error('Error al parsear XML de UCCX: '.$e->getMessage());

            return [];
        }
    }
}
