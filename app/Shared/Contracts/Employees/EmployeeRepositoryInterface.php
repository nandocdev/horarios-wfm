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
    /**
     * Busca un empleado por su ID.
     */
    public function find(int $id): ?EmployeeInterface;

    /**
     * Busca un empleado por su nombre de usuario.
     */
    public function findByUsername(string $username): ?EmployeeInterface;

    /**
     * Retorna todos los empleados activos.
     *
     * @return EmployeeInterface[]
     */
    public function findActive(): array;

    /**
     * Retorna empleados que pertenecen a un equipo.
     *
     * @return EmployeeInterface[]
     */
    public function findByTeam(int $teamId): array;

    /**
     * Retorna empleados activos que pertenecen a una lista de equipos.
     *
     * @param  int[]  $teamIds
     * @return EmployeeInterface[]
     */
    public function findActiveByTeams(array $teamIds): array;

    /**
     * Retorna empleados activos que pertenecen a una lista de cargos.
     *
     * @param  int[]  $positionIds
     * @return EmployeeInterface[]
     */
    public function findActiveByPositions(array $positionIds): array;
}
