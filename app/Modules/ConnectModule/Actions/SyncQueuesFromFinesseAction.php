<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\CallQueue;
use App\Shared\Support\CallQueueCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SyncQueuesFromFinesseAction
{
    private string $baseUrl;

    private string $username;

    private string $password;

    private bool $verifySsl;

    private int $timeout;

    private int $maxQueueId;

    public function __construct()
    {
        $cfg = config('contact-center.finesse');

        $this->baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $this->username = (string) ($cfg['username'] ?? '');
        $this->password = (string) ($cfg['password'] ?? '');
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? false);
        $this->timeout = (int) ($cfg['timeout'] ?? 30);
        $this->maxQueueId = (int) ($cfg['max_queue_id'] ?? 100);
    }

    /**
     * @return array{discovered: int, created: int, updated: int, errors: int}
     */
    public function execute(): array
    {
        $stats = ['discovered' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];

        if (empty($this->baseUrl)) {
            Log::warning('[FINESSE] FINESSE_BASE_URL no configurada.');

            return $stats;
        }

        for ($id = 1; $id <= $this->maxQueueId; $id++) {
            try {
                $queueData = $this->fetchQueue($id);

                if ($queueData === null) {
                    continue;
                }

                $stats['discovered']++;

                $name = $queueData['name'];
                $result = CallQueue::updateOrCreate(
                    ['name' => $name],
                    [
                        'finesse_queue_id' => $id,
                        'is_active' => true,
                    ]
                );

                if ($result->wasRecentlyCreated) {
                    $stats['created']++;
                    Log::info("[FINESSE] Cola creada: {$name} (ID {$id})");
                } elseif ($result->wasChanged()) {
                    $stats['updated']++;
                    Log::info("[FINESSE] Cola actualizada: {$name} (ID {$id})");
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::warning("[FINESSE] Error en Queue/{$id}: {$e->getMessage()}");
            }
        }

        app(CallQueueCache::class)->refresh();

        Log::info('[FINESSE] Sincronización completada', $stats);

        return $stats;
    }

    /**
     * Obtiene información de una cola desde Finesse API.
     *
     * @return array{name: string}|null
     */
    private function fetchQueue(int $queueId): ?array
    {
        $url = "{$this->baseUrl}/finesse/api/Queue/{$queueId}";

        $response = Http::withBasicAuth($this->username, $this->password)
            ->timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->accept('application/xml')
            ->get($url);

        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            Log::warning("[FINESSE] Queue/{$queueId} respondió {$response->status()}");

            return null;
        }

        try {
            $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            $data = json_decode($json, true);

            $name = $data['name'] ?? null;

            if (empty($name)) {
                return null;
            }

            return ['name' => $name];
        } catch (\Exception $e) {
            Log::warning("[FINESSE] Error parseando XML de Queue/{$queueId}: {$e->getMessage()}");

            return null;
        }
    }
}
