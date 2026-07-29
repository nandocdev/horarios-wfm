<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use Livewire\Attributes\On;
use Livewire\Component;

class HeaderWidget extends Component
{
    public string $selectedDate;

    public string $scope = 'all';

    public int $refreshInterval = 60;

    public string $todayLabel;

    public string $currentTime;

    public string $greeting;

    public string $displayName;

    public string $roleLabel;

    public string $role;

    public $teams = [];

    public ?int $teamId = null;

    public string $serverTime = '';

    public function mount(): void
    {
        $this->serverTime = now()->format('H:i:s');
    }

    #[On('control-tower-refresh')]
    public function onRefresh(): void
    {
        $this->serverTime = now()->format('H:i:s');
        $this->dispatch('$refresh');
    }

    public function updatedScope($value): void
    {
        $this->dispatch('control-tower-scope-changed', scope: $value);
    }

    public function updatedTeamId($value): void
    {
        $this->dispatch('control-tower-team-changed', teamId: (int) $value);
    }

    public function render()
    {
        $this->serverTime = now()->format('H:i:s');

        return view('operations::livewire.control-tower.header-widget');
    }
}
