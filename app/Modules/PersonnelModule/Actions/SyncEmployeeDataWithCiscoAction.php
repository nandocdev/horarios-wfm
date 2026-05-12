<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\PersonnelModule\Models\Team;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Sincroniza datos básicos de empleados y su asignación de equipo desde Cisco.
 */
class SyncEmployeeDataWithCiscoAction
{
    public function __construct(
        protected CiscoFinesseClient $client
    ) {}

    public function execute(): array
    {
        $stats = [
            'total_cisco_users' => 0,
            'updated_employees' => 0,
            'unmatched_users' => 0,
            'team_mismatches' => 0,
        ];

        try {
            $response = $this->client->getAllUsers();
            $ciscoUsers = $response['User'] ?? [];

            // Si solo hay un usuario, SimpleXML lo devuelve como objeto único, lo normalizamos a array
            if (isset($ciscoUsers['loginId'])) {
                $ciscoUsers = [$ciscoUsers];
            }

            $stats['total_cisco_users'] = count($ciscoUsers);

            // Caché de equipos para evitar queries repetitivas
            $teamsCache = Team::whereNotNull('cisco_team_id')->get()->keyBy('cisco_team_id');

            foreach ($ciscoUsers as $ciscoUser) {
                $loginId = $ciscoUser['loginId'] ?? null;

                if (! $loginId) {
                    continue;
                }

                $employee = Employee::where('username', $loginId)->first();

                if (! $employee) {
                    $stats['unmatched_users']++;

                    continue;
                }

                $this->syncEmployee($employee, $ciscoUser, $teamsCache, $stats);
            }

        } catch (\Exception $e) {
            Log::error('Error en SyncEmployeeDataWithCiscoAction: '.$e->getMessage());
            throw $e;
        }

        return $stats;
    }

    protected function syncEmployee(Employee $employee, array $ciscoUser, $teamsCache, &$stats): void
    {
        DB::transaction(function () use ($employee, $ciscoUser, $teamsCache, &$stats) {
            $updated = false;

            // 1. Actualizar Nombres
            $firstName = $ciscoUser['firstName'] ?? null;
            $lastName = $ciscoUser['lastName'] ?? null;

            if ($firstName && $employee->first_name !== $firstName) {
                $employee->first_name = $firstName;
                $updated = true;
            }

            if ($lastName && $employee->last_name !== $lastName) {
                $employee->last_name = $lastName;
                $updated = true;
            }

            // 2. Validar Equipo
            // El API de Users suele devolver el equipo en teamUri o similar,
            // a veces necesitamos una segunda llamada por User/{id} para ver el equipo.
            // Según el requerimiento, intentamos obtenerlo de la respuesta de /Users
            $teamIdFromCisco = $this->extractTeamId($ciscoUser);

            if ($teamIdFromCisco) {
                $mappedTeam = $teamsCache->get($teamIdFromCisco);

                if ($mappedTeam) {
                    if ($employee->team_id !== $mappedTeam->id) {
                        $employee->team_id = $mappedTeam->id;
                        $employee->parent_id = $mappedTeam->supervisor_id;
                        $updated = true;
                        $stats['team_mismatches']++;
                    }
                }
            }

            if ($updated) {
                $employee->save();
                $stats['updated_employees']++;
            }
        });
    }

    protected function extractTeamId(array $ciscoUser): ?string
    {
        // En UCCX /finesse/api/Users, el teamId puede venir en 'teamId' o dentro de 'team'
        if (isset($ciscoUser['teamId'])) {
            return (string) $ciscoUser['teamId'];
        }

        if (isset($ciscoUser['team']['id'])) {
            return (string) $ciscoUser['team']['id'];
        }

        return null;
    }
}
