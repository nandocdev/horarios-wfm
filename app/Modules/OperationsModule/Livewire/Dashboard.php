<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Carbon\Carbon;
use Livewire\Component;

class Dashboard extends Component {
    public string $selectedDate;

    public function mount(): void {
        $this->selectedDate = now()->toDateString();
    }

    public function render() {
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
            'currentTime' => Carbon::now()->locale('es')->translatedFormat('H:i A'),
            'weekRange' => '07 Jul - 13 Jul',
            'operationStatus' => [
                'label' => 'Operación Normal',
                'state' => 'normal',
            ],
            'shift' => [
                'start' => '07:00',
                'end' => '15:00',
                'team' => $teamName,
                'supervisor' => $supervisorName,
            ],
            'kpis' => [
                ['label' => 'Personal Programado', 'value' => '426', 'hint' => ''],
                ['label' => 'Conectados', 'value' => '389', 'hint' => '91.3%'],
                ['label' => 'Ausentes', 'value' => '21', 'hint' => '4.9%'],
                ['label' => 'Permisos hoy', 'value' => '11', 'hint' => '8 aprobados · 3 pendientes'],
                ['label' => 'Actividades intradía', 'value' => '14', 'hint' => '7 en ejecución'],
                ['label' => 'Cobertura', 'value' => '96%', 'hint' => 'Objetivo 95%'],
            ],
            'coverageSeries' => [
                ['hour' => '06', 'required' => 92, 'available' => 88],
                ['hour' => '07', 'required' => 95, 'available' => 92],
                ['hour' => '08', 'required' => 98, 'available' => 96],
                ['hour' => '09', 'required' => 100, 'available' => 98],
                ['hour' => '10', 'required' => 99, 'available' => 97],
                ['hour' => '11', 'required' => 96, 'available' => 94],
                ['hour' => '12', 'required' => 95, 'available' => 90],
                ['hour' => '13', 'required' => 92, 'available' => 89],
                ['hour' => '14', 'required' => 90, 'available' => 86],
                ['hour' => '15', 'required' => 88, 'available' => 83],
                ['hour' => '16', 'required' => 86, 'available' => 82],
                ['hour' => '17', 'required' => 84, 'available' => 80],
            ],
            'nextRisk' => [
                'time' => '13:30',
                'coverage' => '89%',
            ],
            'distribution' => [
                ['label' => 'Operando', 'value' => 320],
                ['label' => 'Auxiliar', 'value' => 31],
                ['label' => 'Capacitación', 'value' => 14],
                ['label' => 'Break', 'value' => 18],
                ['label' => 'Almuerzo', 'value' => 26],
                ['label' => 'Offline', 'value' => 17],
            ],
            'queues' => [
                ['name' => 'CSS General', 'waiting' => 18, 'handled' => 1432, 'aht' => '6:13', 'sla' => '93%', 'state' => 'normal'],
                ['name' => 'Farmacia', 'waiting' => 7, 'handled' => 382, 'aht' => '5:41', 'sla' => '96%', 'state' => 'normal'],
                ['name' => 'Citas', 'waiting' => 24, 'handled' => 811, 'aht' => '7:52', 'sla' => '81%', 'state' => 'attention'],
                ['name' => 'Urgencias', 'waiting' => 48, 'handled' => 631, 'aht' => '9:14', 'sla' => '71%', 'state' => 'critical'],
            ],
            'incidents' => [
                ['label' => 'Incapacidades', 'value' => 5],
                ['label' => 'Vacaciones', 'value' => 8],
                ['label' => 'Tardanzas', 'value' => 13],
                ['label' => 'Ausencias', 'value' => 4],
                ['label' => 'Cambios turno', 'value' => 7],
            ],
            'events' => [
                ['time' => '09:00', 'title' => 'Capacitación', 'detail' => 'Equipo Azul · 12 personas'],
                ['time' => '10:15', 'title' => 'Coaching', 'detail' => '4 personas'],
                ['time' => '12:00', 'title' => 'Almuerzos', 'detail' => '48 personas'],
                ['time' => '14:00', 'title' => 'Reunión', 'detail' => 'Supervisores'],
            ],
            'requests' => [
                ['label' => 'Permisos', 'value' => 3],
                ['label' => 'Cambios turno', 'value' => 5],
                ['label' => 'Incidencias', 'value' => 2],
                ['label' => 'Vacaciones', 'value' => 1],
            ],
            'teams' => [
                ['name' => 'Equipo Norte', 'value' => 98],
                ['name' => 'Equipo Oeste', 'value' => 95],
                ['name' => 'Equipo Central', 'value' => 93],
                ['name' => 'Equipo Este', 'value' => 88],
            ],
            'alerts' => [
                ['level' => 'critical', 'message' => 'Cola Farmacia supera SLA.'],
                ['level' => 'attention', 'message' => 'Equipo Norte tiene 5 ausencias.'],
                ['level' => 'attention', 'message' => 'Breaks concentrados entre 12:00 y 12:30.'],
                ['level' => 'critical', 'message' => 'Cobertura baja esperada a las 15:30.'],
                ['level' => 'normal', 'message' => 'Sin conflictos de horarios.'],
            ],
            'trends' => [
                ['label' => 'Ausentismo', 'value' => '▁▂▂▃▂▁▂'],
                ['label' => 'Productividad', 'value' => '▆▇▇█▇▆▇'],
                ['label' => 'Llamadas', 'value' => '▅▆▇▇█▆▅'],
                ['label' => 'Cobertura', 'value' => '███████'],
            ],
            'quickActions' => [
                ['label' => 'Crear permiso'],
                ['label' => 'Registrar incidencia'],
                ['label' => 'Ver horarios'],
                ['label' => 'Planificación semanal'],
                ['label' => 'Reportes'],
                ['label' => 'Cobertura'],
                ['label' => 'Exportar'],
            ],
            'footer' => [
                'connectedUsers' => 389,
                'lastCalculation' => '08:41',
                'lastSchedulesPublished' => '06 Julio 2026',
                'nextRefresh' => '08:45',
            ],
        ])
            ->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
