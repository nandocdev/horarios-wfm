<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Livewire\Component;

class ReportingFrameworkIndex extends Component
{
    public function render()
    {
        $reports = [
            [
                'title' => 'WFM Core (Capa 1)',
                'description' => 'Adherencia real, cobertura y cumplimiento de cronograma.',
                'items' => [
                    ['label' => 'Adherencia y Cobertura', 'route' => 'operations.team-performance', 'icon' => 'clock', 'badge' => 'CRÍTICO'],
                    ['label' => 'Disponibilidad Intradía', 'route' => 'operations.availability', 'icon' => 'check-circle'],
                ],
            ],
            [
                'title' => 'Productividad Operativa (Capa 2)',
                'description' => 'Métricas avanzadas de desempeño individual y aprovechamiento.',
                'items' => [
                    ['label' => 'Analítica PWI / Work Units', 'route' => 'operations.advanced-analytics', 'icon' => 'bolt'],
                    ['label' => 'Scorecard de Desempeño', 'route' => 'operations.performance', 'icon' => 'trophy'],
                ],
            ],
            [
                'title' => 'Performance por Cola (Capa 3)',
                'description' => 'Análisis transaccional de tráfico y niveles de servicio.',
                'items' => [
                    ['label' => 'Reporte de Colas / SLA', 'route' => 'operations.queue-performance', 'icon' => 'phone'],
                ],
            ],
            [
                'title' => 'Gestión de Ausentismo (Capa 4)',
                'description' => 'Control de permisos, incapacidades y flujos de aprobación.',
                'items' => [
                    ['label' => 'Resumen de Solicitudes', 'route' => 'schedules.request-summary', 'icon' => 'envelope-open'],
                    ['label' => 'Inventario de Staffing', 'route' => 'personnel.staffing-summary', 'icon' => 'user-group'],
                ],
            ],
            [
                'title' => 'Executive Layer (Capa 5)',
                'description' => 'Consolidado ejecutivo para la toma de decisiones.',
                'items' => [
                    ['label' => 'Dashboard de Operación', 'route' => 'operations.dashboard', 'icon' => 'presentation-chart-line'],
                ],
            ],
        ];

        return view('operations::livewire.reporting-framework-index', [
            'reports' => $reports,
        ])->layout('layouts.app', ['title' => 'Framework de Reporting']);
    }
}
