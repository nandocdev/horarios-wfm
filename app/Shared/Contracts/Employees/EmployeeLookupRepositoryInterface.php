<?php

declare(strict_types=1);

namespace App\Shared\Contracts\Employees;

/**
 * Contrato para resolución de employee_id desde identificadores de Cisco CUIC.
 *
 * Permite que ConnectModule obtenga IDs de empleados sin acoplar
 * directamente al modelo Eloquent de EmployeesModule.
 *
 * Comunicación inter-módulo: Shared/Contracts (SRP + Dependency Inversion).
 *
 * @see App\Modules\PersonnelModule\Repositories\EloquentEmployeeLookupRepository
 */
interface EmployeeLookupRepositoryInterface
{
    /**
     * Precarga en memoria el mapa login_id → employee_id y fullname → employee_id.
     * Debe llamarse una sola vez por ciclo de proceso (warmup).
     */
    public function warmup(): void;

    /**
     * Resuelve el employee_id desde el login de Cisco (username).
     *
     * @param  string|null  $loginId  Login de Cisco (ej. "fcastillo")
     * @return int|null ID interno del empleado, o null si no existe
     */
    public function findByLoginId(?string $loginId): ?int;

    /**
     * Resuelve el employee_id desde el nombre completo tal como aparece en CUIC.
     *
     * Fallback cuando login_id no está disponible o no hay match.
     *
     * @param  string|null  $fullName  Nombre completo en formato CUIC (ej. "Fernando Castillo Valdez")
     */
    public function findByFullName(?string $fullName): ?int;

    /**
     * Intenta primero por loginId y, si falla, por fullName.
     */
    public function resolve(?string $loginId, ?string $fullName = null): ?int;
}
