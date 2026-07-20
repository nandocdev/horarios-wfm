<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Cisco;

use Exception;
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

    public function __construct()
    {
        $this->baseUrl = config('services.uccx.url_base', env('UCCX_URL_BASE'));
        $this->username = config('services.uccx.username', env('UCCX_USERNAME'));
        $this->password = config('services.uccx.password', env('UCCX_PASSWORD'));
        $this->timeout = (int) config('services.uccx.timeout', env('UCCX_TIMEOUT', 15));
        $this->batchTimeout = (int) config('services.uccx.batch_timeout', env('UCCX_BATCH_TIMEOUT', 45));
        $this->verifySsl = (bool) config('services.uccx.verify_ssl', env('UCCX_VERIFY_SSL', false));
    }

    /**
     * Realiza una petición GET al API de Finesse.
     */
    public function get(string $endpoint, ?int $timeout = null): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($timeout ?? $this->timeout)
                ->withHeaders(['Accept' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->get("{$this->baseUrl}/{$endpoint}");

            if ($response->failed()) {
                Log::warning("Cisco Finesse respondió con error {$response->status()} en {$endpoint}");
            }

            return $this->parseXml($response->body());
        } catch (Exception $e) {
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
     * Convierte una cadena XML en un arreglo asociativo de PHP.
     */
    protected function parseXml(string $xmlContent): array
    {
        // Limpiar el contenido de posibles caracteres invisibles al inicio
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
