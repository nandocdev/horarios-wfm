<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component
{
    public string $selectedDate;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    public function render()
    {
        $user = auth()->user();
        $employee = $user?->employee()->first();
        $displayName = $employee?->full_name ?? $user?->name ?? 'Operador';
        $teamName = $employee?->team?->name ?? 'CSS-01';
        $supervisorName = $employee?->manager?->full_name ?? 'María Pérez';
        $greeting = match (true) {
            now()->hour < 12 => 'Buenos días',
            now()->hour < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };

        return view('operations::livewire.dashboard', [
            'greeting' => $greeting,
            'displayName' => $displayName,
            'todayLabel' => Carbon::now()->locale('es')->translatedFormat('l d F Y'),
            'shift' => [
                'start' => '07:00',
                'end' => '15:00',
                'team' => $teamName,
                'supervisor' => $supervisorName,
            ],
            'operationalScore' => [
                'value' => 91,
                'label' => 'Excelente',
                'delta' => '+3 respecto ayer',
            ],
            'status' => [
                'label' => 'En llamada',
                'subtitle' => 'Llamada actual',
                'currentCall' => '06:14',
                'loggedTime' => '04:28',
                'remainingTime' => '03:32',
            ],
            'journey' => [
                ['time' => '07:00', 'label' => 'Login', 'complete' => true],
                ['time' => '09:30', 'label' => 'Break', 'complete' => true],
                ['time' => '11:30', 'label' => 'Almuerzo', 'complete' => true],
                ['time' => '14:00', 'label' => 'Break', 'complete' => false],
                ['time' => '15:00', 'label' => 'Salida', 'complete' => false],
            ],
            'productivity' => [
                ['label' => 'Llamadas', 'value' => '42', 'meta' => 'Meta 50', 'progress' => 84],
                ['label' => 'Tiempo hablado', 'value' => '2h 41m', 'meta' => 'Meta 3h', 'progress' => 89],
                ['label' => 'Tiempo ACW', 'value' => '21 min', 'meta' => 'Meta 25 min', 'progress' => 84],
                ['label' => 'Tiempo disponible', 'value' => '57 min', 'meta' => 'Meta 45 min', 'progress' => 127],
            ],
            'comparison' => [
                ['indicator' => 'Llamadas', 'self' => '42', 'team' => '38', 'top' => '57'],
                ['indicator' => 'AHT', 'self' => '5:20', 'team' => '5:48', 'top' => '4:55'],
                ['indicator' => 'Calidad', 'self' => '96%', 'team' => '93%', 'top' => '98%'],
                ['indicator' => 'Adherencia', 'self' => '98%', 'team' => '94%', 'top' => '99%'],
            ],
            'adherence' => [
                'value' => 98,
                'detail' => 'Programado 8h · Logueado 7h 56m · Desvío -4 min',
            ],
            'availability' => [
                ['label' => 'En llamada', 'value' => 48],
                ['label' => 'Disponible', 'value' => 19],
                ['label' => 'ACW', 'value' => 11],
                ['label' => 'Break', 'value' => 9],
                ['label' => 'Lunch', 'value' => 13],
            ],
            'quality' => [
                'value' => 96,
                'items' => [
                    ['label' => 'Protocolos', 'value' => 98],
                    ['label' => 'Empatía', 'value' => 94],
                    ['label' => 'Documentación', 'value' => 95],
                ],
            ],
        ])
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
