<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Servicio para interactuar con la API REST de Cisco Finesse.
 * Puerto estándar: 8445 (HTTPS)
 */
final class FinesseService {
    private string $baseUrl;
    private string $username;
    private string $password;
    private bool $verifySsl;

    public function __construct() {
        $cfg = config('contact-center.cisco');

        // Finesse API suele estar en el puerto 8445
        // Si la base_url no tiene puerto, intentamos usar el estándar
        $url = (string) $cfg['base_url'];
        if (!str_contains($url, ':8445')) {
            $url = str_replace(':8444', '', $url); // Limpiar si copiaron la de CUIC
            $url = rtrim($url, '/') . ':8445';
        }

        $this->baseUrl = $url;
        $this->username = (string) $cfg['username'];
        $this->password = (string) $cfg['password'];
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? false);
    }

    /**
     * Obtiene la lista completa de usuarios (agentes/supervisores) desde Finesse.
     * 
     * @return array<int, array<string, mixed>>
     */
    public function getUsers(): array {
        $url = rtrim($this->baseUrl, '/') . '/Users';

        Log::info("[Finesse] Consultando usuarios", ['url' => $url]);

        $response = Http::withBasicAuth($this->username, $this->password)
            ->withHeaders(['Accept' => 'application/xml']) // Finesse es nativamente XML
            ->withoutVerifying(!$this->verifySsl)
            ->get($url);

        if (!$response->successful()) {
            Log::error('[Finesse] Error en la petición', [
                'url' => $url,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException("Finesse API Error {$response->status()} en {$url}");
        }

        try {
            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new RuntimeException("No se pudo parsear el XML de Finesse");
            }

            $json = json_encode($xml);
            $data = json_decode($json, true);

            // La estructura de Finesse para múltiples usuarios es <Users><User>...</User></Users>
            // o a veces simplemente una lista de <User> dependiendo de la versión
            return $data['User'] ?? [];
        } catch (\Throwable $e) {
            Log::error('[Finesse] Error al procesar respuesta XML', [
                'error' => $e->getMessage(),
                'body' => $response->body()
            ]);
            throw $e;
        }
    }
}