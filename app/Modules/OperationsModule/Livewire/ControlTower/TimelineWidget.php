<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire\ControlTower;

use Livewire\Component;

class TimelineWidget extends Component
{
    public ?string $selectedDate = null;

    public function render()
    {
        $events = [
            ['time' => '08:15', 'title' => 'Forecast publicado', 'type' => 'info'],
            ['time' => '08:30', 'title' => 'Déficit cola soporte', 'type' => 'warning'],
            ['time' => '09:10', 'title' => '20 agentes a capacitación', 'type' => 'primary'],
            ['time' => '09:35', 'title' => 'SLA recuperado', 'type' => 'success'],
        ];

        return view('operations::livewire.control-tower.timeline-widget', [
            'events' => $events,
        ]);
    }
}
