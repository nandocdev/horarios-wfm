<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PersonnelModule\Models\Department;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para Departments.
 * Solo orquestación: valida → action → response.
 */
class DepartmentController extends Controller
{
    /**
     * Muestra una lista de departments.
     */
    public function index(): View
    {
        $departments = Department::with('directorate', 'positions')->get();

        return view('organization::departments.index', compact('departments'));
    }

    /**
     * Muestra el formulario para crear un nuevo department.
     */
    public function create(): View
    {
        return view('organization::departments.create');
    }

    /**
     * Almacena un nuevo department.
     */
    public function store(Request $request)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.departments.index');
    }

    /**
     * Muestra un department específico.
     */
    public function show(Department $department): View
    {
        $department->load('directorate', 'positions');

        return view('organization::departments.show', compact('department'));
    }

    /**
     * Muestra el formulario para editar un department.
     */
    public function edit(Department $department): View
    {
        return view('organization::departments.edit', compact('department'));
    }

    /**
     * Actualiza un department específico.
     */
    public function update(Request $request, Department $department)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.departments.show', $department);
    }

    /**
     * Elimina un department específico.
     */
    public function destroy(Department $department)
    {
        // TODO: Implementar con Action
        return redirect()->route('organization.departments.index');
    }
}
