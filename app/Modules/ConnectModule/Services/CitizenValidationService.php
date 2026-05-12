<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CitizenValidationService
{
    protected string $baseUrl;

    public function __construct()
    {
        // Priorizar config si existe, fallback a env
        $this->baseUrl = env('VALIDACION_DERECHO', 'https://validacionderecho.css.gob.pa/wscss/rest/atencionmedica/validarderecho/');
    }

    /**
     * Valida el derecho de un ciudadano por su identificación.
     * 
     * @param string $identifier
     * @return array|null
     */
    public function validate(string $identifier): ?array
    {
        if (empty($identifier)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->withoutVerifying() // Saltamos SSL por ser entorno interno/gobierno
            ->get($this->baseUrl . trim($identifier));

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("CitizenValidationService: Fallo en respuesta ({$response->status()}) para {$identifier}");
            return null;
        } catch (\Exception $e) {
            Log::error("CitizenValidationService Exception: " . $e->getMessage());
            return null;
        }
    }
}
