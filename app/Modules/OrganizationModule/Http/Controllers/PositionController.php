<?php

declare(strict_types=1);

namespace App\Modules\OrganizationModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\OrganizationModule\Models\Position;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para Positions.
 * Solo orquestación: valida → action → response.
 */
class PositionController extends Controller
{
    /**
     * Muestra una lista de positions.
     */
    public function index(): View
    {
        $positions = Position::with('department.directorate')->get();

        return view('organization::positions.index', compact('positions'));
    }

    /**
     * Muestra el formulario para crear un nuevo position.
     */
    public function create(): View
    {
        return view('organization::positions.create');
    }

    /**
     * Almacena un nuevo position.
     */
    public function store(Request $request)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.positions.index');
    }

    /**
     * Muestra un position específico.
     */
    public function show(Position $position): View
    {
        $position->load('department.directorate');

        return view('organization::positions.show', compact('position'));
    }

    /**
     * Muestra el formulario para editar un position.
     */
    public function edit(Position $position): View
    {
        return view('organization::positions.edit', compact('position'));
    }

    /**
     * Actualiza un position específico.
     */
    public function update(Request $request, Position $position)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.positions.show', $position);
    }

    /**
     * Elimina un position específico.
     */
    public function destroy(Position $position)
    {
        // TODO: Implementar con Action
        return redirect()->route('organization.positions.index');
    }
}
