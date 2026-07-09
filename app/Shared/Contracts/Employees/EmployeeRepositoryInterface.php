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
}
