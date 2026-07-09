<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\ConnectModule\Models\CsqRealtimeStat;
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

    #[Computed]
    public function ctiStatus(): array
    {
        try {
            $latest = CsqRealtimeStat::latest('updated_at')->first();
            if ($latest && $latest->updated_at && $latest->updated_at->gt(now()->subMinutes(2))) {
                return [
                    'online' => true,
                    'label' => 'CTI Online',
                    'updated_at' => $latest->updated_at->format('H:i:s'),
                ];
            }
        } catch (\Exception $e) {
            // Fallback silencioso en caso de caída o tabla inexistente
        }

        return [
            'online' => false,
            'label' => 'CTI Offline',
            'updated_at' => now()->format('H:i:s'),
        ];
    }

    public function render()
    {
        return view('operations::livewire.dashboard')
            ->layout('layouts.app', ['title' => 'Dashboard Operativo']);
    }
}
