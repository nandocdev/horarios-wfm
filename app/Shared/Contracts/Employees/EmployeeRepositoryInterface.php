<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Employees;

/**
 * Contrato para el acceso a datos de empleados (Repository Pattern).
 *
 * Permite desacoplar otros módulos del modelo Eloquent Employee.
 */
interface EmployeeRepositoryInterface
{
    public function find(int $id): ?EmployeeInterface;

    public function findByUsername(string $username): ?EmployeeInterface;

    /**
     * @return EmployeeInterface[]
     */
    public function findActive(): array;

    /**
     * @return EmployeeInterface[]
     */
    public function findByTeam(int $teamId): array;

    /**
     * @param  int[]  $teamIds
     * @return EmployeeInterface[]
     */
    public function findActiveByTeams(array $teamIds): array;

    /**
     * @param  int[]  $positionIds
     * @return EmployeeInterface[]
     */
    public function findActiveByPositions(array $positionIds): array;

    /**
     * Empleados objetivo de un scorecard: filtra por posiciones, equipo y búsqueda
     * de nombre en una sola consulta (evita recorrer todos los empleados en memoria).
     *
     * @param  int[]  $positionIds
     * @return EmployeeInterface[]
     */
    public function findAgentsByPositions(array $positionIds, ?int $teamId = null, ?string $search = null): array;

    public function findByUser(int $userId): ?EmployeeInterface;

    /**
     * Retorna los IDs de todos los subordinados recursivos.
     *
     * @return int[]
     */
    public function getSubordinateIds(int $employeeId): array;

    /**
     * Busca empleados por nombre, username o email.
     *
     * @return EmployeeInterface[]
     */
    public function search(string $query): array;
}
