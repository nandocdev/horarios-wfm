<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExplorer extends Component
{
    use WithPagination;

    public string $table = 'fact_calls';

    public array $selectedColumns = [];

    public int $limit = 100;

    public array $filters = [];

    public function mount(): void
    {
        $this->initColumns();
    }

    public function updatedTable(): void
    {
        $this->initColumns();
        $this->filters = [];
        $this->resetPage();
    }

    public function addFilter(): void
    {
        $this->filters[] = ['column' => '', 'operator' => '=', 'value' => ''];
    }

    public function removeFilter(int $index): void
    {
        unset($this->filters[$index]);
        $this->filters = array_values($this->filters);
    }

    public function run(): void
    {
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $columns = $this->getEffectiveColumns();
        $results = $this->buildQuery()->get();

        return response()->streamDownload(function () use ($columns, $results) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, array_values($columns));

            foreach ($results as $row) {
                $data = [];
                $row = (array) $row;
                foreach ($columns as $col => $label) {
                    $data[] = $row[$col] ?? '';
                }
                fputcsv($handle, $data);
            }

            fclose($handle);
        }, 'explorer_'.$this->table.'_'.now()->format('Ymd_His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        $columns = $this->getEffectiveColumns();
        $results = $this->buildQuery()->paginate($this->limit);

        return view('operations::livewire.data-explorer', [
            'tables' => $this->getTables(),
            'tableMeta' => $this->getTableMeta($this->table),
            'columns' => $columns,
            'results' => $results,
        ])->layout('layouts.app', ['title' => 'Data Explorer']);
    }

    private function initColumns(): void
    {
        $meta = $this->getTableMeta($this->table);
        $this->selectedColumns = array_slice(array_keys($meta['columns']), 0, 8);
    }

    private function getTables(): array
    {
        return [
            'fact_calls' => 'Llamadas',
            'fact_schedule' => 'Horarios',
            'fact_quality' => 'Calidad',
            'fact_absence' => 'Ausencias',
            'fact_agent_interval' => 'Intervalos',
            'fact_forecast' => 'Forecast',
            'fact_staffing' => 'Staffing',
        ];
    }

    private function getTableMeta(string $table): array
    {
        $all = [
            'fact_calls' => [
                'label' => 'Llamadas',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'employee_id' => 'Empleado ID',
                    'team_id' => 'Equipo ID',
                    'queue_id' => 'Cola ID',
                    'interval_id' => 'Intervalo ID',
                    'call_offered' => 'Ofrecidas',
                    'call_handled' => 'Atendidas',
                    'call_abandoned' => 'Abandonadas',
                    'talk_time_seconds' => 'Talk (s)',
                    'hold_time_seconds' => 'Hold (s)',
                    'work_time_seconds' => 'Work (s)',
                    'aht_seconds' => 'AHT (s)',
                    'queue_time_seconds' => 'Cola (s)',
                ],
            ],
            'fact_schedule' => [
                'label' => 'Horarios',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'employee_id' => 'Empleado ID',
                    'team_id' => 'Equipo ID',
                    'interval_id' => 'Intervalo ID',
                    'shift_id' => 'Turno ID',
                    'is_scheduled' => 'Programado',
                    'is_working' => 'Trabajando',
                    'is_exception' => 'Excepción',
                    'exception_type' => 'Tipo Excepción',
                ],
            ],
            'fact_quality' => [
                'label' => 'Calidad',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'employee_id' => 'Empleado ID',
                    'queue_id' => 'Cola ID',
                    'evaluation_score' => 'Score',
                    'has_redflag' => 'Red Flag',
                    'evaluation_count' => '# Evaluaciones',
                ],
            ],
            'fact_absence' => [
                'label' => 'Ausencias',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'employee_id' => 'Empleado ID',
                    'team_id' => 'Equipo ID',
                    'is_absent' => 'Ausente',
                    'is_late' => 'Tarde',
                    'minutes_late' => 'Min Tarde',
                    'absence_reason' => 'Motivo',
                ],
            ],
            'fact_agent_interval' => [
                'label' => 'Intervalos',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'employee_id' => 'Empleado ID',
                    'interval_id' => 'Intervalo ID',
                    'talk_seconds' => 'Talk (s)',
                    'hold_seconds' => 'Hold (s)',
                    'ready_seconds' => 'Ready (s)',
                    'not_ready_seconds' => 'Not Ready (s)',
                    'wrap_seconds' => 'Wrap (s)',
                    'calls_handled' => 'Llamadas',
                    'aht_seconds' => 'AHT (s)',
                    'occupancy' => 'Occupancy',
                    'adherence' => 'Adherencia',
                ],
            ],
            'fact_forecast' => [
                'label' => 'Forecast',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'queue_id' => 'Cola ID',
                    'interval_id' => 'Intervalo ID',
                    'forecast_volume' => 'Vol. Pron.',
                    'actual_volume' => 'Vol. Real',
                    'forecast_aht' => 'AHT Pron.',
                    'actual_aht' => 'AHT Real',
                    'staff_required' => 'Staff Req.',
                ],
            ],
            'fact_staffing' => [
                'label' => 'Staffing',
                'columns' => [
                    'id' => 'ID',
                    'date' => 'Fecha',
                    'queue_id' => 'Cola ID',
                    'interval_id' => 'Intervalo ID',
                    'required_agents' => 'Req.',
                    'scheduled_agents' => 'Prog.',
                    'available_agents' => 'Disp.',
                    'coverage' => 'Cobertura',
                    'gap' => 'Gap',
                ],
            ],
        ];

        return $all[$table] ?? ['label' => $table, 'columns' => ['id' => 'ID']];
    }

    private function getEffectiveColumns(): array
    {
        $meta = $this->getTableMeta($this->table);
        $selected = array_intersect_key($meta['columns'], array_flip($this->selectedColumns));

        return $selected ?: array_slice($meta['columns'], 0, 5, true);
    }

    private function buildQuery()
    {
        $query = DB::table($this->table);

        foreach ($this->filters as $filter) {
            if (empty($filter['column']) || $filter['value'] === '') {
                continue;
            }

            $query->where($filter['column'], $filter['operator'], $filter['value']);
        }

        return $query->select(array_keys($this->getEffectiveColumns()));
    }
}
