<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para Teams.
 * Solo orquestación: valida → action → response.
 */
class TeamController extends Controller
{
    /**
     * Muestra una lista de teams.
     */
    public function index(): View
    {
        $teams = Team::with('members.employee')->get();

        return view('organization::teams.index', compact('teams'));
    }

    /**
     * Muestra el formulario para crear un nuevo team.
     */
    public function create(): View
    {
        return view('organization::teams.create');
    }

    /**
     * Almacena un nuevo team.
     */
    public function store(Request $request)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.teams.index');
    }

    /**
     * Muestra un team específico.
     */
    public function show(Team $team): View
    {
        $team->load('members.employee');

        return view('organization::teams.show', compact('team'));
    }

    /**
     * Muestra el formulario para editar un team.
     */
    public function edit(Team $team): View
    {
        return view('organization::teams.edit', compact('team'));
    }

    /**
     * Actualiza un team específico.
     */
    public function update(Request $request, Team $team)
    {
        // TODO: Implementar con Action y Request
        return redirect()->route('organization.teams.show', $team);
    }

    /**
     * Elimina un team específico.
     */
    public function destroy(Team $team)
    {
        // TODO: Implementar con Action
        return redirect()->route('organization.teams.index');
    }
}
