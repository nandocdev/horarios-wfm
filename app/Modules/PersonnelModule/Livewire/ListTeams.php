<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Livewire;

use App\Modules\PersonnelModule\Actions\SyncEmployeeTeamsWithCiscoAction;
use App\Modules\PersonnelModule\Actions\SyncTeamsWithCiscoAction;
use App\Modules\PersonnelModule\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire para listar equipos.
 *
 * Incluye filtros por nombre y estado, paginación y acciones básicas.
 */
class ListTeams extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    public ?bool $activeFilter = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'activeFilter' => ['except' => null],
    ];

    /**
     * Resetea la paginación cuando cambian los filtros.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedActiveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * Obtiene la consulta base con filtros aplicados.
     */
    public function getTeamsQuery(): Builder
    {
        return Team::query()
            ->when($this->search, function (Builder $query) {
                $query->where('name', 'ilike', '%'.$this->search.'%')
                    ->orWhere('description', 'ilike', '%'.$this->search.'%');
            })
            ->when($this->activeFilter !== null, function (Builder $query) {
                $query->where('is_active', $this->activeFilter);
            })
            ->orderBy('name');
    }

    /**
     * Sincroniza los equipos locales con los datos de Cisco Finesse.
     */
    public function syncWithCisco(
        SyncTeamsWithCiscoAction $syncTeams,
        SyncEmployeeTeamsWithCiscoAction $syncMembers
    ): void {
        try {
            $teamCount = $syncTeams->execute();
            $memberResults = $syncMembers->execute();

            \Flux::toast(
                heading: 'Sincronización Completada',
                text: "Se han sincronizado {$teamCount} equipos y {$memberResults['synced']} agentes con Cisco Finesse. ({$memberResults['transfers']} transferencias detectadas).",
                variant: 'success'
            );
        } catch (\Exception $e) {
            \Flux::toast(
                heading: 'Error de Sincronización',
                text: 'No se pudo conectar con la API de Cisco Finesse: '.$e->getMessage(),
                variant: 'danger'
            );
        }

        $this->resetPage();
    }

    public function render()
    {
        $teams = $this->getTeamsQuery()
            ->withCount('users')
            ->paginate($this->perPage);

        return view('personnel::livewire.list-teams', [
            'teams' => $teams,
        ]);
    }
}
