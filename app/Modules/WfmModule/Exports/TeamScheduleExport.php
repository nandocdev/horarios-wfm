<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Exports;

use App\Modules\PersonnelModule\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

final class TeamScheduleExport
{
    /**
     * Exporta el horario semanal del equipo como XLS (HTML table).
     *
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<string, Collection>  $assignments  keyed by employee_id
     */
    public function toXls(
        Collection $employees,
        Collection $assignments,
        Carbon $weekStart,
        Carbon $weekEnd,
    ): Response {
        $filename = sprintf('horario_equipo_%s.xls', now()->format('Ymd_His'));

        $rows = '';
        foreach ($employees as $employee) {
            $empAssignments = $assignments->get((string) $employee->id, collect());
            $rows .= '<tr>';
            $rows .= '<td>'.e($employee->full_name).'</td>';

            for ($day = 1; $day <= 7; $day++) {
                $a = $empAssignments->firstWhere('day_of_week', $day);
                if ($a && $a->start_time && $a->end_time) {
                    $start = $this->formatTime($a->start_time);
                    $end = $this->formatTime($a->end_time);
                    $lunch = $a->lunch_start_time ? $this->formatTime($a->lunch_start_time).'-'.$this->formatTime($a->lunch_end_time) : '—';
                    $break = $a->break_start_time ? $this->formatTime($a->break_start_time).'-'.$this->formatTime($a->break_end_time) : '—';
                    $scheduleName = $a->schedule?->name ?? '—';
                    $rows .= "<td>{$start}–{$end}<br><small>{$scheduleName}</small></td>";
                    $rows .= "<td>{$lunch}</td>";
                    $rows .= "<td>{$break}</td>";
                } else {
                    $rows .= '<td>—</td><td>—</td><td>—</td>';
                }
            }
            $rows .= '</tr>';
        }

        $dayHeaders = '';
        $daySubHeaders = '';
        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($dayNames as $name) {
            $dayHeaders .= "<th colspan=\"3\">{$name}</th>";
            $daySubHeaders .= '<th>Turno</th><th>Almuerzo</th><th>Descanso</th>';
        }

        $html = <<<HTML
        <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse;font-family:sans-serif;font-size:12px;">
            <thead>
                <tr style="background:#f3f4f6;">
                    <th rowspan="2">Empleado</th>
                    {$dayHeaders}
                </tr>
                <tr style="background:#f9fafb;">
                    {$daySubHeaders}
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>
        HTML;

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Exporta el horario semanal como CSV.
     */
    public function toCsv(
        Collection $employees,
        Collection $assignments,
        Carbon $weekStart,
        Carbon $weekEnd,
    ): Response {
        $filename = sprintf('horario_equipo_%s.csv', now()->format('Ymd_His'));

        $headers = ['Empleado'];
        $dayNames = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        foreach ($dayNames as $name) {
            $headers[] = "{$name} Entrada";
            $headers[] = "{$name} Salida";
            $headers[] = "{$name} Almuerzo";
            $headers[] = "{$name} Descanso";
        }

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($employees as $employee) {
            $row = [$employee->full_name];
            $empAssignments = $assignments->get((string) $employee->id, collect());

            for ($day = 1; $day <= 7; $day++) {
                $a = $empAssignments->firstWhere('day_of_week', $day);
                if ($a && $a->start_time && $a->end_time) {
                    $row[] = $this->formatTime($a->start_time);
                    $row[] = $this->formatTime($a->end_time);
                    $row[] = $a->lunch_start_time ? $this->formatTime($a->lunch_start_time).'-'.$this->formatTime($a->lunch_end_time) : '—';
                    $row[] = $a->break_start_time ? $this->formatTime($a->break_start_time).'-'.$this->formatTime($a->break_end_time) : '—';
                } else {
                    $row[] = '—';
                    $row[] = '—';
                    $row[] = '—';
                    $row[] = '—';
                }
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    private function formatTime(mixed $time): string
    {
        if (! $time) {
            return '—';
        }

        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }
}
