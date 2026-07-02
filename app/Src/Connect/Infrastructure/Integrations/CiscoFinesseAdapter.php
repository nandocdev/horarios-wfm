<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Integrations;

use App\Src\Connect\Application\DTOs\SyncEmployeeDTO;
use App\Src\Connect\Application\DTOs\SyncTeamDTO;
use App\Src\Connect\Domain\Ports\CiscoAprovisioningInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CiscoFinesseAdapter implements CiscoAprovisioningInterface
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl = config('contact-center.cisco.base_url', '');
        $this->username = config('contact-center.cisco.username', '');
        $this->password = config('contact-center.cisco.password', '');
        $this->timeout = (int) config('contact-center.cisco.timeout', 15);
        $this->verifySsl = (bool) config('contact-center.cisco.verify_ssl', false);
    }

    public function syncEmployee(SyncEmployeeDTO $dto): array
    {
        $payload = [
            'loginId' => $dto->loginId,
            'firstName' => $dto->firstName,
            'lastName' => $dto->lastName,
            'emailAddress' => $dto->email ?? '',
        ];

        if ($dto->teamId) {
            $payload['teamId'] = $dto->teamId;
        }

        if ($dto->extension) {
            $payload['extension'] = $dto->extension;
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->put("{$this->baseUrl}/User/{$dto->loginId}", $this->buildUserXml($payload));

            if ($response->failed()) {
                throw new \RuntimeException(
                    "Cisco API error: {$response->status()} - {$response->body()}"
                );
            }

            return $this->parseXml($response->body());

        } catch (ConnectionException $e) {
            Log::error("Cisco connection timeout for user {$dto->loginId}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function syncTeam(SyncTeamDTO $dto): array
    {
        $payload = [
            'id' => $dto->teamId,
            'name' => $dto->name,
            'description' => $dto->description ?? '',
        ];

        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->put("{$this->baseUrl}/Team/{$dto->teamId}", $this->buildTeamXml($payload));

            if ($response->failed()) {
                throw new \RuntimeException(
                    "Cisco API error: {$response->status()} - {$response->body()}"
                );
            }

            return $this->parseXml($response->body());

        } catch (ConnectionException $e) {
            Log::error("Cisco connection timeout for team {$dto->teamId}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function removeEmployee(string $loginId): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withHeaders(['Accept' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->delete("{$this->baseUrl}/User/{$loginId}");

            return $response->successful();

        } catch (ConnectionException $e) {
            Log::error("Cisco connection timeout deleting user {$loginId}: {$e->getMessage()}");
            return false;
        }
    }

    public function testConnection(): bool
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(5)
                ->withHeaders(['Accept' => 'application/xml'])
                ->when(! $this->verifySsl, fn ($http) => $http->withoutVerifying())
                ->get("{$this->baseUrl}/Users");

            return $response->successful();

        } catch (\Throwable) {
            return false;
        }
    }

    private function buildUserXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<User/>');
        foreach ($data as $key => $value) {
            $xml->addChild($key, htmlspecialchars((string) $value));
        }
        return $xml->asXML();
    }

    private function buildTeamXml(array $data): string
    {
        $xml = new \SimpleXMLElement('<Team/>');
        foreach ($data as $key => $value) {
            $xml->addChild($key, htmlspecialchars((string) $value));
        }
        return $xml->asXML();
    }

    private function parseXml(string $content): array
    {
        $content = trim($content);
        if (empty($content)) return [];

        try {
            $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
            $json = json_encode($xml);
            return json_decode($json, true) ?? [];
        } catch (\Throwable $e) {
            Log::warning("Failed to parse Cisco XML response: {$e->getMessage()}");
            return [];
        }
    }
}
