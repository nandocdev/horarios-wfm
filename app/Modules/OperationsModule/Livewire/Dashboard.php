<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class Dashboard extends Component
{
    public int $refreshInterval = 15;

    public string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    #[Computed]
    public function isHistorical(): bool
    {
        return $this->selectedDate !== now()->toDateString();
    }

    public function render()
    {
        return view('operations::livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard Operativo']);
    }
}
