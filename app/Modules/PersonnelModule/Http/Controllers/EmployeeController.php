<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PersonnelModule\Actions\CreateEmployeeAction;
use App\Modules\PersonnelModule\Actions\DeleteEmployeeAction;
use App\Modules\PersonnelModule\Actions\UpdateEmployeeAction;
use App\Modules\PersonnelModule\DTOs\CreateEmployeeDTO;
use App\Modules\PersonnelModule\DTOs\UpdateEmployeeDTO;
use App\Modules\PersonnelModule\Http\Requests\StoreEmployeeRequest;
use App\Modules\PersonnelModule\Http\Requests\UpdateEmployeeRequest;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador para gestión de empleados.
 * Solo orquestación: valida → action → response
 *
 * @module EmployeesModule
 *
 * @type Controller
 *
 * @author GitHub Copilot
 *
 * @created 2026-03-25
 */
class EmployeeController extends Controller
{
    /**
     * Muestra la lista de empleados.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Employee::class);

        return view('employees::index');
    }

    /**
     * Muestra el formulario para crear un empleado.
     */
    public function create(): View
    {
        return view('employees::create');
    }

    /**
     * Muestra el formulario de importación masiva CSV.
     */
    public function import(): View
    {
        $this->authorize('import', Employee::class);

        return view('employees::import');
    }

    /**
     * Almacena un nuevo empleado.
     */
    public function store(
        StoreEmployeeRequest $request,
        CreateEmployeeAction $action,
    ): RedirectResponse {
        $dto = CreateEmployeeDTO::fromArray($request->validated());
        $employee = $action->execute($dto);

        return redirect()
            ->route('employees.show', $employee)
            ->with('success', 'Empleado creado correctamente.');
    }

    /**
     * Muestra los detalles de un empleado.
     */
    public function show(Employee $employee): View
    {
        $employee->load([
            'department',
            'position',
            'employmentStatus',
            'township',
            'manager',
            'subordinates.position',
        ]);

        return view('employees::show', compact('employee'));
    }

    /**
     * Muestra el formulario para editar un empleado.
     */
    public function edit(Employee $employee): View
    {
        return view('employees::edit', compact('employee'));
    }

    /**
     * Actualiza un empleado existente.
     */
    public function update(
        UpdateEmployeeRequest $request,
        Employee $employee,
        UpdateEmployeeAction $action,
    ): RedirectResponse {
        $dto = UpdateEmployeeDTO::fromArray($request->validated());
        $updatedEmployee = $action->execute($employee, $dto);

        return redirect()
            ->route('employees.show', $updatedEmployee)
            ->with('success', 'Empleado actualizado correctamente.');
    }

    /**
     * Elimina un empleado (soft delete).
     */
    public function destroy(Employee $employee, DeleteEmployeeAction $action): RedirectResponse
    {
        $this->authorize('delete', $employee);

        $action->execute($employee);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Empleado eliminado correctamente.');
    }
}
