<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CiscoFinesseService
{
    protected string $baseUrl;

    protected string $username;

    protected string $password;

    protected int $timeout;

    protected bool $verifySsl;

    public function __construct()
    {
        $this->baseUrl = env('UCCX_URL_BASE', 'https://10.11.24.85:8445/finesse/api');
        $this->username = env('UCCX_USERNAME');
        $this->password = env('UCCX_PASSWORD');
        $this->timeout = (int) env('UCCX_TIMEOUT', 15);
        $this->verifySsl = (bool) env('UCCX_VERIFY_SSL', false);
    }

    /**
     * Obtiene la lista de equipos desde Cisco Finesse API.
     *
     * @throws \Exception
     */
    public function getTeams(): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySsl,
                ])
                ->get("{$this->baseUrl}/Teams");

            if ($response->failed()) {
                Log::error('Finesse API Error [Teams]: '.$response->body());
                throw new \Exception('Error al conectar con Cisco Finesse: '.$response->status());
            }

            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new \Exception('Error al parsear XML de Finesse.');
            }

            $teams = [];
            foreach ($xml->Team as $team) {
                $teams[] = [
                    'id' => (string) $team->id,
                    'name' => (string) $team->name,
                    'uri' => (string) $team->uri,
                ];
            }

            return $teams;

        } catch (\Exception $e) {
            Log::error('Finesse Service Exception [Teams]: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene la lista de usuarios (agentes) desde Cisco Finesse API.
     *
     * @throws \Exception
     */
    public function getUsers(): array
    {
        try {
            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->withOptions([
                    'verify' => $this->verifySsl,
                ])
                ->get("{$this->baseUrl}/Users");

            if ($response->failed()) {
                Log::error('Finesse API Error [Users]: '.$response->body());
                throw new \Exception('Error al conectar con Cisco Finesse (Users): '.$response->status());
            }

            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                throw new \Exception('Error al parsear XML de Finesse (Users).');
            }

            $users = [];
            foreach ($xml->User as $user) {
                $users[] = [
                    'loginId' => (string) $user->loginId,
                    'firstName' => (string) $user->firstName,
                    'lastName' => (string) $user->lastName,
                    'teamId' => (string) $user->teamId,
                    'teamName' => (string) $user->teamName,
                ];
            }

            return $users;

        } catch (\Exception $e) {
            Log::error('Finesse Service Exception [Users]: '.$e->getMessage());
            throw $e;
        }
    }
}
