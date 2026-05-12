<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PersonnelModule\Models\Directorate;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para Directorates.
 * Solo orquestación: valida → action → response.
 */
class DirectorateController extends Controller
{
    /**
     * Muestra una lista de directorates.
     */
    public function index(): View
    {
        $directorates = Directorate::with('departments')->get();

        return view('organization::directorates.index', compact('directorates'));
    }

    /**
     * Muestra el formulario para crear un nuevo directorate.
     */
    public function create(): View
    {
        return view('organization::directorates.create');
    }

    /**
     * Almacena un nuevo directorate.
     */
    public function store(Request $request)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.directorates.index');
    }

    /**
     * Muestra un directorate específico.
     */
    public function show(Directorate $directorate): View
    {
        $directorate->load('departments.positions');

        return view('organization::directorates.show', compact('directorate'));
    }

    /**
     * Muestra el formulario para editar un directorate.
     */
    public function edit(Directorate $directorate): View
    {
        return view('organization::directorates.edit', compact('directorate'));
    }

    /**
     * Actualiza un directorate específico.
     */
    public function update(Request $request, Directorate $directorate)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.directorates.show', $directorate);
    }

    /**
     * Elimina un directorate específico.
     */
    public function destroy(Directorate $directorate)
    {
        // TODO: Implementar con Action
        return redirect()->route('organization.directorates.index');
    }
}
